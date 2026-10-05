<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Repositories\RoleRepository;

final class RoleRepositoryTest extends TestCase
{
    private function repository(): RoleRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, username TEXT, email TEXT, password_hash TEXT, status TEXT)');
        $db->execute('CREATE TABLE roles (role_id INTEGER PRIMARY KEY, company_id INTEGER, role_name TEXT, role_description TEXT, is_system_role INTEGER, status TEXT)');
        $db->execute('CREATE TABLE user_roles (user_role_id INTEGER PRIMARY KEY, company_id INTEGER, user_id INTEGER, role_id INTEGER, assigned_at TEXT, assigned_by_user_id INTEGER)');

        $db->execute('INSERT INTO users (user_id, username, email, password_hash, status) VALUES (7, :username, :email, :hash, :status)', [
            'username' => 'guard7',
            'email' => 'guard@example.test',
            'hash' => password_hash('secret', PASSWORD_DEFAULT),
            'status' => 'ACTIVE',
        ]);
        $db->execute('INSERT INTO roles (role_id, company_id, role_name, role_description, is_system_role, status) VALUES (1, 101, :role_name, :description, 1, :status)', [
            'role_name' => 'System Administrator',
            'description' => 'Admin role',
            'status' => 'ACTIVE',
        ]);
        $db->execute('INSERT INTO user_roles (user_role_id, company_id, user_id, role_id, assigned_at, assigned_by_user_id) VALUES (1, 101, 7, 1, :assigned, NULL)', ['assigned' => '2026-01-01 00:00:00']);

        return new RoleRepository($db);
    }

    public function testUserWithRoleReturnsTrue(): void
    {
        self::assertTrue($this->repository()->userHasRole('7', 'System Administrator'));
    }

    public function testUserWithoutRoleReturnsFalse(): void
    {
        self::assertFalse($this->repository()->userHasRole('7', 'Auditor'));
    }

    public function testUnknownUserReturnsFalse(): void
    {
        self::assertFalse($this->repository()->userHasRole('999', 'System Administrator'));
    }

    public function testUnknownRoleReturnsFalse(): void
    {
        self::assertFalse($this->repository()->userHasRole('7', 'Missing Role'));
    }
}
