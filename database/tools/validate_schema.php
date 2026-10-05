<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$runner = $root . '/schema/000_run_all.sql';
$sources = preg_match_all('/^SOURCE\s+([a-z0-9_]+\.sql);\s*$/mi', (string) file_get_contents($runner), $matches) ? $matches[1] : [];
$errors = [];
$positions = [];
foreach ($sources as $index => $file) {
	$positions[$file] = $index;
	if (!is_file($root . '/schema/' . $file)) $errors[] = "Runner source is missing: {$file}";
}
foreach (glob($root . '/schema/0*.sql') ?: [] as $path) {
	if (basename($path) === '000_run_all.sql') continue;
	$sql = (string) file_get_contents($path);
	if (preg_match_all('/\b(?:CONSTRAINT|KEY|INDEX)\s+`?([A-Za-z0-9_]+)`?/i', $sql, $identifiers) === false) {
		$errors[] = 'Could not validate SQL identifiers in ' . basename($path) . '.';
	} else {
		foreach ($identifiers[1] as $identifier) {
			if (strlen($identifier) > 64) $errors[] = 'SQL identifier exceeds 64 characters in ' . basename($path) . ': ' . $identifier;
		}
	}
	if (preg_match('/\btenant_id\b|\bguard_id\b/i', $sql) === 1 && preg_match('/^(053|054|055|056|057|058|059|060|061|062)_/', basename($path)) === 1) $errors[] = 'Forbidden identifier in new canonical schema: ' . basename($path);
}
foreach (range(53, 62) as $number) {
	$prefix = str_pad((string) $number, 3, '0', STR_PAD_LEFT) . '_';
	$found = array_values(array_filter($sources, static fn (string $source): bool => str_starts_with($source, $prefix)));
	if (count($found) !== 1) $errors[] = "Runner must include exactly one {$prefix} schema.";
}
if (($positions['053_devices.sql'] ?? -1) > ($positions['054_device_assignments.sql'] ?? -1) || ($positions['054_device_assignments.sql'] ?? -1) > ($positions['055_device_sessions.sql'] ?? -1)) $errors[] = 'Device schemas are not dependency ordered.';
$declaredTables = [];
foreach ($sources as $source) {
	$path = $root . '/schema/' . $source;
	$sql = (string) file_get_contents($path);
	preg_match_all('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+([a-z_][a-z0-9_]*)/i', $sql, $tableMatches);
	foreach ($tableMatches[1] as $table) $declaredTables[$table] = true;
	if (preg_match('/^(053|054|055|056|057|058|059|060|061|062)_/', $source) === 1) {
		preg_match_all('/REFERENCES\s+([a-z_][a-z0-9_]*)\s*\(/i', $sql, $referenceMatches);
		foreach ($referenceMatches[1] as $target) if (!isset($declaredTables[$target])) $errors[] = "Foreign-key target is not dependency ordered: {$source} -> {$target}";
	}
}
if ($errors !== []) { fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL); exit(1); }
fwrite(STDOUT, 'Schema validation passed for ' . count($sources) . ' runner sources.' . PHP_EOL);
