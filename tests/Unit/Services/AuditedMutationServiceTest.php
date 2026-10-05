<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Services;
use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Config, Database, HttpException, Request, Response, RuntimeContext};
use Sentinel\Repositories\AuditRepository;
use Sentinel\Services\{AuditService, AuditedMutationService};

final class AuditedMutationServiceTest extends TestCase
{
    private function fixture(): array
    {
        $db = new Database(new Config([]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE sample (id INTEGER, company_id INTEGER)');
        $db->execute('CREATE TABLE audit_logs (company_id INTEGER,user_id INTEGER,entity_name TEXT,entity_id TEXT,action_name TEXT,action_status TEXT,ip_address TEXT,user_agent TEXT,old_values TEXT,new_values TEXT,metadata TEXT)');
        $context = new RuntimeContext(); $context->setTenantId(101); $context->setUser('1');
        return [$db, new AuditedMutationService($db, $context, new AuditService(new AuditRepository($db, $context), $context))];
    }
    public function testMutationAndSafeAuditCommitTogether(): void
    {
        [$db, $service] = $this->fixture();
        $response = $service->handle(Request::create('POST', '/api/v1/sample', [], [], '{"password":"secret"}'), 'sample.create', function () use ($db): Response { $db->execute('INSERT INTO sample VALUES (1,101)'); return Response::json(['ok' => true], 201); });
        self::assertSame(201, $response->status()); self::assertCount(1, $db->fetchAll('SELECT * FROM sample'));
        $audit = $db->fetch('SELECT * FROM audit_logs'); self::assertSame(101, $audit['company_id']); self::assertStringContainsString('sample.create', $audit['metadata']); self::assertStringNotContainsString('secret', json_encode($audit));
    }
    public function testAuditFailureAndErrorResponseRollBackMutation(): void
    {
        [$db, $service] = $this->fixture();
        $response = $service->handle(Request::create('POST', '/sample'), 'sample.create', function () use ($db): Response { $db->execute('INSERT INTO sample VALUES (1,101)'); return Response::json([], 422); });
        self::assertSame(422, $response->status()); self::assertSame([], $db->fetchAll('SELECT * FROM sample'));
        $db->execute('DROP TABLE audit_logs');
        try { $service->handle(Request::create('POST', '/sample'), 'sample.create', function () use ($db): Response { $db->execute('INSERT INTO sample VALUES (1,101)'); return Response::json([]); }); self::fail('Expected audit failure'); }
        catch (HttpException $error) { self::assertSame(503, $error->statusCode()); }
        self::assertSame([], $db->fetchAll('SELECT * FROM sample')); self::assertFalse($db->inTransaction());
    }
}
