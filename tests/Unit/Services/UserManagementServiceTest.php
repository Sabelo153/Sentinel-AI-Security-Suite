<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\UserRepository;
use Sentinel\Services\UserManagementService;

final class UserManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): UserManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)');
        $db->execute('INSERT INTO employees (employee_id, company_id) VALUES (3, 101), (4, 202)');
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY AUTOINCREMENT, user_uuid TEXT NOT NULL, company_id INTEGER NOT NULL, employee_id INTEGER NOT NULL, username TEXT NOT NULL, email TEXT NOT NULL, password_hash TEXT NOT NULL, status TEXT NOT NULL)');
        $db->execute('INSERT INTO users (user_uuid, company_id, employee_id, username, email, password_hash, status) VALUES (\'u-1\', 101, 1, \'alpha\', \'alpha@example.test\', \'hash-a\', \'ACTIVE\'), (\'u-2\', 202, 2, \'beta\', \'beta@example.test\', \'hash-b\', \'ACTIVE\')');

        return new UserManagementService(new UserRepository($db, $context));
    }

    public function testCreateHashesPasswordAndReturnsSafeProjection(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $result = $this->service($context)->createUser([
            'username' => 'new-user',
            'email' => 'New@Example.Test',
            'password' => 'secret',
            'employee_id' => 3,
            'company_id' => 202,
        ]);

        self::assertSame('101', (string) $result['company_id']);
        self::assertSame('new@example.test', $result['email']);
        self::assertArrayNotHasKey('password', $result);
        self::assertArrayNotHasKey('password_hash', $result);
    }

    public function testCrossCompanyAndMissingUsersUseSameNotFoundResponse(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);
        $errors = [];

        foreach (['2', '999'] as $userId) {
            try {
                $service->getUser($userId);
                self::fail('Expected not found exception.');
            } catch (HttpException $exception) {
                $errors[] = [$exception->statusCode(), $exception->errorCode(), $exception->getMessage()];
            }
        }

        self::assertSame($errors[0], $errors[1]);
        self::assertSame([404, 'user_not_found', 'User not found.'], $errors[0]);
    }

    public function testCrossCompanyAndMissingEmployeesUseSameSafeResponse(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);
        $errors = [];

        foreach ([4, 999] as $employeeId) {
            try {
                $service->createUser([
                    'username' => 'new-' . $employeeId,
                    'email' => 'new-' . $employeeId . '@example.test',
                    'password' => 'secret',
                    'employee_id' => $employeeId,
                    'company_id' => 202,
                ]);
                self::fail('Expected employee not found exception.');
            } catch (HttpException $exception) {
                $errors[] = [$exception->statusCode(), $exception->errorCode(), $exception->getMessage()];
            }
        }

        self::assertSame($errors[0], $errors[1]);
        self::assertSame([404, 'employee_not_found', 'Employee not found.'], $errors[0]);
    }

    public function testValidationRejectsInvalidEmailEmptyPasswordAndUnsafeUpdateFields(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        foreach ([
            ['username' => 'user', 'email' => 'bad', 'password' => 'secret', 'employee_id' => 3],
            ['username' => 'user', 'email' => 'user@example.test', 'password' => '', 'employee_id' => 3],
        ] as $input) {
            try {
                $service->createUser($input);
                self::fail('Expected validation exception.');
            } catch (HttpException $exception) {
                self::assertSame(422, $exception->statusCode());
            }
        }

        try {
            $service->updateUser('1', ['company_id' => 202, 'password_hash' => 'injected']);
            self::fail('Expected whitelist validation exception.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->statusCode());
        }
    }
}