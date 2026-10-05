<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\HttpException;
use Sentinel\Core\Request;
use Sentinel\Security\BearerToken;

final class BearerTokenTest extends TestCase
{
    public function testExtractsBearerToken(): void
    {
        self::assertSame('abc123', BearerToken::fromRequest(Request::create('GET', '/', [], ['Authorization' => 'Bearer abc123'])));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidHeaders')]
    public function testRejectsMissingMalformedAndUnexpectedAuthorizationHeaders(?string $header): void
    {
        $headers = $header === null ? [] : ['Authorization' => $header];

        try {
            BearerToken::fromRequest(Request::create('GET', '/', [], $headers));
            self::fail('Expected authentication failure.');
        } catch (HttpException $exception) {
            self::assertSame(401, $exception->statusCode());
            self::assertSame('authentication_failed', $exception->errorCode());
        }
    }

    /** @return array<string, array{0: string|null}> */
    public static function invalidHeaders(): array
    {
        return ['missing' => [null], 'empty' => [''], 'basic' => ['Basic abc'], 'bare' => ['Bearer'], 'extra' => ['Bearer abc def']];
    }
}