<?php

declare(strict_types=1);

return [
    'default' => 'local',
    'disks' => [
        'local' => ['root' => dirname(__DIR__) . '/storage/app'],
        'private' => ['root' => dirname(__DIR__) . '/storage/private'],
    ],
    'max_upload_size' => (int) env('MAX_UPLOAD_SIZE', 52_428_800),
];
