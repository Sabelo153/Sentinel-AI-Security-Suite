<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Services;
use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Config,Container,Database,HttpException,RuntimeContext};
use Sentinel\Services\GuardWorkflowService;

final class GuardWorkflowServiceTest extends TestCase
{
    private function fixture():array
    {
        $db=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:',null,null,[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]));
        foreach(['users (user_id INTEGER,company_id INTEGER,employee_id INTEGER,status TEXT)','employees (employee_id INTEGER,company_id INTEGER,employment_status TEXT)','mobile_operations (mobile_operation_id INTEGER PRIMARY KEY,company_id INTEGER,user_id INTEGER,operation_uuid TEXT,request_hash TEXT,response_json TEXT,UNIQUE(company_id,user_id,operation_uuid))','shift_assignments (shift_assignment_id INTEGER,company_id INTEGER,employee_id INTEGER,assignment_status TEXT,site_id INTEGER,contract_id INTEGER,post_id INTEGER,assignment_date TEXT,deleted_at TEXT)','recorded (id INTEGER PRIMARY KEY,company_id INTEGER,employee_id INTEGER,user_id INTEGER)']as$s)$db->execute('CREATE TABLE '.$s);
        $db->execute("INSERT INTO users VALUES (1,101,1,'ACTIVE'),(2,202,2,'ACTIVE'),(3,101,3,'ACTIVE')");$db->execute("INSERT INTO employees VALUES (1,101,'ACTIVE'),(2,202,'ACTIVE'),(3,101,'ACTIVE')");$db->execute("INSERT INTO shift_assignments VALUES (1,101,1,'ASSIGNED',1,NULL,NULL,'2026-09-14',NULL),(2,202,2,'ASSIGNED',2,NULL,NULL,'2026-09-14',NULL),(3,101,3,'ASSIGNED',1,NULL,NULL,'2026-09-14',NULL)");
        $context=new RuntimeContext();$context->setTenantId(101);$context->setUser('1');$container=new Container();
        $container->instance('location_management_service',new class($db){public function __construct(private Database $db){}public function recordLocation(array $data):array{$this->db->execute('INSERT INTO recorded (company_id,employee_id,user_id) VALUES (101,:employee_id,:user_id)',['employee_id'=>$data['employee_id'],'user_id'=>$data['user_id']]);return$data;}});
        return[$db,$context,new GuardWorkflowService($db,$context,$container)];
    }
    private function command():array{return['operation_uuid'=>'a0000000-0000-4000-8000-000000000001','action'=>'location','data'=>['latitude'=>-26.2,'longitude'=>28.0,'captured_at'=>'2026-09-14 08:00:00']];}
    private function assertHttpStatus(int $status,callable $operation):void{try{$operation();self::fail('Expected controlled error');}catch(HttpException $e){self::assertSame($status,$e->statusCode());}}
    public function testRetriesReturnSameResultWithoutRepeatingMutation():void
    {
        [$db,,$service]=$this->fixture();$first=$service->command($this->command());$second=$service->command($this->command());
        self::assertEquals($first,$second);self::assertSame('1',$first['employee_id']);self::assertSame(1,$first['user_id']);self::assertCount(1,$db->fetchAll('SELECT * FROM recorded'));self::assertCount(1,$db->fetchAll('SELECT * FROM mobile_operations'));
        $changed=$this->command();$changed['data']['latitude']=0;$this->assertHttpStatus(409,fn()=>$service->command($changed));
    }
    public function testForgedIdentityAndOtherEmployeesShiftsAreRejected():void
    {
        [$db,,$service]=$this->fixture();$forged=$this->command();$forged['data']['employee_id']=3;$this->assertHttpStatus(422,fn()=>$service->command($forged));
        foreach([2,3]as$id)$this->assertHttpStatus(404,fn()=>$service->command(['operation_uuid'=>uuid4(),'action'=>'clock_in','data'=>['shift_assignment_id'=>$id,'occurred_at'=>'2026-09-14 08:00:00']]));
        self::assertSame([],$db->fetchAll('SELECT * FROM mobile_operations'));self::assertSame([],$db->fetchAll('SELECT * FROM recorded'));
    }
    public function testReceiptIsScopedToActorAndTenantAndMissingContextFailsClosed():void
    {
        [$db,$context,$service]=$this->fixture();$service->command($this->command());$context->setUser('3');$service->command($this->command());self::assertCount(2,$db->fetchAll('SELECT * FROM mobile_operations'));
        $context->setTenantId(202);$this->assertHttpStatus(403,fn()=>$service->command($this->command()));$context->setTenantId(null);$this->assertHttpStatus(403,fn()=>$service->command($this->command()));
    }
    public function testPatrolDetailsRejectOtherGuardsAndOtherCompaniesBeforeLoadingEvidence():void
    {
        [$db,$context,$service]=$this->fixture();
        $db->execute('CREATE TABLE employee_assignments (employee_assignment_id INTEGER,company_id INTEGER,employee_id INTEGER)');
        $db->execute('CREATE TABLE patrol_sessions (patrol_session_id INTEGER,company_id INTEGER,employee_assignment_id INTEGER,deleted_at TEXT)');
        $db->execute('INSERT INTO employee_assignments VALUES (1,101,3),(2,202,2)');
        $db->execute('INSERT INTO patrol_sessions VALUES (1,101,1,NULL),(2,202,2,NULL)');
        $this->assertHttpStatus(404,fn()=>$service->patrolDetails('1'));
        $this->assertHttpStatus(404,fn()=>$service->patrolDetails('2'));
        $this->assertHttpStatus(404,fn()=>$service->patrolDetails('999'));
        $context->setTenantId(null);$this->assertHttpStatus(403,fn()=>$service->patrolDetails('1'));
    }

}
