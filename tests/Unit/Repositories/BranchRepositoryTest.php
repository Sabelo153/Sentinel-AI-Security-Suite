<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\BranchRepository;

final class BranchRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): BranchRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE clients (client_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, client_name TEXT NOT NULL)');
        $db->execute('INSERT INTO clients (client_id, company_id, client_name) VALUES (1, 101, \'Acme\'), (2, 202, \'Other\')');
        $db->execute('CREATE TABLE branches (branch_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, client_id INTEGER NOT NULL, branch_name TEXT NOT NULL, phone TEXT, email TEXT, address TEXT, city TEXT, province TEXT, status TEXT NOT NULL, created_at TEXT, updated_at TEXT, UNIQUE(client_id, branch_name))');
        $db->execute('INSERT INTO branches (company_id, client_id, branch_name, status) VALUES (101, 1, \'A Branch\', \'ACTIVE\'), (202, 2, \'B Branch\', \'ACTIVE\')');

        return new BranchRepository($db, $context);
    }

    public function testListAndFindAreScopedToRuntimeCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertCount(1, $repository->listForCompany());
        self::assertSame('A Branch', $repository->findByIdForCompany('1')['branch_name']);
        self::assertNull($repository->findByIdForCompany('2'));
    }

    public function testClientValidationAndCreateUseRuntimeCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertTrue($repository->clientBelongsToCompany(1));
        self::assertFalse($repository->clientBelongsToCompany(2));
        self::assertFalse($repository->clientBelongsToCompany(999));

        $created = $repository->createForCompany(['client_id' => 1, 'branch_name' => 'New Branch', 'company_id' => 202]);
        self::assertSame(101, (int) $created['company_id']);
        self::assertNull($repository->updateForCompany('2', ['branch_name' => 'Hijacked']));
        self::assertSame('Updated', $repository->updateForCompany('1', ['branch_name' => 'Updated'])['branch_name']);
    }
}