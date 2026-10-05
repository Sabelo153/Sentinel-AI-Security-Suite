<?php

declare(strict_types=1);

$schemaDirectory = dirname(__DIR__) . '/schema';
$runner = $schemaDirectory . '/000_run_all.sql';
$contents = is_file($runner) ? (string) file_get_contents($runner) : '';
preg_match_all('/^SOURCE\s+([a-z0-9_]+\.sql);\s*$/mi', $contents, $matches);

return array_map(
    static fn (string $name): array => ['name' => $name, 'path' => $schemaDirectory . '/' . $name],
    $matches[1] ?? [],
);
