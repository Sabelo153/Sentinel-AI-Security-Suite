<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class RbacAdministrationRepository extends Repository
{
	public function __construct(Database $db, ?RuntimeContext $context = null)
	{
		parent::__construct($db, $context);
	}

	protected function table(): string
	{
		return 'roles';
	}

	/** @return array<int, array<string, mixed>> */
	public function listRolesForCompany(): array
	{
		return $this->db->fetchAll(
			'SELECT role_id, company_id, role_name, role_description, is_system_role, status FROM roles WHERE company_id = :company_id ORDER BY is_system_role DESC, role_name, role_id',
			['company_id' => $this->requireCompanyId()],
		);
	}

	/** @return array<int, array<string, mixed>> */
	public function listPermissionsForCompany(): array
	{
		return $this->db->fetchAll(
			'SELECT permission_id, company_id, permission_code, permission_name, module_name, action_name, permission_description, status FROM permissions WHERE company_id = :company_id ORDER BY permission_code, permission_id',
			['company_id' => $this->requireCompanyId()],
		);
	}

	/** @return array<string, mixed>|null */
	public function findRoleForCompany(string $roleId): ?array
	{
		return $this->db->fetch(
			'SELECT role_id, company_id, role_name, role_description, is_system_role, status FROM roles WHERE role_id = :role_id AND company_id = :company_id LIMIT 1',
			['role_id' => $roleId, 'company_id' => $this->requireCompanyId()],
		);
	}

	/** @return array<string, mixed>|null */
	public function findPermissionForCompany(string $permissionId): ?array
	{
		return $this->db->fetch(
			'SELECT permission_id, company_id, permission_code, permission_name, module_name, action_name, permission_description, status FROM permissions WHERE permission_id = :permission_id AND company_id = :company_id LIMIT 1',
			['permission_id' => $permissionId, 'company_id' => $this->requireCompanyId()],
		);
	}

	/** @return array<int, array<string, mixed>> */
	public function listPermissionsForRole(string $roleId): array
	{
		return $this->db->fetchAll(
			'SELECT p.permission_id, p.company_id, p.permission_code, p.permission_name, p.module_name, p.action_name, p.permission_description, p.status FROM role_permissions rp INNER JOIN permissions p ON p.permission_id = rp.permission_id AND p.company_id = rp.company_id WHERE rp.role_id = :role_id AND rp.company_id = :company_id ORDER BY p.permission_code, p.permission_id',
			['role_id' => $roleId, 'company_id' => $this->requireCompanyId()],
		);
	}

	/** @return array<int, array<string, mixed>> */
	public function listRolesForUser(string $userId): array
	{
		return $this->db->fetchAll(
			'SELECT r.role_id, r.company_id, r.role_name, r.role_description, r.is_system_role, r.status FROM user_roles ur INNER JOIN roles r ON r.role_id = ur.role_id AND r.company_id = ur.company_id WHERE ur.user_id = :user_id AND ur.company_id = :company_id ORDER BY r.role_name, r.role_id',
			['user_id' => $userId, 'company_id' => $this->requireCompanyId()],
		);
	}

	public function userExistsForCompany(string $userId): bool
	{
		return $this->db->fetch(
			'SELECT user_id FROM users WHERE user_id = :user_id AND company_id = :company_id LIMIT 1',
			['user_id' => $userId, 'company_id' => $this->requireCompanyId()],
		) !== null;
	}

	public function roleIsSystemRole(string $roleId): bool
	{
		$role = $this->findRoleForCompany($roleId);

		return $role !== null && (bool) $role['is_system_role'];
	}

	/** @param array{role_name: string, role_description: ?string, status: string} $data @return array<string, mixed> */
	public function createRoleForCompany(array $data): array
	{
		$this->db->execute(
			'INSERT INTO roles (company_id, role_name, role_description, is_system_role, status) VALUES (:company_id, :role_name, :role_description, 0, :status)',
			['company_id' => $this->requireCompanyId(), ...$data],
		);

		$role = $this->findRoleForCompany((string) $this->db->connection()->lastInsertId());
		if ($role === null) {
			throw new \RuntimeException('Created role could not be loaded.');
		}

		return $role;
	}

	/** @param array{role_name?: string, role_description?: ?string, status?: string} $data @return array<string, mixed>|null */
	public function updateRoleForCompany(string $roleId, array $data): ?array
	{
		$role = $this->findRoleForCompany($roleId);
		if ($role === null || (bool) $role['is_system_role']) {
			return null;
		}

		$assignments = [];
		$params = ['role_id' => $roleId, 'company_id' => $this->requireCompanyId()];
		foreach (['role_name', 'role_description', 'status'] as $field) {
			if (array_key_exists($field, $data)) {
				$assignments[] = $field . ' = :' . $field;
				$params[$field] = $data[$field];
			}
		}
		if ($assignments !== []) {
			$this->db->execute('UPDATE roles SET ' . implode(', ', $assignments) . ' WHERE role_id = :role_id AND company_id = :company_id AND is_system_role = 0', $params);
		}

		return $this->findRoleForCompany($roleId);
	}

	public function inactivateRoleForCompany(string $roleId): bool
	{
		return $this->db->execute(
			'UPDATE roles SET status = :status WHERE role_id = :role_id AND company_id = :company_id AND is_system_role = 0',
			['status' => 'INACTIVE', 'role_id' => $roleId, 'company_id' => $this->requireCompanyId()],
		) > 0;
	}

	public function grantPermissionToRole(string $roleId, string $permissionId, ?string $grantedByUserId): bool
	{
		$companyId = $this->requireCompanyId();
		if ($this->roleIsSystemRole($roleId) || $this->findPermissionForCompany($permissionId) === null) {
			return false;
		}

		return $this->db->execute(
			'INSERT INTO role_permissions (company_id, role_id, permission_id, granted_by_user_id) VALUES (:company_id, :role_id, :permission_id, :granted_by_user_id)',
			['company_id' => $companyId, 'role_id' => $roleId, 'permission_id' => $permissionId, 'granted_by_user_id' => $grantedByUserId],
		) > 0;
	}

	public function revokePermissionFromRole(string $roleId, string $permissionId): bool
	{
		if ($this->roleIsSystemRole($roleId)) {
			return false;
		}

		return $this->db->execute(
			'DELETE FROM role_permissions WHERE role_id = :role_id AND permission_id = :permission_id AND company_id = :company_id',
			['role_id' => $roleId, 'permission_id' => $permissionId, 'company_id' => $this->requireCompanyId()],
		) > 0;
	}

	public function assignRoleToUser(string $roleId, string $userId, ?string $assignedByUserId): bool
	{
		$companyId = $this->requireCompanyId();
		if ($this->roleIsSystemRole($roleId) || !$this->userExistsForCompany($userId)) {
			return false;
		}

		return $this->db->execute(
			'INSERT INTO user_roles (company_id, user_id, role_id, assigned_by_user_id) VALUES (:company_id, :user_id, :role_id, :assigned_by_user_id)',
			['company_id' => $companyId, 'user_id' => $userId, 'role_id' => $roleId, 'assigned_by_user_id' => $assignedByUserId],
		) > 0;
	}

	public function removeRoleFromUser(string $roleId, string $userId): bool
	{
		if ($this->roleIsSystemRole($roleId)) {
			return false;
		}

		return $this->db->execute(
			'DELETE FROM user_roles WHERE role_id = :role_id AND user_id = :user_id AND company_id = :company_id',
			['role_id' => $roleId, 'user_id' => $userId, 'company_id' => $this->requireCompanyId()],
		) > 0;
	}
}