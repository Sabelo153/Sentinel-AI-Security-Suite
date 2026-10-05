<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\Router;

final class AccountSecurityRouteTest extends TestCase
{
    public function testPasswordAndUnlockRoutesRequireSecurityPermissions(): void
    {
        $router = new Router(); $handler = static fn (): Response => Response::raw('ok');
        $router->post('/api/v1/auth/password', $handler, ['auth'=>true,'tenant'=>true,'permission'=>'users.password_change']);
        $router->post('/api/v1/users/{userId}/unlock', $handler, ['auth'=>true,'tenant'=>true,'permission'=>'users.unlock']);
        $calls=[]; $auth=static function(Request$r,callable$n)use(&$calls):Response{$calls[]='auth';return$n($r);}; $authz=static function(Request$r,callable$n,string$p)use(&$calls):Response{$calls[]=$p;return$n($r);}; $tenant=static function(Request$r,callable$n)use(&$calls):Response{$calls[]='tenant';return$n($r);};
        self::assertSame('ok',$router->dispatch(Request::create('POST','/api/v1/auth/password'),$auth,$authz,$tenant)->body());
        self::assertSame(['auth','tenant','users.password_change'],$calls);
        self::assertSame('ok',$router->dispatch(Request::create('POST','/api/v1/users/7/unlock'),$auth,$authz,$tenant)->body());
    }
}
