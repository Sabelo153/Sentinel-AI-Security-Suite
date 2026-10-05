<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Response;

final class ResponseTest extends TestCase
{
    public function testJsonDefaultsToStatus200(): void
    {
        $response = Response::json(['ok' => true]);

        self::assertSame(200, $response->status());
        self::assertSame('{"ok":true}', $response->body());
        self::assertSame('application/json; charset=utf-8', $response->header('Content-Type'));
    }

    public function testJsonAcceptsCustomStatus(): void
    {
        $response = Response::json(['success' => false], 404);

        self::assertSame(404, $response->status());
    }

    public function testWithHeaderReturnsNewInstanceLeavingOriginalUnchanged(): void
    {
        $response = Response::json(['ok' => true]);
        $withHeader = $response->withHeader('X-Request-Id', 'abc-123');

        self::assertSame('abc-123', $withHeader->header('X-Request-Id'));
        self::assertNull($response->header('X-Request-Id'));
    }

    public function testWithStatusReturnsNewInstanceLeavingOriginalUnchanged(): void
    {
        $response = Response::json(['ok' => true]);
        $changed = $response->withStatus(500);

        self::assertSame(500, $changed->status());
        self::assertSame(200, $response->status());
    }

    public function testRawResponseKeepsExactBody(): void
    {
        $response = Response::raw('plain text', 200, ['Content-Type' => 'text/plain']);

        self::assertSame('plain text', $response->body());
        self::assertSame('text/plain', $response->header('Content-Type'));
    }

    public function testSendEmitsStatusHeadersAndBodyThroughTestSeams(): void
    {
        $response = Response::json(['ok' => true], 201)->withHeader('X-Request-Id', 'req-1');

        $capturedStatus = null;
        $capturedHeaders = [];

        $this->expectOutputString('{"ok":true}');
        $response->send(
            function (int $status) use (&$capturedStatus): void {
                $capturedStatus = $status;
            },
            function (string $name, string $value) use (&$capturedHeaders): void {
                $capturedHeaders[$name] = $value;
            },
        );

        self::assertSame(201, $capturedStatus);
        self::assertSame([
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Request-Id' => 'req-1',
        ], $capturedHeaders);
    }
}
