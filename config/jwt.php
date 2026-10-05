<?php

declare(strict_types=1);

return [
    'algorithm' => env('JWT_ALGORITHM', 'HS256'),
    // No default on purpose: the JWT service must fail closed when this is unset.
    'secret' => env('JWT_SECRET'),
    'issuer' => env('JWT_ISSUER', 'sentinel'),
    'ttl' => (int) env('JWT_TTL_SECONDS', 3600),
];
