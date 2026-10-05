<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\AttendanceRepository;
use Sentinel\Services\AttendanceManagementService;

final class AttendanceManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): AttendanceManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY, company_id INTEGER, first_name TEXT, last_name TEXT)');
        $db->execute('INSERT INTO people VALUES (1,101,\'A\',\'Guard\'),(2,202,\'B\',\'Guard\')');

        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER, person_id INTEGER, employee_number TEXT, employment_status TEXT)');
        $db->execute('INSERT INTO employees VALUES (1,101,1,\'E1\',\'ACTIVE\'),(2,202,2,\'E2\',\'ACTIVE\')');

        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY, company_id INTEGER, site_name TEXT)');
        $db->execute('INSERT INTO sites VALUES (1,101,\'A Site\'),(2,202,\'B Site\')');

        $db->execute('CREATE TABLE shift_assignments (shift_assignment_id INTEGER PRIMARY KEY, company_id INTEGER, employee_id INTEGER, site_id INTEGER, shift_name TEXT, shift_start_at TEXT, shift_end_at TEXT, deleted_at TEXT)');
        $db->execute('INSERT INTO shift_assignments VALUES (1,101,1,1,\'Day\',\'2026-09-10 08:00:00\',\'2026-09-10 16:00:00\',NULL),(2,101,1,1,\'Night\',\'2026-09-11 20:00:00\',\'2026-09-12 04:00:00\',NULL),(3,202,2,2,\'Other\',\'2026-09-10 08:00:00\',\'2026-09-10 16:00:00\',NULL)');

        $db->execute('CREATE TABLE attendance (attendance_id INTEGER PRIMARY KEY AUTOINCREMENT, attendance_ref TEXT, company_id INTEGER, employee_id INTEGER, shift_assignment_id INTEGER, contract_id INTEGER, site_id INTEGER, post_id INTEGER, shift_date TEXT, clock_in_at TEXT, clock_out_at TEXT, attendance_status TEXT, source TEXT, notes TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, UNIQUE(employee_id,shift_date))');
        $db->execute('INSERT INTO attendance (attendance_id,attendance_ref,company_id,employee_id,shift_assignment_id,site_id,shift_date,clock_in_at,clock_out_at,attendance_status,source,deleted_at) VALUES (1,\'ATT1\',101,1,1,1,\'2026-09-10\',\'2026-09-10 08:00:00\',\'2026-09-10 16:00:00\',\'PRESENT\',\'API\',NULL),(2,\'ATT2\',202,2,3,2,\'2026-09-10\',\'2026-09-10 08:00:00\',NULL,\'PRESENT\',\'API\',NULL)');

        return new AttendanceManagementService(new AttendanceRepository($db, $context));
    }

    public function testCreateValidAttendanceAndStatusSet(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        $dates = [
            'PRESENT' => '2026-09-11',
            'LATE' => '2026-09-12',
            'ABSENT' => '2026-09-13',
            'OFF_DUTY' => '2026-09-14',
        ];

        foreach ($dates as $status => $shiftDate) {
            $created = $service->createAttendance([
                'employee_id' => 1,
                'shift_assignment_id' => 2,
                'site_id' => 1,
                'shift_date' => $shiftDate,
                'clock_in_at' => '2026-09-11 20:00:00',
                'clock_out_at' => '2026-09-12 04:00:00',
                'attendance_status' => $status,
                'source' => 'MANUAL',
            ]);

            self::assertSame($status, $created['attendance_status']);
        }

        try {
            $service->createAttendance([
                'employee_id' => 1,
                'shift_assignment_id' => 2,
                'site_id' => 1,
                'shift_date' => '2026-09-11',
            ]);
            self::fail('Expected duplicate attendance conflict.');
        } catch (HttpException $exception) {
            self::assertSame(409, $exception->statusCode());
            self::assertSame('attendance_conflict', $exception->errorCode());
        }
    }

    public function testRejectsInvalidStatusAndInvalidClockOrdering(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        try {
            $service->createAttendance([
                'employee_id' => 1,
                'shift_date' => '2026-09-11',
                'attendance_status' => 'COMPLETED',
            ]);
            self::fail('Expected invalid status error.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->statusCode());
            self::assertSame('invalid_attendance_status', $exception->errorCode());
        }

        try {
            $service->createAttendance([
                'employee_id' => 1,
                'shift_date' => '2026-09-11',
                'clock_in_at' => '2026-09-11 18:00:00',
                'clock_out_at' => '2026-09-11 08:00:00',
            ]);
            self::fail('Expected invalid clock ordering.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->statusCode());
            self::assertSame('invalid_attendance_timestamps', $exception->errorCode());
        }
    }

    public function testRejectsInvalidShiftAndSiteRelationships(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        try {
            $service->createAttendance([
                'employee_id' => 1,
                'shift_assignment_id' => 999,
                'site_id' => 1,
                'shift_date' => '2026-09-11',
            ]);
            self::fail('Expected shift not found.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->statusCode());
            self::assertSame('shift_assignment_not_found', $exception->errorCode());
        }

        try {
            $service->createAttendance([
                'employee_id' => 1,
                'shift_assignment_id' => 2,
                'site_id' => 2,
                'shift_date' => '2026-09-11',
            ]);
            self::fail('Expected site not found for tenant.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->statusCode());
            self::assertSame('site_not_found', $exception->errorCode());
        }

        try {
            $service->createAttendance([
                'employee_id' => 1,
                'shift_assignment_id' => 2,
                'site_id' => 1,
                'shift_date' => '2026-09-11',
            ]);
        } catch (HttpException) {
            self::fail('Expected valid site/shift relationship.');
        }
    }

    public function testTenantHidesCrossCompanyEmployeeAndShift(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        try {
            $service->createAttendance([
                'employee_id' => 2,
                'shift_date' => '2026-09-11',
            ]);
            self::fail('Expected employee_not_found.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->statusCode());
            self::assertSame('employee_not_found', $exception->errorCode());
        }

        try {
            $service->createAttendance([
                'employee_id' => 1,
                'shift_assignment_id' => 3,
                'site_id' => 1,
                'shift_date' => '2026-09-11',
            ]);
            self::fail('Expected shift_assignment_not_found.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->statusCode());
            self::assertSame('shift_assignment_not_found', $exception->errorCode());
        }
    }

    public function testUpdateUsesEffectiveValidationAndProtectsImmutableFields(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        try {
            $service->updateAttendance('1', ['clock_in_at' => '2026-09-10 18:00:00']);
            self::fail('Expected effective validation rejection.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->statusCode());
            self::assertTrue(in_array($exception->errorCode(), ['invalid_attendance_timestamps', 'attendance_already_clocked_in'], true));
        }

        $updated = $service->updateAttendance('1', [
            'company_id' => 202,
            'attendance_id' => 999,
            'notes' => 'Correction note',
            'clock_out_at' => '2026-09-10 16:10:00',
            'attendance_status' => 'LATE',
        ]);

        self::assertSame('101', (string) $updated['company_id']);
        self::assertSame('1', (string) $updated['attendance_id']);
        self::assertSame('Correction note', (string) $updated['notes']);
        self::assertSame('LATE', (string) $updated['attendance_status']);
    }

    public function testCrossCompanyAttendanceIsHiddenOnReadAndUpdate(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        try {
            $service->getAttendance('2');
            self::fail('Expected hidden cross-company attendance.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->statusCode());
            self::assertSame('attendance_not_found', $exception->errorCode());
        }

        try {
            $service->updateAttendance('2', ['notes' => 'x']);
            self::fail('Expected hidden cross-company attendance on update.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->statusCode());
            self::assertSame('attendance_not_found', $exception->errorCode());
        }
    }
}