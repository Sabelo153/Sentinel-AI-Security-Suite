<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\Router;

final class GeofenceRouteTest extends TestCase
{
    public function testGeofenceRoutesRequireAuthTenantAndPermission(): void
    {
        $router = new Router(); $handler = static fn (): Response => Response::raw('ok');
        $router->get('/api/v1/geofences', $handler, ['auth' => true, 'tenant' => true, 'permission' => 'geofences.view']);
        $router->get('/api/v1/geofences/{geofenceId}', $handler, ['auth' => true, 'tenant' => true, 'permission' => 'geofences.view']);
        $router->post('/api/v1/geofences', $handler, ['auth' => true, 'tenant' => true, 'permission' => 'geofences.create']);
        $router->patch('/api/v1/geofences/{geofenceId}', $handler, ['auth' => true, 'tenant' => true, 'permission' => 'geofences.update']);
        $calls = [];
        $auth = static function (Request $request, callable $next) use (&$calls): Response { $calls[] = 'auth'; return $next($request); };
        $authz = static function (Request $request, callable $next, string $permission) use (&$calls): Response { $calls[] = $permission; return $next($request); };
        $tenant = static function (Request $request, callable $next) use (&$calls): Response { $calls[] = 'tenant'; return $next($request); };
        self::assertSame('ok', $router->dispatch(Request::create('GET', '/api/v1/geofences'), $auth, $authz, $tenant)->body());
        self::assertSame(['auth', 'tenant', 'geofences.view'], $calls);
        self::assertSame('ok', $router->dispatch(Request::create('PATCH', '/api/v1/geofences/1'), $auth, $authz, $tenant)->body());
    }
}
