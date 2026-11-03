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
        if (in_array($permission, ['support.manage', 'managed_android.manage', 'retention.manage'], true) && !$this->context->mfaVerified()) {
            return false;
        }

        if ($this->subscriptions !== null && !$this->subscriptions->allows($permission)) {
            return false;
        }
        if(!$this->permissionRepository->userHasPermission($userId,$permission))return false;
        // A restricted role wins over any additional broad role. Only routes
        // whose services enforce site/self scope are usable by these accounts.
        if($this->permissionRepository->userHasPermission($userId,'site_scope.restricted'))return in_array($permission,[
            'site_workspace.view','inspection_forms.view','inspection_forms.manage',
            'client_requests.view','client_requests.manage','client_requests.client',
            'report_reviews.view','report_reviews.client','availability.self','users.password_change',
        ],true);
        return true;
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
