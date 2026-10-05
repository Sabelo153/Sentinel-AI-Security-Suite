<?php
declare(strict_types=1);
namespace Sentinel\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Application,Config,Database,Request};

final class HealthEndpointTest extends TestCase
{
    public function testLivenessDoesNotNeedDatabaseAndReadinessReportsDatabaseState(): void
    {
        $app=Application::create(dirname(__DIR__,2))->bootstrap();
        $database=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:'));
        $app->container()->instance('database',$database);
        self::assertSame(200,$app->handle(Request::create('GET','/healthz'))->status());
        self::assertSame(['status'=>'ok','service'=>'sentinel'],json_decode($app->handle(Request::create('GET','/healthz'))->body(),true));
        self::assertSame(200,$app->handle(Request::create('GET','/readyz'))->status());
    }

    public function testReadinessReturnsSafe503WhenDatabaseIsUnavailable(): void
    {
        $app=Application::create(dirname(__DIR__,2))->bootstrap();
        $database=new Database(new Config([]),static function():PDO { throw new \PDOException('connection details must not leak'); });
        $app->container()->instance('database',$database);
        $response=$app->handle(Request::create('GET','/readyz'));
        self::assertSame(503,$response->status());
        self::assertSame(['status'=>'unavailable'],json_decode($response->body(),true));
        self::assertStringNotContainsString('connection details',$response->body());
    }
}
