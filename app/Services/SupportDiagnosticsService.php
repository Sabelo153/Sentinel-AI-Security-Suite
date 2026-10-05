<?php

declare(strict_types=1);

namespace Sentinel\Services;

use Sentinel\Core\Config;
use Sentinel\Core\Database;

/**
 * Non-invasive health information for the local support console. It never
 * returns configuration values, credentials, customer records or raw errors.
 */
final class SupportDiagnosticsService
{
    /** @var array<string, list<string>> */
    private const FEATURE_TABLES = [
        'accounts_and_access' => ['companies', 'users', 'roles', 'permissions', 'user_roles', 'role_permissions', 'refresh_tokens', 'login_history'],
        'organisation_and_staffing' => ['branches', 'sites', 'people', 'employees', 'positions', 'employee_assignments'],
        'shifts_and_attendance' => ['shift_assignments', 'attendance', 'guard_site_assignments'],
        'patrols_and_checkpoints' => ['patrol_routes', 'patrol_checkpoints', 'patrol_sessions', 'patrol_checkpoint_events', 'patrol_schedule_windows'],
        'guard_mobile_sync' => ['mobile_operations', 'gps_events'],
        'devices' => ['devices', 'device_assignments', 'device_sessions'],
        'incidents_and_occurrence_book' => ['incidents', 'occurrence_entries', 'occurrence_entry_revisions'],
        'visitor_vehicle_and_access' => ['visitors', 'visitor_visits', 'vehicles', 'access_events'],
        'inspections' => ['inspections', 'inspection_items'],
        'alerts_and_notifications' => ['alerts', 'notifications', 'notification_deliveries'],
        'reports_and_audit' => ['audit_logs', 'activity_logs'],
        'subscriptions' => ['company_subscriptions'],
        'geofencing' => ['geofences', 'gps_locations', 'gps_events'],
        'contracts_and_posts' => ['contracts', 'posts'],
    ];

    public function __construct(
        private readonly Config $config,
        private readonly Database $database,
    ) {
    }

    /** @return array<string, mixed> */
    public function report(): array
    {
        $databaseStatus = 'ready';
        $canCheckFeatureTables = false;
        try {
            $this->database->fetch('SELECT 1 AS ready');
            $canCheckFeatureTables = in_array($this->database->connection()->getAttribute(\PDO::ATTR_DRIVER_NAME), ['mysql', 'sqlite'], true);
        } catch (\Throwable) {
            $databaseStatus = 'unavailable';
        }

        $storageStatus = is_writable(sys_get_temp_dir()) ? 'ready' : 'unavailable';
        $checks = [
            'application' => ['status' => 'ready'],
            'database' => ['status' => $databaseStatus],
            'runtime_storage' => ['status' => $storageStatus],
            'php_runtime' => ['status' => 'ready'],
        ];
        foreach (self::FEATURE_TABLES as $feature => $tables) {
            $checks[$feature] = $this->featureStatus($tables, $databaseStatus === 'ready', $canCheckFeatureTables);
        }
        $checks['local_ai_guide'] = ['status' => (bool) $this->config->get('ai.enabled', false) ? 'configured' : 'not_enabled'];
        $healthy = !array_intersect(['unavailable', 'not_checked'], array_column($checks, 'status'));

        return [
            'status' => $healthy ? 'ready' : 'attention_required',
            'checks' => $checks,
            'generated_at' => gmdate('c'),
            'scope' => 'Read-only infrastructure checks. No tenant records read. Manual workflows and external delivery remain unverified.',
        ];
    }

    /** @param list<string> $tables */
    private function featureStatus(array $tables, bool $databaseReady, bool $canCheckFeatureTables): array
    {
        if (!$databaseReady) {
            return ['status' => 'unavailable', 'detail' => 'Restore the database connection, then repeat this check.'];
        }
        if (!$canCheckFeatureTables) {
            return ['status' => 'not_checked', 'detail' => 'This database driver is not supported by the schema check.'];
        }
        $missing = [];
        $unavailable = [];
        foreach ($tables as $table) {
            try {
                // Validate access without reading any tenant rows. Identifiers
                // come exclusively from the internal allow-list above.
                $this->database->fetch('SELECT 1 FROM `' . $table . '` WHERE 1 = 0');
            } catch (\PDOException $error) {
                if ($error->getCode() === '42S02' || str_contains($error->getMessage(), 'no such table:')) {
                    $missing[] = $table;
                } else {
                    $unavailable[] = $table;
                }
            } catch (\Throwable) {
                $unavailable[] = $table;
            }
        }
        return [
            'status' => $missing || $unavailable ? 'unavailable' : 'ready',
            'missing_tables' => $missing,
            'unavailable_tables' => $unavailable,
            'detail' => $missing ? 'Apply the approved missing schema using a database administrator account.'
                : ($unavailable ? 'Check database permissions and connection health.' : 'Required tables are accessible. Workflow testing is still required.'),
        ];
    }
}

