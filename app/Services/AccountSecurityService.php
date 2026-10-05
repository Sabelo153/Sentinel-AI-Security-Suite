<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\UserRepository;

final class AccountSecurityService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly RuntimeContext $context,
        private readonly ?AuditService $audit = null,
    ) {
    }

    public function changePassword(string $userId, string $currentPassword, string $newPassword): void
    {
        if ($currentPassword === '' || strlen($newPassword) < 12) {
            throw HttpException::unprocessable('Password requirements are not satisfied.', 'invalid_password');
        }
        $user = $this->users->findCredentialsForCompany($userId);
        if ($user === null || !password_verify($currentPassword, (string) $user['password_hash'])) {
            throw HttpException::unprocessable('Current password is invalid.', 'invalid_current_password');
        }
        try {
            if (!$this->users->changePasswordForCompany($userId, password_hash($newPassword, PASSWORD_DEFAULT))) {
                throw HttpException::notFound('User not found.', 'user_not_found');
            }
            $this->audit('user', 'password_changed', $userId);
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw new HttpException(503, 'Account security service unavailable.', 'account_security_unavailable', $e);
        }
    }

    public function unlockUser(string $userId): void
    {
        try {
            if (!$this->users->unlockForCompany($userId)) {
                throw HttpException::notFound('User not found.', 'user_not_found');
            }
            $this->audit('user', 'unlocked', $userId);
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw new HttpException(503, 'Account security service unavailable.', 'account_security_unavailable', $e);
        }
    }

    private function audit(string $entity, string $action, string $entityId): void
    {
        try {
            $this->audit?->record($entity, $action, $entityId);
        } catch (\Throwable) {
        }
    }
}