<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\HttpException;

final class PermissionRepository extends Repository
{
    protected function table(): string
    {
        return 'permissions';
    }

    public function userHasPermission(string $userId, string $permission): bool
    {
        if ($userId === '' || $permission === '') {
            return false;
        }

        $companyId = $this->context?->requireTenant() ?? throw HttpException::forbidden('Tenant context is required.', 'tenant_required');
        try {
            $row = $this->db->fetch(
                'SELECT 1
                FROM user_roles ur INNER JOIN users u ON u.user_id = ur.user_id AND u.company_id = ur.company_id
                INNER JOIN roles r ON r.role_id = ur.role_id AND r.company_id = ur.company_id
                INNER JOIN role_permissions rp ON rp.role_id = r.role_id AND rp.company_id = r.company_id
                INNER JOIN permissions p ON p.permission_id = rp.permission_id AND p.company_id = rp.company_id
                WHERE ur.user_id = :user_id AND ur.company_id = :company_id AND u.status = :user_status
                  AND p.permission_code = :permission_code
                  AND r.status = :role_status
                  AND p.status = :permission_status
                LIMIT 1',
                [
                    'user_id' => $userId, 'company_id' => $companyId, 'user_status' => 'ACTIVE',
                    'permission_code' => $permission,
                    'role_status' => 'ACTIVE',
                    'permission_status' => 'ACTIVE',
                ],
            );

            return $row !== null;
        } catch (\Throwable $exception) {
            throw new HttpException(503, 'Service unavailable.', 'service_unavailable', $exception);
        }
    }
}
