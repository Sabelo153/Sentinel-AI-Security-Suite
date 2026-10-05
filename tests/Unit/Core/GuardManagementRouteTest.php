<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\Router;

final class GuardManagementRouteTest extends TestCase
{
    public function testGuardRoutesRunAuthenticationTenantAndServerPermissionInOrder(): void
    {
        $router = new Router();
        $router->get('/api/v1/guards', static fn (): Response => Response::raw('list'), ['tenant'=>true,'permission'=>'guards.view']);
        $router->get('/api/v1/guards/{employeeId}', static fn (): Response => Response::raw('get'), ['tenant'=>true,'permission'=>'guards.view']);
        $router->post('/api/v1/guards', static fn (): Response => Response::raw('create'), ['tenant'=>true,'permission'=>'guards.create']);
        $router->patch('/api/v1/guards/{employeeId}', static fn (): Response => Response::raw('update'), ['tenant'=>true,'permission'=>'guards.update']);
        $calls=[];
        $auth=static function(Request $request, callable $next) use (&$calls): Response {$calls[]='auth'; return $next($request);};
        $authz=static function(Request $request, callable $next, string $permission) use (&$calls): Response {$calls[]=$permission; return $next($request);};
        $tenant=static function(Request $request, callable $next) use (&$calls): Response {$calls[]='tenant'; return $next($request);};
        self::assertSame('list',$router->dispatch(Request::create('GET','/api/v1/guards'),$auth,$authz,$tenant)->body());
        self::assertSame(['auth','tenant','guards.view'],$calls);
    }
}