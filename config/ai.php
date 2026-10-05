<?php
declare(strict_types=1);
return [
    'enabled' => filter_var(env('AI_HELP_ENABLED', false), FILTER_VALIDATE_BOOL),
    'base_url' => env('AI_HELP_BASE_URL', 'http://127.0.0.1:11434'),
    'model' => env('AI_HELP_MODEL', 'qwen2.5:0.5b'),
];
