<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Core;
use PHPUnit\Framework\TestCase; use Sentinel\Core\Application;
final class DeviceRouteTest extends TestCase { public function testAllCanonicalDeviceRoutesAreProtected():void{$routes=Application::create(dirname(__DIR__,3))->bootstrap()->router()->securityMetadata();$deviceRoutes=array_filter($routes,static fn(array $route):bool=>str_contains($route['path'],'/device'));self::assertCount(13,$deviceRoutes);foreach($deviceRoutes as$route){self::assertTrue($route['auth']);self::assertTrue($route['tenant']);self::assertNotSame('',$route['permission']);}} }