<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\AuditRepository;
use Sentinel\Services\AuditService;

final class AuditServiceTest extends TestCase
{
    public function testAuditUsesRuntimeTenantAndRedactsSecurityValues(): void
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE audit_logs (audit_log_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, user_id INTEGER, entity_name TEXT, entity_id TEXT, action_name TEXT, action_status TEXT, ip_address TEXT, user_agent TEXT, old_values TEXT, new_values TEXT, metadata TEXT)');
        $context = new RuntimeContext(); $context->setTenantId(101); $context->setUser('7'); $context->setRequest(Request::create('POST', '/api/v1/users', [], ['User-Agent' => 'test'], null, ['REMOTE_ADDR' => '127.0.0.1']));
        (new AuditService(new AuditRepository($db, $context), $context))->record('user', 'password_change', '7', [], ['password' => 'secret', 'profile' => ['jwt' => 'token']], ['api_key' => 'key']);
        $row = $db->fetch('SELECT * FROM audit_logs WHERE audit_log_id = 1');
        self::assertSame('101', (string) $row['company_id']);
        self::assertStringContainsString('***REDACTED***', (string) $row['new_values']);
        self::assertStringNotContainsString('secret', (string) $row['new_values']);
        self::assertStringNotContainsString('"key"', (string) $row['metadata']);
    }
}
