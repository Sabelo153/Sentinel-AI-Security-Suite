<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PatrolCheckpointEventRepository;

final class PatrolCheckpointEventRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): PatrolCheckpointEventRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE patrol_sessions (patrol_session_id INTEGER PRIMARY KEY, company_id INTEGER, patrol_route_id INTEGER, status TEXT, started_at TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO patrol_sessions VALUES (1,101,1,'IN_PROGRESS','2026-09-11 08:00:00',NULL),(2,202,2,'IN_PROGRESS','2026-09-11 08:00:00',NULL)");
        $db->execute('CREATE TABLE patrol_checkpoints (patrol_checkpoint_id INTEGER PRIMARY KEY, company_id INTEGER, patrol_route_id INTEGER, checkpoint_name TEXT, checkpoint_order INTEGER, deleted_at TEXT)');
        $db->execute("INSERT INTO patrol_checkpoints VALUES (1,101,1,'Gate',1,NULL),(2,202,2,'Gate',1,NULL)");
        $db->execute('CREATE TABLE patrol_checkpoint_events (patrol_checkpoint_event_id INTEGER PRIMARY KEY AUTOINCREMENT, patrol_checkpoint_event_uuid TEXT UNIQUE, company_id INTEGER, patrol_session_id INTEGER, patrol_checkpoint_id INTEGER, event_type TEXT, recorded_at TEXT, event_notes TEXT, created_at TEXT)');
        $db->execute("INSERT INTO patrol_checkpoint_events VALUES (1,'event-a',101,1,1,'VISITED','2026-09-11 08:10:00','A','2026-09-11'),(2,'event-b',202,2,2,'VISITED','2026-09-11 08:10:00','B','2026-09-11')");
        return new PatrolCheckpointEventRepository($db, $context);
    }

    public function testEventsAreTenantScopedAndAppendOnly(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);
        self::assertCount(1, $repository->listForSession('1'));
        self::assertSame('event-a', $repository->findByIdForCompany('1')['patrol_checkpoint_event_uuid']);
        self::assertNull($repository->findByIdForCompany('2'));
        self::assertNull($repository->findByIdForCompany('999'));
        self::assertFalse(method_exists($repository, 'updateForCompany'));
        self::assertFalse(method_exists($repository, 'updateForCompany'));
    }

    public function testCreateUsesRuntimeTenantAndAllowsMultipleEvents(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);
        $data = ['patrol_session_id' => 1, 'patrol_checkpoint_id' => 1, 'event_type' => 'FAILED', 'recorded_at' => '2026-09-11 08:11:00', 'event_notes' => 'retry'];
        $created = $repository->createForCompany($data);
        self::assertSame('101', (string) $created['company_id']);
        self::assertNotEmpty($created['patrol_checkpoint_event_uuid']);
        self::assertCount(2, $repository->listForSession('1'));
    }
}
