<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Security;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\UserRepository;
use Sentinel\Security\TenantContextService;

final class TenantContextServiceTest extends TestCase
{
    private function service(RuntimeContext $context, bool $databaseFailure = false): TenantContextService
    {
        $connector = $databaseFailure
            ? static function (): PDO { throw new PDOException('database detail'); }
            : static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $db = new Database(new Config(['database' => []]), $connector);

        if (!$databaseFailure) {
            $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, company_id INTEGER, status TEXT)');
            $db->execute('INSERT INTO users (user_id, company_id, status) VALUES (7, 101, \'ACTIVE\')');
        }

        return new TenantContextService(new UserRepository($db), $context);
    }

    public function testAuthenticatedUserResolvesAndStoresVerifiedCompany(): void
    {
        $context = new RuntimeContext();
        $context->setUser('7');

        self::assertSame('101', $this->service($context)->resolveForAuthenticatedUser());
        self::assertSame(101, $context->tenantId());
    }

    public function testUnauthenticatedUserReceivesAuthenticationFailure(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(0);
        $context = new RuntimeContext();

        try {
            $this->service($context)->resolveForAuthenticatedUser();
        } catch (HttpException $exception) {
            self::assertSame(401, $exception->statusCode());
            self::assertSame('authentication_failed', $exception->errorCode());
            throw $exception;
        }
    }

    public function testUnknownUserReceivesSafeForbidden(): void
    {
        $context = new RuntimeContext();
        $context->setUser('99');

        try {
            $this->service($context)->resolveForAuthenticatedUser();
            self::fail('Expected forbidden exception.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->statusCode());
            self::assertSame('forbidden', $exception->errorCode());
            self::assertNull($context->tenantId());
        }
    }

    public function testDatabaseFailureReceivesSafeServiceUnavailable(): void
    {
        $context = new RuntimeContext();
        $context->setUser('7');

        try {
            $this->service($context, true)->resolveForAuthenticatedUser();
            self::fail('Expected service unavailable exception.');
        } catch (HttpException $exception) {
            self::assertSame(503, $exception->statusCode());
            self::assertSame('tenant_service_unavailable', $exception->errorCode());
            self::assertStringNotContainsString('database detail', $exception->getMessage());
        }
    }
}