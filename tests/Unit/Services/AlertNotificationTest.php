<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Services;
use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Application,Config,Database,HttpException,RuntimeContext};
use Sentinel\Repositories\{OperationalRepository,AuditRepository};
use Sentinel\Services\{AlertNotificationService,AuditService};
final class AlertNotificationTest extends TestCase
{
    private function fixture():array
    {
        $db=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:',null,null,[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]));
        foreach([
            'users (user_id INTEGER,company_id INTEGER,employee_id INTEGER,status TEXT)','employees (employee_id INTEGER,company_id INTEGER)',
            'alerts (alert_id INTEGER PRIMARY KEY AUTOINCREMENT,company_id INTEGER,alert_type TEXT,severity TEXT,related_entity_type TEXT,related_entity_id TEXT,message TEXT,payload_json TEXT,status TEXT,occurred_at TEXT,acknowledged_at TEXT,acknowledged_by_user_id INTEGER,resolved_at TEXT,resolved_by_user_id INTEGER)',
            'notifications (notification_id INTEGER PRIMARY KEY AUTOINCREMENT,company_id INTEGER,recipient_user_id INTEGER,recipient_employee_id INTEGER,alert_id INTEGER,event_type TEXT,severity TEXT,title TEXT,message TEXT,read_at TEXT,sent_at TEXT)',
            'notification_deliveries (notification_delivery_id INTEGER PRIMARY KEY AUTOINCREMENT,company_id INTEGER,notification_id INTEGER,channel TEXT,destination_reference TEXT,delivery_status TEXT,attempt_count INTEGER,next_attempt_at TEXT,sent_at TEXT,failed_at TEXT,error_code TEXT,error_message TEXT)',
            'audit_logs (audit_log_id INTEGER PRIMARY KEY AUTOINCREMENT,company_id INTEGER,user_id INTEGER,entity_name TEXT,entity_id TEXT,action_name TEXT,action_status TEXT,ip_address TEXT,user_agent TEXT,old_values TEXT,new_values TEXT,metadata TEXT)',
        ]as$schema)$db->execute('CREATE TABLE '.$schema);
        $db->execute("INSERT INTO users VALUES (1,101,1,'ACTIVE'),(2,202,2,'ACTIVE'),(3,101,3,'ACTIVE')");$db->execute('INSERT INTO employees VALUES (1,101),(2,202),(3,101)');
        $context=new RuntimeContext();$context->setTenantId(101);$context->setUser('1');
        return[$db,$context,new AlertNotificationService(new OperationalRepository($db,$context),$context,new AuditService(new AuditRepository($db,$context),$context))];
    }
    private function assertHttpStatus(int $status,callable $operation):void{try{$operation();self::fail('Expected HTTP error');}catch(HttpException $error){self::assertSame($status,$error->statusCode());}}
    private function notification():array{return['recipient_user_id'=>1,'event_type'=>'ALERT','title'=>'Attention','message'=>'Review alert'];}
    public function testAlertTransitionsRecordActorAndRejectTerminalChanges():void
    {
        [$db,,$service]=$this->fixture();$id=(string)$service->createAlert(['alert_type'=>'MANUAL','message'=>'Review','severity'=>'HIGH'])['alert_id'];
        $this->assertHttpStatus(409,fn()=>$service->transitionAlert($id,['status'=>'RESOLVED']));
        $row=$service->transitionAlert($id,['status'=>'ACKNOWLEDGED']);self::assertSame(1,$row['acknowledged_by_user_id']);self::assertNotNull($row['acknowledged_at']);
        self::assertSame('RESOLVED',$service->transitionAlert($id,['status'=>'RESOLVED'])['status']);
        $this->assertHttpStatus(409,fn()=>$service->transitionAlert($id,['status'=>'DISMISSED']));self::assertCount(3,$db->fetchAll('SELECT * FROM audit_logs'));
    }
    public function testInboxRecipientIsolationAndIdempotentRead():void
    {
        [$db,$context,$service]=$this->fixture();$row=$service->createNotification($this->notification());$id=(string)$row['notification_id'];
        self::assertSame(1,$row['recipient_employee_id']);self::assertCount(1,$service->inbox());self::assertSame('SENT',$service->deliveries($id)[0]['delivery_status']);
        $first=$service->markRead($id,[]);self::assertNotNull($first['read_at']);self::assertSame($first['read_at'],$service->markRead($id,[])['read_at']);
        self::assertCount(2,$db->fetchAll('SELECT * FROM audit_logs'));
        $context->setUser('3');self::assertSame([],$service->inbox());$this->assertHttpStatus(404,fn()=>$service->getNotification($id));$this->assertHttpStatus(404,fn()=>$service->markRead($id,[]));$this->assertHttpStatus(404,fn()=>$service->deliveries($id));
        $context->setTenantId(202);$context->setUser('2');self::assertSame([],$service->inbox());$this->assertHttpStatus(404,fn()=>$service->getNotification($id));
    }
    public function testCrossTenantReferencesAndProviderFailuresAreSafe():void
    {
        [,$context,$service]=$this->fixture();$id=(string)$service->createAlert(['alert_type'=>'MANUAL','message'=>'Review'])['alert_id'];
        $this->assertHttpStatus(404,fn()=>$service->createNotification([...$this->notification(),'recipient_user_id'=>2]));
        $this->assertHttpStatus(503,fn()=>$service->createNotification([...$this->notification(),'channel'=>'EMAIL']));self::assertSame([],$service->inbox());
        $this->assertHttpStatus(422,fn()=>$service->createAlert(['alert_type'=>'A','message'=>'B','company_id'=>202]));
        $this->assertHttpStatus(422,fn()=>$service->inbox(['recipient_user_id'=>3]));
        $context->setTenantId(202);$context->setUser('2');self::assertSame([],$service->alerts([]));$this->assertHttpStatus(404,fn()=>$service->getAlert($id));$this->assertHttpStatus(404,fn()=>$service->transitionAlert($id,['status'=>'ACKNOWLEDGED']));
        $this->assertHttpStatus(404,fn()=>$service->createNotification([...$this->notification(),'recipient_user_id'=>2,'alert_id'=>$id]));
    }
    public function testAuditFailureRollsBackNotificationAndDeliveryTogether():void
    {
        [$db,,$service]=$this->fixture();$db->execute('DROP TABLE audit_logs');$this->assertHttpStatus(503,fn()=>$service->createNotification($this->notification()));
        self::assertSame([],$service->inbox());self::assertSame([],$db->fetchAll('SELECT * FROM notification_deliveries'));
    }
    public function testProtectedRoutes():void
    {
        $routes=Application::create(dirname(__DIR__,3))->bootstrap()->router()->securityMetadata();$routes=array_values(array_filter($routes,static fn(array $r):bool=>preg_match('#^/api/v1/(alerts|notifications)(/|$)#',$r['path'])===1));
        self::assertCount(9,$routes);foreach($routes as$route){self::assertTrue($route['auth']);self::assertTrue($route['tenant']);self::assertNotEmpty($route['permission']);}
    }
}
