<?php

declare(strict_types=1);

namespace Sentinel\Security;

use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PermissionRepository;
use Sentinel\Services\SubscriptionService;

final class AuthorizationService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly RuntimeContext $context,
        private readonly ?SubscriptionService $subscriptions = null,
    ) {
    }

    public function isAllowed(string $userId, string $permission): bool
    {
        if ($userId === '' || $permission === '') {
            return false;
        }
        if (in_array($permission, ['support.manage', 'managed_android.manage'], true) && !$this->context->mfaVerified()) {
            return false;
        }

        if ($this->subscriptions !== null && !$this->subscriptions->allows($permission)) {
            return false;
        }
        return $this->permissionRepository->userHasPermission($userId, $permission);
    }

    public function requirePermission(string $userId, string $permission): void
    {
        if ($userId === '') {
            throw new HttpException(401, 'Authentication failed.', 'authentication_failed');
        }

        if (!$this->isAllowed($userId, $permission)) {
            throw new HttpException(403, 'Forbidden.', 'forbidden');
        }
    }
}
