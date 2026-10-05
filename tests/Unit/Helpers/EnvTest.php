<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    private array $keys = [];

    protected function tearDown(): void
    {
        foreach ($this->keys as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
        $this->keys = [];
    }

    private function set(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $this->keys[] = $key;
    }

    public function testReturnsDefaultWhenVariableIsUnset(): void
    {
        self::assertSame('fallback', env('SENTINEL_TEST_UNSET_VAR', 'fallback'));
    }

    public function testConvertsBooleanStrings(): void
    {
        $this->set('SENTINEL_TEST_BOOL_TRUE', 'true');
        $this->set('SENTINEL_TEST_BOOL_FALSE', 'FALSE');

        self::assertTrue(env('SENTINEL_TEST_BOOL_TRUE'));
        self::assertFalse(env('SENTINEL_TEST_BOOL_FALSE'));
    }

    public function testConvertsNullString(): void
    {
        $this->set('SENTINEL_TEST_NULL', 'null');

        self::assertNull(env('SENTINEL_TEST_NULL', 'default'));
    }

    public function testConvertsNumericStrings(): void
    {
        $this->set('SENTINEL_TEST_INT', '42');
        $this->set('SENTINEL_TEST_FLOAT', '3.14');

        self::assertSame(42, env('SENTINEL_TEST_INT'));
        self::assertSame(3.14, env('SENTINEL_TEST_FLOAT'));
    }

    public function testLeavesPlainStringsUnchanged(): void
    {
        $this->set('SENTINEL_TEST_STRING', 'hello-world');

        self::assertSame('hello-world', env('SENTINEL_TEST_STRING'));
    }
}
