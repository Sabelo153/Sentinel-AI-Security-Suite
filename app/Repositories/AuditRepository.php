<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class AuditRepository extends Repository
{
    public function __construct(Database $db, ?RuntimeContext $context = null) { parent::__construct($db, $context); }
    protected function table(): string { return 'audit_logs'; }

    public function record(array $data): void
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute(
            'INSERT INTO audit_logs (company_id, user_id, entity_name, entity_id, action_name, action_status, ip_address, user_agent, old_values, new_values, metadata) VALUES (:company_id, :user_id, :entity_name, :entity_id, :action_name, :action_status, :ip_address, :user_agent, :old_values, :new_values, :metadata)',
            [
                'company_id' => $companyId,
                'user_id' => $data['user_id'] ?? null,
                'entity_name' => $data['entity_name'],
                'entity_id' => $data['entity_id'] ?? null,
                'action_name' => $data['action_name'],
                'action_status' => $data['action_status'] ?? 'SUCCESS',
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'old_values' => $data['old_values'] ?? null,
                'new_values' => $data['new_values'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ],
        );
    }
}