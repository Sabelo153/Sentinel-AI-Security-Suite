<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Repositories;
use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Config,Database,RuntimeContext,HttpException};
use Sentinel\Repositories\PermissionRepository;
final class PermissionTenantIsolationTest extends TestCase
{
    public function testCrossCompanyRoleAndPermissionLinksNeverAuthorize():void
    {
        $db=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:',null,null,[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]));
        foreach(['users (user_id INTEGER,company_id INTEGER,status TEXT)','roles (role_id INTEGER,company_id INTEGER,status TEXT)','permissions (permission_id INTEGER,company_id INTEGER,permission_code TEXT,status TEXT)','user_roles (user_id INTEGER,role_id INTEGER,company_id INTEGER)','role_permissions (role_id INTEGER,permission_id INTEGER,company_id INTEGER)']as$s)$db->execute('CREATE TABLE '.$s);
        $db->execute("INSERT INTO users VALUES (1,101,'ACTIVE'),(2,202,'ACTIVE')");$db->execute("INSERT INTO roles VALUES (1,101,'ACTIVE'),(2,202,'ACTIVE')");$db->execute("INSERT INTO permissions VALUES (1,101,'reports.view','ACTIVE'),(2,202,'alerts.manage','ACTIVE')");
        $db->execute('INSERT INTO user_roles VALUES (1,1,101),(1,2,101),(2,2,202)');$db->execute('INSERT INTO role_permissions VALUES (1,1,101),(1,2,101),(2,2,202)');
        $context=new RuntimeContext();$context->setTenantId(101);$repo=new PermissionRepository($db,$context);
        self::assertTrue($repo->userHasPermission('1','reports.view'));self::assertFalse($repo->userHasPermission('1','alerts.manage'));self::assertFalse($repo->userHasPermission('2','alerts.manage'));
        $context->setTenantId(202);self::assertTrue($repo->userHasPermission('2','alerts.manage'));self::assertFalse($repo->userHasPermission('1','reports.view'));
        $context->setTenantId(null);try{$repo->userHasPermission('1','reports.view');self::fail('Missing tenant must fail closed');}catch(HttpException $e){self::assertSame(403,$e->statusCode());}
    }
}
