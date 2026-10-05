<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PatrolCheckpointEventRepository;
use Sentinel\Services\PatrolCheckpointEventManagementService;

final class PatrolCheckpointEventManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): PatrolCheckpointEventManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE patrol_sessions (patrol_session_id INTEGER PRIMARY KEY, company_id INTEGER, patrol_route_id INTEGER, status TEXT, started_at TEXT, deleted_at TEXT)'); $db->execute("INSERT INTO patrol_sessions VALUES (1,101,1,'IN_PROGRESS','2026-09-11 08:00:00',NULL),(2,101,1,'COMPLETED','2026-09-11 08:00:00',NULL),(3,202,2,'IN_PROGRESS','2026-09-11 08:00:00',NULL)");
        $db->execute('CREATE TABLE patrol_checkpoints (patrol_checkpoint_id INTEGER PRIMARY KEY, company_id INTEGER, patrol_route_id INTEGER, checkpoint_name TEXT, checkpoint_order INTEGER, deleted_at TEXT)'); $db->execute("INSERT INTO patrol_checkpoints VALUES (1,101,1,'A',1,NULL),(2,101,2,'Other route',1,NULL),(3,202,2,'B',1,NULL)");
        $db->execute('CREATE TABLE patrol_checkpoint_events (patrol_checkpoint_event_id INTEGER PRIMARY KEY AUTOINCREMENT, patrol_checkpoint_event_uuid TEXT UNIQUE, company_id INTEGER, patrol_session_id INTEGER, patrol_checkpoint_id INTEGER, event_type TEXT, recorded_at TEXT, event_notes TEXT, created_at TEXT)');
        return new PatrolCheckpointEventManagementService(new PatrolCheckpointEventRepository($db, $context));
    }

    public function testEventTypesDefaultAndMultipleHistoryAreSupported(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        foreach (['VISITED', 'SKIPPED', 'FAILED'] as $type) {
            $event = $service->recordEvent('1', ['patrol_checkpoint_id' => 1, 'event_type' => $type, 'recorded_at' => '2026-09-11 08:10:00']);
            self::assertSame($type, $event['event_type']); self::assertNotEmpty($event['patrol_checkpoint_event_uuid']);
        }
        $default = $service->recordEvent('1', ['patrol_checkpoint_id' => 1, 'recorded_at' => '2026-09-11 08:11:00']);
        self::assertSame('VISITED', $default['event_type']);
    }

    public function testSessionCheckpointAndTimeRulesAreSafe(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        $cases = [
            ['session' => '2', 'input' => ['patrol_checkpoint_id' => 1, 'recorded_at' => '2026-09-11 08:10:00'], 'code' => 'invalid_patrol_session_state'],
            ['session' => '3', 'input' => ['patrol_checkpoint_id' => 3, 'recorded_at' => '2026-09-11 08:10:00'], 'code' => 'patrol_session_not_found'],
            ['session' => '1', 'input' => ['patrol_checkpoint_id' => 3, 'recorded_at' => '2026-09-11 08:10:00'], 'code' => 'patrol_checkpoint_not_found'],
            ['session' => '1', 'input' => ['patrol_checkpoint_id' => 2, 'recorded_at' => '2026-09-11 08:10:00'], 'code' => 'invalid_patrol_checkpoint_event_relationship'],
            ['session' => '1', 'input' => ['patrol_checkpoint_id' => 1, 'recorded_at' => '2026-09-11 07:59:00'], 'code' => 'invalid_patrol_checkpoint_event_timestamp'],
        ];
        foreach ($cases as $case) {
            try { $service->recordEvent($case['session'], $case['input']); self::fail('Expected event validation error.'); } catch (HttpException $e) { self::assertSame($case['code'], $e->errorCode()); }
        }
    }

    public function testCrossCompanyEventReadsAreHiddenAndEventsAreImmutable(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        try { $service->listEventsForSession('3'); self::fail('Expected hidden session.'); } catch (HttpException $e) { self::assertSame('patrol_session_not_found', $e->errorCode()); }
        try { $service->getEvent('999'); self::fail('Expected hidden event.'); } catch (HttpException $e) { self::assertSame('patrol_checkpoint_event_not_found', $e->errorCode()); }
        self::assertFalse(method_exists($service, 'updateEvent'));
        self::assertFalse(method_exists($service, 'deleteEvent'));
    }
}
