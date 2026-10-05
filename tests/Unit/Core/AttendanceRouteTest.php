<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\HttpException;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\Router;

final class AttendanceRouteTest extends TestCase
{
    private function registerAttendanceRoutes(Router $router): void
    {
        $router->get('/api/v1/attendance', static fn (): Response => Response::raw('list'), ['auth' => true, 'tenant' => true, 'permission' => 'attendance.view']);
        $router->get('/api/v1/attendance/{attendanceId}', static fn (): Response => Response::raw('get'), ['auth' => true, 'tenant' => true, 'permission' => 'attendance.view']);
        $router->post('/api/v1/attendance', static fn (): Response => Response::raw('create'), ['auth' => true, 'tenant' => true, 'permission' => 'attendance.create']);
        $router->patch('/api/v1/attendance/{attendanceId}', static fn (): Response => Response::raw('update'), ['auth' => true, 'tenant' => true, 'permission' => 'attendance.update']);
    }

    public function testAttendanceRoutesRunAuthenticationTenantAndPermissionInOrder(): void
    {
        $router = new Router();
        $this->registerAttendanceRoutes($router);

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

        self::assertSame('list', $router->dispatch(Request::create('GET', '/api/v1/attendance'), $auth, $authz, $tenant)->body());
        self::assertSame(['auth', 'tenant', 'attendance.view'], $calls);
    }

    public function testAttendanceRouteUnauthenticatedReturnsAuthenticationFailure(): void
    {
        $router = new Router();
        $this->registerAttendanceRoutes($router);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Authentication failed.');

        $auth = static function (Request $request, callable $next): Response {
            throw HttpException::unauthorized('Authentication failed.', 'authentication_failed');
        };
        $authz = static fn (Request $request, callable $next, string $permission): Response => $next($request);
        $tenant = static fn (Request $request, callable $next): Response => $next($request);

        $router->dispatch(Request::create('GET', '/api/v1/attendance'), $auth, $authz, $tenant);
    }

    public function testAttendanceRouteMissingPermissionReturnsForbidden(): void
    {
        $router = new Router();
        $this->registerAttendanceRoutes($router);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Forbidden.');

        $auth = static fn (Request $request, callable $next): Response => $next($request);
        $authz = static function (Request $request, callable $next, string $permission): Response {
            throw HttpException::forbidden('Forbidden.', 'forbidden');
        };
        $tenant = static fn (Request $request, callable $next): Response => $next($request);

        $router->dispatch(Request::create('GET', '/api/v1/attendance'), $auth, $authz, $tenant);
    }

    public function testAttendanceRouteAllowsAuthorizedReadCreateAndUpdate(): void
    {
        $router = new Router();
        $this->registerAttendanceRoutes($router);

        $auth = static fn (Request $request, callable $next): Response => $next($request);
        $authz = static fn (Request $request, callable $next, string $permission): Response => $next($request);
        $tenant = static fn (Request $request, callable $next): Response => $next($request);

        self::assertSame('get', $router->dispatch(Request::create('GET', '/api/v1/attendance/1'), $auth, $authz, $tenant)->body());
        self::assertSame('create', $router->dispatch(Request::create('POST', '/api/v1/attendance'), $auth, $authz, $tenant)->body());
        self::assertSame('update', $router->dispatch(Request::create('PATCH', '/api/v1/attendance/1'), $auth, $authz, $tenant)->body());
    }
}