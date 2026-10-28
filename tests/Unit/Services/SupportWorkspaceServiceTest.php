<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Config, Database, HttpException, RuntimeContext};
use Sentinel\Repositories\AuditRepository;
use Sentinel\Services\{AuditService, SupportWorkspaceService};

final class SupportWorkspaceServiceTest extends TestCase
{
    private function setupService(): array
    {
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        $db = new Database(new Config([]), static fn (): PDO => $pdo);
        foreach ([
            'users (user_id INTEGER, company_id INTEGER, status TEXT)',
            'devices (device_id INTEGER, company_id INTEGER, device_name TEXT, device_status TEXT, last_seen_at TEXT, deleted_at TEXT)',
            'sites (site_id INTEGER, company_id INTEGER, site_name TEXT)',
            'device_sessions (device_session_id INTEGER, company_id INTEGER, device_id INTEGER, employee_id INTEGER, site_id INTEGER, last_seen_at TEXT, started_at TEXT, session_status TEXT)',
            'mobile_operations (company_id INTEGER,user_id INTEGER,created_at TEXT,response_json TEXT)',
            'audit_logs (audit_log_id INTEGER PRIMARY KEY,company_id INTEGER,user_id TEXT,entity_name TEXT,entity_id TEXT,action_name TEXT,action_status TEXT,ip_address TEXT,user_agent TEXT,old_values TEXT,new_values TEXT,metadata TEXT,occurred_at TEXT)',
        ] as $table) $db->execute('CREATE TABLE '.$table);
        $context = new RuntimeContext(); $context->setTenantId(101); $context->setUser('1');
        $audit = new AuditService(new AuditRepository($db, $context), $context);
        return [new SupportWorkspaceService($db, $context, $audit), $db, $context];
    }

    public function testOverviewIsTenantScopedAndDoesNotExposePayloads(): void
    {
        [$service,$db] = $this->setupService();
        $db->execute("INSERT INTO devices VALUES (1,101,'Site phone','ACTIVE','2026-10-28 08:00:00',NULL),(2,202,'Other company secret','ACTIVE',NULL,NULL),(3,101,'No contact','ACTIVE',NULL,NULL),(4,101,'Future','ACTIVE','2026-10-28 10:00:00',NULL)");
        $db->execute("INSERT INTO sites VALUES (1,101,'Own site'),(1,202,'Other secret site')");
        $db->execute("INSERT INTO device_sessions VALUES (1,101,1,7,1,'2026-10-28 08:59:00','2026-10-28 08:00:00','ACTIVE'),(2,202,2,8,1,NULL,'2026-10-28 08:00:00','ACTIVE')");
        $db->execute("INSERT INTO mobile_operations VALUES (101,7,'2026-10-28 08:00:00','private payload'),(202,8,'2026-10-28 08:00:00',NULL)");
        $report = $service->overview(new \DateTimeImmutable('2026-10-28 09:00:00',new \DateTimeZone('UTC')));
        self::assertSame(['CLOCK_SKEW','UNKNOWN','STALE'],array_column($report['sections']['devices']['rows'],'contact_state'));
        self::assertCount(1,$report['sections']['sessions']['rows']);
        self::assertSame('RECENT',$report['sections']['sessions']['rows'][0]['contact_state']);
        self::assertStringNotContainsString('Other',json_encode($report));
        self::assertStringNotContainsString('private payload',json_encode($report));
    }

    public function testMissingTablesArePartialNotHealthy(): void
    {
        [$service,$db] = $this->setupService(); $db->execute('DROP TABLE devices');
        $report=$service->overview(); self::assertSame('partial',$report['status']);
        self::assertSame('unavailable',$report['sections']['devices']['status']);
        self::assertSame('ready',$report['sections']['sync']['status']);
    }

    public function testDisableIsScopedAuditedAndRequiresConfirmation(): void
    {
        [$service,$db] = $this->setupService();
        $db->execute("INSERT INTO users VALUES (1,101,'ACTIVE'),(2,101,'ACTIVE'),(3,202,'ACTIVE')");
        foreach ([['1','1'],['3','3'],['2','wrong']] as [$id,$confirm]) {
            try { $service->disableAccount($id,['reason'=>'Lost site phone confirmed','confirm_user_id'=>$confirm]); self::fail('Must reject'); }
            catch(HttpException $e) { self::assertContains($e->statusCode(),[409,422]); }
        }
        $service->disableAccount('2',['reason'=>'Lost site phone confirmed','confirm_user_id'=>'2']);
        self::assertSame('DISABLED',$db->fetch('SELECT status FROM users WHERE user_id=2')['status']);
        self::assertSame('ACTIVE',$db->fetch('SELECT status FROM users WHERE user_id=3')['status']);
        self::assertSame('account_disabled',$db->fetch('SELECT action_name FROM audit_logs')['action_name']);
    }

    public function testAuditFailureRollsBackContainment(): void
    {
        [$service,$db] = $this->setupService();
        $db->execute("INSERT INTO users VALUES (2,101,'ACTIVE')"); $db->execute('DROP TABLE audit_logs');
        try { $service->disableAccount('2',['reason'=>'Lost phone confirmed','confirm_user_id'=>'2']); self::fail('Audit failure must fail closed'); }
        catch(HttpException $e) { self::assertSame(503,$e->statusCode()); }
        self::assertSame('ACTIVE',$db->fetch('SELECT status FROM users WHERE user_id=2')['status']);
    }

    public function testExportContainsNoIdentifiersOrFreeTextAndIsAudited(): void
    {
        [$service,$db] = $this->setupService();
        $db->execute("INSERT INTO devices VALUES (77,101,'Confidential client phone','ACTIVE',NULL,NULL)");
        $bundle=$service->export(); $json=json_encode($bundle);
        foreach(['company_id','user_id','device_id','Confidential'] as $secret) self::assertStringNotContainsString($secret,$json);
        self::assertSame(1,$bundle['summary']['devices']['visible_records']);
        self::assertSame('bundle_exported',$db->fetch('SELECT action_name FROM audit_logs')['action_name']);
    }
}
