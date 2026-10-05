<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\HttpException;
use Sentinel\Security\JwtService;

final class JwtServiceTest extends TestCase
{
    private const SECRET = 'PUBLIC_EXAMPLE_CONFIGURE_LOCALLY';

    private function service(int $ttl = 3600, string $issuer = 'test-suite'): JwtService
    {
        return new JwtService(new Config(['jwt' => [
            'algorithm' => 'HS256', 'secret' => self::SECRET, 'issuer' => $issuer, 'ttl' => $ttl,
        ]]));
    }

    public function testCreatesAndValidatesMinimalTokenClaims(): void
    {
        $service = $this->service();
        $token = $service->createToken('user-123');
        $parsed = $service->validateToken($token);

        self::assertSame('user-123', $parsed->claims()->get('sub'));
        self::assertSame('test-suite', $parsed->claims()->get('iss'));
        self::assertTrue($parsed->claims()->has('iat'));
        self::assertTrue($parsed->claims()->has('exp'));
        self::assertTrue($parsed->claims()->has('jti'));
        self::assertSame('HS256', $parsed->headers()->get('alg'));
        self::assertStringNotContainsString('password', $token);
        self::assertStringNotContainsString(self::SECRET, $token);
    }

    public function testJtiIsUnique(): void
    {
        $service = $this->service();
        $first = $service->validateToken($service->createToken('user'))->claims()->get('jti');
        $second = $service->validateToken($service->createToken('user'))->claims()->get('jti');

        self::assertNotSame($first, $second);
    }

    public function testTamperedMalformedExpiredAndWrongIssuerTokensFailSafely(): void
    {
        $service = $this->service();
        $token = $service->createToken('user');
        $parts = explode('.', $token);
        $parts[1] = rtrim(strtr(base64_encode('{"iss":"test-suite","sub":"user","iat":1,"exp":2,"jti":"x"}'), '+/', '-_'), '=');

        foreach (array_merge([implode('.', $parts), 'not-a-token'], [$this->service(3600, 'other')->createToken('user')]) as $invalid) {
            try {
                $service->validateToken($invalid);
                self::fail('Expected token validation to fail.');
            } catch (HttpException $exception) {
                self::assertSame('authentication_failed', $exception->errorCode());
                self::assertSame(401, $exception->statusCode());
                self::assertSame('Authentication failed.', $exception->getMessage());
            }
        }
    }

    public function testMissingSigningConfigurationFailsClosed(): void
    {
        $this->expectException(\LogicException::class);

        new JwtService(new Config(['jwt' => ['algorithm' => 'HS256', 'issuer' => 'test', 'ttl' => 3600]]));
    }

    public function testInvalidUserIdentifierAndLifetimeAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service()->createToken('bad user id');
    }
}