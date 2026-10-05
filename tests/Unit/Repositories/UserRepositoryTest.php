<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Repositories\UserRepository;

final class UserRepositoryTest extends TestCase
{
    private function repository(): UserRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, company_id INTEGER, username TEXT, email TEXT, password_hash TEXT, status TEXT)');
        $db->execute('INSERT INTO users (user_id, company_id, username, email, password_hash, status) VALUES (:id, :company_id, :username, :email, :hash, :status)', [
            'id' => 7, 'company_id' => 101, 'username' => 'guard7', 'email' => 'guard@example.test', 'hash' => password_hash('secret', PASSWORD_DEFAULT), 'status' => 'ACTIVE',
        ]);

        return new UserRepository($db);
    }

    public function testAuthenticationLookupUsesMinimumProjectionAndPreparedParameter(): void
    {
        $row = $this->repository()->findForAuthentication('guard@example.test');

        self::assertSame(['user_id', 'username', 'email', 'password_hash', 'status'], array_keys($row));
        self::assertSame('guard@example.test', $row['email']);
    }

    public function testUnknownIdentifierReturnsNull(): void
    {
        self::assertNull($this->repository()->findForAuthentication("' OR 1=1 --"));
    }

    public function testFindCompanyIdByUserIdReturnsOnlyActiveUsersCompany(): void
    {
        $repository = $this->repository();

        self::assertSame('101', $repository->findCompanyIdByUserId('7'));
        self::assertNull($repository->findCompanyIdByUserId('99'));
    }
}