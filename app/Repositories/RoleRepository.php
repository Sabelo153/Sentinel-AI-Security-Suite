<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\HttpException;

final class RoleRepository extends Repository
{
    protected function table(): string
    {
        return 'roles';
    }

    public function userHasRole(string $userId, string $role): bool
    {
        if ($userId === '' || $role === '') {
            return false;
        }

        try {
            $row = $this->db->fetch(
                'SELECT 1
                FROM user_roles ur
                INNER JOIN roles r ON r.role_id = ur.role_id
                WHERE ur.user_id = :user_id
                  AND r.role_name = :role_name
                  AND r.status = :status
                LIMIT 1',
                [
                    'user_id' => $userId,
                    'role_name' => $role,
                    'status' => 'ACTIVE',
                ],
            );

            return $row !== null;
        } catch (\Throwable $exception) {
            throw new HttpException(503, 'Service unavailable.', 'service_unavailable', $exception);
        }
    }
}
