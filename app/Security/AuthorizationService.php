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
        private readonly ?\Sentinel\Services\SubcontractorPolicy $subcontractors = null,
    ) {
    }

    public function isAllowed(string $userId, string $permission): bool
    {
        if ($userId === '' || $permission === '') {
            return false;
        }
        if($permission!=='users.password_change'&&$this->subcontractors&&!$this->subcontractors->allowed($userId))return false;
        if (in_array($permission, ['support.manage', 'managed_android.manage', 'retention.manage', 'integrations.manage', 'backups.manage', 'pilot_setup.manage'], true) && !$this->context->mfaVerified()) {
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
            'key_register.view','key_register.manage','key_register.self','emergency_procedures.view','emergency_procedures.manage','workflow_languages.self','workflow_languages.view','company_branding.read',
            'radio.self','radio.manage','control_map.view','service_reviews.view','service_reviews.manage','service_reviews.client','customer_support.self','customer_support.view','customer_support.manage','release_notices.view','operational_alerts.view','operational_alerts.manage','shift_offers.view','shift_offers.manage','shift_offers.self','visitor_invites.view','visitor_invites.manage','visitor_invites.client','visitor_invites.gate','fleet.view','fleet.manage','fleet.self','learning.view','learning.manage','learning.self','lost_found.view','lost_found.manage','lost_found.dispose','investigations.view','investigations.manage','investigations.review','stock.view','stock.manage','stock.adjust','stock.self','alarm_response.view','alarm_response.manage','guard_transport.view','guard_transport.manage','post_orders.view','post_orders.manage','continuity.view','continuity.manage','equipment_checks.view','equipment_checks.manage','control_handover.view','control_handover.manage','extra_services.view','extra_services.manage','client_mobilisation.view','client_mobilisation.manage','risk_assessments.view','risk_assessments.manage','alarm_response.self','guard_transport.self','post_orders.self','continuity.self','extra_services.client','client_mobilisation.client','risk_assessments.client',
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
