<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\LocationRepository;

final class LocationRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): LocationRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER)');
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, company_id INTEGER)');
        $db->execute('CREATE TABLE business_objects (business_object_id INTEGER PRIMARY KEY, company_id INTEGER)');
        $db->execute('CREATE TABLE gps_locations (gps_location_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, employee_id INTEGER, user_id INTEGER, business_object_id INTEGER, latitude REAL, longitude REAL, accuracy REAL, heading REAL, speed REAL, altitude REAL, provider TEXT, battery_level REAL, signal_strength REAL, device_id TEXT, mock_location INTEGER, confidence REAL, captured_at TEXT, created_at TEXT)');
        $db->execute("INSERT INTO employees VALUES (1,101),(2,202)");
        $db->execute("INSERT INTO users VALUES (11,101),(22,202)");
        $db->execute("INSERT INTO business_objects VALUES (31,101),(32,202)");
        $db->execute("INSERT INTO gps_locations (company_id, employee_id, user_id, business_object_id, latitude, longitude, captured_at) VALUES (101,1,11,31,-29.1,31.0,'2026-09-11 08:00:00'),(101,1,11,31,-29.2,31.1,'2026-09-11 09:00:00'),(202,2,22,32,-30.0,30.0,'2026-09-11 10:00:00')");
        return new LocationRepository($db, $context);
    }

    public function testHistoryIsTenantScopedAndNewestFirst(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertCount(2, $repository->listForEmployee('1'));
        self::assertSame('-29.2', (string) $repository->latestForEmployee('1')['latitude']);
        self::assertNull($repository->latestForEmployee('2'));
        self::assertTrue($repository->employeeBelongsToCompany('1'));
        self::assertFalse($repository->employeeBelongsToCompany('2'));
    }

    public function testCreateAlwaysUsesRuntimeTenant(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        $created = $repository->createForCompany(['employee_id' => 1, 'user_id' => 11, 'latitude' => -29.3, 'longitude' => 31.2, 'captured_at' => '2026-09-11 11:00:00']);

        self::assertSame('101', (string) $created['company_id']);
        self::assertCount(3, $repository->listForEmployee('1'));
    }
}
