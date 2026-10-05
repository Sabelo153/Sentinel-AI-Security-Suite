<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\UserRepository;

final class UserManagementRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): UserRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)');
        $db->execute('INSERT INTO employees (employee_id, company_id) VALUES (3, 101), (4, 202)');
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY AUTOINCREMENT, user_uuid TEXT NOT NULL, company_id INTEGER NOT NULL, employee_id INTEGER NOT NULL, username TEXT NOT NULL, email TEXT NOT NULL, password_hash TEXT NOT NULL, status TEXT NOT NULL)');
        $db->execute('INSERT INTO users (user_uuid, company_id, employee_id, username, email, password_hash, status) VALUES (\'u-1\', 101, 1, \'alpha\', \'alpha@example.test\', \'hash-a\', \'ACTIVE\'), (\'u-2\', 202, 2, \'beta\', \'beta@example.test\', \'hash-b\', \'ACTIVE\')');

        return new UserRepository($db, $context);
    }

    public function testListAndFindAreScopedToRuntimeCompanyAndHidePasswordHash(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        $users = $repository->listForCompany();
        self::assertCount(1, $users);
        self::assertSame('alpha', $users[0]['username']);
        self::assertArrayNotHasKey('password_hash', $users[0]);
        self::assertSame('alpha', $repository->findByIdForCompany('1')['username']);
        self::assertNull($repository->findByIdForCompany('2'));
    }

    public function testCreateUsesRuntimeCompanyEvenWhenCallerDataContainsAnotherCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        $created = $repository->createForCompany([
            'username' => 'new-user',
            'email' => 'new@example.test',
            'password_hash' => password_hash('secret', PASSWORD_DEFAULT),
            'employee_id' => 3,
            'company_id' => 202,
        ]);

        self::assertSame('101', (string) $created['company_id']);
        self::assertArrayNotHasKey('password_hash', $created);
        self::assertNull($repository->findByIdForCompany('2'));
    }

    public function testEmployeeValidationUsesRuntimeCompany(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertTrue($repository->employeeBelongsToCompany(3));
        self::assertFalse($repository->employeeBelongsToCompany(4));
        self::assertFalse($repository->employeeBelongsToCompany(999));
    }

    public function testUpdateCannotCrossCompanyBoundary(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertNull($repository->updateForCompany('2', ['username' => 'hijacked']));

        $updated = $repository->updateForCompany('1', ['username' => 'updated']);
        self::assertSame('updated', $updated['username']);
    }
}