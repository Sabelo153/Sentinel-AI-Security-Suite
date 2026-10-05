<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Services;
use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Config,Database,RuntimeContext};
use Sentinel\Services\SupervisorAttentionService;
final class SupervisorAttentionServiceTest extends TestCase
{
    public function testDeterministicAttentionFindingsAreTenantScopedAndDoNotMutate():void
    {
        $db=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:',null,null,[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]));
        foreach(['shift_assignments (shift_assignment_id INTEGER,company_id INTEGER,employee_id INTEGER,site_id INTEGER,shift_start_at TEXT,shift_end_at TEXT,check_in_window_end TEXT,assignment_status TEXT,deleted_at TEXT)','attendance (shift_assignment_id INTEGER,company_id INTEGER,employee_id INTEGER,clock_in_at TEXT,clock_out_at TEXT,deleted_at TEXT)','gps_locations (company_id INTEGER,employee_id INTEGER,captured_at TEXT)','patrol_sessions (patrol_session_id INTEGER,company_id INTEGER,patrol_route_id INTEGER,started_at TEXT,status TEXT,deleted_at TEXT,shift_assignment_id INTEGER,employee_assignment_id INTEGER)','employee_assignments (employee_assignment_id INTEGER,company_id INTEGER,employee_id INTEGER)','patrol_routes (patrol_route_id INTEGER,company_id INTEGER,site_id INTEGER)','patrol_checkpoints (patrol_checkpoint_id INTEGER,company_id INTEGER,patrol_route_id INTEGER,checkpoint_order INTEGER,expected_interval_minutes INTEGER,status TEXT,deleted_at TEXT)','patrol_checkpoint_events (company_id INTEGER,patrol_session_id INTEGER,patrol_checkpoint_id INTEGER,event_type TEXT,recorded_at TEXT)','patrol_schedule_windows (patrol_schedule_id INTEGER,company_id INTEGER,patrol_route_id INTEGER,shift_assignment_id INTEGER,starts_at TEXT,ends_at TEXT,cancelled_at TEXT)']as$s)$db->execute('CREATE TABLE '.$s);
        foreach([101,202]as$c){$db->execute("INSERT INTO shift_assignments VALUES (1,:company,1,1,'2026-09-14 08:00:00','2026-09-14 16:00:00',NULL,'ASSIGNED',NULL),(2,:other,2,1,'2026-09-14 08:00:00','2026-09-14 16:00:00',NULL,'IN_PROGRESS',NULL)",['company'=>$c,'other'=>$c]);$db->execute("INSERT INTO attendance VALUES (2,:company,2,'2026-09-14 08:00:00',NULL,NULL)",['company'=>$c]);$db->execute("INSERT INTO patrol_sessions VALUES (1,:company,1,'2026-09-14 08:00:00','IN_PROGRESS',NULL,NULL,NULL)",['company'=>$c]);$db->execute('INSERT INTO patrol_routes VALUES (1,:company,1)',['company'=>$c]);$db->execute("INSERT INTO patrol_checkpoints VALUES (1,:company,1,1,10,'ACTIVE',NULL)",['company'=>$c]);}
        $context=new RuntimeContext();$context->setTenantId(101);$service=new SupervisorAttentionService($db,$context);$now=new \DateTimeImmutable('2026-09-14 09:00:00',new \DateTimeZone('UTC'));
        $first=$service->attention([],$now);self::assertCount(3,$first['findings']);self::assertSame(['MISSED_CHECK_IN','STALE_TELEMETRY','OVERDUE_CHECKPOINT'],array_column($first['findings'],'type'));self::assertFalse($first['truncated']);self::assertSame($first,$service->attention([],$now));
        $db->execute("INSERT INTO gps_locations VALUES (101,2,'2026-09-14 08:59:00')");$db->execute("INSERT INTO patrol_checkpoint_events VALUES (101,1,1,'VISITED','2026-09-14 08:09:00')");self::assertCount(1,$service->attention([],$now)['findings']);
    }

    public function testScheduledPatrolIsReportedOnlyWhenNoOwnTenantStartExists():void
    {
        $db=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:',null,null,[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]));
        foreach(['shift_assignments (shift_assignment_id INTEGER,company_id INTEGER,employee_id INTEGER,site_id INTEGER,shift_start_at TEXT,shift_end_at TEXT,check_in_window_end TEXT,assignment_status TEXT,deleted_at TEXT)','attendance (shift_assignment_id INTEGER,company_id INTEGER,employee_id INTEGER,clock_in_at TEXT,clock_out_at TEXT,deleted_at TEXT)','gps_locations (company_id INTEGER,employee_id INTEGER,captured_at TEXT)','patrol_sessions (patrol_session_id INTEGER,company_id INTEGER,patrol_route_id INTEGER,started_at TEXT,status TEXT,deleted_at TEXT,shift_assignment_id INTEGER,employee_assignment_id INTEGER)','employee_assignments (employee_assignment_id INTEGER,company_id INTEGER,employee_id INTEGER)','patrol_routes (patrol_route_id INTEGER,company_id INTEGER,site_id INTEGER)','patrol_checkpoints (patrol_checkpoint_id INTEGER,company_id INTEGER,patrol_route_id INTEGER,checkpoint_order INTEGER,expected_interval_minutes INTEGER,status TEXT,deleted_at TEXT)','patrol_checkpoint_events (company_id INTEGER,patrol_session_id INTEGER,patrol_checkpoint_id INTEGER,event_type TEXT,recorded_at TEXT)','patrol_schedule_windows (patrol_schedule_id INTEGER,company_id INTEGER,patrol_route_id INTEGER,shift_assignment_id INTEGER,starts_at TEXT,ends_at TEXT,cancelled_at TEXT)']as$s)$db->execute('CREATE TABLE '.$s);
        $db->execute("INSERT INTO shift_assignments VALUES (1,101,7,1,'2026-09-14 08:00:00','2026-09-14 16:00:00',NULL,'ASSIGNED',NULL)");$db->execute('INSERT INTO patrol_routes VALUES (1,101,1)');$db->execute('INSERT INTO employee_assignments VALUES (1,101,7)');$db->execute("INSERT INTO patrol_schedule_windows VALUES (1,101,1,1,'2026-09-14 08:15:00','2026-09-14 08:30:00',NULL)");
        $context=new RuntimeContext();$context->setTenantId(101);$service=new SupervisorAttentionService($db,$context);$now=new \DateTimeImmutable('2026-09-14 09:00:00',new \DateTimeZone('UTC'));
        self::assertSame('MISSED_PATROL',$service->attention([],$now)['findings'][1]['type']);
        $db->execute("INSERT INTO patrol_sessions VALUES (1,101,1,'2026-09-14 08:20:00','IN_PROGRESS',NULL,1,1)");self::assertCount(1,$service->attention([],$now)['findings']);
    }
}
