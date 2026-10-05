<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\AuditRepository;
use Sentinel\Repositories\RbacAdministrationRepository;
use Sentinel\Services\AuditService;
use Sentinel\Services\RbacAdministrationService;

final class RbacAdministrationServiceTest extends TestCase
{
	/** @return array{0: RbacAdministrationService, 1: Database} */
	private function service(): array
	{
		$db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
		$db->execute('CREATE TABLE roles (role_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, role_name TEXT NOT NULL, role_description TEXT, is_system_role INTEGER NOT NULL, status TEXT NOT NULL, UNIQUE(company_id, role_name))');
		$db->execute('CREATE TABLE permissions (permission_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, permission_code TEXT NOT NULL, permission_name TEXT NOT NULL, module_name TEXT NOT NULL, action_name TEXT NOT NULL, permission_description TEXT, status TEXT NOT NULL)');
		$db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)');
		$db->execute('CREATE TABLE role_permissions (company_id INTEGER NOT NULL, role_id INTEGER NOT NULL, permission_id INTEGER NOT NULL, granted_by_user_id INTEGER, UNIQUE(role_id, permission_id))');
		$db->execute('CREATE TABLE user_roles (company_id INTEGER NOT NULL, user_id INTEGER NOT NULL, role_id INTEGER NOT NULL, assigned_by_user_id INTEGER, UNIQUE(user_id, role_id))');
		$db->execute('CREATE TABLE audit_logs (audit_log_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, user_id INTEGER, entity_name TEXT, entity_id TEXT, action_name TEXT, action_status TEXT, ip_address TEXT, user_agent TEXT, old_values TEXT, new_values TEXT, metadata TEXT)');
		$db->execute("INSERT INTO roles VALUES (1, 101, 'Officer', NULL, 0, 'ACTIVE'), (2, 101, 'System', NULL, 1, 'ACTIVE'), (3, 202, 'Foreign', NULL, 0, 'ACTIVE')");
		$db->execute("INSERT INTO permissions VALUES (10, 101, 'sites.view', 'View sites', 'sites', 'view', NULL, 'ACTIVE'), (20, 202, 'sites.view', 'View sites', 'sites', 'view', NULL, 'ACTIVE')");
		$db->execute('INSERT INTO users VALUES (7, 101), (8, 202)');
		$context = new RuntimeContext(); $context->setTenantId(101); $context->setUser('7');
		$repository = new RbacAdministrationRepository($db, $context);
		return [new RbacAdministrationService($repository, $context, new AuditService(new AuditRepository($db, $context), $context)), $db];
	}

	public function testAssignmentLifecycleIsTenantScopedAndAudited(): void
	{
		[$service, $db] = $this->service();
		$service->grantPermission('1', '10');
		$service->assignRole('1', '7');
		self::assertCount(1, $service->listRolePermissions('1'));
		self::assertCount(1, $service->listUserRoles('7'));
		try { $service->grantPermission('1', '10'); self::fail('Expected duplicate role permission conflict.'); } catch (HttpException $exception) { self::assertSame(409, $exception->statusCode()); }
		try { $service->assignRole('1', '7'); self::fail('Expected duplicate user role conflict.'); } catch (HttpException $exception) { self::assertSame(409, $exception->statusCode()); }
		$service->revokePermission('1', '10');
		$service->removeRole('1', '7');
		self::assertSame('4', (string) $db->fetch('SELECT COUNT(*) AS total FROM audit_logs')['total']);
	}

	public function testForeignAndMissingRolesReturnTheSameSafeNotFoundResponse(): void
	{
		[$service] = $this->service(); $errors = [];
		foreach (['3', '999'] as $roleId) {
			try { $service->getRole($roleId); self::fail('Expected role not found.'); } catch (HttpException $exception) { $errors[] = [$exception->statusCode(), $exception->errorCode(), $exception->getMessage()]; }
		}
		self::assertSame([404, 'role_not_found', 'Role not found.'], $errors[0]);
		self::assertSame($errors[0], $errors[1]);
	}

	public function testSystemRoleAndInvalidAssignmentInputAreRejected(): void
	{
		[$service] = $this->service();
		foreach ([['2', '10'], ['', '10']] as [$roleId, $permissionId]) {
			try { $service->grantPermission($roleId, $permissionId); self::fail('Expected RBAC validation failure.'); } catch (HttpException $exception) { self::assertContains($exception->statusCode(), [403, 422]); }
		}
		try { $service->updateRole('2', ['status' => 'INACTIVE']); self::fail('Expected protected system role.'); } catch (HttpException $exception) { self::assertSame(403, $exception->statusCode()); }
	}

	public function testCreateIgnoresRequestCompanyAndRejectsDuplicateRoleNames(): void
	{
		[$service] = $this->service();
		$role = $service->createRole(['role_name' => 'Coordinator', 'company_id' => 202]);
		self::assertSame('101', (string) $role['company_id']);
		try { $service->createRole(['role_name' => 'Coordinator']); self::fail('Expected duplicate role conflict.'); } catch (HttpException $exception) { self::assertSame(409, $exception->statusCode()); }
	}

	public function testPermissionProjectionAndForeignUsersRemainTenantSafe(): void
	{
		[$service] = $this->service();
		$permissions = $service->listPermissions();
		self::assertCount(1, $permissions);
		self::assertArrayNotHasKey('created_at', $permissions[0]);
		try { $service->listUserRoles('8'); self::fail('Expected user not found.'); } catch (HttpException $exception) { self::assertSame([404, 'user_not_found'], [$exception->statusCode(), $exception->errorCode()]); }
		try { $service->createRole(['role_name' => 'Invalid', 'status' => 'ARCHIVED']); self::fail('Expected invalid status.'); } catch (HttpException $exception) { self::assertSame(422, $exception->statusCode()); }
	}

	public function testRoleCreationAndUpdateAreAudited(): void
	{
		[$service, $db] = $this->service();
		$role = $service->createRole(['role_name' => 'Coordinator']);
		$service->updateRole((string) $role['role_id'], ['status' => 'INACTIVE']);
		$actions = $db->fetchAll('SELECT action_name FROM audit_logs ORDER BY audit_log_id');
		self::assertSame([['action_name' => 'created'], ['action_name' => 'updated']], $actions);
	}
}