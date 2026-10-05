<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\HttpException;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\Router;

final class LocationRouteTest extends TestCase
{
    private function registerRoutes(Router $router): void
    {
        $handler = static fn (): Response => Response::raw('ok');
        $router->get('/api/v1/locations', $handler, ['auth' => true, 'tenant' => true, 'permission' => 'locations.view']);
        $router->post('/api/v1/locations', $handler, ['auth' => true, 'tenant' => true, 'permission' => 'locations.create']);
        $router->get('/api/v1/employees/{employeeId}/locations', $handler, ['auth' => true, 'tenant' => true, 'permission' => 'locations.view']);
        $router->get('/api/v1/employees/{employeeId}/locations/latest', $handler, ['auth' => true, 'tenant' => true, 'permission' => 'locations.view']);
    }

    public function testLocationRoutesEnforceAuthenticationTenantAndPermissionOrder(): void
    {
        $router = new Router();
        $this->registerRoutes($router);
        $calls = [];
        $auth = static function (Request $request, callable $next) use (&$calls): Response { $calls[] = 'auth'; return $next($request); };
        $authz = static function (Request $request, callable $next, string $permission) use (&$calls): Response { $calls[] = $permission; return $next($request); };
        $tenant = static function (Request $request, callable $next) use (&$calls): Response { $calls[] = 'tenant'; return $next($request); };

        self::assertSame('ok', $router->dispatch(Request::create('GET', '/api/v1/locations'), $auth, $authz, $tenant)->body());
        self::assertSame(['auth', 'tenant', 'locations.view'], $calls);
    }

    public function testLocationRoutesReturn401And403AndAllowPermittedAccess(): void
    {
        $router = new Router();
        $this->registerRoutes($router);
        $tenant = static fn (Request $request, callable $next): Response => $next($request);
        $authz = static fn (Request $request, callable $next, string $permission): Response => $next($request);

        try { $router->dispatch(Request::create('GET', '/api/v1/locations'), static fn (Request $request, callable $next): Response => throw HttpException::unauthorized('Authentication failed.', 'authentication_failed'), $authz, $tenant); self::fail('Expected 401.'); } catch (HttpException $e) { self::assertSame(401, $e->statusCode()); }
        try { $router->dispatch(Request::create('GET', '/api/v1/locations'), static fn (Request $request, callable $next): Response => $next($request), static fn (Request $request, callable $next, string $permission): Response => throw HttpException::forbidden('Forbidden.', 'forbidden'), $tenant); self::fail('Expected 403.'); } catch (HttpException $e) { self::assertSame(403, $e->statusCode()); }
        self::assertSame('ok', $router->dispatch(Request::create('POST', '/api/v1/locations'), static fn (Request $request, callable $next): Response => $next($request), $authz, $tenant)->body());
    }
}
