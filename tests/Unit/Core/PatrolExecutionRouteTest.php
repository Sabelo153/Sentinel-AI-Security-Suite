<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\HttpException;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\Router;

final class PatrolExecutionRouteTest extends TestCase
{
    private function register(Router $router): void
    {
        $router->get('/api/v1/patrol-sessions', static fn (): Response => Response::raw('sessions'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_sessions.view']);
        $router->get('/api/v1/patrol-sessions/{sessionId}/events', static fn (): Response => Response::raw('events'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoint_events.view']);
        $router->get('/api/v1/patrol-sessions/{sessionId}', static fn (): Response => Response::raw('session'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_sessions.view']);
        $router->post('/api/v1/patrol-sessions', static fn (): Response => Response::raw('start'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_sessions.create']);
        $router->patch('/api/v1/patrol-sessions/{sessionId}', static fn (): Response => Response::raw('update'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_sessions.update']);
        $router->post('/api/v1/patrol-sessions/{sessionId}/events', static fn (): Response => Response::raw('record'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoint_events.create']);
        $router->get('/api/v1/patrol-checkpoint-events/{eventId}', static fn (): Response => Response::raw('event'), ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoint_events.view']);
    }

    public function testNestedOrderingAndMiddlewareOrder(): void
    {
        $router = new Router(); $this->register($router); $calls = [];
        $auth = static function (Request $request, callable $next) use (&$calls): Response { $calls[] = 'auth'; return $next($request); };
        $authz = static function (Request $request, callable $next, string $permission) use (&$calls): Response { $calls[] = $permission; return $next($request); };
        $tenant = static function (Request $request, callable $next) use (&$calls): Response { $calls[] = 'tenant'; return $next($request); };
        self::assertSame('events', $router->dispatch(Request::create('GET', '/api/v1/patrol-sessions/1/events'), $auth, $authz, $tenant)->body());
        self::assertSame(['auth', 'tenant', 'patrol_checkpoint_events.view'], $calls);
        self::assertSame('session', $router->dispatch(Request::create('GET', '/api/v1/patrol-sessions/1'), $auth, $authz, $tenant)->body());
    }

    public function testAuthorizedMethodsExistAndNoEventMutationRoutes(): void
    {
        $router = new Router(); $this->register($router);
        $auth = static fn (Request $request, callable $next): Response => $next($request);
        $authz = static fn (Request $request, callable $next, string $permission): Response => $next($request);
        $tenant = static fn (Request $request, callable $next): Response => $next($request);
        foreach ([['GET', '/api/v1/patrol-sessions', 'sessions'], ['GET', '/api/v1/patrol-sessions/1', 'session'], ['POST', '/api/v1/patrol-sessions', 'start'], ['PATCH', '/api/v1/patrol-sessions/1', 'update'], ['GET', '/api/v1/patrol-sessions/1/events', 'events'], ['POST', '/api/v1/patrol-sessions/1/events', 'record'], ['GET', '/api/v1/patrol-checkpoint-events/1', 'event']] as [$method, $path, $body]) {
            self::assertSame($body, $router->dispatch(Request::create($method, $path), $auth, $authz, $tenant)->body());
        }
        self::assertSame(405, $router->dispatch(Request::create('PATCH', '/api/v1/patrol-checkpoint-events/1'), $auth, $authz, $tenant)->status());
    }

    public function testAuthenticationAndAuthorizationFailuresAreSafe(): void
    {
        $router = new Router(); $this->register($router);
        $tenant = static fn (Request $request, callable $next): Response => $next($request);
        try { $router->dispatch(Request::create('GET', '/api/v1/patrol-sessions'), static function (): Response { throw HttpException::unauthorized('Authentication failed.', 'authentication_failed'); }, static fn (Request $r, callable $n, string $p): Response => $n($r), $tenant); self::fail('Expected auth failure.'); } catch (HttpException $e) { self::assertSame(401, $e->statusCode()); }
        try { $router->dispatch(Request::create('GET', '/api/v1/patrol-sessions'), static fn (Request $r, callable $n): Response => $n($r), static function (): Response { throw HttpException::forbidden('Forbidden.', 'forbidden'); }, $tenant); self::fail('Expected authorization failure.'); } catch (HttpException $e) { self::assertSame(403, $e->statusCode()); }
    }
}
