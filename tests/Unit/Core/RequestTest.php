<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\HttpException;
use Sentinel\Core\Request;

final class RequestTest extends TestCase
{
    public function testCreateExposesMethodAndPath(): void
    {
        $request = Request::create('get', '/clients/42');

        self::assertSame('GET', $request->method());
        self::assertSame('/clients/42', $request->path());
    }

    public function testQueryParameters(): void
    {
        $request = Request::create('GET', '/clients', ['page' => '2']);

        self::assertSame('2', $request->query('page'));
        self::assertSame(1, $request->query('missing', 1));
        self::assertSame(['page' => '2'], $request->queryParams());
    }

    public function testHeaderLookupIsCaseInsensitive(): void
    {
        $request = Request::create('GET', '/', [], ['Authorization' => 'Bearer abc']);

        self::assertSame('Bearer abc', $request->header('authorization'));
        self::assertSame('Bearer abc', $request->header('AUTHORIZATION'));
        self::assertNull($request->header('X-Missing'));
    }

    public function testPostRequestWithJsonBody(): void
    {
        $request = Request::create('POST', '/clients', [], ['Content-Type' => 'application/json'], '{"name":"Acme"}');

        self::assertSame('POST', $request->method());
        self::assertSame(['name' => 'Acme'], $request->json());
    }

    public function testMissingBodyDecodesToEmptyArray(): void
    {
        $request = Request::create('GET', '/');

        self::assertSame([], $request->json());
    }

    public function testInvalidJsonThrowsHttpException(): void
    {
        $request = Request::create('POST', '/clients', [], [], '{not-json');

        $this->expectException(HttpException::class);
        $request->json();
    }

    public function testInvalidJsonExceptionHasBadRequestStatus(): void
    {
        $request = Request::create('POST', '/clients', [], [], '{not-json');

        try {
            $request->json();
            self::fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            self::assertSame(400, $e->statusCode());
        }
    }

    public function testExplicitUnsupportedContentTypeAndOversizedJsonAreRejected(): void
    {
        $plainText = Request::create('POST', '/clients', [], ['Content-Type' => 'text/plain'], '{"name":"Acme"}');
        try {
            $plainText->assertJsonBodyIsAcceptable(100);
            self::fail('Expected unsupported media type.');
        } catch (HttpException $exception) {
            self::assertSame([415, 'unsupported_media_type'], [$exception->statusCode(), $exception->errorCode()]);
        }

        try {
            Request::create('POST', '/clients', [], ['Content-Type' => 'application/json'], str_repeat('a', 101))->assertJsonBodyIsAcceptable(100);
            self::fail('Expected body size failure.');
        } catch (HttpException $exception) {
            self::assertSame([413, 'request_too_large'], [$exception->statusCode(), $exception->errorCode()]);
        }
    }

    public function testRequestIdIsNullByDefaultAndSettableViaClone(): void
    {
        $request = Request::create('GET', '/');
        self::assertNull($request->requestId());

        $withId = $request->withRequestId('req-123');
        self::assertSame('req-123', $withId->requestId());
        self::assertNull($request->requestId(), 'original request must remain unchanged');
    }

    public function testFromGlobalsBuildsRequestFromSuperglobals(): void
    {
        $originalServer = $_SERVER;
        $originalGet = $_GET;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/health?verbose=1';
        $_SERVER['HTTP_X_TEST_HEADER'] = 'value';
        $_GET = ['verbose' => '1'];

        try {
            $request = Request::fromGlobals();

            self::assertSame('GET', $request->method());
            self::assertSame('/health', $request->path());
            self::assertSame('1', $request->query('verbose'));
            self::assertSame('value', $request->header('X-Test-Header'));
        } finally {
            $_SERVER = $originalServer;
            $_GET = $originalGet;
        }
    }
}
