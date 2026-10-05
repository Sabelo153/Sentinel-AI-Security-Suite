<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Core;
use PHPUnit\Framework\TestCase;use Sentinel\Core\Application;
final class IncidentRouteTest extends TestCase {public function testIncidentCrudRoutesRequireTenantAndPermissions():void{$routes=Application::create(dirname(__DIR__,3))->bootstrap()->router()->securityMetadata();$incidents=array_values(array_filter($routes,static fn(array $route):bool=>str_starts_with($route['path'],'/api/v1/incidents')));self::assertCount(4,$incidents);self::assertSame(['incidents.view','incidents.view','incidents.create','incidents.update'],array_column($incidents,'permission'));foreach($incidents as$route){self::assertTrue($route['auth']);self::assertTrue($route['tenant']);}}}