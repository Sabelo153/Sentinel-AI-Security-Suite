<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Security;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\AuditRepository;
use Sentinel\Repositories\UserRepository;
use Sentinel\Security\PasswordAuthenticator;
use Sentinel\Services\AuditService;
use Sentinel\Services\AccountSecurityService;

final class AccountSecurityTest extends TestCase
{
    private function database(): Database
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, company_id INTEGER, username TEXT, email TEXT, password_hash TEXT, password_last_changed_at TEXT, failed_login_attempts INTEGER DEFAULT 0, locked_until TEXT, last_login_at TEXT, status TEXT)');
        $db->execute('CREATE TABLE login_history (login_history_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, user_id INTEGER, login_status TEXT, failure_reason TEXT)');
        $db->execute('CREATE TABLE audit_logs (audit_log_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, user_id INTEGER, entity_name TEXT, entity_id TEXT, action_name TEXT, action_status TEXT, ip_address TEXT, user_agent TEXT, old_values TEXT, new_values TEXT, metadata TEXT)');
        $db->execute("INSERT INTO users (user_id,company_id,username,email,password_hash,status) VALUES (7,101,'guard7','guard@example.test',:hash,'ACTIVE'),(8,202,'guard8','other@example.test',:hash,'ACTIVE')", ['hash' => password_hash('secret', PASSWORD_DEFAULT)]);
        return $db;
    }

    public function testFailedLoginsLockAndSuccessfulLoginResetsState(): void
    {
        $db = $this->database(); $users = new UserRepository($db); $authenticator = new PasswordAuthenticator($users);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try { $authenticator->authenticate('guard@example.test', 'wrong'); self::fail('Expected authentication failure.'); } catch (HttpException $e) { self::assertSame('authentication_failed', $e->errorCode()); }
        }
        $state = $db->fetch('SELECT failed_login_attempts, status, locked_until FROM users WHERE user_id = 7');
        self::assertSame('5', (string) $state['failed_login_attempts']);
        self::assertSame('LOCKED', $state['status']);
        self::assertNotNull($state['locked_until']);
        self::assertSame('5', (string) $db->fetch('SELECT COUNT(*) AS count FROM login_history WHERE user_id = 7')['count']);
    }

    public function testPasswordChangeAndTenantSafeUnlock(): void
    {
        $db = $this->database(); $context = new RuntimeContext(); $context->setTenantId(101); $context->setUser('7'); $users = new UserRepository($db, $context); $service = new AccountSecurityService($users, $context, new AuditService(new AuditRepository($db, $context), $context));
        $service->changePassword('7', 'secret', 'new-password-123');
        self::assertTrue(password_verify('new-password-123', (string) $db->fetch('SELECT password_hash FROM users WHERE user_id = 7')['password_hash']));
        try { $service->changePassword('8', 'secret', 'new-password-123'); self::fail('Expected cross-company user to be hidden.'); } catch (HttpException $e) { self::assertSame('invalid_current_password', $e->errorCode()); }
        $db->execute("UPDATE users SET status='LOCKED', failed_login_attempts=5, locked_until='2099-01-01 00:00:00' WHERE user_id=7");
        $service->unlockUser('7');
        $state = $db->fetch('SELECT status, failed_login_attempts, locked_until FROM users WHERE user_id = 7');
        self::assertSame('ACTIVE', $state['status']); self::assertSame('0', (string) $state['failed_login_attempts']); self::assertNull($state['locked_until']);
        self::assertSame([['action_name' => 'password_changed'], ['action_name' => 'unlocked']], $db->fetchAll('SELECT action_name FROM audit_logs ORDER BY audit_log_id'));
    }
}
