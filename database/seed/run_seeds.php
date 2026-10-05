<?php

declare(strict_types=1);

$options = getopt('', ['env::', 'profile::', 'dry-run']);
$profile = strtolower((string) ($options['profile'] ?? 'production'));
$plans = require __DIR__ . '/seed_plan.php';
if (!isset($plans[$profile])) {
    fwrite(STDERR, 'Unknown seed profile. Use production or development.' . PHP_EOL);
    exit(1);
}

$files = $plans[$profile];
foreach ($files as $index => $file) {
    if (!is_file($file) || !is_readable($file)) {
        fwrite(STDERR, 'Seed source is missing or unreadable: ' . $file . PHP_EOL);
        exit(1);
    }
    fwrite(STDOUT, sprintf("%02d  %s\n", $index + 1, str_replace('\\', '/', substr($file, strlen(dirname(__DIR__, 2)) + 1))));
}

fwrite(STDOUT, sprintf("Validated %d files in the %s seed profile. No database changes were made.\n", count($files), $profile));
fwrite(STDOUT, "Apply these files only through the reviewed staging/production procedure in docs/OPERATIONS_RELEASE_RUNBOOK.md.\n");
