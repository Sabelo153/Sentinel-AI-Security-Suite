<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PatrolCheckpointRepository;

final class PatrolCheckpointRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): PatrolCheckpointRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE patrol_routes (patrol_route_id INTEGER PRIMARY KEY, company_id INTEGER, site_id INTEGER, deleted_at TEXT)');
        $db->execute('INSERT INTO patrol_routes VALUES (1,101,1,NULL),(2,202,2,NULL),(3,101,1,\'2026-09-11\')');
        $db->execute('CREATE TABLE posts (post_id INTEGER PRIMARY KEY, company_id INTEGER, site_id INTEGER, post_name TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO posts VALUES (1,101,1,'A Post',NULL),(2,202,2,'B Post',NULL)");
        $db->execute('CREATE TABLE patrol_checkpoints (patrol_checkpoint_id INTEGER PRIMARY KEY AUTOINCREMENT, patrol_checkpoint_uuid TEXT NOT NULL, company_id INTEGER, patrol_route_id INTEGER, post_id INTEGER, checkpoint_name TEXT, checkpoint_order INTEGER, qr_code TEXT, nfc_tag TEXT, gps_lat REAL, gps_long REAL, expected_interval_minutes INTEGER, status TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, UNIQUE(patrol_route_id,checkpoint_order))');
        $db->execute("INSERT INTO patrol_checkpoints VALUES (1,'cp-a',101,1,1,'Gate',1,'QR-A','NFC-A',-26.1,28.1,5,'ACTIVE','2026-09-11','2026-09-11',NULL),(2,'cp-b',202,2,2,'Gate',1,'QR-B','NFC-B',-26.2,28.2,5,'ACTIVE','2026-09-11','2026-09-11',NULL),(3,'cp-deleted',101,1,1,'Deleted',3,NULL,NULL,NULL,NULL,NULL,'ARCHIVED','2026-09-11','2026-09-11','2026-09-11')");

        return new PatrolCheckpointRepository($db, $context);
    }

    public function testCheckpointReadsAreTenantScopedAndOrdered(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertCount(1, $repository->listForRoute('1'));
        self::assertSame('Gate', $repository->findByIdForCompany('1')['checkpoint_name']);
        self::assertNull($repository->findByIdForCompany('2'));
        self::assertNull($repository->findByIdForCompany('3'));
        self::assertFalse($repository->routeBelongsToCompany('2'));
    }

    public function testCreateUpdateAndSequenceHelpersAreTenantScoped(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        $created = $repository->createForCompany([
            'company_id' => 202,
            'patrol_route_id' => 1,
            'post_id' => 1,
            'checkpoint_name' => 'Parking',
            'checkpoint_order' => 2,
            'qr_code' => 'QR-C',
            'nfc_tag' => 'NFC-C',
            'gps_lat' => 1.5,
            'gps_long' => 2.5,
            'status' => 'ACTIVE',
        ]);
        self::assertSame('101', (string) $created['company_id']);
        self::assertTrue($repository->sequenceExistsForRoute('1', 2));
        self::assertFalse($repository->sequenceExistsForRoute('1', 2, (string) $created['patrol_checkpoint_id']));

        $updated = $repository->updateForCompany((string) $created['patrol_checkpoint_id'], ['checkpoint_name' => 'Parking Updated']);
        self::assertSame('Parking Updated', $updated['checkpoint_name']);
        self::assertNull($repository->updateForCompany('2', ['checkpoint_name' => 'No access']));
    }
}
