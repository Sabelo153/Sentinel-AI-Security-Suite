<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\Router;

final class RouterTest extends TestCase
{
    public function testStaticAndParameterizedRoutesDispatch(): void
    {
        $router = new Router();
        $router->get('/users/{id}', static fn (Request $request, array $params): Response => Response::json($params));

        $response = $router->dispatch(Request::create('GET', '/users/123?ignored=true/'));

        self::assertSame(200, $response->status());
        self::assertSame('{"id":"123"}', $response->body());
    }

    public function testMultipleParametersAndMethodsAreSupported(): void
    {
        $router = new Router();
        $router->any(['GET', 'POST'], '/companies/{companyId}/users/{userId}', static fn (): Response => Response::raw('matched'));

        self::assertSame(200, $router->dispatch(Request::create('POST', '/companies/acme/users/7'))->status());
    }

    public function testUnsupportedMethodReturnsAllowHeader(): void
    {
        $router = new Router();
        $router->get('/health', static fn (): Response => Response::raw('ok'));

        $response = $router->dispatch(Request::create('POST', '/health'));

        self::assertSame(405, $response->status());
        self::assertSame('GET', $response->header('Allow'));
    }

    public function testUnknownPathReturns404(): void
    {
        $router = new Router();

        self::assertSame(404, $router->dispatch(Request::create('GET', '/missing'))->status());
    }

    public function testDuplicateMethodAndPathIsRejected(): void
    {
        $router = new Router();
        $router->get('/health', static fn (): Response => Response::raw('one'));

        $this->expectException(\LogicException::class);
        $router->get('/health/', static fn (): Response => Response::raw('two'));
    }

    public function testHandlerReceivesRequestAndParameters(): void
    {
        $router = new Router();
        $request = Request::create('GET', '/users/123');
        $router->get('/users/{id}', static function (Request $received, array $params) use ($request): Response {
            TestCase::assertSame($request, $received);
            TestCase::assertSame(['id' => '123'], $params);

            return Response::raw('done');
        });

        self::assertSame('done', $router->dispatch($request)->body());
    }

    public function testProtectedRouteUsesExplicitAuthenticationCallbackOnlyWhenRequired(): void
    {
        $router = new Router();
        $router->get('/public', static fn (): Response => Response::raw('public'));
        $router->get('/protected', static fn (): Response => Response::raw('protected'), ['auth' => true]);
        $authCalls = 0;
        $authentication = static function (Request $request, callable $next) use (&$authCalls): Response {
            $authCalls++;

            return $next($request);
        };

        self::assertSame('public', $router->dispatch(Request::create('GET', '/public'), $authentication)->body());
        self::assertSame(0, $authCalls);
        self::assertSame('protected', $router->dispatch(Request::create('GET', '/protected'), $authentication)->body());
        self::assertSame(1, $authCalls);
    }

    public function testPermissionRouteRequiresAuthenticationAndAuthorizationCallback(): void
    {
        $router = new Router();
        $router->get('/secret', static fn (): Response => Response::raw('secret'), ['permission' => 'system.test']);
        $authCalls = 0;
        $authzCalls = 0;

        $authentication = static function (Request $request, callable $next) use (&$authCalls): Response {
            $authCalls++;

            return $next($request);
        };
        $authorization = static function (Request $request, callable $next, string $permission) use (&$authzCalls): Response {
            $authzCalls++;
            self::assertSame('system.test', $permission);

            return $next($request);
        };

        self::assertSame('secret', $router->dispatch(Request::create('GET', '/secret'), $authentication, $authorization)->body());
        self::assertSame(1, $authCalls);
        self::assertSame(1, $authzCalls);
    }

    public function testTenantPermissionRouteRunsAuthenticationTenantAuthorizationInOrder(): void
    {
        $router = new Router();
        $router->get('/tenant-secret', static fn (): Response => Response::raw('secret'), [
            'tenant' => true,
            'permission' => 'sites.view',
        ]);
        $calls = [];

        $authentication = static function (Request $request, callable $next) use (&$calls): Response {
            $calls[] = 'authentication';

            return $next($request);
        };
        $authorization = static function (Request $request, callable $next, string $permission) use (&$calls): Response {
            $calls[] = 'authorization:' . $permission;

            return $next($request);
        };
        $tenant = static function (Request $request, callable $next) use (&$calls): Response {
            $calls[] = 'tenant';

            return $next($request);
        };

        $response = $router->dispatch(Request::create('GET', '/tenant-secret'), $authentication, $authorization, $tenant);

        self::assertSame('secret', $response->body());
        self::assertSame(['authentication', 'tenant', 'authorization:sites.view'], $calls);
    }
}