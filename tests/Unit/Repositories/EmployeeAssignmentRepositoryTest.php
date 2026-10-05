<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\EmployeeAssignmentRepository;

final class EmployeeAssignmentRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): EmployeeAssignmentRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)'); $db->execute('INSERT INTO employees VALUES (1,101),(2,202)');
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)'); $db->execute('INSERT INTO users VALUES (1,101),(2,202)');
        $db->execute('CREATE TABLE organizations (organization_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, deleted_at TEXT)'); $db->execute('INSERT INTO organizations VALUES (1,101,NULL),(2,202,NULL)');
        $db->execute('CREATE TABLE organizational_units (organizational_unit_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, organization_id INTEGER NOT NULL, deleted_at TEXT)'); $db->execute('INSERT INTO organizational_units VALUES (1,101,1,NULL),(2,202,2,NULL)');
        $db->execute('CREATE TABLE positions (position_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, organization_id INTEGER, organizational_unit_id INTEGER, position_category TEXT, deleted_at TEXT)'); $db->execute('INSERT INTO positions VALUES (1,101,1,1,\'GUARD\',NULL),(2,202,2,2,\'GUARD\',NULL)');
        $db->execute('CREATE TABLE authority_levels (authority_level_id INTEGER PRIMARY KEY, authority_status TEXT NOT NULL)'); $db->execute('INSERT INTO authority_levels VALUES (1,\'ACTIVE\'),(2,\'INACTIVE\')');
        $db->execute('CREATE TABLE employee_assignments (employee_assignment_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, employee_id INTEGER NOT NULL, user_id INTEGER, organization_id INTEGER, organizational_unit_id INTEGER, position_id INTEGER NOT NULL, authority_level_id INTEGER NOT NULL, assignment_code TEXT NOT NULL, assignment_status TEXT NOT NULL, starts_at TEXT, ends_at TEXT, is_primary INTEGER, assigned_by_user_id INTEGER, assignment_notes TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, UNIQUE(assignment_code))');
        $db->execute('INSERT INTO employee_assignments (company_id,employee_id,position_id,authority_level_id,assignment_code,assignment_status) VALUES (101,1,1,1,\'A-1\',\'ACTIVE\'),(202,2,2,1,\'B-1\',\'ACTIVE\')');
        return new EmployeeAssignmentRepository($db, $context);
    }

    public function testAssignmentQueriesAreTenantScopedAndGuardCategoryIsDetectable(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $repository = $this->repository($context);
        self::assertCount(1, $repository->listForCompany());
        self::assertSame('A-1', $repository->findByIdForCompany('1')['assignment_code']);
        self::assertNull($repository->findByIdForCompany('2'));
        self::assertTrue($repository->assignmentHasPositionCategory('1', 'GUARD'));
        self::assertFalse($repository->assignmentHasPositionCategory('1', 'SUPERVISOR'));
    }

    public function testRequiredAndOptionalReferencesUseTenantAndGlobalAuthorityRules(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $repository = $this->repository($context);
        self::assertTrue($repository->employeeBelongsToCompany(1)); self::assertFalse($repository->employeeBelongsToCompany(2));
        self::assertTrue($repository->positionBelongsToCompany(1)); self::assertFalse($repository->positionBelongsToCompany(2));
        self::assertTrue($repository->authorityLevelIsUsable(1)); self::assertFalse($repository->authorityLevelIsUsable(2));
        self::assertTrue($repository->userBelongsToCompany(1)); self::assertFalse($repository->userBelongsToCompany(2));
        self::assertTrue($repository->unitBelongsToOrganization(1, 1));
    }
}