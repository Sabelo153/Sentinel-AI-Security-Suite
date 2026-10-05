<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PatrolCheckpointRepository;
use Sentinel\Services\PatrolCheckpointManagementService;

final class PatrolCheckpointManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): PatrolCheckpointManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE patrol_routes (patrol_route_id INTEGER PRIMARY KEY, company_id INTEGER, site_id INTEGER, deleted_at TEXT)');
        $db->execute('INSERT INTO patrol_routes VALUES (1,101,1,NULL),(2,202,2,NULL)');
        $db->execute('CREATE TABLE posts (post_id INTEGER PRIMARY KEY, company_id INTEGER, site_id INTEGER, post_name TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO posts VALUES (1,101,1,'A Post',NULL),(2,202,2,'B Post',NULL)");
        $db->execute('CREATE TABLE patrol_checkpoints (patrol_checkpoint_id INTEGER PRIMARY KEY AUTOINCREMENT, patrol_checkpoint_uuid TEXT NOT NULL, company_id INTEGER, patrol_route_id INTEGER, post_id INTEGER, checkpoint_name TEXT, checkpoint_order INTEGER, qr_code TEXT, nfc_tag TEXT, gps_lat REAL, gps_long REAL, expected_interval_minutes INTEGER, status TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, UNIQUE(patrol_route_id,checkpoint_order))');
        $db->execute("INSERT INTO patrol_checkpoints VALUES (1,'cp-a',101,1,1,'Gate',1,'QR-A','NFC-A',-26.1,28.1,5,'ACTIVE','2026-09-11','2026-09-11',NULL)");

        return new PatrolCheckpointManagementService(new PatrolCheckpointRepository($db, $context));
    }

    public function testCreateAcceptsCoordinatesAndConfigurationValues(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        $checkpoint = $service->createCheckpoint([
            'patrol_route_id' => 1,
            'post_id' => 1,
            'checkpoint_name' => 'Parking',
            'checkpoint_order' => 2,
            'qr_code' => 'QR-2',
            'nfc_tag' => 'NFC-2',
            'gps_lat' => -90,
            'gps_long' => 180,
            'expected_interval_minutes' => 10,
        ]);

        self::assertSame('QR-2', $checkpoint['qr_code']);
        self::assertSame('NFC-2', $checkpoint['nfc_tag']);
        self::assertSame('ACTIVE', $checkpoint['status']);
    }

    public function testRejectsCrossTenantRouteBadPostSequenceCoordinatesAndStatus(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        $cases = [
            [['patrol_route_id' => 2, 'checkpoint_name' => 'Wrong', 'checkpoint_order' => 2], 'patrol_route_not_found'],
            [['patrol_route_id' => 1, 'post_id' => 2, 'checkpoint_name' => 'Wrong Post', 'checkpoint_order' => 2], 'invalid_patrol_checkpoint_relationship'],
            [['patrol_route_id' => 1, 'checkpoint_name' => 'Duplicate', 'checkpoint_order' => 1], 'patrol_checkpoint_conflict'],
            [['patrol_route_id' => 1, 'checkpoint_name' => 'Bad Lat', 'checkpoint_order' => 2, 'gps_lat' => 91], 'invalid_gps_lat'],
            [['patrol_route_id' => 1, 'checkpoint_name' => 'Bad Long', 'checkpoint_order' => 2, 'gps_long' => -181], 'invalid_gps_long'],
            [['patrol_route_id' => 1, 'checkpoint_name' => 'Bad Status', 'checkpoint_order' => 2, 'status' => 'DRAFT'], 'invalid_patrol_checkpoint_status'],
        ];
        foreach ($cases as [$input, $code]) {
            try {
                $service->createCheckpoint($input);
                self::fail('Expected validation error.');
            } catch (HttpException $exception) {
                self::assertSame($code, $exception->errorCode());
            }
        }
    }

    public function testUpdateRevalidatesRouteAndProtectsImmutableFields(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        $updated = $service->updateCheckpoint('1', ['company_id' => 202, 'patrol_checkpoint_id' => 99, 'checkpoint_name' => 'Updated']);
        self::assertSame('101', (string) $updated['company_id']);
        self::assertSame('1', (string) $updated['patrol_checkpoint_id']);
        self::assertSame('Updated', $updated['checkpoint_name']);

        try {
            $service->updateCheckpoint('1', ['patrol_route_id' => 2]);
            self::fail('Expected cross-tenant route rejection.');
        } catch (HttpException $exception) {
            self::assertSame('patrol_route_not_found', $exception->errorCode());
        }
    }
}
