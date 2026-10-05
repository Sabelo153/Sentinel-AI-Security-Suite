<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\DashboardRepository;

final class DashboardRepositoryTest extends TestCase
{
    private function repository(): DashboardRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        foreach ([
            'employees (company_id INTEGER, employment_status TEXT)', 'employee_assignments (company_id INTEGER, employee_assignment_id INTEGER, position_id INTEGER, assignment_status TEXT, deleted_at TEXT)', 'positions (company_id INTEGER, position_id INTEGER, position_category TEXT, position_status TEXT, deleted_at TEXT)',
            'guard_site_assignments (company_id INTEGER, deployment_status TEXT, deleted_at TEXT)', 'sites (company_id INTEGER, status TEXT)', 'shift_assignments (company_id INTEGER, assignment_date TEXT, assignment_status TEXT, deleted_at TEXT)',
            'attendance (company_id INTEGER, shift_date TEXT, attendance_status TEXT, deleted_at TEXT)', 'patrol_sessions (company_id INTEGER, status TEXT, completed_at TEXT, ended_at TEXT, deleted_at TEXT)', 'patrol_checkpoint_events (company_id INTEGER, recorded_at TEXT)',
            'contracts (company_id INTEGER, status TEXT, deleted_at TEXT)', 'posts (company_id INTEGER, status TEXT, deleted_at TEXT)', 'geofences (company_id INTEGER, geofence_status TEXT, deleted_at TEXT)',
            'audit_logs (audit_log_id INTEGER, company_id INTEGER, user_id INTEGER, entity_name TEXT, entity_id TEXT, action_name TEXT, action_status TEXT, occurred_at TEXT)',
        ] as $schema) $db->execute('CREATE TABLE ' . $schema);
        $today = '2026-09-14';
        $db->execute("INSERT INTO employees VALUES (101, 'ACTIVE'), (202, 'ACTIVE')"); $db->execute("INSERT INTO positions VALUES (101, 1, 'GUARD', 'ACTIVE', NULL), (202, 1, 'GUARD', 'ACTIVE', NULL)"); $db->execute("INSERT INTO employee_assignments VALUES (101, 1, 1, 'ACTIVE', NULL), (202, 1, 1, 'ACTIVE', NULL)");
        $db->execute("INSERT INTO guard_site_assignments VALUES (101, 'ACTIVE', NULL), (202, 'ACTIVE', NULL)"); $db->execute("INSERT INTO sites VALUES (101, 'ACTIVE'), (202, 'ACTIVE')"); $db->execute("INSERT INTO shift_assignments VALUES (101, '$today', 'IN_PROGRESS', NULL), (202, '$today', 'IN_PROGRESS', NULL)");
        $db->execute("INSERT INTO attendance VALUES (101, '$today', 'PRESENT', NULL), (101, '$today', 'LATE', NULL), (202, '$today', 'ABSENT', NULL)"); $db->execute("INSERT INTO patrol_sessions VALUES (101, 'IN_PROGRESS', NULL, NULL, NULL), (101, 'COMPLETED', '$today 08:00:00', NULL, NULL), (202, 'ABORTED', NULL, '$today 08:00:00', NULL)");
        $db->execute("INSERT INTO patrol_checkpoint_events VALUES (101, '$today 09:00:00'), (202, '$today 09:00:00')"); $db->execute("INSERT INTO contracts VALUES (101, 'ACTIVE', NULL), (202, 'ACTIVE', NULL)"); $db->execute("INSERT INTO posts VALUES (101, 'ACTIVE', NULL), (202, 'ACTIVE', NULL)"); $db->execute("INSERT INTO geofences VALUES (101, 'ACTIVE', NULL), (202, 'ACTIVE', NULL)");
        $db->execute("INSERT INTO audit_logs VALUES (1, 101, 7, 'user', '7', 'updated', 'SUCCESS', '$today 10:00:00'), (2, 202, 8, 'user', '8', 'updated', 'SUCCESS', '$today 11:00:00')");
        $context = new RuntimeContext(); $context->setTenantId(101);
        return new DashboardRepository($db, $context);
    }

    public function testSummaryCountsOnlyCurrentCompanyAndRecentActivityIsBounded(): void
    {
        $repository = $this->repository(); $summary = $repository->summary('2026-09-14');
        self::assertSame(1, $summary['total_active_employees']); self::assertSame(1, $summary['total_active_guards']); self::assertSame(2, $summary['attendance_today']); self::assertSame(1, $summary['present_today']); self::assertSame(1, $summary['late_today']); self::assertSame(0, $summary['absent_today']); self::assertSame(1, $summary['patrols_in_progress']); self::assertSame(1, $summary['patrols_completed_today']); self::assertSame(0, $summary['patrols_cancelled_or_aborted_today']);
        self::assertSame([['audit_log_id' => 1, 'user_id' => 7, 'entity_name' => 'user', 'entity_id' => '7', 'action_name' => 'updated', 'action_status' => 'SUCCESS', 'occurred_at' => '2026-09-14 10:00:00']], $repository->recentActivity(20));
    }
}