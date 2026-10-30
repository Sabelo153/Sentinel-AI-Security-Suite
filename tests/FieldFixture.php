<?php
declare(strict_types=1);
namespace Sentinel\Tests;
use PDO;
use Sentinel\Core\{Application,Config,Database};
final class FieldFixture
{
    public static function create():array{
        $db=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:',null,null,[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]));
        foreach([
            'companies(company_id INTEGER PRIMARY KEY)',
            'users(user_id INTEGER PRIMARY KEY,company_id INTEGER,employee_id INTEGER,username TEXT,email TEXT,password_hash TEXT,status TEXT)',
            'employees(employee_id INTEGER PRIMARY KEY,company_id INTEGER,employment_status TEXT)',
            'sites(site_id INTEGER PRIMARY KEY,company_id INTEGER,client_id INTEGER,site_name TEXT,deleted_at TEXT)',
            'clients(client_id INTEGER PRIMARY KEY,company_id INTEGER)',
            'attendance(attendance_id INTEGER PRIMARY KEY,company_id INTEGER,employee_id INTEGER,site_id INTEGER,shift_date TEXT,clock_in_at TEXT,clock_out_at TEXT,deleted_at TEXT,shift_assignment_id INTEGER)',
            'roles(role_id INTEGER PRIMARY KEY,company_id INTEGER,status TEXT)',
            'permissions(permission_id INTEGER PRIMARY KEY,company_id INTEGER,permission_code TEXT,status TEXT)',
            'user_roles(user_id INTEGER,role_id INTEGER,company_id INTEGER)',
            'role_permissions(role_id INTEGER,permission_id INTEGER,company_id INTEGER)',
            'audit_logs(audit_log_id INTEGER PRIMARY KEY,company_id INTEGER,user_id INTEGER,entity_name TEXT,entity_id TEXT,action_name TEXT,action_status TEXT,ip_address TEXT,user_agent TEXT,old_values TEXT,new_values TEXT,metadata TEXT,occurred_at TEXT)',
            'shift_assignments(shift_assignment_id INTEGER PRIMARY KEY,company_id INTEGER,employee_id INTEGER,site_id INTEGER,contract_id INTEGER,post_id INTEGER,assignment_date TEXT,shift_start_at TEXT,shift_end_at TEXT,check_in_window_end TEXT,assignment_status TEXT,deleted_at TEXT)',
            'employee_assignments(employee_assignment_id INTEGER PRIMARY KEY,company_id INTEGER,employee_id INTEGER)',
            'gps_locations(company_id INTEGER,employee_id INTEGER,captured_at TEXT)',
            'patrol_routes(patrol_route_id INTEGER PRIMARY KEY,company_id INTEGER,site_id INTEGER)',
            'patrol_sessions(patrol_session_id INTEGER PRIMARY KEY,company_id INTEGER,patrol_route_id INTEGER,shift_assignment_id INTEGER,employee_assignment_id INTEGER,started_at TEXT,status TEXT,deleted_at TEXT)',
            'patrol_checkpoints(patrol_checkpoint_id INTEGER,company_id INTEGER,patrol_route_id INTEGER,checkpoint_order INTEGER,expected_interval_minutes INTEGER,status TEXT,deleted_at TEXT)',
            'patrol_checkpoint_events(company_id INTEGER,patrol_session_id INTEGER,patrol_checkpoint_id INTEGER,event_type TEXT,recorded_at TEXT)',
            'patrol_schedule_windows(patrol_schedule_id INTEGER,company_id INTEGER,patrol_route_id INTEGER,shift_assignment_id INTEGER,starts_at TEXT,ends_at TEXT,cancelled_at TEXT)',
        ]as$table)$db->execute('CREATE TABLE '.$table);
        foreach(['056_incidents.sql','057_occurrence_book.sql','061_alerts.sql','062_notifications.sql','063_mobile_operations.sql','066_field_operations_release.sql','067_managed_android.sql']as$file)self::loadSchema($db,$file);
        $db->execute('INSERT INTO companies VALUES(101),(202)');$password=password_hash('test-password-at-least-12',PASSWORD_DEFAULT);
        foreach([[1,101],[2,101],[3,202]]as[$id,$company]){$db->execute("INSERT INTO employees VALUES(:id,:company,'ACTIVE')",['id'=>$id,'company'=>$company]);$db->execute("INSERT INTO users VALUES(:id,:company,:employee,:username,:email,:password,'ACTIVE')",['id'=>$id,'company'=>$company,'employee'=>$id,'username'=>'user'.$id,'email'=>'user'.$id.'@example.test','password'=>$password]);}
        $db->execute('INSERT INTO clients VALUES(1,101),(2,101),(3,202)');$db->execute("INSERT INTO sites VALUES(1,101,1,'Own site',NULL),(2,101,2,'Second client',NULL),(3,202,3,'Other company',NULL)");
        $app=Application::create(dirname(__DIR__))->bootstrap();$app->container()->instance('database',$db);$app->config()->set('jwt.secret','fixture-secret-longer-than-32-characters');$app->config()->set('security.mfa_encryption_key','independent-mfa-test-key-longer-than-32-characters');$context=$app->runtimeContext();$context->setTenantId(101);$context->setUser('1');
        $db->execute("INSERT INTO roles VALUES(1,101,'ACTIVE'),(2,101,'ACTIVE'),(3,202,'ACTIVE')");$db->execute('INSERT INTO user_roles VALUES(1,1,101),(2,2,101),(3,3,202)');$counter=0;
        $codes=array_unique(array_filter(array_column($app->router()->securityMetadata(),'permission'),'is_string'));
        foreach([101=>1,202=>3]as$company=>$role)foreach($codes as$code){$id=++$counter;$db->execute("INSERT INTO permissions VALUES(:id,:company,:code,'ACTIVE')",['id'=>$id,'company'=>$company,'code'=>$code]);$db->execute('INSERT INTO role_permissions VALUES(:role,:permission,:company)',['role'=>$role,'permission'=>$id,'company'=>$company]);if($company===101&&in_array($code,['guard.self','users.password_change'],true))$db->execute('INSERT INTO role_permissions VALUES(2,:permission,101)',['permission'=>$id]);}
        return [$app,$db,$context];
    }
    private static function loadSchema(Database $db,string $file):void{
        $sql=file_get_contents(dirname(__DIR__).'/database/schema/'.$file);
        $sql=preg_replace('/^\s*(?:USE .*?;|--[^\n]*)\s*$/m','',$sql);
        $sql=preg_replace('/BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY/i','INTEGER PRIMARY KEY AUTOINCREMENT',$sql);
        $sql=preg_replace('/\b(?:BIGINT|INT) UNSIGNED\b/i','INTEGER',$sql);
        $sql=preg_replace('/ENUM\([^)]*\)/i','TEXT',$sql);
        $sql=preg_replace('/ ON UPDATE CURRENT_TIMESTAMP/i','',$sql);
        $sql=preg_replace('/UNIQUE KEY \w+\s*\(/i','UNIQUE (',$sql);
        $sql=preg_replace('/(?:,\s*)?INDEX \w+\s*\([^)]*\)\s*,?/i','',$sql);
        $sql=preg_replace('/,\s*\)/',')',$sql);$db->connection()->exec($sql);
    }
}
