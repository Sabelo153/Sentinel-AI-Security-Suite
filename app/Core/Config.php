<?php

declare(strict_types=1);

namespace Sentinel\Core;

/**
 * Dot-notation configuration repository. Holds config arrays in memory only;
 * no global mutable state is used (each Application owns its own instance).
 */
final class Config
{
    /** @param array<string, mixed> $items */
    public function __construct(private array $items = [])
    {
    }

    /**
     * Load every *.php config file in $path, keyed by filename (e.g. app.php -> "app").
     */
    public static function fromDirectory(string $path): self
    {
        $config = new self();
        $config->loadDirectory($path);

        return $config;
    }

    public function loadDirectory(string $path): void
    {
        $path = rtrim($path, '/\\');

        foreach (glob($path . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            $this->items[$key] = require $file;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function has(string $key): bool
    {
        $missing = new \stdClass();

        return $this->get($key, $missing) !== $missing;
    }

    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &$this->items;

        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                $target[$segment] = $value;
                break;
            }

            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target = &$target[$segment];
        }
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->items;
    }
}
