<?php

declare(strict_types=1);

require __DIR__ . '/Migration.php';
require __DIR__ . '/Migrator.php';

$options = getopt('', ['env::', 'until::', 'domain::', 'dry-run', 'rollback']);
$environment = (string) ($options['env'] ?? getenv('APP_ENV') ?: 'development');
$supported = ['development', 'testing', 'staging', 'production'];
if (!in_array($environment, $supported, true)) {
    fwrite(STDERR, 'Unsupported environment: ' . $environment . PHP_EOL);
    exit(1);
}
if (isset($options['rollback'])) {
    fwrite(STDERR, "Rollback is intentionally unavailable for the canonical schema. Restore a tested backup or use a reviewed forward migration.\n");
    exit(2);
}
if (isset($options['domain'])) {
    fwrite(STDERR, "Domain filtering is not supported by the dependency-ordered canonical schema. Use --until=<filename> when reviewing a partial plan.\n");
    exit(2);
}

try {
    $migrator = new Migrator(require __DIR__ . '/migration_plan.php');
    $plan = $migrator->validatedPlan(isset($options['until']) ? (string) $options['until'] : null);
    foreach ($plan as $index => $migration) {
        fwrite(STDOUT, sprintf("%02d  %s\n", $index + 1, $migration->name));
    }
    fwrite(STDOUT, sprintf("Validated %d canonical schema sources for %s. No database changes were made.\n", count($plan), $environment));
    fwrite(STDOUT, "Execution requires the reviewed procedure in docs/OPERATIONS_RELEASE_RUNBOOK.md.\n");
    exit(0);
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
