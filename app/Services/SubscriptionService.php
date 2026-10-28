<?php

declare(strict_types=1);

namespace Sentinel\Services;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Core\HttpException;

final class SubscriptionService
{
    public function __construct(private readonly Database $db, private readonly RuntimeContext $context) {}

    /** @return array<string,mixed>|null */
    public function current(): ?array
    {
        $companyId = (string) $this->context->requireTenant();
        try {
            return $this->db->fetch('SELECT plan_code, subscription_status, trial_ends_at, current_period_ends_at FROM company_subscriptions WHERE company_id = :company_id LIMIT 1', ['company_id' => $companyId]);
        } catch (\PDOException) {
            return null;
        }
    }

    public function allows(string $permission): bool
    {
        $subscription = $this->current();
        if ($subscription === null) return true; // Existing tenants are grandfathered until migrated.
        if (in_array($permission, ['subscriptions.view', 'users.password_change', 'support.view', 'support.manage'], true)) return true;
        if (!in_array($subscription['subscription_status'], ['ACTIVE', 'TRIALING'], true)) return false;
        if ($subscription['subscription_status'] === 'TRIALING' && $subscription['trial_ends_at'] !== null && strtotime((string) $subscription['trial_ends_at']) < time()) return false;
        return SubscriptionPolicy::allows((string) $subscription['plan_code'], $permission);
    }

    public function assertCanCreate(string $resource): void
    {
        $subscription=$this->current();
        if($subscription===null)return;
        $plan=(string)$subscription['plan_code'];$limit=SubscriptionPolicy::plans()[$plan]['limits'][$resource]??null;
        if($limit===null)return;
        [$table,$where]=match($resource){'users'=>['users','status <> :excluded'],'sites'=>['sites','status NOT IN (:excluded, :excluded_two)'],default=>throw new \LogicException('Unsupported subscription resource.')};
        $params=['company_id'=>(string)$this->context->requireTenant(),'excluded'=>$resource==='users'?'ARCHIVED':'CLOSED'];
        if($resource==='sites')$params['excluded_two']='INACTIVE';
        $row=$this->db->fetch("SELECT COUNT(*) AS total FROM {$table} WHERE company_id = :company_id AND {$where}",$params);
        if((int)($row['total']??0)>=$limit)throw HttpException::conflict("The {$plan} plan allows up to {$limit} active {$resource}. Upgrade the plan or deactivate an existing record.",'subscription_limit_reached');
    }
}
