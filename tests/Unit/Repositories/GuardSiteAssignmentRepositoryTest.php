<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\GuardSiteAssignmentRepository;

final class GuardSiteAssignmentRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): GuardSiteAssignmentRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY, company_id INTEGER, first_name TEXT, last_name TEXT, phone TEXT, email TEXT)');
        $db->execute('INSERT INTO people VALUES (1,101,\'Guard\',\'One\',NULL,NULL),(2,202,\'Other\',\'Guard\',NULL,NULL)');
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER, person_id INTEGER, employee_number TEXT, psira_number TEXT, employment_status TEXT)');
        $db->execute('INSERT INTO employees VALUES (1,101,1,\'E1\',\'PSIRA\',\'ACTIVE\'),(2,202,2,\'E2\',NULL,\'ACTIVE\')');
        $db->execute('CREATE TABLE positions (position_id INTEGER PRIMARY KEY, company_id INTEGER, position_code TEXT, position_name TEXT, position_category TEXT, position_status TEXT, deleted_at TEXT)');
        $db->execute('INSERT INTO positions VALUES (1,101,\'G\',\'Guard\',\'GUARD\',\'ACTIVE\',NULL),(2,202,\'G2\',\'Guard\',\'GUARD\',\'ACTIVE\',NULL)');
        $db->execute('CREATE TABLE employee_assignments (employee_assignment_id INTEGER PRIMARY KEY, company_id INTEGER, employee_id INTEGER, position_id INTEGER, assignment_code TEXT, assignment_status TEXT, starts_at TEXT, ends_at TEXT, is_primary INTEGER, assignment_notes TEXT, deleted_at TEXT)');
        $db->execute('INSERT INTO employee_assignments VALUES (1,101,1,1,\'A1\',\'ACTIVE\',\'2026-01-01 00:00:00\',NULL,1,NULL,NULL),(2,202,2,2,\'B1\',\'ACTIVE\',\'2026-01-01 00:00:00\',NULL,1,NULL,NULL)');
        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY, company_id INTEGER, client_id INTEGER, branch_id INTEGER, site_name TEXT)');
        $db->execute('INSERT INTO sites VALUES (1,101,10,20,\'A Site\'),(2,202,30,40,\'B Site\')');
        $db->execute('CREATE TABLE guard_site_assignments (guard_site_assignment_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, employee_assignment_id INTEGER, site_id INTEGER, deployment_status TEXT NOT NULL, starts_at TEXT NOT NULL, ends_at TEXT, assignment_notes TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        $db->execute('INSERT INTO guard_site_assignments (company_id,employee_assignment_id,site_id,deployment_status,starts_at) VALUES (101,1,1,\'ACTIVE\',\'2026-01-01 00:00:00\'),(202,2,2,\'ACTIVE\',\'2026-01-01 00:00:00\')');
        return new GuardSiteAssignmentRepository($db, $context);
    }

    public function testTenantScopedProjectionAndDependencyChecks(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $repository = $this->repository($context);
        self::assertCount(1, $repository->listForCompany());
        self::assertSame('A Site', $repository->findByIdForCompany('1')['site_name']);
        self::assertNull($repository->findByIdForCompany('2'));
        self::assertTrue($repository->employeeAssignmentIsGuardForCompany('1'));
        self::assertFalse($repository->employeeAssignmentIsGuardForCompany('2'));
        self::assertTrue($repository->siteBelongsToCompany('1'));
        self::assertFalse($repository->siteBelongsToCompany('2'));
    }

    public function testDeletedDeploymentIsExcludedAndCreateUsesRuntimeCompany(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $repository = $this->repository($context);
        self::assertSame(1, count($repository->listForCompany()));
        $created = $repository->createForCompany(['employee_assignment_id'=>1,'site_id'=>1,'starts_at'=>'2026-02-01 00:00:00','company_id'=>202]);
        self::assertSame(101, (int) $created['company_id']);
        self::assertSame('PLANNED', $created['deployment_status']);
    }
}