<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PatrolSessionRepository;

final class PatrolSessionRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): PatrolSessionRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY, company_id INTEGER, site_name TEXT)');
        $db->execute("INSERT INTO sites VALUES (1,101,'A Site'),(2,202,'B Site')");
        $db->execute('CREATE TABLE patrol_routes (patrol_route_id INTEGER PRIMARY KEY, company_id INTEGER, site_id INTEGER, route_name TEXT, route_code TEXT, status TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO patrol_routes VALUES (1,101,1,'A Route','A','ACTIVE',NULL),(2,202,2,'B Route','B','ACTIVE',NULL)");
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY, company_id INTEGER, first_name TEXT, last_name TEXT)');
        $db->execute("INSERT INTO people VALUES (1,101,'Ada','Guard'),(2,202,'Ben','Guard')");
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER, person_id INTEGER, employee_number TEXT, employment_status TEXT)');
        $db->execute("INSERT INTO employees VALUES (1,101,1,'A1','ACTIVE'),(2,202,2,'B1','ACTIVE')");
        $db->execute('CREATE TABLE positions (position_id INTEGER PRIMARY KEY, company_id INTEGER, position_name TEXT, position_category TEXT, position_status TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO positions VALUES (1,101,'Guard','GUARD','ACTIVE',NULL),(2,202,'Guard','GUARD','ACTIVE',NULL)");
        $db->execute('CREATE TABLE employee_assignments (employee_assignment_id INTEGER PRIMARY KEY, company_id INTEGER, employee_id INTEGER, position_id INTEGER, assignment_status TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO employee_assignments VALUES (1,101,1,1,'ACTIVE',NULL),(2,202,2,2,'ACTIVE',NULL)");
        $db->execute('CREATE TABLE shift_assignments (shift_assignment_id INTEGER PRIMARY KEY, company_id INTEGER, employee_id INTEGER, site_id INTEGER, shift_name TEXT, assignment_date TEXT, shift_start_at TEXT, shift_end_at TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO shift_assignments VALUES (1,101,1,1,'Day','2026-09-11','2026-09-11 08:00:00','2026-09-11 16:00:00',NULL)");
        $db->execute('CREATE TABLE patrol_sessions (patrol_session_id INTEGER PRIMARY KEY AUTOINCREMENT, patrol_session_uuid TEXT UNIQUE, company_id INTEGER, patrol_route_id INTEGER, employee_assignment_id INTEGER, shift_assignment_id INTEGER, status TEXT, started_at TEXT, completed_at TEXT, ended_at TEXT, execution_notes TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO patrol_sessions VALUES (1,'session-a',101,1,1,NULL,'IN_PROGRESS','2026-09-11 08:00:00',NULL,NULL,'A','2026-09-11','2026-09-11',NULL),(2,'session-b',202,2,2,NULL,'COMPLETED','2026-09-11 08:00:00','2026-09-11 09:00:00','2026-09-11 09:00:00','B','2026-09-11','2026-09-11',NULL),(3,'session-deleted',101,1,1,NULL,'IN_PROGRESS','2026-09-11 08:00:00',NULL,NULL,'X','2026-09-11','2026-09-11','2026-09-11')");

        return new PatrolSessionRepository($db, $context);
    }

    public function testTenantReadsAndSoftDelete(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);
        self::assertCount(1, $repository->listForCompany());
        self::assertSame('session-a', $repository->findByIdForCompany('1')['patrol_session_uuid']);
        self::assertNull($repository->findByIdForCompany('2'));
        self::assertNull($repository->findByIdForCompany('3'));
    }

    public function testCreateUpdateAndActiveConflictAreTenantScoped(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);
        self::assertTrue($repository->activeSessionExistsForEmployeeAssignment('1'));
        self::assertFalse($repository->activeSessionExistsForEmployeeAssignment('1', '1'));
        self::assertFalse($repository->activeSessionExistsForEmployeeAssignment('2'));

        $created = $repository->createForCompany(['company_id' => 202, 'patrol_route_id' => 1, 'employee_assignment_id' => 1, 'started_at' => '2026-09-11 10:00:00', 'execution_notes' => 'new']);
        self::assertSame('101', (string) $created['company_id']);
        self::assertNotEmpty($created['patrol_session_uuid']);
        $updated = $repository->updateForCompany((string) $created['patrol_session_id'], ['status' => 'COMPLETED', 'completed_at' => '2026-09-11 11:00:00', 'ended_at' => '2026-09-11 11:00:00']);
        self::assertSame('COMPLETED', $updated['status']);
        self::assertNull($repository->updateForCompany('2', ['execution_notes' => 'no access']));
    }
}
