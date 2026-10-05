<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Application;

final class DashboardRouteTest extends TestCase
{
    public function testDashboardSummaryRequiresAuthenticationTenantAndPermission(): void
    {
        $routes = Application::create(dirname(__DIR__, 3))->bootstrap()->router()->securityMetadata();
        $route = array_values(array_filter($routes, static fn (array $route): bool => $route['path'] === '/api/v1/dashboard/summary'))[0];
        self::assertSame([true, true, 'dashboard.view'], [$route['auth'], $route['tenant'], $route['permission']]);
    }
}