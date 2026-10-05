<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\HttpErrorResponder;
use Sentinel\Core\HttpException;

final class HttpErrorResponderTest extends TestCase
{
    public function testControlledExceptionKeepsSafeStatusMessageAndCode(): void
    {
        $response = HttpErrorResponder::respond(
            HttpException::forbidden('Permission denied.', 'permission_denied'),
        );

        self::assertSame(403, $response->status());
        self::assertSame('no-store', $response->header('Cache-Control'));
        self::assertSame([
            'message' => 'Permission denied.',
            'code' => 'permission_denied',
        ], json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUnexpectedExceptionReturnsGenericUnavailableResponse(): void
    {
        $logged = null;
        $response = HttpErrorResponder::respond(
            new \RuntimeException('private detail'),
            static function (string $message) use (&$logged): void { $logged = $message; },
        );

        self::assertSame(503, $response->status());
        self::assertSame([
            'message' => 'Service temporarily unavailable.',
            'code' => 'service_unavailable',
        ], json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('private detail', $response->body());
        self::assertStringContainsString('private detail', (string) $logged);
    }
}
