<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Security;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\UserRepository;
use Sentinel\Security\PasswordAuthenticator;

final class PasswordAuthenticatorTest extends TestCase
{
    private function authenticator(string $status = 'ACTIVE'): PasswordAuthenticator
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:'));
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, username TEXT, email TEXT, password_hash TEXT, status TEXT)');
        $db->execute('INSERT INTO users VALUES (:id, :username, :email, :hash, :status)', [
            'id' => 7, 'username' => 'guard7', 'email' => 'guard@example.test', 'hash' => password_hash('secret', PASSWORD_DEFAULT), 'status' => $status,
        ]);

        return new PasswordAuthenticator(new UserRepository($db));
    }

    public function testValidCredentialsReturnOnlyUserIdentity(): void
    {
        self::assertSame(['user_id' => '7'], $this->authenticator()->authenticate('guard@example.test', 'secret'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidCredentials')]
    public function testCredentialFailuresAreIndistinguishable(string $identifier, string $password, string $status = 'ACTIVE'): void
    {
        try {
            $this->authenticator($status)->authenticate($identifier, $password);
            self::fail('Expected authentication failure.');
        } catch (HttpException $exception) {
            self::assertSame(401, $exception->statusCode());
            self::assertSame('authentication_failed', $exception->errorCode());
            self::assertSame('Authentication failed.', $exception->getMessage());
        }
    }

    /** @return array<string, array{0: string, 1: string, 2?: string}> */
    public static function invalidCredentials(): array
    {
        return ['wrong password' => ['guard@example.test', 'wrong'], 'unknown user' => ['missing@example.test', 'secret'], 'empty identifier' => ['', 'secret'], 'empty password' => ['guard@example.test', ''], 'inactive' => ['guard@example.test', 'secret', 'DISABLED']];
    }
}