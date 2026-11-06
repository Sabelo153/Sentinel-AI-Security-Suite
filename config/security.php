<?php

declare(strict_types=1);

return [
	'max_json_body_bytes' => (int) env('MAX_JSON_BODY_BYTES', 4194304),
    'mfa_encryption_key' => env('ENCRYPTION_KEY'),
    'minimum_guard_version_code' => (int) env('MINIMUM_GUARD_VERSION_CODE', 16),
];
