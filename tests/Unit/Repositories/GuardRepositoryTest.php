<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\GuardRepository;

final class GuardRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): GuardRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY, company_id INTEGER, first_name TEXT, last_name TEXT, phone TEXT, email TEXT)');
        $db->execute('INSERT INTO people VALUES (1,101,\'Guard\',\'One\',NULL,NULL),(2,101,\'Psira\',\'Only\',NULL,NULL),(3,202,\'Other\',\'Guard\',NULL,NULL)');
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER, person_id INTEGER, employee_number TEXT, psira_number TEXT, employment_status TEXT)');
        $db->execute('INSERT INTO employees VALUES (1,101,1,\'E1\',\'PSIRA-1\',\'ACTIVE\'),(2,101,2,\'E2\',\'PSIRA-2\',\'ACTIVE\'),(3,202,3,\'E3\',\'PSIRA-3\',\'ACTIVE\')');
        $db->execute('CREATE TABLE positions (position_id INTEGER PRIMARY KEY, company_id INTEGER, position_code TEXT, position_name TEXT, position_category TEXT, position_status TEXT, deleted_at TEXT)');
        $db->execute('INSERT INTO positions VALUES (1,101,\'G\',\'Guard\',\'GUARD\',\'ACTIVE\',NULL),(2,101,\'S\',\'Supervisor\',\'SUPERVISOR\',\'ACTIVE\',NULL),(3,202,\'G2\',\'Guard\',\'GUARD\',\'ACTIVE\',NULL)');
        $db->execute('CREATE TABLE employee_assignments (employee_assignment_id INTEGER PRIMARY KEY, company_id INTEGER, employee_id INTEGER, position_id INTEGER, assignment_code TEXT, assignment_status TEXT, starts_at TEXT, ends_at TEXT, is_primary INTEGER, assignment_notes TEXT, deleted_at TEXT)');
        $db->execute('INSERT INTO employee_assignments (employee_assignment_id,company_id,employee_id,position_id,assignment_code,assignment_status,is_primary,deleted_at) VALUES (1,101,1,1,\'A1\',\'ACTIVE\',1,NULL),(2,101,2,2,\'A2\',\'ACTIVE\',1,NULL),(3,202,3,3,\'B1\',\'ACTIVE\',1,NULL)');
        return new GuardRepository($db, $context);
    }

    public function testOnlyGuardAssignmentsInCurrentCompanyAreListed(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $repository = $this->repository($context);
        $guards = $repository->listForCompany();
        self::assertCount(1, $guards); self::assertSame('1', (string) $guards[0]['employee_id']); self::assertSame('GUARD', $guards[0]['position_category']);
        self::assertTrue($repository->isGuardEmployee('1')); self::assertFalse($repository->isGuardEmployee('2')); self::assertFalse($repository->isGuardEmployee('3'));
    }

    public function testPsiraWithoutGuardPositionDoesNotClassifyEmployee(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $repository = $this->repository($context);
        self::assertNull($repository->findByEmployeeIdForCompany('2'));
    }
}