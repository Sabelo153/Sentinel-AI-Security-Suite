<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\RbacAdministrationRepository;

final class RbacAdministrationService
{
	private const STATUSES = ['ACTIVE', 'INACTIVE'];

	public function __construct(private readonly RbacAdministrationRepository $rbac, private readonly RuntimeContext $context, private readonly AuditService $audit)
	{
	}

	/** @return array<int, array<string, mixed>> */
	public function listRoles(): array { return $this->rbac->listRolesForCompany(); }
	/** @return array<string, mixed> */
	public function getRole(string $roleId): array { return $this->rbac->findRoleForCompany($roleId) ?? throw HttpException::notFound('Role not found.', 'role_not_found'); }
	/** @return array<int, array<string, mixed>> */
	public function listPermissions(): array { return $this->rbac->listPermissionsForCompany(); }
	/** @return array<string, mixed> */
	public function getPermission(string $permissionId): array { return $this->rbac->findPermissionForCompany($permissionId) ?? throw HttpException::notFound('Permission not found.', 'permission_not_found'); }
	/** @return array<string, mixed> */
	public function createRole(array $input): array
	{
		try { $role = $this->rbac->createRoleForCompany($this->roleFields($input, false)); } catch (PDOException) { throw HttpException::conflict('Role name is already in use.', 'role_conflict'); }
		$this->audit('role', 'created', (string) $role['role_id'], [], $role);
		return $role;
	}
	/** @return array<string, mixed> */
	public function updateRole(string $roleId, array $input): array
	{
		$this->assertMutableRole($roleId);
		$data = $this->roleFields($input, true);
		if ($data === []) { throw HttpException::unprocessable('No editable role fields were provided.', 'invalid_role_update'); }
		$before = $this->getRole($roleId);
		try { $role = $this->rbac->updateRoleForCompany($roleId, $data) ?? throw HttpException::notFound('Role not found.', 'role_not_found'); } catch (PDOException) { throw HttpException::conflict('Role name is already in use.', 'role_conflict'); }
		$this->audit('role', 'updated', $roleId, $before, $role);
		return $role;
	}
	public function grantPermission(string $roleId, string $permissionId): void
	{
		$this->requiredIdentifier($roleId, 'role_id');
		$this->requiredIdentifier($permissionId, 'permission_id');
		$this->assertMutableRole($roleId);
		if ($this->rbac->findPermissionForCompany($permissionId) === null) { throw HttpException::notFound('Permission not found.', 'permission_not_found'); }
		try { if (!$this->rbac->grantPermissionToRole($roleId, $permissionId, $this->actorId())) { throw HttpException::conflict('Permission is already assigned to this role.', 'role_permission_conflict'); } } catch (PDOException) { throw HttpException::conflict('Permission is already assigned to this role.', 'role_permission_conflict'); }
		$this->audit('role_permission', 'assigned', $roleId . ':' . $permissionId, [], ['role_id' => $roleId, 'permission_id' => $permissionId]);
	}
	public function revokePermission(string $roleId, string $permissionId): void
	{
		$this->assertMutableRole($roleId);
		if (!$this->rbac->revokePermissionFromRole($roleId, $permissionId)) { throw HttpException::notFound('Role permission not found.', 'role_permission_not_found'); }
		$this->audit('role_permission', 'removed', $roleId . ':' . $permissionId, ['role_id' => $roleId, 'permission_id' => $permissionId]);
	}
	public function assignRole(string $roleId, string $userId): void
	{
		$this->requiredIdentifier($roleId, 'role_id');
		$this->assertMutableRole($roleId);
		$this->requiredIdentifier($userId, 'user_id');
		if (!$this->rbac->userExistsForCompany($userId)) { throw HttpException::notFound('User not found.', 'user_not_found'); }
		try { if (!$this->rbac->assignRoleToUser($roleId, $userId, $this->actorId())) { throw HttpException::conflict('Role is already assigned to this user.', 'user_role_conflict'); } } catch (PDOException) { throw HttpException::conflict('Role is already assigned to this user.', 'user_role_conflict'); }
		$this->audit('user_role', 'assigned', $userId . ':' . $roleId, [], ['user_id' => $userId, 'role_id' => $roleId]);
	}
	public function removeRole(string $roleId, string $userId): void
	{
		$this->assertMutableRole($roleId);
		if (!$this->rbac->removeRoleFromUser($roleId, $userId)) { throw HttpException::notFound('User role not found.', 'user_role_not_found'); }
		$this->audit('user_role', 'removed', $userId . ':' . $roleId, ['user_id' => $userId, 'role_id' => $roleId]);
	}
	/** @return array<int, array<string, mixed>> */
	public function listRolePermissions(string $roleId): array { $this->getRole($roleId); return $this->rbac->listPermissionsForRole($roleId); }
	/** @return array<int, array<string, mixed>> */
	public function listUserRoles(string $userId): array { if (!$this->rbac->userExistsForCompany($userId)) { throw HttpException::notFound('User not found.', 'user_not_found'); } return $this->rbac->listRolesForUser($userId); }
	private function assertMutableRole(string $roleId): void
	{
		$role = $this->rbac->findRoleForCompany($roleId);
		if ($role === null) { throw HttpException::notFound('Role not found.', 'role_not_found'); }
		if ((bool) $role['is_system_role']) { throw HttpException::forbidden('System roles cannot be modified.', 'system_role_protected'); }
	}
	/** @return array{role_name?: string, role_description?: ?string, status?: string} */
	private function roleFields(array $input, bool $update): array
	{
		$data = array_intersect_key($input, array_flip(['role_name', 'role_description', 'status']));
		if (!$update || array_key_exists('role_name', $data)) {
			$name = $data['role_name'] ?? null;
			if (!is_string($name) || trim($name) === '' || strlen(trim($name)) > 100) { throw HttpException::unprocessable('Role name is invalid.', 'invalid_role_name'); }
			$data['role_name'] = trim($name);
		}
		if (array_key_exists('role_description', $data) && $data['role_description'] !== null && (!is_string($data['role_description']) || strlen(trim($data['role_description'])) > 65535)) { throw HttpException::unprocessable('Role description is invalid.', 'invalid_role_description'); }
		if (array_key_exists('role_description', $data) && is_string($data['role_description'])) { $data['role_description'] = trim($data['role_description']); }
		if (!$update && !array_key_exists('status', $data)) { $data['status'] = 'ACTIVE'; }
		if (array_key_exists('status', $data) && (!is_string($data['status']) || !in_array($data['status'], self::STATUSES, true))) { throw HttpException::unprocessable('Role status is invalid.', 'invalid_role_status'); }
		return $data;
	}
	private function actorId(): ?string { $user = $this->context->user(); return is_int($user) || (is_string($user) && ctype_digit($user)) ? (string) $user : null; }
	private function audit(string $entity, string $action, string $entityId, array $oldValues = [], array $newValues = []): void { try { $this->audit->record($entity, $action, $entityId, $oldValues, $newValues); } catch (\Throwable) {} }
	private function requiredIdentifier(string $value, string $field): void { if ($value === '' || !ctype_digit($value) || (int) $value < 1) { throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field); } }
}