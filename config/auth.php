<?php

declare(strict_types=1);

return [
    'guard' => env('AUTH_GUARD', 'api'),
    'token_driver' => env('AUTH_TOKEN_DRIVER', 'jwt'),
    'token_ttl' => (int) env('AUTH_TOKEN_TTL', 3600),
];
