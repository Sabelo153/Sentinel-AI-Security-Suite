<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\HttpException;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\Router;

final class PatrolManagementRouteTest extends TestCase
{
    private function register(Router $router): void
    {
        $router->get('/api/v1/patrol-routes', static fn (): Response => Response::raw('routes'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_routes.view']);
        $router->get('/api/v1/patrol-routes/{routeId}/checkpoints', static fn (): Response => Response::raw('checkpoints'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoints.view']);
        $router->get('/api/v1/patrol-routes/{routeId}', static fn (): Response => Response::raw('route'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_routes.view']);
        $router->post('/api/v1/patrol-routes', static fn (): Response => Response::raw('create-route'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_routes.create']);
        $router->patch('/api/v1/patrol-routes/{routeId}', static fn (): Response => Response::raw('update-route'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_routes.update']);
        $router->post('/api/v1/patrol-routes/{routeId}/checkpoints', static fn (): Response => Response::raw('create-checkpoint'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoints.create']);
        $router->get('/api/v1/patrol-checkpoints/{checkpointId}', static fn (): Response => Response::raw('checkpoint'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoints.view']);
        $router->patch('/api/v1/patrol-checkpoints/{checkpointId}', static fn (): Response => Response::raw('update-checkpoint'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoints.update']);
    }

    public function testRouteOrderingAndMiddlewareOrderAreCorrect(): void
    {
        $router = new Router();
        $this->register($router);
        $calls = [];
        $auth = static function (Request $request, callable $next) use (&$calls): Response { $calls[] = 'auth'; return $next($request); };
        $authz = static function (Request $request, callable $next, string $permission) use (&$calls): Response { $calls[] = $permission; return $next($request); };
        $tenant = static function (Request $request, callable $next) use (&$calls): Response { $calls[] = 'tenant'; return $next($request); };

        self::assertSame('checkpoints', $router->dispatch(Request::create('GET', '/api/v1/patrol-routes/1/checkpoints'), $auth, $authz, $tenant)->body());
        self::assertSame(['auth', 'tenant', 'patrol_checkpoints.view'], $calls);
        self::assertSame('route', $router->dispatch(Request::create('GET', '/api/v1/patrol-routes/1'), $auth, $authz, $tenant)->body());
    }

    public function testAuthenticationAndPermissionFailuresAreSafe(): void
    {
        $router = new Router();
        $this->register($router);
        $authz = static fn (Request $request, callable $next, string $permission): Response => $next($request);
        $tenant = static fn (Request $request, callable $next): Response => $next($request);

        try {
            $router->dispatch(Request::create('GET', '/api/v1/patrol-routes'), static function (): Response { throw HttpException::unauthorized('Authentication failed.', 'authentication_failed'); }, $authz, $tenant);
            self::fail('Expected authentication failure.');
        } catch (HttpException $exception) {
            self::assertSame(401, $exception->statusCode());
            self::assertSame('authentication_failed', $exception->errorCode());
        }

        try {
            $router->dispatch(Request::create('GET', '/api/v1/patrol-routes'), static fn (Request $request, callable $next): Response => $next($request), static function (): Response { throw HttpException::forbidden('Forbidden.', 'forbidden'); }, $tenant);
            self::fail('Expected permission failure.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->statusCode());
            self::assertSame('forbidden', $exception->errorCode());
        }
    }

    public function testAuthorizedRouteMethodsAreAvailable(): void
    {
        $router = new Router();
        $this->register($router);
        $auth = static fn (Request $request, callable $next): Response => $next($request);
        $authz = static fn (Request $request, callable $next, string $permission): Response => $next($request);
        $tenant = static fn (Request $request, callable $next): Response => $next($request);

        self::assertSame('routes', $router->dispatch(Request::create('GET', '/api/v1/patrol-routes'), $auth, $authz, $tenant)->body());
        self::assertSame('create-route', $router->dispatch(Request::create('POST', '/api/v1/patrol-routes'), $auth, $authz, $tenant)->body());
        self::assertSame('update-route', $router->dispatch(Request::create('PATCH', '/api/v1/patrol-routes/1'), $auth, $authz, $tenant)->body());
        self::assertSame('create-checkpoint', $router->dispatch(Request::create('POST', '/api/v1/patrol-routes/1/checkpoints'), $auth, $authz, $tenant)->body());
        self::assertSame('checkpoint', $router->dispatch(Request::create('GET', '/api/v1/patrol-checkpoints/1'), $auth, $authz, $tenant)->body());
        self::assertSame('update-checkpoint', $router->dispatch(Request::create('PATCH', '/api/v1/patrol-checkpoints/1'), $auth, $authz, $tenant)->body());
    }
}
