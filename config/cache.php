<?php

declare(strict_types=1);

return [
    'driver' => env('CACHE_DRIVER', 'file'),
    'ttl' => (int) env('CACHE_TTL', 3600),
    'path' => dirname(__DIR__) . '/storage/cache',
];
