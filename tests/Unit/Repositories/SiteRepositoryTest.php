<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\SiteRepository;

final class SiteRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): SiteRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE clients (client_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)');
        $db->execute('INSERT INTO clients (client_id, company_id) VALUES (1, 101), (2, 202), (3, 101)');
        $db->execute('CREATE TABLE branches (branch_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, client_id INTEGER NOT NULL)');
        $db->execute('INSERT INTO branches (branch_id, company_id, client_id) VALUES (1, 101, 1), (2, 202, 2), (3, 101, 3)');
        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, client_id INTEGER NOT NULL, branch_id INTEGER NOT NULL, site_name TEXT NOT NULL, address TEXT, city TEXT, province TEXT, latitude REAL, longitude REAL, status TEXT NOT NULL, created_at TEXT, updated_at TEXT, UNIQUE(branch_id, site_name))');
        $db->execute('INSERT INTO sites (company_id, client_id, branch_id, site_name, status) VALUES (101, 1, 1, \'A Site\', \'ACTIVE\'), (202, 2, 2, \'B Site\', \'ACTIVE\')');

        return new SiteRepository($db, $context);
    }

    public function testListAndFindAreScopedToRuntimeCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertCount(1, $repository->listForCompany());
        self::assertSame('A Site', $repository->findByIdForCompany('1')['site_name']);
        self::assertNull($repository->findByIdForCompany('2'));
    }

    public function testDependenciesAndCreateUseRuntimeCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertTrue($repository->clientBelongsToCompany(1));
        self::assertFalse($repository->clientBelongsToCompany(2));
        self::assertTrue($repository->branchBelongsToClient(1, 1));
        self::assertFalse($repository->branchBelongsToClient(1, 3));

        $created = $repository->createForCompany(['client_id' => 1, 'branch_id' => 1, 'site_name' => 'New Site', 'company_id' => 202]);
        self::assertSame(101, (int) $created['company_id']);
        self::assertNull($repository->updateForCompany('2', ['site_name' => 'Hijacked']));
    }
}