<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\EmployeeRepository;

final class EmployeeRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): EmployeeRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)');
        $db->execute('INSERT INTO people (person_id, company_id) VALUES (1, 101), (2, 202), (3, 101)');
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, person_id INTEGER NOT NULL, employee_number TEXT, psira_number TEXT, hire_date TEXT, employment_status TEXT, created_at TEXT, updated_at TEXT)');
        $db->execute('INSERT INTO employees (company_id, person_id, employee_number, employment_status) VALUES (101, 1, \'A-1\', \'ACTIVE\'), (202, 2, \'B-1\', \'ACTIVE\')');

        return new EmployeeRepository($db, $context);
    }

    public function testListAndFindAreScopedToRuntimeCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertCount(1, $repository->listForCompany());
        self::assertSame('A-1', $repository->findByIdForCompany('1')['employee_number']);
        self::assertNull($repository->findByIdForCompany('2'));
    }

    public function testCreateUsesRuntimeCompanyAndPersonCompanyValidation(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertTrue($repository->personBelongsToCompany(3));
        self::assertFalse($repository->personBelongsToCompany(2));
        self::assertFalse($repository->personBelongsToCompany(999));

        $created = $repository->createForCompany([
            'person_id' => 3,
            'employee_number' => 'A-2',
            'company_id' => 202,
        ]);

        self::assertSame(101, (int) $created['company_id']);
        self::assertSame(3, (int) $created['person_id']);
    }

    public function testUpdateCannotCrossCompanyBoundary(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertNull($repository->updateForCompany('2', ['employee_number' => 'HIJACKED']));
        self::assertSame('A-Updated', $repository->updateForCompany('1', ['employee_number' => 'A-Updated'])['employee_number']);
    }
}