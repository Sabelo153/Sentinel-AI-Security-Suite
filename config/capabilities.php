<?php

declare(strict_types=1);

return [
    'plans' => [
        'FREE' => ['core_records', 'people', 'users', 'roles'],
        'ESSENTIAL' => ['core_records', 'people', 'users', 'roles', 'workforce', 'reports'],
        'PROFESSIONAL' => ['core_records', 'people', 'users', 'roles', 'workforce', 'reports', 'guard_operations', 'devices', 'gps'],
        'ENTERPRISE' => ['*'],
    ],
    'support' => ['view' => 'Tenant diagnostics and sanitised export', 'manage' => 'Audited compromised-account containment'],
    'policy_class' => Sentinel\Services\SubscriptionPolicy::class,
];
