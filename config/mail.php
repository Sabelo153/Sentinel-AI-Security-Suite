<?php

declare(strict_types=1);

return [
    'driver' => env('MAIL_DRIVER', 'smtp'),
    'host' => env('MAIL_HOST', 'localhost'),
    'port' => (int) env('MAIL_PORT', 587),
    'username' => env('MAIL_USERNAME', ''),
    'password' => env('MAIL_PASSWORD', ''),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-reply@sentinel.local'),
        'name' => env('MAIL_FROM_NAME', 'Sentinel'),
    ],
];
