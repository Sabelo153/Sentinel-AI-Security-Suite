<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    public function testGeneratesCanonicalFormat(): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            uuid4(),
        );
    }

    public function testSetsVersion4Marker(): void
    {
        self::assertSame('4', uuid4()[14]);
    }

    public function testSetsRfc4122VariantMarker(): void
    {
        self::assertContains(uuid4()[19], ['8', '9', 'a', 'b']);
    }

    public function testGeneratesDistinctValuesAcrossRepeatedCalls(): void
    {
        $values = array_map(static fn () => uuid4(), range(1, 50));

        self::assertCount(50, array_unique($values));
    }
}
