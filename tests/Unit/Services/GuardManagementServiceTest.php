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
use Sentinel\Repositories\GuardRepository;
use Sentinel\Repositories\PositionRepository;
use Sentinel\Services\EmployeeAssignmentManagementService;
use Sentinel\Services\GuardManagementService;

final class GuardManagementServiceTest extends TestCase
{
    /** @return array{0: GuardManagementService, 1: Database} */
    private function service(RuntimeContext $context): array
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY, company_id INTEGER, first_name TEXT, last_name TEXT, phone TEXT, email TEXT)'); $db->execute('INSERT INTO people VALUES (1,101,\'Guard\',\'One\',NULL,NULL),(2,101,\'Other\',\'Two\',NULL,NULL)');
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER, person_id INTEGER, employee_number TEXT, psira_number TEXT, employment_status TEXT)'); $db->execute('INSERT INTO employees VALUES (1,101,1,\'E1\',NULL,\'ACTIVE\'),(2,101,2,\'E2\',NULL,\'ACTIVE\')');
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, company_id INTEGER)'); $db->execute('INSERT INTO users VALUES (1,101)');
        $db->execute('CREATE TABLE organizations (organization_id INTEGER PRIMARY KEY, company_id INTEGER, deleted_at TEXT)'); $db->execute('INSERT INTO organizations VALUES (1,101,NULL)');
        $db->execute('CREATE TABLE organizational_units (organizational_unit_id INTEGER PRIMARY KEY, company_id INTEGER, organization_id INTEGER, deleted_at TEXT)'); $db->execute('INSERT INTO organizational_units VALUES (1,101,1,NULL)');
        $db->execute('CREATE TABLE positions (position_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, organization_id INTEGER, organizational_unit_id INTEGER, position_code TEXT, position_name TEXT, position_category TEXT, hierarchy_rank INTEGER, position_status TEXT, description TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)'); $db->execute('INSERT INTO positions (company_id,position_code,position_name,position_category,position_status) VALUES (101,\'G\',\'Guard\',\'GUARD\',\'ACTIVE\'),(101,\'S\',\'Supervisor\',\'SUPERVISOR\',\'ACTIVE\')');
        $db->execute('CREATE TABLE authority_levels (authority_level_id INTEGER PRIMARY KEY, authority_status TEXT)'); $db->execute('INSERT INTO authority_levels VALUES (1,\'ACTIVE\')');
        $db->execute('CREATE TABLE employee_assignments (employee_assignment_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, employee_id INTEGER, user_id INTEGER, organization_id INTEGER, organizational_unit_id INTEGER, position_id INTEGER, authority_level_id INTEGER, assignment_code TEXT UNIQUE, assignment_status TEXT, starts_at TEXT, ends_at TEXT, is_primary INTEGER, assigned_by_user_id INTEGER, assignment_notes TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        $repository = new EmployeeAssignmentRepository($db, $context);
        return [new GuardManagementService(new GuardRepository($db, $context), new EmployeeAssignmentManagementService($repository), new PositionRepository($db, $context)), $db];
    }

    public function testRegisterUsesExistingEmployeeAndGuardPosition(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); [$service] = $this->service($context);
        $guard = $service->registerGuard(['employee_id'=>1,'position_id'=>1,'authority_level_id'=>1,'assignment_code'=>'G-1']);
        self::assertSame('1', (string) $guard['employee_id']); self::assertSame('GUARD', $guard['position_category']);
    }

    public function testNonGuardPositionIsRejectedAndNonGuardEmployeeIsHidden(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); [$service] = $this->service($context);
        try { $service->registerGuard(['employee_id'=>1,'position_id'=>2,'authority_level_id'=>1,'assignment_code'=>'S-1']); self::fail('Expected invalid guard position.'); }
        catch (HttpException $exception) { self::assertSame(422, $exception->statusCode()); self::assertSame('invalid_guard_position', $exception->errorCode()); }
        try { $service->getGuard('2'); self::fail('Expected guard not found.'); }
        catch (HttpException $exception) { self::assertSame(404, $exception->statusCode()); self::assertSame('guard_not_found', $exception->errorCode()); }
    }

    public function testGuardAssignmentUpdateAllowsOnlySafeFields(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); [$service] = $this->service($context);
        $service->registerGuard(['employee_id'=>1,'position_id'=>1,'authority_level_id'=>1,'assignment_code'=>'G-2']);
        $updated = $service->updateGuardAssignment('1', ['assignment_notes'=>'updated']);
        self::assertSame('updated', $updated['assignment_notes']);
        try { $service->updateGuardAssignment('1', ['employee_id'=>2]); self::fail('Expected immutable guard update rejection.'); }
        catch (HttpException $exception) { self::assertSame(422, $exception->statusCode()); }
    }
}