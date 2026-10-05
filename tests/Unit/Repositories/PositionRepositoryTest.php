<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PositionRepository;

final class PositionRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): PositionRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE organizations (organization_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, deleted_at TEXT)');
        $db->execute('INSERT INTO organizations VALUES (1, 101, NULL), (2, 202, NULL)');
        $db->execute('CREATE TABLE organizational_units (organizational_unit_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, organization_id INTEGER NOT NULL, deleted_at TEXT)');
        $db->execute('INSERT INTO organizational_units VALUES (1, 101, 1, NULL), (2, 202, 2, NULL), (3, 101, 1, NULL)');
        $db->execute('CREATE TABLE positions (position_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, organization_id INTEGER, organizational_unit_id INTEGER, position_code TEXT NOT NULL, position_name TEXT NOT NULL, position_category TEXT NOT NULL, hierarchy_rank INTEGER NOT NULL, position_status TEXT NOT NULL, description TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, UNIQUE(company_id, position_code))');
        $db->execute('INSERT INTO positions (company_id, organization_id, organizational_unit_id, position_code, position_name, position_category, hierarchy_rank, position_status) VALUES (101, 1, 1, \'A-G\', \'Guard\', \'GUARD\', 100, \'ACTIVE\'), (202, 2, 2, \'B-G\', \'Guard\', \'GUARD\', 100, \'ACTIVE\')');

        return new PositionRepository($db, $context);
    }

    public function testListAndFindAreScopedToRuntimeCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertCount(1, $repository->listForCompany());
        self::assertSame('A-G', $repository->findByIdForCompany('1')['position_code']);
        self::assertNull($repository->findByIdForCompany('2'));
    }

    public function testOptionalReferencesAndCreateUseRuntimeCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertTrue($repository->organizationBelongsToCompany(1));
        self::assertFalse($repository->organizationBelongsToCompany(2));
        self::assertTrue($repository->unitBelongsToOrganization(1, 1));
        self::assertFalse($repository->unitBelongsToOrganization(1, 2));

        $created = $repository->createForCompany(['organization_id' => 1, 'organizational_unit_id' => 1, 'position_code' => 'A-S', 'position_name' => 'Supervisor', 'position_category' => 'SUPERVISOR', 'company_id' => 202]);
        self::assertSame(101, (int) $created['company_id']);
        self::assertNull($repository->updateForCompany('2', ['position_name' => 'Hijacked']));
    }
}