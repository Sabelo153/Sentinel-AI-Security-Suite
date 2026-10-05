<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\DepartmentRepository;

final class DepartmentRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): DepartmentRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE clients (client_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)');
        $db->execute('INSERT INTO clients (client_id, company_id) VALUES (1, 101), (2, 202)');
        $db->execute('CREATE TABLE branches (branch_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)');
        $db->execute('INSERT INTO branches (branch_id, company_id) VALUES (1, 101), (2, 202)');
        $db->execute('CREATE TABLE departments (department_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, client_id INTEGER, branch_id INTEGER, department_name TEXT NOT NULL, department_code TEXT, status TEXT NOT NULL, created_at TEXT, updated_at TEXT, UNIQUE(company_id, department_name), UNIQUE(company_id, department_code))');
        $db->execute('INSERT INTO departments (company_id, client_id, branch_id, department_name, status) VALUES (101, 1, 1, \'A Department\', \'ACTIVE\'), (202, 2, 2, \'B Department\', \'ACTIVE\')');

        return new DepartmentRepository($db, $context);
    }

    public function testListAndFindAreScopedToRuntimeCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertCount(1, $repository->listForCompany());
        self::assertSame('A Department', $repository->findByIdForCompany('1')['department_name']);
        self::assertNull($repository->findByIdForCompany('2'));
    }

    public function testForeignKeysAndCreateUseRuntimeCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertTrue($repository->clientBelongsToCompany(1));
        self::assertFalse($repository->clientBelongsToCompany(2));
        self::assertTrue($repository->branchBelongsToCompany(1));
        self::assertFalse($repository->branchBelongsToCompany(2));

        $created = $repository->createForCompany(['client_id' => 1, 'branch_id' => 1, 'department_name' => 'New Department', 'company_id' => 202]);
        self::assertSame(101, (int) $created['company_id']);
        self::assertNull($repository->updateForCompany('2', ['department_name' => 'Hijacked']));
        self::assertSame('Updated', $repository->updateForCompany('1', ['department_name' => 'Updated'])['department_name']);
    }
}