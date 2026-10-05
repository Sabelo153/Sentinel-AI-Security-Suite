<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\Router;

final class RbacAdministrationRouteTest extends TestCase
{
	public function testRbacAdministrationRoutesRequireAuthenticationTenantAndPermission(): void
	{
		$router = new Router();
		$router->post('/api/v1/roles/{roleId}/permissions', static fn (): Response => Response::raw('assigned'), ['auth' => true, 'tenant' => true, 'permission' => 'roles.assign_permissions']);
		$calls = [];
		$authentication = static function (Request $request, callable $next) use (&$calls): Response { $calls[] = 'auth'; return $next($request); };
		$tenant = static function (Request $request, callable $next) use (&$calls): Response { $calls[] = 'tenant'; return $next($request); };
		$authorization = static function (Request $request, callable $next, string $permission) use (&$calls): Response { $calls[] = $permission; return $next($request); };

		self::assertSame('assigned', $router->dispatch(Request::create('POST', '/api/v1/roles/1/permissions'), $authentication, $authorization, $tenant)->body());
		self::assertSame(['auth', 'tenant', 'roles.assign_permissions'], $calls);
	}
}