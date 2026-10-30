<?php
declare(strict_types=1);
namespace Sentinel\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Application, Config, Database, HttpException, Request};
use Sentinel\Security\JwtService;

final class SupportWorkspaceTest extends TestCase
{
    public function testSupportPermissionsContainmentAndExistingTokenRejection(): void
    {
        $db=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:',null,null,[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]));
        foreach([
            'users (user_id INTEGER,company_id INTEGER,status TEXT)',
            'roles (role_id INTEGER,company_id INTEGER,status TEXT)',
            'permissions (permission_id INTEGER,company_id INTEGER,permission_code TEXT,status TEXT)',
            'user_roles (user_id INTEGER,role_id INTEGER,company_id INTEGER)',
            'role_permissions (role_id INTEGER,permission_id INTEGER,company_id INTEGER)',
            'audit_logs (audit_log_id INTEGER PRIMARY KEY,company_id INTEGER,user_id INTEGER,entity_name TEXT,entity_id TEXT,action_name TEXT,action_status TEXT,ip_address TEXT,user_agent TEXT,old_values TEXT,new_values TEXT,metadata TEXT,occurred_at TEXT)',
        ] as $table) $db->execute('CREATE TABLE '.$table);
        $db->execute("INSERT INTO users VALUES (1,101,'ACTIVE'),(2,101,'ACTIVE'),(3,101,'ACTIVE'),(4,202,'ACTIVE')");
        $db->execute("INSERT INTO roles VALUES (1,101,'ACTIVE'),(2,101,'ACTIVE')");
        $db->execute("INSERT INTO permissions VALUES (1,101,'support.view','ACTIVE'),(2,101,'support.manage','ACTIVE')");
        $db->execute('INSERT INTO user_roles VALUES (1,1,101),(2,2,101)');
        $db->execute('INSERT INTO role_permissions VALUES (1,1,101),(1,2,101),(2,1,101)');
        $jwt=new JwtService(new Config(['jwt'=>['algorithm'=>'HS256','secret'=>'support-test-secret-longer-than-thirty-two-characters','issuer'=>'support-tests','ttl'=>3600]]));
        $app=Application::create(dirname(__DIR__,2))->bootstrap();
        $app->container()->instance('database',$db); $app->container()->instance('jwt',$jwt);
        $tokens=[];foreach(['1','2','3','4'] as $user) $tokens[$user]=$jwt->createToken($user, $user==='1');
        $call=static function(string $method,string $path,?string $token,array $body=[]) use($app):array {
            $headers=['Content-Type'=>'application/json'];if($token!==null)$headers['Authorization']='Bearer '.$token;
            try{$response=$app->handle(Request::create($method,$path,[],$headers,$method==='GET'?null:json_encode($body)));return[$response->status(),json_decode($response->body(),true),$response->header('Cache-Control')];}
            catch(HttpException $e){return[$e->statusCode(),[],null];}
        };
        foreach(['/overview','/health','/bundle'] as $path) self::assertSame(401,$call('GET','/api/v1/support'.$path,null)[0]);
        self::assertSame(401,$call('POST','/api/v1/support/accounts/3/disable',null)[0]);
        self::assertSame(403,$call('GET','/api/v1/support/overview',$tokens['3'])[0]);
        [$status,$payload,$cache]=$call('GET','/api/v1/support/overview',$tokens['2']);
        self::assertSame(200,$status); self::assertSame(101,$payload['data']['company_id']); self::assertSame('no-store',$cache);
        $body=['reason'=>'Lost device confirmed by supervisor','confirm_user_id'=>'3'];
        self::assertSame(403,$call('POST','/api/v1/support/accounts/3/disable',$tokens['2'],$body)[0]);
        self::assertSame(409,$call('POST','/api/v1/support/accounts/4/disable',$tokens['1'],[...$body,'confirm_user_id'=>'4'])[0]);
        self::assertSame(422,$call('POST','/api/v1/support/accounts/3/disable',$tokens['1'],[...$body,'company_id'=>202])[0]);
        self::assertSame(200,$call('POST','/api/v1/support/accounts/3/disable',$tokens['1'],$body)[0]);
        // The very same token issued before containment can no longer resolve a company.
        self::assertSame(403,$call('GET','/api/v1/auth/tenant',$tokens['3'])[0]);
        self::assertSame('ACTIVE',$db->fetch('SELECT status FROM users WHERE user_id=4')['status']);
        self::assertSame(2,(int)$db->fetch('SELECT COUNT(*) AS total FROM audit_logs')['total']);
    }
}
