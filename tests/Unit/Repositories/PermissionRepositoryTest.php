<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Repositories\PermissionRepository;

final class PermissionRepositoryTest extends TestCase
{
    private function repository(): PermissionRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, company_id INTEGER DEFAULT 101, username TEXT, email TEXT, password_hash TEXT, status TEXT)');
        $db->execute('CREATE TABLE roles (role_id INTEGER PRIMARY KEY, company_id INTEGER, role_name TEXT, role_description TEXT, is_system_role INTEGER, status TEXT)');
        $db->execute('CREATE TABLE permissions (permission_id INTEGER PRIMARY KEY, company_id INTEGER, permission_code TEXT, permission_name TEXT, module_name TEXT, action_name TEXT, permission_description TEXT, status TEXT)');
        $db->execute('CREATE TABLE user_roles (user_role_id INTEGER PRIMARY KEY, company_id INTEGER, user_id INTEGER, role_id INTEGER, assigned_at TEXT, assigned_by_user_id INTEGER)');
        $db->execute('CREATE TABLE role_permissions (role_permission_id INTEGER PRIMARY KEY, company_id INTEGER, role_id INTEGER, permission_id INTEGER, granted_at TEXT, granted_by_user_id INTEGER)');

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
        $db->execute('INSERT INTO permissions (permission_id, company_id, permission_code, permission_name, module_name, action_name, permission_description, status) VALUES (1, 101, :code, :name, :module, :action, :description, :status)', [
            'code' => 'system.test',
            'name' => 'System Test',
            'module' => 'system',
            'action' => 'test',
            'description' => 'System test permission',
            'status' => 'ACTIVE',
        ]);
        $db->execute('INSERT INTO user_roles (user_role_id, company_id, user_id, role_id, assigned_at, assigned_by_user_id) VALUES (1, 101, 7, 1, :assigned, NULL)', ['assigned' => '2026-01-01 00:00:00']);
        $db->execute('INSERT INTO role_permissions (role_permission_id, company_id, role_id, permission_id, granted_at, granted_by_user_id) VALUES (1, 101, 1, 1, :granted, NULL)', ['granted' => '2026-01-01 00:00:00']);

        $context = new \Sentinel\Core\RuntimeContext(); $context->setTenantId(101);
        return new PermissionRepository($db, $context);
    }

    public function testUserWithPermissionReturnsTrue(): void
    {
        self::assertTrue($this->repository()->userHasPermission('7', 'system.test'));
    }

    public function testUserWithoutPermissionReturnsFalse(): void
    {
        self::assertFalse($this->repository()->userHasPermission('7', 'users.view'));
    }

    public function testUnknownUserReturnsFalse(): void
    {
        self::assertFalse($this->repository()->userHasPermission('999', 'system.test'));
    }

    public function testUnknownPermissionReturnsFalse(): void
    {
        self::assertFalse($this->repository()->userHasPermission('7', 'missing.permission'));
    }

    public function testDatabaseFailureIsHandledSafely(): void
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null));
        $repository = new PermissionRepository($db);

        $this->expectException(\Sentinel\Core\HttpException::class);
        $repository->userHasPermission('7', 'system.test');
    }
}
