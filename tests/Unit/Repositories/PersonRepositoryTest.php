<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PersonRepository;

final class PersonRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): PersonRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, first_name TEXT NOT NULL, last_name TEXT NOT NULL, id_number TEXT, passport_number TEXT, phone TEXT, email TEXT, gender TEXT, date_of_birth TEXT, created_at TEXT, updated_at TEXT)');
        $db->execute('INSERT INTO people (company_id, first_name, last_name, email) VALUES (101, \'Alice\', \'A\', \'alice@example.test\'), (202, \'Bob\', \'B\', \'bob@example.test\')');

        return new PersonRepository($db, $context);
    }

    public function testListAndFindAreScopedToRuntimeCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertCount(1, $repository->listForCompany());
        self::assertSame('Alice', $repository->findByIdForCompany('1')['first_name']);
        self::assertNull($repository->findByIdForCompany('2'));
    }

    public function testCreateForcesRuntimeCompanyAndUpdateCannotCrossTenant(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        $created = $repository->createForCompany([
            'first_name' => 'Carol',
            'last_name' => 'C',
            'email' => 'carol@example.test',
            'company_id' => 202,
        ]);
        self::assertSame(101, (int) $created['company_id']);
        self::assertNull($repository->updateForCompany('2', ['first_name' => 'Hijacked']));
        self::assertSame('Updated', $repository->updateForCompany('1', ['first_name' => 'Updated'])['first_name']);
    }
}