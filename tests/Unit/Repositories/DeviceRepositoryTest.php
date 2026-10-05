<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\DeviceRepository;

final class DeviceRepositoryTest extends TestCase
{
    public function testDeviceQueriesAreTenantScopedAndUseCanonicalDevicesTable(): void
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE devices (device_id INTEGER PRIMARY KEY AUTOINCREMENT, device_uuid TEXT, company_id INTEGER, device_name TEXT, device_type TEXT, platform TEXT, device_identifier TEXT, device_status TEXT, last_seen_at TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO devices VALUES (1, 'a', 101, 'A Device', 'ANDROID', 'ANDROID', 'a-identifier', 'ACTIVE', NULL, NULL, NULL, NULL), (2, 'b', 202, 'B Device', 'ANDROID', 'ANDROID', 'b-identifier', 'ACTIVE', NULL, NULL, NULL, NULL)");
        $context = new RuntimeContext(); $context->setTenantId(101); $repository = new DeviceRepository($db, $context);
        self::assertCount(1, $repository->listForCompany()); self::assertSame('A Device', $repository->findByIdForCompany('1')['device_name']); self::assertNull($repository->findByIdForCompany('2'));
    }
}