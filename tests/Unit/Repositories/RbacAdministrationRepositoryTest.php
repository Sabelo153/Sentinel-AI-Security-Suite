<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\RbacAdministrationRepository;

final class RbacAdministrationRepositoryTest extends TestCase
{
	private function repository(RuntimeContext $context): RbacAdministrationRepository
	{
		$db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
		$db->execute('CREATE TABLE roles (role_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, role_name TEXT NOT NULL, role_description TEXT, is_system_role INTEGER NOT NULL, status TEXT NOT NULL)');
		$db->execute('CREATE TABLE permissions (permission_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, permission_code TEXT NOT NULL, permission_name TEXT NOT NULL, module_name TEXT NOT NULL, action_name TEXT NOT NULL, permission_description TEXT, status TEXT NOT NULL)');
		$db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)');
		$db->execute('CREATE TABLE role_permissions (company_id INTEGER NOT NULL, role_id INTEGER NOT NULL, permission_id INTEGER NOT NULL, granted_by_user_id INTEGER)');
		$db->execute('CREATE TABLE user_roles (company_id INTEGER NOT NULL, user_id INTEGER NOT NULL, role_id INTEGER NOT NULL, assigned_by_user_id INTEGER)');
		$db->execute("INSERT INTO roles VALUES (1, 101, 'Custom Officer', NULL, 0, 'ACTIVE'), (2, 101, 'System Administrator', NULL, 1, 'ACTIVE'), (3, 202, 'Other Company Role', NULL, 0, 'ACTIVE')");
		$db->execute("INSERT INTO permissions VALUES (10, 101, 'sites.view', 'View sites', 'sites', 'view', NULL, 'ACTIVE'), (20, 202, 'sites.view', 'View sites', 'sites', 'view', NULL, 'ACTIVE')");
		$db->execute('INSERT INTO users VALUES (7, 101), (8, 202)');

		return new RbacAdministrationRepository($db, $context);
	}

	public function testTenantScopedAssignmentsAndPermissionGrantsRejectForeignIdentifiers(): void
	{
		$context = new RuntimeContext();
		$context->setTenantId(101);
		$repository = $this->repository($context);

		self::assertTrue($repository->grantPermissionToRole('1', '10', '7'));
		self::assertTrue($repository->assignRoleToUser('1', '7', '7'));
		self::assertFalse($repository->grantPermissionToRole('3', '20', '7'));
		self::assertFalse($repository->assignRoleToUser('3', '8', '7'));
	}

	public function testSystemRolesCannotHavePermissionsOrAssignmentsChanged(): void
	{
		$context = new RuntimeContext();
		$context->setTenantId(101);
		$repository = $this->repository($context);

		self::assertFalse($repository->grantPermissionToRole('2', '10', '7'));
		self::assertFalse($repository->revokePermissionFromRole('2', '10'));
		self::assertFalse($repository->assignRoleToUser('2', '7', '7'));
		self::assertFalse($repository->removeRoleFromUser('2', '7'));
	}

	public function testCustomRolesCanBeCreatedUpdatedAndInactivatedWithinTheirTenant(): void
	{
		$context = new RuntimeContext();
		$context->setTenantId(101);
		$repository = $this->repository($context);

		$created = $repository->createRoleForCompany([
			'role_name' => 'Shift Coordinator',
			'role_description' => 'Coordinates shifts.',
			'status' => 'ACTIVE',
		]);
		$updated = $repository->updateRoleForCompany((string) $created['role_id'], ['status' => 'INACTIVE']);

		self::assertSame('101', (string) $created['company_id']);
		self::assertSame('INACTIVE', $updated['status']);
		self::assertTrue($repository->inactivateRoleForCompany((string) $created['role_id']));
		self::assertSame('INACTIVE', $repository->findRoleForCompany((string) $created['role_id'])['status']);
	}
}