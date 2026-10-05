<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Application;

final class ReportingRouteTest extends TestCase
{
    public function testAllReportRoutesRequireTenantAndReportsViewPermission(): void
    {
        $routes = Application::create(dirname(__DIR__, 3))->bootstrap()->router()->securityMetadata();
        $reportRoutes = array_filter($routes, static fn (array $route): bool => str_starts_with($route['path'], '/api/v1/reports/'));
        self::assertCount(13, $reportRoutes);
        foreach ($reportRoutes as $route) self::assertSame([true, true, 'reports.view'], [$route['auth'], $route['tenant'], $route['permission']]);
    }
}