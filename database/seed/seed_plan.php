<?php

declare(strict_types=1);

$root = __DIR__;
$production = [
    $root . '/001_roles_seed.sql',
    $root . '/002_permissions_seed.sql',
    $root . '/003_role_permissions_seed.sql',
    $root . '/004_company_settings_seed.sql',
    $root . '/operational_permissions.sql',
];

return [
    'production' => $production,
    'development' => [...$production, ...array_values(array_filter(glob($root . '/development/*.sql') ?: [], 'is_file'))],
];
