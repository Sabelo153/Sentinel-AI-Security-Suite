<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\EmployeeAssignmentRepository;
use Sentinel\Services\EmployeeAssignmentManagementService;

final class EmployeeAssignmentManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): EmployeeAssignmentManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)'); $db->execute('INSERT INTO employees VALUES (1,101),(2,202)');
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)'); $db->execute('INSERT INTO users VALUES (1,101),(2,202)');
        $db->execute('CREATE TABLE organizations (organization_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, deleted_at TEXT)'); $db->execute('INSERT INTO organizations VALUES (1,101,NULL),(2,202,NULL)');
        $db->execute('CREATE TABLE organizational_units (organizational_unit_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, organization_id INTEGER NOT NULL, deleted_at TEXT)'); $db->execute('INSERT INTO organizational_units VALUES (1,101,1,NULL),(2,202,2,NULL)');
        $db->execute('CREATE TABLE positions (position_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, organization_id INTEGER, organizational_unit_id INTEGER, position_category TEXT, deleted_at TEXT)'); $db->execute('INSERT INTO positions VALUES (1,101,1,1,\'GUARD\',NULL),(2,202,2,2,\'GUARD\',NULL)');
        $db->execute('CREATE TABLE authority_levels (authority_level_id INTEGER PRIMARY KEY, authority_status TEXT NOT NULL)'); $db->execute('INSERT INTO authority_levels VALUES (1,\'ACTIVE\')');
        $db->execute('CREATE TABLE employee_assignments (employee_assignment_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, employee_id INTEGER NOT NULL, user_id INTEGER, organization_id INTEGER, organizational_unit_id INTEGER, position_id INTEGER NOT NULL, authority_level_id INTEGER NOT NULL, assignment_code TEXT NOT NULL, assignment_status TEXT NOT NULL, starts_at TEXT, ends_at TEXT, is_primary INTEGER, assigned_by_user_id INTEGER, assignment_notes TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, UNIQUE(assignment_code))');
        return new EmployeeAssignmentManagementService(new EmployeeAssignmentRepository($db, $context));
    }

    public function testCreateAcceptsSameCompanyChainAndIgnoresCompanyOverride(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101);
        $assignment = $this->service($context)->createAssignment(['employee_id'=>1,'position_id'=>1,'authority_level_id'=>1,'assignment_code'=>'A-NEW','organization_id'=>1,'organizational_unit_id'=>1,'company_id'=>202]);
        self::assertSame(101, (int) $assignment['company_id']);
    }

    public function testCrossCompanyAndMissingRequiredReferencesAreSafe(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        foreach ([2,999] as $employeeId) {
            try { $service->createAssignment(['employee_id'=>$employeeId,'position_id'=>1,'authority_level_id'=>1,'assignment_code'=>'E-'.$employeeId]); self::fail('Expected employee error.'); }
            catch (HttpException $exception) { self::assertSame(404, $exception->statusCode()); self::assertSame('employee_not_found', $exception->errorCode()); }
        }
        foreach ([2,999] as $positionId) {
            try { $service->createAssignment(['employee_id'=>1,'position_id'=>$positionId,'authority_level_id'=>1,'assignment_code'=>'P-'.$positionId]); self::fail('Expected position error.'); }
            catch (HttpException $exception) { self::assertSame(404, $exception->statusCode()); self::assertSame('position_not_found', $exception->errorCode()); }
        }
    }

    public function testConsistencyAndAuthorityValidationAreSafe(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        try { $service->createAssignment(['employee_id'=>1,'position_id'=>1,'authority_level_id'=>1,'assignment_code'=>'M','organization_id'=>1,'organizational_unit_id'=>2]); self::fail('Expected relationship error.'); }
        catch (HttpException $exception) { self::assertSame(404, $exception->statusCode()); }
        try { $service->createAssignment(['employee_id'=>1,'position_id'=>1,'authority_level_id'=>2,'assignment_code'=>'AUTH']); self::fail('Expected authority error.'); }
        catch (HttpException $exception) { self::assertSame(404, $exception->statusCode()); self::assertSame('authority_level_not_found', $exception->errorCode()); }
        try { $service->createAssignment(['employee_id'=>1,'position_id'=>1,'authority_level_id'=>1,'assignment_code'=>'BAD','organization_id'=>1,'organizational_unit_id'=>999]); self::fail('Expected unit error.'); }
        catch (HttpException $exception) { self::assertSame('organizational_unit_not_found', $exception->errorCode()); }
    }
}