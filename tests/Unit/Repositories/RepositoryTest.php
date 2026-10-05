<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\Repository;

final class ClientsRepository extends Repository
{
    protected function table(): string
    {
        return 'clients';
    }

    public function exposeRequireCompanyId(): int
    {
        return $this->requireCompanyId();
    }

    public function exposeValidateIdentifier(string $identifier): string
    {
        return $this->validateIdentifier($identifier);
    }
}

final class RepositoryTest extends TestCase
{
    private function makeDatabase(): Database
    {
        $config = new Config(['database' => []]);
        $connector = static fn (): PDO => new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $db = new Database($config, $connector);
        $db->execute('CREATE TABLE clients (id INTEGER PRIMARY KEY, name TEXT, company_id INTEGER)');

        return $db;
    }

    public function testValidateIdentifierAcceptsSafeNames(): void
    {
        $repo = new ClientsRepository($this->makeDatabase());

        self::assertSame('clients', $repo->exposeValidateIdentifier('clients'));
        self::assertSame('company_id', $repo->exposeValidateIdentifier('company_id'));
    }

    public function testValidateIdentifierRejectsUnsafeNames(): void
    {
        $repo = new ClientsRepository($this->makeDatabase());

        $this->expectException(\InvalidArgumentException::class);
        $repo->exposeValidateIdentifier('clients; DROP TABLE clients;--');
    }

    public function testInsertAndFindById(): void
    {
        $repo = new ClientsRepository($this->makeDatabase());

        $id = $repo->insert('clients', ['name' => 'Acme', 'company_id' => 1]);
        $row = $repo->findById($id);

        self::assertNotNull($row);
        self::assertSame('Acme', $row['name']);
        self::assertSame(1, (int) $row['company_id']);
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        $repo = new ClientsRepository($this->makeDatabase());

        self::assertNull($repo->findById(9999));
    }

    public function testUpdateModifiesMatchingRow(): void
    {
        $repo = new ClientsRepository($this->makeDatabase());
        $id = $repo->insert('clients', ['name' => 'Acme', 'company_id' => 1]);

        $affected = $repo->update('clients', ['name' => 'Acme Renamed'], 'id = :id', ['id' => $id]);

        self::assertSame(1, $affected);
        self::assertSame('Acme Renamed', $repo->findById($id)['name']);
    }

    public function testDeleteRemovesMatchingRow(): void
    {
        $repo = new ClientsRepository($this->makeDatabase());
        $id = $repo->insert('clients', ['name' => 'Acme', 'company_id' => 1]);

        $affected = $repo->delete('clients', 'id = :id', ['id' => $id]);

        self::assertSame(1, $affected);
        self::assertNull($repo->findById($id));
    }

    public function testFindAllDoesNotSilentlyScopeByTenant(): void
    {
        $repo = new ClientsRepository($this->makeDatabase());
        $repo->insert('clients', ['name' => 'Company One Client', 'company_id' => 1]);
        $repo->insert('clients', ['name' => 'Company Two Client', 'company_id' => 2]);

        // Base Repository never auto-injects company_id; scoping is the concrete repository's responsibility.
        self::assertCount(2, $repo->findAll());
    }

    public function testRequireCompanyIdThrowsWithoutRuntimeContext(): void
    {
        $repo = new ClientsRepository($this->makeDatabase());

        $this->expectException(\RuntimeException::class);
        $repo->exposeRequireCompanyId();
    }

    public function testRequireCompanyIdThrowsForbiddenWhenTenantNotEstablished(): void
    {
        $repo = new ClientsRepository($this->makeDatabase(), new RuntimeContext());

        try {
            $repo->exposeRequireCompanyId();
            self::fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            self::assertSame(403, $e->statusCode());
        }
    }

    public function testRequireCompanyIdReturnsEstablishedTenant(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(7);

        $repo = new ClientsRepository($this->makeDatabase(), $context);

        self::assertSame(7, $repo->exposeRequireCompanyId());
    }

    public function testInsertRejectsEmptyData(): void
    {
        $repo = new ClientsRepository($this->makeDatabase());

        $this->expectException(\InvalidArgumentException::class);
        $repo->insert('clients', []);
    }
}
