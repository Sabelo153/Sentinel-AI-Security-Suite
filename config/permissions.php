<?php

declare(strict_types=1);

return [
    'runtime_authority' => 'protected route metadata',
    'catalog_seed' => dirname(__DIR__) . '/database/seed/operational_permissions.sql',
    'tenant_column' => 'company_id',
    'administrator_role' => 'Company Administrator',
    'grant_all_implicitly' => false,
];
