<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PDO;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\DashboardRepository;
use Sentinel\Services\DashboardService;

final class DashboardServiceTest extends TestCase
{
    public function testSummaryCombinesMetricsAndBoundedRecentActivity(): void
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        foreach ([
            'employees (company_id INTEGER, employment_status TEXT)', 'employee_assignments (company_id INTEGER, employee_assignment_id INTEGER, position_id INTEGER, assignment_status TEXT, deleted_at TEXT)', 'positions (company_id INTEGER, position_id INTEGER, position_category TEXT, position_status TEXT, deleted_at TEXT)', 'guard_site_assignments (company_id INTEGER, deployment_status TEXT, deleted_at TEXT)', 'sites (company_id INTEGER, status TEXT)', 'shift_assignments (company_id INTEGER, assignment_date TEXT, assignment_status TEXT, deleted_at TEXT)', 'attendance (company_id INTEGER, shift_date TEXT, attendance_status TEXT, deleted_at TEXT)', 'patrol_sessions (company_id INTEGER, status TEXT, completed_at TEXT, ended_at TEXT, deleted_at TEXT)', 'patrol_checkpoint_events (company_id INTEGER, recorded_at TEXT)', 'contracts (company_id INTEGER, status TEXT, deleted_at TEXT)', 'posts (company_id INTEGER, status TEXT, deleted_at TEXT)', 'geofences (company_id INTEGER, geofence_status TEXT, deleted_at TEXT)', 'audit_logs (audit_log_id INTEGER, company_id INTEGER, user_id INTEGER, entity_name TEXT, entity_id TEXT, action_name TEXT, action_status TEXT, occurred_at TEXT)',
        ] as $schema) $db->execute('CREATE TABLE ' . $schema);
        $context = new RuntimeContext(); $context->setTenantId(101);
        $summary = (new DashboardService(new DashboardRepository($db, $context)))->summary();
        self::assertSame(0, $summary['metrics']['active_sites']); self::assertSame([], $summary['recent_activity']);
    }
}