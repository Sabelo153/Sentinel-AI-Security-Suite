<?php

declare(strict_types=1);

// Deliberately standalone (no Application/Database bootstrap): health must
// stay reportable even if configuration or the database is unavailable.
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'ok', 'application' => 'Sentinel AI Security Suite']);
