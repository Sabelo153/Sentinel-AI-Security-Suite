<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Application;
use Sentinel\Core\Config;
use Sentinel\Core\HttpException;
use Sentinel\Core\Request;
use Sentinel\Security\JwtService;

final class ProtectedRouteTest extends TestCase
{
    private function application(): Application
    {
        $app = Application::create(dirname(__DIR__, 3))->bootstrap();
        $app->container()->instance('jwt', new JwtService(new Config(['jwt' => [
            'algorithm' => 'HS256',
            'secret' => 'test-secret-that-is-at-least-32-bytes-long',
            'issuer' => 'test',
            'ttl' => 3600,
        ]])));

        return $app;
    }

    public function testPublicRoutesDoNotRequireAuthentication(): void
    {
        $app = $this->application();

        self::assertSame(200, $app->handle(Request::create('GET', '/'))->status());
        self::assertSame(200, $app->handle(Request::create('GET', '/api/v1/status'))->status());
    }

    public function testProtectedMeRouteRejectsMissingAndMalformedTokensBeforeHandler(): void
    {
        foreach ([[], ['Authorization' => 'Bearer malformed']] as $headers) {
            try {
                $this->application()->handle(Request::create('GET', '/api/v1/auth/me', [], $headers));
                self::fail('Expected authentication failure.');
            } catch (HttpException $exception) {
                self::assertSame(401, $exception->statusCode());
                self::assertSame('authentication_failed', $exception->errorCode());
                self::assertSame('Authentication failed.', $exception->getMessage());
            }
        }
    }

    public function testValidTokenReachesMeRouteWithOnlyContextUserId(): void
    {
        $app = $this->application();
        $token = $app->jwt()->createToken('user-42');

        $response = $app->handle(Request::create('GET', '/api/v1/auth/me', [], ['Authorization' => 'Bearer ' . $token]));
        $body = json_decode($response->body(), true);

        self::assertSame(200, $response->status());
        self::assertSame(['data' => ['user_id' => 'user-42']], $body);
        self::assertTrue($app->runtimeContext()->isAuthenticated());
        self::assertSame('user-42', $app->runtimeContext()->user());
        self::assertNull($app->runtimeContext()->tenantId());
        self::assertNotSame($token, $app->runtimeContext()->user());
    }

    public function testAuthenticationStateDoesNotLeakBetweenRequests(): void
    {
        $app = $this->application();
        $token = $app->jwt()->createToken('user-a');
        $app->handle(Request::create('GET', '/api/v1/auth/me', [], ['Authorization' => 'Bearer ' . $token]));
        self::assertSame('user-a', $app->runtimeContext()->user());

        try {
            $app->handle(Request::create('GET', '/api/v1/auth/me'));
            self::fail('Expected second request to require authentication.');
        } catch (HttpException $exception) {
            self::assertSame(401, $exception->statusCode());
        }

        self::assertFalse($app->runtimeContext()->isAuthenticated());
        self::assertNull($app->runtimeContext()->user());
        self::assertNull($app->runtimeContext()->tenantId());
    }
}