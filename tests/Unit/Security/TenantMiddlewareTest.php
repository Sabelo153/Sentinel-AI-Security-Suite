<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Security;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\UserRepository;
use Sentinel\Security\TenantContextService;
use Sentinel\Security\TenantMiddleware;

final class TenantMiddlewareTest extends TestCase
{
    private function middleware(RuntimeContext $context): TenantMiddleware
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, company_id INTEGER, status TEXT)');
        $db->execute('INSERT INTO users (user_id, company_id, status) VALUES (7, 101, \'ACTIVE\')');

        return new TenantMiddleware(new TenantContextService(new UserRepository($db), $context));
    }

    public function testTenantIsResolvedBeforeHandlerAndRequestValuesAreIgnored(): void
    {
        $context = new RuntimeContext();
        $context->setUser('7');
        $middleware = $this->middleware($context);

        $response = $middleware->process(Request::create('GET', '/tenant', ['company_id' => '999'], ['X-Company-Id' => '999']), function () use ($context): Response {
            self::assertSame(101, $context->tenantId());

            return Response::json(['company_id' => $context->tenantId()]);
        });

        self::assertSame('{"company_id":101}', $response->body());
    }

    public function testUnauthenticatedRequestIsRejectedBeforeHandler(): void
    {
        $context = new RuntimeContext();
        $called = false;

        try {
            $this->middleware($context)->process(Request::create('GET', '/tenant'), function () use (&$called): Response {
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

    public function testContextResetClearsTenantBetweenRequests(): void
    {
        $context = new RuntimeContext();
        $context->setUser('7');
        $this->middleware($context)->process(Request::create('GET', '/tenant'), static fn (): Response => Response::raw('ok'));
        self::assertSame(101, $context->tenantId());

        $context->reset();
        self::assertNull($context->tenantId());
        self::assertNull($context->user());
    }
}