<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\Repository;

final class TenantIsolationTest extends TestCase
{
    public function testConcreteTenantQueryUsesRuntimeCompanyAndCannotBeOverriddenByCaller(): void
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE tenant_records (record_id INTEGER PRIMARY KEY, company_id INTEGER, label TEXT)');
        $db->execute('INSERT INTO tenant_records (record_id, company_id, label) VALUES (1, 101, \'A\'), (2, 202, \'B\')');

        $context = new RuntimeContext();
        $repository = new TenantFixtureRepository($db, $context);

        $context->setTenantId(101);
        self::assertSame('A', $repository->findVisible(1)['label']);
        self::assertNull($repository->findVisible(2));
        self::assertNull($repository->findVisible(2, 101));

        $context->setTenantId(202);
        self::assertSame('B', $repository->findVisible(2)['label']);
        self::assertNull($repository->findVisible(1));
    }
}

final class TenantFixtureRepository extends Repository
{
    protected function table(): string
    {
        return 'tenant_records';
    }

    /** @return array<string, mixed>|null */
    public function findVisible(int $recordId, ?int $callerCompanyId = null): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT record_id, company_id, label FROM tenant_records '
            . 'WHERE record_id = :record_id AND company_id = :company_id LIMIT 1',
            ['record_id' => $recordId, 'company_id' => $companyId],
        );
    }
}