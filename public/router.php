<?php

declare(strict_types=1);

// PHP's development server does not apply the production web-server rules.
// Let it serve existing public assets directly and send clean application URLs
// through the normal front controller.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$candidate = is_string($path) ? __DIR__ . $path : '';

if ($path !== '/' && $candidate !== '' && is_file($candidate)) {
    return false;
}

require __DIR__ . '/index.php';
