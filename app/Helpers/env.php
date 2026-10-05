<?php

declare(strict_types=1);

use Dotenv\Dotenv;

if (!function_exists('env')) {
    /**
     * Read an environment variable with type coercion (bool/null/numeric), falling
     * back to $default when unset. Loads .env once via phpdotenv (never overwrites
     * variables already present in the process environment).
     */
    function env(string $key, mixed $default = null): mixed
    {
        static $loaded = false;

        if (!$loaded) {
            $root = dirname(__DIR__, 2);
            if (is_file($root . '/.env')) {
                Dotenv::createImmutable($root)->safeLoad();
            }
            $loaded = true;
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return env_cast((string) $value);
    }
}

if (!function_exists('env_cast')) {
    /**
     * Coerce a raw environment string into bool/null/int/float where unambiguous.
     */
    function env_cast(string $value): string|int|float|bool|null
    {
        $trimmed = trim($value);

        return match (true) {
            strcasecmp($trimmed, 'true') === 0 => true,
            strcasecmp($trimmed, 'false') === 0 => false,
            strcasecmp($trimmed, 'null') === 0 => null,
            $trimmed === '' => '',
            is_numeric($trimmed) => str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed,
            default => $value,
        };
    }
}
