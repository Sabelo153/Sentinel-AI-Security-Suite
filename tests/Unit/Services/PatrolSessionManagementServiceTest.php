<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PatrolSessionRepository;
use Sentinel\Services\PatrolSessionManagementService;

final class PatrolSessionManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): PatrolSessionManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY, company_id INTEGER, site_name TEXT)'); $db->execute("INSERT INTO sites VALUES (1,101,'A'),(2,202,'B')");
        $db->execute('CREATE TABLE patrol_routes (patrol_route_id INTEGER PRIMARY KEY, company_id INTEGER, site_id INTEGER, route_name TEXT, route_code TEXT, status TEXT, deleted_at TEXT)'); $db->execute("INSERT INTO patrol_routes VALUES (1,101,1,'A','A','ACTIVE',NULL),(2,101,1,'Inactive','I','INACTIVE',NULL),(3,202,2,'B','B','ACTIVE',NULL)");
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY, company_id INTEGER, first_name TEXT, last_name TEXT)'); $db->execute("INSERT INTO people VALUES (1,101,'A','Guard'),(2,101,'B','Supervisor'),(3,202,'C','Guard')");
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER, person_id INTEGER, employee_number TEXT, employment_status TEXT)'); $db->execute("INSERT INTO employees VALUES (1,101,1,'A1','ACTIVE'),(2,101,2,'A2','ACTIVE'),(3,202,3,'B1','ACTIVE')");
        $db->execute('CREATE TABLE positions (position_id INTEGER PRIMARY KEY, company_id INTEGER, position_name TEXT, position_category TEXT, position_status TEXT, deleted_at TEXT)'); $db->execute("INSERT INTO positions VALUES (1,101,'Guard','GUARD','ACTIVE',NULL),(2,101,'Supervisor','SUPERVISOR','ACTIVE',NULL),(3,202,'Guard','GUARD','ACTIVE',NULL)");
        $db->execute('CREATE TABLE employee_assignments (employee_assignment_id INTEGER PRIMARY KEY, company_id INTEGER, employee_id INTEGER, position_id INTEGER, assignment_status TEXT, deleted_at TEXT)'); $db->execute("INSERT INTO employee_assignments VALUES (1,101,1,1,'ACTIVE',NULL),(2,101,2,2,'ACTIVE',NULL),(3,202,3,3,'ACTIVE',NULL)");
        $db->execute('CREATE TABLE shift_assignments (shift_assignment_id INTEGER PRIMARY KEY, company_id INTEGER, employee_id INTEGER, site_id INTEGER, shift_name TEXT, assignment_date TEXT, shift_start_at TEXT, shift_end_at TEXT, deleted_at TEXT)'); $db->execute("INSERT INTO shift_assignments VALUES (1,101,1,1,'Day','2026-09-11','2026-09-11 08:00:00','2026-09-11 16:00:00',NULL),(2,101,2,1,'Other','2026-09-11','2026-09-11 08:00:00','2026-09-11 16:00:00',NULL),(3,101,1,2,'Wrong site','2026-09-11','2026-09-11 08:00:00','2026-09-11 16:00:00',NULL),(4,202,3,2,'B','2026-09-11','2026-09-11 08:00:00','2026-09-11 16:00:00',NULL)");
        $db->execute('CREATE TABLE patrol_sessions (patrol_session_id INTEGER PRIMARY KEY AUTOINCREMENT, patrol_session_uuid TEXT UNIQUE, company_id INTEGER, patrol_route_id INTEGER, employee_assignment_id INTEGER, shift_assignment_id INTEGER, status TEXT, started_at TEXT, completed_at TEXT, ended_at TEXT, execution_notes TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        return new PatrolSessionManagementService(new PatrolSessionRepository($db, $context));
    }

    public function testStartSupportsGuardWithoutShiftAndOptionalValidShift(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        $session = $service->startSession(['patrol_route_id' => 1, 'employee_assignment_id' => 1, 'started_at' => '2026-09-11 08:00:00']);
        self::assertSame('IN_PROGRESS', $session['status']); self::assertSame('101', (string) $session['company_id']); self::assertNotEmpty($session['patrol_session_uuid']);

        try { $service->startSession(['patrol_route_id' => 1, 'employee_assignment_id' => 1, 'started_at' => '2026-09-11 09:00:00']); self::fail('Expected active conflict.'); } catch (HttpException $e) { self::assertSame('patrol_session_conflict', $e->errorCode()); }
    }

    public function testGuardRouteAndShiftRelationshipsAreSafe(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        foreach ([['employee_assignment_id' => 2, 'code' => 'guard_assignment_not_found'], ['employee_assignment_id' => 3, 'code' => 'guard_assignment_not_found'], ['employee_assignment_id' => 1, 'patrol_route_id' => 2, 'code' => 'patrol_route_not_found'], ['employee_assignment_id' => 1, 'patrol_route_id' => 1, 'shift_assignment_id' => 2, 'code' => 'invalid_patrol_session_relationship'], ['employee_assignment_id' => 1, 'patrol_route_id' => 1, 'shift_assignment_id' => 3, 'code' => 'invalid_patrol_session_relationship'], ['employee_assignment_id' => 1, 'patrol_route_id' => 3, 'code' => 'patrol_route_not_found']] as $case) {
            try { $service->startSession(['patrol_route_id' => $case['patrol_route_id'] ?? 1, 'employee_assignment_id' => $case['employee_assignment_id'], 'shift_assignment_id' => $case['shift_assignment_id'] ?? null, 'started_at' => '2026-09-11 08:00:00']); self::fail('Expected safe validation.'); } catch (HttpException $e) { self::assertSame($case['code'], $e->errorCode()); }
        }
    }

    public function testLifecycleAndEffectiveTimestampValidation(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        $session = $service->startSession(['patrol_route_id' => 1, 'employee_assignment_id' => 1, 'started_at' => '2026-09-11 08:00:00']);
        $completed = $service->updateSession((string) $session['patrol_session_id'], ['status' => 'COMPLETED', 'completed_at' => '2026-09-11 09:00:00', 'ended_at' => '2026-09-11 09:05:00']);
        self::assertSame('COMPLETED', $completed['status']);
        try { $service->updateSession((string) $session['patrol_session_id'], ['status' => 'CANCELLED', 'ended_at' => '2026-09-11 10:00:00']); self::fail('Expected terminal rejection.'); } catch (HttpException $e) { self::assertSame('invalid_patrol_session_state', $e->errorCode()); }

        $second = $service->startSession(['patrol_route_id' => 1, 'employee_assignment_id' => 1, 'started_at' => '2026-09-11 11:00:00']);
        try { $service->updateSession((string) $second['patrol_session_id'], ['status' => 'COMPLETED', 'completed_at' => '2026-09-11 10:00:00', 'ended_at' => '2026-09-11 10:05:00']); self::fail('Expected timestamp rejection.'); } catch (HttpException $e) { self::assertSame('invalid_patrol_session_timestamps', $e->errorCode()); }
        try { $service->updateSession((string) $second['patrol_session_id'], ['patrol_route_id' => 2]); self::fail('Expected immutable rejection.'); } catch (HttpException $e) { self::assertSame('invalid_patrol_session_update', $e->errorCode()); }
    }
}
