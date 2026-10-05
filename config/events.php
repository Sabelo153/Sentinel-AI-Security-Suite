<?php

declare(strict_types=1);

return [
    'listeners' => [],
    'queue_driver' => env('QUEUE_DRIVER', 'sync'),
    'retry_attempts' => 3,
];
