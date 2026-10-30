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
    'managed_android' => ['permissions' => ['managed_android.view', 'managed_android.manage'], 'enforcement' => 'Provisioned company device owner; management requires MFA'],
    'policy_class' => Sentinel\Services\SubscriptionPolicy::class,
];
