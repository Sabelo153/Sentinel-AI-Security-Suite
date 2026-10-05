<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\HttpException;
use Sentinel\Core\Request;
use Sentinel\Core\RuntimeContext;

final class RuntimeContextTest extends TestCase
{
    public function testNewContextHasNoAuthenticatedUser(): void
    {
        $context = new RuntimeContext();

        self::assertNull($context->user());
        self::assertFalse($context->isAuthenticated());
    }

    public function testNewContextHasNoTenant(): void
    {
        $context = new RuntimeContext();

        self::assertNull($context->tenantId());
    }

    public function testSettingUserMakesContextAuthenticated(): void
    {
        $context = new RuntimeContext();
        $context->setUser(['id' => 1]);

        self::assertTrue($context->isAuthenticated());
        self::assertSame(['id' => 1], $context->user());
    }

    public function testTenantCanBeExplicitlyEstablished(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(42);

        self::assertSame(42, $context->tenantId());
        self::assertSame(42, $context->requireTenant());
    }

    public function testRequireTenantThrowsForbiddenWhenTenantAbsent(): void
    {
        $context = new RuntimeContext();

        try {
            $context->requireTenant();
            self::fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            self::assertSame(403, $e->statusCode());
        }
    }

    public function testRequestCanBeAttached(): void
    {
        $context = new RuntimeContext();
        $request = Request::create('GET', '/health');

        self::assertNull($context->request());

        $context->setRequest($request);

        self::assertSame($request, $context->request());
    }

    public function testRequestIdIsGeneratedWhenNotSupplied(): void
    {
        $context = new RuntimeContext();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $context->requestId(),
        );
    }

    public function testExplicitlySuppliedValidRequestIdIsPreserved(): void
    {
        $context = new RuntimeContext('req-abc-123');

        self::assertSame('req-abc-123', $context->requestId());
    }

    public function testImplausibleSuppliedRequestIdIsReplacedWithGeneratedOne(): void
    {
        $context = new RuntimeContext("bad id\nwith control chars");

        self::assertNotSame("bad id\nwith control chars", $context->requestId());
    }

    public function testEnvironmentIsReturnedAsSupplied(): void
    {
        $context = new RuntimeContext(null, 'testing');

        self::assertSame('testing', $context->environment());
    }

    public function testSeparateContextsDoNotShareTenantOrUserState(): void
    {
        $first = new RuntimeContext();
        $first->setTenantId(1);
        $first->setUser(['id' => 1]);

        $second = new RuntimeContext();

        self::assertNull($second->tenantId());
        self::assertNull($second->user());
        self::assertNotSame($first->requestId(), $second->requestId());
    }

    public function testResetClearsRequestUserAndTenantAndIssuesNewRequestId(): void
    {
        $context = new RuntimeContext();
        $context->setUser(['id' => 1]);
        $context->setTenantId(7);
        $context->setRequest(Request::create('GET', '/'));
        $originalRequestId = $context->requestId();

        $context->reset();

        self::assertNull($context->user());
        self::assertNull($context->tenantId());
        self::assertNull($context->request());
        self::assertNotSame($originalRequestId, $context->requestId());
    }
}
