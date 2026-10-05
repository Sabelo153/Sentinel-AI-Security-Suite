<?php

declare(strict_types=1);

namespace Sentinel\Core;

/**
 * Minimal service locator. No reflection-based autowiring — factories are
 * explicit closures, which keeps resolution predictable and debuggable.
 *
 * @phpstan-type Factory callable(self): mixed
 */
final class Container
{
    /** @var array<string, callable(self): mixed> */
    private array $bindings = [];

    /** @var array<string, true> */
    private array $singletons = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /**
     * Register a factory that runs once per make() call.
     *
     * @param callable(self): mixed $factory
     */
    public function bind(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
        unset($this->singletons[$id], $this->instances[$id]);
    }

    /**
     * Register a factory whose result is cached after the first resolution.
     *
     * @param callable(self): mixed $factory
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
        $this->singletons[$id] = true;
        unset($this->instances[$id]);
    }

    /**
     * Register an already-constructed value; make() always returns it as-is.
     */
    public function instance(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances) || isset($this->bindings[$id]);
    }

    public function make(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!isset($this->bindings[$id])) {
            throw new \RuntimeException("Unable to resolve service \"{$id}\": no binding registered.");
        }

        $value = ($this->bindings[$id])($this);

        if (isset($this->singletons[$id])) {
            $this->instances[$id] = $value;
        }

        return $value;
    }
}
