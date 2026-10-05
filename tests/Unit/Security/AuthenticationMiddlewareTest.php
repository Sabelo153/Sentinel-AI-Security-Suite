<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\HttpException;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\RuntimeContext;
use Sentinel\Security\AuthenticationMiddleware;
use Sentinel\Security\JwtService;

final class AuthenticationMiddlewareTest extends TestCase
{
    private function service(): JwtService
    {
        return new JwtService(new Config(['jwt' => ['algorithm' => 'HS256', 'secret' => 'test-secret-that-is-at-least-32-bytes-long', 'issuer' => 'test', 'ttl' => 3600]]));
    }

    public function testValidTokenSetsOnlyAuthenticatedUserAndContinues(): void
    {
        $context = new RuntimeContext();
        $middleware = new AuthenticationMiddleware($this->service(), $context);
        $request = Request::create('GET', '/', [], ['Authorization' => 'Bearer ' . $this->service()->createToken('user-7')]);
        $called = false;

        $response = $middleware->process($request, function (Request $received) use (&$called): Response {
            $called = true;

            return Response::raw('ok');
        });

        self::assertTrue($called);
        self::assertSame('user-7', $context->user());
        self::assertTrue($context->isAuthenticated());
        self::assertNull($context->tenantId());
        self::assertSame('ok', $response->body());
    }

    public function testMissingAndInvalidTokensStopBeforeDestination(): void
    {
        foreach ([[], ['Authorization' => 'Bearer invalid']] as $headers) {
            $context = new RuntimeContext();
            $middleware = new AuthenticationMiddleware($this->service(), $context);
            $called = false;

            try {
                $middleware->process(Request::create('GET', '/', [], $headers), function () use (&$called): Response {
                    $called = true;

                    return Response::raw('should-not-run');
                });
                self::fail('Expected authentication failure.');
            } catch (HttpException $exception) {
                self::assertSame(401, $exception->statusCode());
                self::assertSame('authentication_failed', $exception->errorCode());
            }

            self::assertFalse($called);
        }
    }
}