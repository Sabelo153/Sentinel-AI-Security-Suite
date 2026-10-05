<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Core;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Application;
final class AiHelpRouteTest extends TestCase
{
    public function testHelpAssistantUsesNormalProtectionAndNoOperationalSummary():void
    {
        $routes=Application::create(dirname(__DIR__,3))->bootstrap()->router()->securityMetadata();
        $route=array_values(array_filter($routes,static fn(array $route):bool=>$route['path']==='/api/v1/help/assistant'))[0];
        self::assertSame([true,true,'dashboard.view'],[$route['auth'],$route['tenant'],$route['permission']]);
        $guard=array_values(array_filter($routes,static fn(array $route):bool=>$route['path']==='/api/v1/guard/help'))[0];
        self::assertSame([true,true,'guard.self'],[$guard['auth'],$guard['tenant'],$guard['permission']]);
        $source=file_get_contents(dirname(__DIR__,3).'/routes/api.php');self::assertStringNotContainsString('supervisor/advisory',$source);self::assertStringNotContainsString("generate(\$container->make('operational_reporting_service')->summary())",$source);
    }
}
