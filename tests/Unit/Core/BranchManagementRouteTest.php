<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\Router;

final class BranchManagementRouteTest extends TestCase
{
    public function testBranchRoutesRunAuthenticationTenantAndServerPermissionInOrder(): void
    {
        $router = new Router();
        $router->get('/api/v1/branches', static fn (): Response => Response::raw('list'), ['tenant' => true, 'permission' => 'branches.view']);
        $router->get('/api/v1/branches/{branchId}', static fn (): Response => Response::raw('get'), ['tenant' => true, 'permission' => 'branches.view']);
        $router->post('/api/v1/branches', static fn (): Response => Response::raw('create'), ['tenant' => true, 'permission' => 'branches.create']);
        $router->patch('/api/v1/branches/{branchId}', static fn (): Response => Response::raw('update'), ['tenant' => true, 'permission' => 'branches.update']);
        $calls = [];

        $auth = static function (Request $request, callable $next) use (&$calls): Response {
            $calls[] = 'auth';
            return $next($request);
        };
        $authz = static function (Request $request, callable $next, string $permission) use (&$calls): Response {
            $calls[] = $permission;
            return $next($request);
        };
        $tenant = static function (Request $request, callable $next) use (&$calls): Response {
            $calls[] = 'tenant';
            return $next($request);
        };

        self::assertSame('list', $router->dispatch(Request::create('GET', '/api/v1/branches'), $auth, $authz, $tenant)->body());
        self::assertSame(['auth', 'tenant', 'branches.view'], $calls);
    }
}