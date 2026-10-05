<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\ClientRepository;

final class ClientRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): ClientRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE clients (client_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, client_name TEXT NOT NULL, contact_person TEXT, phone TEXT, email TEXT, address TEXT, city TEXT, province TEXT, postal_code TEXT, country TEXT, contract_start TEXT, contract_end TEXT, status TEXT NOT NULL, created_at TEXT, updated_at TEXT, UNIQUE(company_id, client_name))');
        $db->execute('INSERT INTO clients (company_id, client_name, email, status) VALUES (101, \'Acme\', \'acme@example.test\', \'ACTIVE\'), (202, \'Other\', \'other@example.test\', \'ACTIVE\')');

        return new ClientRepository($db, $context);
    }

    public function testListAndFindAreScopedToRuntimeCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertCount(1, $repository->listForCompany());
        self::assertSame('Acme', $repository->findByIdForCompany('1')['client_name']);
        self::assertNull($repository->findByIdForCompany('2'));
    }

    public function testCreateForcesRuntimeCompanyAndUpdateCannotCrossTenant(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        $created = $repository->createForCompany(['client_name' => 'New Client', 'company_id' => 202]);
        self::assertSame(101, (int) $created['company_id']);
        self::assertNull($repository->updateForCompany('2', ['client_name' => 'Hijacked']));
        self::assertSame('Updated', $repository->updateForCompany('1', ['client_name' => 'Updated'])['client_name']);
    }
}