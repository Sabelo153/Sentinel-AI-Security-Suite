<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\ShiftAssignmentRepository;
use Sentinel\Services\ShiftAssignmentManagementService;

final class ShiftAssignmentManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): ShiftAssignmentManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY, company_id INTEGER, first_name TEXT, last_name TEXT)'); $db->execute('INSERT INTO people VALUES (1,101,\'A\',\'Guard\'),(2,101,\'B\',\'Guard\')');
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER, person_id INTEGER, employee_number TEXT, psira_number TEXT, employment_status TEXT)'); $db->execute('INSERT INTO employees VALUES (1,101,1,\'E1\',NULL,\'ACTIVE\'),(2,101,2,\'E2\',NULL,\'ACTIVE\')');
        $db->execute('CREATE TABLE positions (position_id INTEGER PRIMARY KEY, company_id INTEGER, position_category TEXT, position_status TEXT, deleted_at TEXT)'); $db->execute('INSERT INTO positions VALUES (1,101,\'GUARD\',\'ACTIVE\',NULL),(2,101,\'SUPERVISOR\',\'ACTIVE\',NULL)');
        $db->execute('CREATE TABLE employee_assignments (employee_assignment_id INTEGER PRIMARY KEY, company_id INTEGER, employee_id INTEGER, position_id INTEGER, assignment_status TEXT, deleted_at TEXT)'); $db->execute('INSERT INTO employee_assignments VALUES (1,101,1,1,\'ACTIVE\',NULL),(2,101,2,2,\'ACTIVE\',NULL)');
        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY, company_id INTEGER, site_name TEXT)'); $db->execute('INSERT INTO sites VALUES (1,101,\'A Site\'),(2,202,\'B Site\')');
        $db->execute('CREATE TABLE posts (post_id INTEGER PRIMARY KEY, company_id INTEGER, site_id INTEGER, post_name TEXT, deleted_at TEXT)'); $db->execute('CREATE TABLE contracts (contract_id INTEGER PRIMARY KEY, company_id INTEGER, deleted_at TEXT)');
        $db->execute('CREATE TABLE shift_assignments (shift_assignment_id INTEGER PRIMARY KEY AUTOINCREMENT, shift_assignment_uuid TEXT, company_id INTEGER, employee_id INTEGER, contract_id INTEGER, site_id INTEGER, post_id INTEGER, assignment_date TEXT, shift_name TEXT, shift_start_at TEXT, shift_end_at TEXT, assignment_status TEXT, check_in_window_start TEXT, check_in_window_end TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        return new ShiftAssignmentManagementService(new ShiftAssignmentRepository($db, $context));
    }

    public function testCreateAndOverlapRules(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        $created = $service->createShiftAssignment(['employee_id'=>1,'site_id'=>1,'assignment_date'=>'2026-09-10','shift_name'=>'Day','shift_start_at'=>'2026-09-10 08:00:00','shift_end_at'=>'2026-09-10 16:00:00']); self::assertSame('PLANNED',$created['assignment_status']);
        try { $service->createShiftAssignment(['employee_id'=>1,'site_id'=>1,'assignment_date'=>'2026-09-10','shift_name'=>'Overlap','shift_start_at'=>'2026-09-10 12:00:00','shift_end_at'=>'2026-09-10 18:00:00']); self::fail('Expected overlap.'); } catch(HttpException $exception){self::assertSame('shift_schedule_conflict',$exception->errorCode());}
        $adjacent=$service->createShiftAssignment(['employee_id'=>1,'site_id'=>1,'assignment_date'=>'2026-09-10','shift_name'=>'Evening','shift_start_at'=>'2026-09-10 16:00:00','shift_end_at'=>'2026-09-10 20:00:00']); self::assertSame('Evening',$adjacent['shift_name']);
    }

    public function testGuardSiteAndTimeValidationAreSafe(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        try{$service->createShiftAssignment(['employee_id'=>2,'site_id'=>1,'assignment_date'=>'2026-09-10','shift_name'=>'Bad','shift_start_at'=>'2026-09-10 08:00:00','shift_end_at'=>'2026-09-10 16:00:00']);self::fail('Expected guard rejection.');}catch(HttpException $exception){self::assertSame('guard_not_found',$exception->errorCode());}
        try{$service->createShiftAssignment(['employee_id'=>1,'site_id'=>2,'assignment_date'=>'2026-09-10','shift_name'=>'Bad','shift_start_at'=>'2026-09-10 08:00:00','shift_end_at'=>'2026-09-10 16:00:00']);self::fail('Expected site rejection.');}catch(HttpException $exception){self::assertSame('site_not_found',$exception->errorCode());}
        try{$service->createShiftAssignment(['employee_id'=>1,'site_id'=>1,'assignment_date'=>'2026-09-10','shift_name'=>'Bad','shift_start_at'=>'2026-09-10 16:00:00','shift_end_at'=>'2026-09-10 08:00:00']);self::fail('Expected time rejection.');}catch(HttpException $exception){self::assertSame('invalid_shift_time',$exception->errorCode());}
    }
}