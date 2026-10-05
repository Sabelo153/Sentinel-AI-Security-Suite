<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\LocationRepository;
use Sentinel\Services\LocationManagementService;

final class LocationManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): LocationManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER)');
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, company_id INTEGER)');
        $db->execute('CREATE TABLE business_objects (business_object_id INTEGER PRIMARY KEY, company_id INTEGER)');
        $db->execute('CREATE TABLE gps_locations (gps_location_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, employee_id INTEGER, user_id INTEGER, business_object_id INTEGER, latitude REAL, longitude REAL, accuracy REAL, heading REAL, speed REAL, altitude REAL, provider TEXT, battery_level REAL, signal_strength REAL, device_id TEXT, mock_location INTEGER, confidence REAL, captured_at TEXT, created_at TEXT)');
        $db->execute("INSERT INTO employees VALUES (1,101),(2,202)");
        $db->execute("INSERT INTO users VALUES (11,101),(22,202)");
        $db->execute("INSERT INTO business_objects VALUES (31,101),(32,202)");
        return new LocationManagementService(new LocationRepository($db, $context));
    }

    public function testCoordinatesAndTelemetryAreValidated(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        $location = $service->recordLocation(['employee_id' => 1, 'user_id' => 11, 'business_object_id' => 31, 'latitude' => -29.1, 'longitude' => 31.0, 'accuracy' => 4.5, 'battery_level' => 80, 'captured_at' => '2026-09-11 08:00:00']);
        self::assertSame('101', (string) $location['company_id']);

        foreach ([['latitude' => 91, 'longitude' => 31], ['latitude' => -29, 'longitude' => 181], ['latitude' => -29, 'longitude' => 31, 'battery_level' => 101]] as $input) {
            try { $service->recordLocation($input); self::fail('Expected coordinate or telemetry validation.'); } catch (HttpException $e) { self::assertSame(422, $e->statusCode()); }
        }
    }

    public function testCrossCompanyReferencesAndCompanyOverrideAreRejected(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        foreach ([['employee_id' => 2], ['user_id' => 22], ['business_object_id' => 32], ['company_id' => 202]] as $extra) {
            try { $service->recordLocation($extra + ['latitude' => -29, 'longitude' => 31]); self::fail('Expected tenant-safe rejection.'); } catch (HttpException $e) { self::assertSame(isset($extra['company_id']) ? 422 : 404, $e->statusCode()); }
        }
    }

    public function testLatestAndHistoryHideCrossCompanyEmployees(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);
        $service->recordLocation(['employee_id' => 1, 'latitude' => -29, 'longitude' => 31, 'captured_at' => '2026-09-11 08:00:00']);
        $service->recordLocation(['employee_id' => 1, 'latitude' => -29.1, 'longitude' => 31.1, 'captured_at' => '2026-09-11 09:00:00']);

        self::assertSame('-29.1', (string) $service->getLatestEmployeeLocation('1')['latitude']);
        self::assertCount(2, $service->listEmployeeLocations('1'));
        try { $service->getLatestEmployeeLocation('2'); self::fail('Expected hidden employee.'); } catch (HttpException $e) { self::assertSame('employee_not_found', $e->errorCode()); }
    }
}
