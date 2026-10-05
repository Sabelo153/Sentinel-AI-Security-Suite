<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\AttendanceRepository;

final class AttendanceRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): AttendanceRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY, company_id INTEGER, first_name TEXT, last_name TEXT)');
        $db->execute('INSERT INTO people VALUES (1,101,\'A\',\'Guard\'),(2,202,\'B\',\'Guard\')');

        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER, person_id INTEGER, employee_number TEXT, employment_status TEXT)');
        $db->execute('INSERT INTO employees VALUES (1,101,1,\'E1\',\'ACTIVE\'),(2,202,2,\'E2\',\'ACTIVE\')');

        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY, company_id INTEGER, site_name TEXT)');
        $db->execute('INSERT INTO sites VALUES (1,101,\'A Site\'),(2,202,\'B Site\')');

        $db->execute('CREATE TABLE shift_assignments (shift_assignment_id INTEGER PRIMARY KEY, company_id INTEGER, employee_id INTEGER, site_id INTEGER, shift_name TEXT, shift_start_at TEXT, shift_end_at TEXT, deleted_at TEXT)');
        $db->execute('INSERT INTO shift_assignments VALUES (1,101,1,1,\'Day\',\'2026-09-10 08:00:00\',\'2026-09-10 16:00:00\',NULL),(2,202,2,2,\'Night\',\'2026-09-10 20:00:00\',\'2026-09-11 04:00:00\',NULL)');

        $db->execute('CREATE TABLE attendance (attendance_id INTEGER PRIMARY KEY AUTOINCREMENT, attendance_ref TEXT, company_id INTEGER, employee_id INTEGER, shift_assignment_id INTEGER, contract_id INTEGER, site_id INTEGER, post_id INTEGER, shift_date TEXT, clock_in_at TEXT, clock_out_at TEXT, attendance_status TEXT, source TEXT, notes TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, UNIQUE(employee_id,shift_date))');
        $db->execute('INSERT INTO attendance (attendance_id,attendance_ref,company_id,employee_id,shift_assignment_id,site_id,shift_date,clock_in_at,clock_out_at,attendance_status,source,notes,deleted_at) VALUES (1,\'ATT1\',101,1,1,1,\'2026-09-10\',\'2026-09-10 08:01:00\',\'2026-09-10 16:00:00\',\'PRESENT\',\'API\',NULL,NULL),(2,\'ATT2\',202,2,2,2,\'2026-09-10\',\'2026-09-10 20:00:00\',NULL,\'PRESENT\',\'API\',NULL,NULL),(3,\'ATT3\',101,1,1,1,\'2026-09-09\',NULL,NULL,\'ABSENT\',\'MANUAL\',NULL,\'2026-09-09 23:00:00\')');

        return new AttendanceRepository($db,$context);
    }

    public function testListAndFindAreTenantScopedAndExcludeSoftDeleted(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        $rows = $repository->listForCompany();
        self::assertCount(1, $rows);
        self::assertSame('1', (string) $rows[0]['attendance_id']);
        self::assertNull($repository->findByIdForCompany('2'));
        self::assertNull($repository->findByIdForCompany('3'));
        self::assertNull($repository->findByIdForCompany('999'));
    }

    public function testCreateUsesRuntimeTenantAndIgnoresCallerCompanyOverride(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        $created = $repository->createForCompany([
            'company_id' => 202,
            'employee_id' => 1,
            'shift_assignment_id' => 1,
            'site_id' => 1,
            'shift_date' => '2026-09-11',
            'clock_in_at' => '2026-09-11 08:00:00',
            'attendance_status' => 'PRESENT',
            'source' => 'MOBILE_APP',
            'notes' => 'On time',
        ]);

        self::assertSame('101', (string) $created['company_id']);
        self::assertSame('1', (string) $created['employee_id']);
        self::assertSame('2026-09-11', (string) $created['shift_date']);
    }

    public function testUpdateIsTenantScopedAndCrossCompanyIsHidden(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        $updated = $repository->updateForCompany('1', [
            'clock_out_at' => '2026-09-10 16:05:00',
            'attendance_status' => 'PRESENT',
            'notes' => 'Late sign-out',
        ]);

        self::assertNotNull($updated);
        self::assertSame('2026-09-10 16:05:00', (string) $updated['clock_out_at']);
        self::assertSame('Late sign-out', (string) $updated['notes']);
        self::assertNull($repository->updateForCompany('2', ['notes' => 'forbidden']));
    }

    public function testFkHelperChecksAreTenantScoped(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertTrue($repository->employeeBelongsToCompany('1'));
        self::assertFalse($repository->employeeBelongsToCompany('2'));
        self::assertTrue($repository->siteBelongsToCompany('1'));
        self::assertFalse($repository->siteBelongsToCompany('2'));
        self::assertTrue($repository->shiftBelongsToCompany('1'));
        self::assertFalse($repository->shiftBelongsToCompany('2'));
    }

    public function testDuplicateCheckUsesEmployeeShiftDateAndSupportsExcludeId(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertTrue($repository->attendanceExistsForEmployeeDate('1', '2026-09-10'));
        self::assertFalse($repository->attendanceExistsForEmployeeDate('1', '2026-09-10', '1'));
        self::assertFalse($repository->attendanceExistsForEmployeeDate('1', '2026-09-12'));
    }
}