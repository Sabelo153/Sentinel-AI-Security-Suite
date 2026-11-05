<?php

declare(strict_types=1);

return [
    'plans' => [
        'FREE' => ['core_records', 'people', 'users', 'roles'],
        'ESSENTIAL' => ['core_records', 'people', 'users', 'roles', 'workforce', 'reports', 'compliance', 'equipment', 'client_portal', 'payroll_export'],
        'PROFESSIONAL' => ['core_records', 'people', 'users', 'roles', 'workforce', 'reports', 'compliance', 'equipment', 'client_portal', 'payroll_export', 'guard_operations', 'devices', 'gps', 'escalations', 'managed_android'],
        'ENTERPRISE' => ['*'],
    ],
    'support' => ['view' => 'Tenant diagnostics and sanitised export', 'manage' => 'Audited compromised-account containment'],
    'field_operations' => ['compliance', 'equipment', 'client_portal', 'payroll_export', 'escalations'],
    'control_room' => ['dispatch', 'lone_worker', 'rostering', 'gate_register', 'billing', 'device_health', 'readiness'],
    'site_assurance' => ['site_instructions', 'handovers', 'training', 'evidence', 'service_levels', 'maintenance', 'retention'],
    'operational_readiness' => ['offline_inspections','key_register','replacement_suggestions','emergency_procedures','workflow_languages','company_workspace'],
    'client_workflows' => ['site_access','site_workspace','inspection_forms','client_requests','availability','report_reviews','quotations'],
    'reporting' => ['photo_reports','client_reports'],
    'managed_android' => ['permissions' => ['managed_android.view', 'managed_android.manage'], 'enforcement' => 'Provisioned company device owner; management requires MFA'],
    'policy_class' => Sentinel\Services\SubscriptionPolicy::class,
];
