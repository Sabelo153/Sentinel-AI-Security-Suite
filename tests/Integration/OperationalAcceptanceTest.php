<?php
declare(strict_types=1);
namespace Sentinel\Tests\Integration;
use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Application,Config,Database,HttpException,Request};
use Sentinel\Security\JwtService;

/** Full application dispatcher and real authentication/tenant/authorization middleware; disposable SQLite only. */
final class OperationalAcceptanceTest extends TestCase
{
    private function fixture():array
    {
        $db=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:',null,null,[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]));
        foreach([
            'users (user_id INTEGER,company_id INTEGER,status TEXT)','roles (role_id INTEGER,company_id INTEGER,status TEXT)','permissions (permission_id INTEGER,company_id INTEGER,permission_code TEXT,status TEXT)','user_roles (user_id INTEGER,role_id INTEGER,company_id INTEGER)','role_permissions (role_id INTEGER,permission_id INTEGER,company_id INTEGER)',
            'sites (site_id INTEGER,company_id INTEGER)','employees (employee_id INTEGER,company_id INTEGER)','shift_assignments (shift_assignment_id INTEGER,company_id INTEGER,site_id INTEGER,employee_id INTEGER,deleted_at TEXT)',
            'occurrence_entries (occurrence_entry_id INTEGER PRIMARY KEY AUTOINCREMENT,occurrence_entry_uuid TEXT,company_id INTEGER,site_id INTEGER,employee_id INTEGER,shift_assignment_id INTEGER,entry_reference TEXT,entry_type TEXT,occurred_at TEXT,entry_text TEXT,handover_required INTEGER,handover_reference TEXT,created_by_user_id INTEGER,created_at TEXT DEFAULT CURRENT_TIMESTAMP)',
            'occurrence_entry_revisions (occurrence_entry_revision_id INTEGER PRIMARY KEY AUTOINCREMENT,company_id INTEGER,occurrence_entry_id INTEGER,revision_number INTEGER,previous_entry_text TEXT,revision_reason TEXT,revised_by_user_id INTEGER,revised_at TEXT DEFAULT CURRENT_TIMESTAMP,UNIQUE(occurrence_entry_id,revision_number))',
            'audit_logs (audit_log_id INTEGER PRIMARY KEY AUTOINCREMENT,company_id INTEGER,user_id INTEGER,entity_name TEXT,entity_id TEXT,action_name TEXT,action_status TEXT,ip_address TEXT,user_agent TEXT,old_values TEXT,new_values TEXT,metadata TEXT)',
        ]as$s)$db->execute('CREATE TABLE '.$s);
        $db->execute("INSERT INTO users VALUES (1,101,'ACTIVE'),(2,202,'ACTIVE'),(3,101,'ACTIVE')");$db->execute("INSERT INTO roles VALUES (1,101,'ACTIVE'),(2,202,'ACTIVE')");$db->execute('INSERT INTO user_roles VALUES (1,1,101),(2,2,202)');$db->execute('INSERT INTO sites VALUES (1,101),(2,202)');
        foreach([101=>1,202=>2]as$company=>$role)foreach(['occurrence_book.view','occurrence_book.create','occurrence_book.revise']as$i=>$code){$id=$role*10+$i;$db->execute('INSERT INTO permissions VALUES (:id,:company,:code,\'ACTIVE\')',['id'=>$id,'company'=>$company,'code'=>$code]);$db->execute('INSERT INTO role_permissions VALUES (:role,:id,:company)',['role'=>$role,'id'=>$id,'company'=>$company]);}
        $jwt=new JwtService(new Config(['jwt'=>['algorithm'=>'HS256','secret'=>'acceptance-test-secret-at-least-32-characters','issuer'=>'acceptance','ttl'=>3600]]));
        $app=Application::create(dirname(__DIR__,2))->bootstrap();$app->container()->instance('database',$db);$app->container()->instance('jwt',$jwt);
        return[$app,$db,$jwt];
    }
    private function request(Application $app,JwtService $jwt,string $method,string $path,?string $user,array $body=[]):array
    {
        $headers=['Content-Type'=>'application/json'];if($user!==null)$headers['Authorization']='Bearer '.$jwt->createToken($user);
        try{$response=$app->handle(Request::create($method,$path,[],$headers,$method==='GET'?null:json_encode($body)));return[$response->status(),json_decode($response->body(),true)];}
        catch(HttpException $error){self::assertStringNotContainsString('SQLSTATE',$error->getMessage());return[$error->statusCode(),['message'=>$error->getMessage()]];}
    }
    public function testAuthenticatedOccurrenceLifecycleAndAllSafetyStatuses():void
    {
        [$app,$db,$jwt]=$this->fixture();$base='/api/v1/occurrence-entries';
        self::assertSame(401,$this->request($app,$jwt,'GET',$base,null)[0]);self::assertSame(403,$this->request($app,$jwt,'GET',$base,'3')[0]);
        $body=['site_id'=>1,'entry_type'=>'HANDOVER','occurred_at'=>'2026-09-14 08:00:00','entry_text'=>'Start of shift'];
        self::assertSame(422,$this->request($app,$jwt,'POST',$base,'1',[...$body,'company_id'=>202])[0]);
        [$status,$payload]=$this->request($app,$jwt,'POST',$base,'1',$body);self::assertSame(201,$status);$id=$payload['data']['occurrence_entry_id'];self::assertSame(101,$payload['data']['company_id']);
        self::assertSame(200,$this->request($app,$jwt,'GET',"$base/$id",'1')[0]);self::assertSame(404,$this->request($app,$jwt,'GET',"$base/$id",'2')[0]);
        $revision=['expected_revision'=>0,'entry_text'=>'Corrected handover','revision_reason'=>'Correction'];
        self::assertSame(200,$this->request($app,$jwt,'POST',"$base/$id/revisions",'1',$revision)[0]);self::assertSame(409,$this->request($app,$jwt,'POST',"$base/$id/revisions",'1',$revision)[0]);
        $db->execute('DROP TABLE audit_logs');self::assertSame(503,$this->request($app,$jwt,'POST',$base,'1',$body)[0]);self::assertSame(1,(int)$db->fetch('SELECT COUNT(*) AS total FROM occurrence_entries')['total']);
    }
    public function testEveryNewOperationalRouteRejectsAnonymousRequestsBeforeDatabaseUse():void
    {
        [$app,,$jwt]=$this->fixture();$checked=0;
        foreach($app->router()->securityMetadata()as$route){
            if(preg_match('#^/api/v1/(occurrence-entries|visitors|vehicles|visitor-visits|access-events|inspections|alerts|notifications)(/|$)#',$route['path'])!==1)continue;
            $path=preg_replace('/\{[^}]+\}/','1',$route['path']);self::assertSame(401,$this->request($app,$jwt,$route['methods'][0],$path,null)[0],$path);$checked++;
        }
        self::assertSame(38,$checked);
    }
    public function testPatrolSetupAndGuardEvidenceEndpointsRequireAuthenticationAndPermission():void
    {
        [$app,,$jwt]=$this->fixture();
        foreach([['GET','/api/v1/authority-levels'],['GET','/api/v1/patrol-schedules'],['POST','/api/v1/patrol-schedules'],['POST','/api/v1/patrol-schedules/1/cancel'],['GET','/api/v1/guard/patrols/1'],['GET','/api/v1/guard/patrol-schedules']]as[$method,$path]){
            self::assertSame(401,$this->request($app,$jwt,$method,$path,null)[0],$path);
            self::assertSame(403,$this->request($app,$jwt,$method,$path,'3')[0],$path);
        }
    }

    public function testAccessDiscoveryIsAuthenticatedTenantScopedAndPlanAware(): void
    {
        [$app,$db,$jwt]=$this->fixture();
        self::assertSame(401,$this->request($app,$jwt,'GET','/api/v1/auth/access',null)[0]);
        [$status,$payload]=$this->request($app,$jwt,'GET','/api/v1/auth/access','1');
        self::assertSame(200,$status);
        self::assertContains('occurrence_book.view',$payload['data']['permissions']);
        self::assertNotContains('dashboard.view',$payload['data']['permissions']);
        self::assertContains('/api/v1/occurrence-entries',$payload['data']['read_paths']);
        $db->execute("INSERT INTO permissions VALUES (99,202,'dashboard.view','ACTIVE')");
        $db->execute('INSERT INTO role_permissions VALUES (1,99,202)');
        self::assertNotContains('dashboard.view',$this->request($app,$jwt,'GET','/api/v1/auth/access','1')[1]['data']['permissions']);
        self::assertSame([],$this->request($app,$jwt,'GET','/api/v1/auth/access','3')[1]['data']['permissions']);
        $db->execute('CREATE TABLE company_subscriptions(company_id INTEGER,plan_code TEXT,subscription_status TEXT,trial_ends_at TEXT,current_period_ends_at TEXT)');
        $db->execute("INSERT INTO company_subscriptions VALUES(101,'ENTERPRISE','TRIALING','2000-01-01',NULL)");
        self::assertSame([],$this->request($app,$jwt,'GET','/api/v1/auth/access','1')[1]['data']['permissions']);
    }
}
