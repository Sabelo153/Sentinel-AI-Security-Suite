<?php

declare(strict_types=1);

return [
    // The console is for this PC during development or an authorised support
    // session. It is deliberately off by default in production.
    'diagnostics_enabled' => filter_var(
        env('SUPPORT_DIAGNOSTICS_ENABLED', env('APP_ENV', 'production') !== 'production'),
        FILTER_VALIDATE_BOOL,
    ),
];
