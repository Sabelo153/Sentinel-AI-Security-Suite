<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Security;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\UserRepository;
use Sentinel\Security\JwtService;
use Sentinel\Security\LoginService;
use Sentinel\Security\PasswordAuthenticator;

final class LoginServiceTest extends TestCase
{
    public function testSuccessfulLoginCreatesJwtForAuthenticatedSubject(): void
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:'));
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, username TEXT, email TEXT, password_hash TEXT, status TEXT)');
        $db->execute('INSERT INTO users VALUES (7, :username, :email, :hash, :status)', ['username' => 'guard7', 'email' => 'guard@example.test', 'hash' => password_hash('secret', PASSWORD_DEFAULT), 'status' => 'ACTIVE']);
        $jwt = new JwtService(new Config(['jwt' => ['algorithm' => 'HS256', 'secret' => 'test-secret-that-is-at-least-32-bytes-long', 'issuer' => 'test', 'ttl' => 3600]]));

        $token = (new LoginService(new PasswordAuthenticator(new UserRepository($db)), $jwt))->login('guard@example.test', 'secret');
        $parsed = $jwt->validateToken($token);

        self::assertSame('7', $parsed->claims()->get('sub'));
        self::assertFalse($parsed->claims()->has('password'));
        self::assertFalse($parsed->claims()->has('tenant_id'));
        self::assertFalse($parsed->claims()->has('roles'));
    }

    public function testFailedLoginDoesNotCreateToken(): void
    {
        $this->expectException(HttpException::class);
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:'));
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, username TEXT, email TEXT, password_hash TEXT, status TEXT)');

        (new LoginService(new PasswordAuthenticator(new UserRepository($db)), new JwtService(new Config(['jwt' => ['algorithm' => 'HS256', 'secret' => 'test-secret-that-is-at-least-32-bytes-long', 'issuer' => 'test', 'ttl' => 3600]]))))->login('missing', 'wrong');
    }
}