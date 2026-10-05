<?php

declare(strict_types=1);

namespace Sentinel\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Application, Config, Database, Request};
use Sentinel\Core\HttpException;

final class SupportDiagnosticsTest extends TestCase
{
    public function testLocalSupportConsoleReturnsOnlySafeHealthStates(): void
    {
        $app = Application::create(dirname(__DIR__, 2))->bootstrap();
        $app->config()->set('support.diagnostics_enabled', true);
        $app->container()->instance('database', new Database(new Config([]), static fn (): PDO => new PDO('sqlite::memory:')));
        $app->container()->instance('support_diagnostics_service', new \Sentinel\Services\SupportDiagnosticsService($app->config(), $app->container()->make('database')));

        $response = $app->handle(Request::create('GET', '/support/diagnostics', server: ['REMOTE_ADDR' => '127.0.0.1']));

        self::assertSame(503, $response->status());
        self::assertSame('no-store', $response->header('Cache-Control'));
        self::assertSame('attention_required', json_decode($response->body(), true)['status']);
        self::assertArrayNotHasKey('database_host', json_decode($response->body(), true));
        self::assertSame('unavailable', json_decode($response->body(), true)['checks']['patrols_and_checkpoints']['status']);
        self::assertContains('patrol_schedule_windows', json_decode($response->body(), true)['checks']['patrols_and_checkpoints']['missing_tables']);
        self::assertArrayHasKey('local_ai_guide', json_decode($response->body(), true)['checks']);
    }

    public function testSupportConsoleIsNotReachableFromNetworkClients(): void
    {
        $app = Application::create(dirname(__DIR__, 2))->bootstrap();
        $app->config()->set('support.diagnostics_enabled', true);

        foreach (['/support/diagnostics', '/support'] as $path) {
            try {
                $app->handle(Request::create('GET', $path, server: ['REMOTE_ADDR' => '192.168.1.10']));
                self::fail('Network support route must not be reachable.');
            } catch (HttpException $error) {
                self::assertSame(404, $error->statusCode());
            }
        }
    }

    public function testSchemaChecksHandleEmptyTablesWithoutExposingRecords(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE inspections (private_note TEXT); CREATE TABLE inspection_items (private_note TEXT)');
        $pdo->exec("INSERT INTO inspections VALUES ('confidential report contents')");
        $config = new Config([]);
        $database = new Database($config, static fn (): PDO => $pdo);
        $report = (new \Sentinel\Services\SupportDiagnosticsService($config, $database))->report();
        self::assertSame('ready', $report['checks']['inspections']['status']);
        self::assertSame([], $report['checks']['inspections']['missing_tables']);
        self::assertStringNotContainsString('confidential', json_encode($report));
        self::assertSame('not_enabled', $report['checks']['local_ai_guide']['status']);
    }

    public function testDatabaseFailureDoesNotLeakDriverError(): void
    {
        $config = new Config([]);
        $database = new Database($config, static function (): PDO { throw new \PDOException('secret connection details'); });
        $report = (new \Sentinel\Services\SupportDiagnosticsService($config, $database))->report();
        self::assertSame('attention_required', $report['status']);
        self::assertSame('unavailable', $report['checks']['patrols_and_checkpoints']['status']);
        self::assertStringNotContainsString('secret', json_encode($report));
    }
    public function testFeatureGuideCoversAutomatedDomainsAndDeclaresManualLimitations(): void
    {
        $catalog=json_decode(file_get_contents(dirname(__DIR__,2).'/public/assets/support-catalog.json'),true,512,JSON_THROW_ON_ERROR);
        $config=new Config([]);$database=new Database($config,static fn():PDO=>new PDO('sqlite::memory:'));
        $report=(new \Sentinel\Services\SupportDiagnosticsService($config,$database))->report();
        $ids=array_column($catalog,'id');
        foreach(array_keys($report['checks']) as $id){if(in_array($id,['application','database','runtime_storage','php_runtime'],true))continue;self::assertContains($id,$ids);}
        foreach($catalog as $guide){self::assertNotEmpty($guide['remedy']);self::assertNotEmpty($guide['manual']);}
        self::assertStringContainsString('Manual workflows',$report['scope']);
    }
}
