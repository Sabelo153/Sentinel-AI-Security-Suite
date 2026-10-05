<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\ShiftAssignmentRepository;

final class ShiftAssignmentRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): ShiftAssignmentRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY, company_id INTEGER, first_name TEXT, last_name TEXT)'); $db->execute('INSERT INTO people VALUES (1,101,\'A\',\'Guard\'),(2,202,\'B\',\'Guard\')');
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER, person_id INTEGER, employee_number TEXT, psira_number TEXT, employment_status TEXT)'); $db->execute('INSERT INTO employees VALUES (1,101,1,\'E1\',NULL,\'ACTIVE\'),(2,202,2,\'E2\',NULL,\'ACTIVE\')');
        $db->execute('CREATE TABLE positions (position_id INTEGER PRIMARY KEY, company_id INTEGER, position_category TEXT, position_status TEXT, deleted_at TEXT)'); $db->execute('INSERT INTO positions VALUES (1,101,\'GUARD\',\'ACTIVE\',NULL),(2,202,\'GUARD\',\'ACTIVE\',NULL)');
        $db->execute('CREATE TABLE employee_assignments (employee_assignment_id INTEGER PRIMARY KEY, company_id INTEGER, employee_id INTEGER, position_id INTEGER, assignment_status TEXT, deleted_at TEXT)'); $db->execute('INSERT INTO employee_assignments VALUES (1,101,1,1,\'ACTIVE\',NULL),(2,202,2,2,\'ACTIVE\',NULL)');
        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY, company_id INTEGER, site_name TEXT)'); $db->execute('INSERT INTO sites VALUES (1,101,\'A Site\'),(2,202,\'B Site\')');
        $db->execute('CREATE TABLE posts (post_id INTEGER PRIMARY KEY, company_id INTEGER, site_id INTEGER, post_name TEXT, deleted_at TEXT)');
        $db->execute('CREATE TABLE contracts (contract_id INTEGER PRIMARY KEY, company_id INTEGER, deleted_at TEXT)');
        $db->execute('CREATE TABLE shift_assignments (shift_assignment_id INTEGER PRIMARY KEY AUTOINCREMENT, shift_assignment_uuid TEXT, company_id INTEGER, employee_id INTEGER, contract_id INTEGER, site_id INTEGER, post_id INTEGER, assignment_date TEXT, shift_name TEXT, shift_start_at TEXT, shift_end_at TEXT, assignment_status TEXT, check_in_window_start TEXT, check_in_window_end TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        $db->execute('INSERT INTO shift_assignments (shift_assignment_uuid,company_id,employee_id,site_id,assignment_date,shift_name,shift_start_at,shift_end_at,assignment_status) VALUES (\'A\',101,1,1,\'2026-09-10\',\'Day\',\'2026-09-10 08:00:00\',\'2026-09-10 16:00:00\',\'PLANNED\'),(\'B\',202,2,2,\'2026-09-10\',\'Day\',\'2026-09-10 08:00:00\',\'2026-09-10 16:00:00\',\'PLANNED\')');
        return new ShiftAssignmentRepository($db, $context);
    }

    public function testListFindAndGuardChecksAreTenantScoped(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $repository = $this->repository($context);
        self::assertCount(1, $repository->listForCompany()); self::assertSame('Day', $repository->findByIdForCompany('1')['shift_name']); self::assertNull($repository->findByIdForCompany('2')); self::assertTrue($repository->employeeIsGuard('1')); self::assertFalse($repository->employeeIsGuard('2')); self::assertTrue($repository->siteBelongsToCompany('1')); self::assertFalse($repository->siteBelongsToCompany('2'));
    }
}