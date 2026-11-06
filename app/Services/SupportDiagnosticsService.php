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
        'account_security' => ['security_sessions','account_mfa','support_grants'],
        'managed_android' => ['managed_android'],
        'control_room' => ['dispatch_jobs','lone_worker_plans','lone_worker_events','coverage_requirements','shift_relief_history','gate_register','contract_hourly_rates','invoice_drafts','operational_signals','worker_runs','backup_checks','phone_health'],
        'site_assurance' => ['site_instruction_versions','site_instruction_acknowledgements','shift_handovers','training_competencies','site_training_requirements','employee_training','maintenance_tickets','maintenance_history','site_service_targets','incident_evidence','evidence_access_history','retention_policies','retention_holds','retention_previews'],
        'workforce_assurance' => ['learning_courses','learning_assignments','learning_attempts','learning_certificates','found_property','found_property_events','investigation_cases','investigation_notes','investigation_actions','investigation_history','stock_items','stock_allocations','stock_movements'],
        'business_operations' => ['open_shift_offers','open_shift_candidates','visitor_appointments','fleet_vehicles','fleet_trips','fleet_fuel','fleet_services','contract_renewals','contract_term_history','business_operation_settings','business_reminders','backup_schedules','encrypted_backups','pilot_role_templates','pilot_demo_records'],
        'operations_hub' => ['radio_channels','radio_members','radio_messages','radio_priority_calls','monthly_service_reviews','service_review_actions','service_review_feedback','integration_credentials','integration_receipts','integration_webhooks','integration_events','webhook_deliveries','customer_support_tickets','customer_support_messages','support_diagnostic_consents','release_notices','release_notice_reads','inspection_queue_health','operational_alert_rules','operational_alerts','operational_alert_deliveries'],
        'operational_readiness' => ['custody_assets','custody_loans','custody_events','deployment_preferences','emergency_procedures','emergency_exercises','emergency_roll_calls','emergency_step_records','user_workflow_languages','workflow_translations','company_workspace_profiles','company_setup_checks','subscription_change_requests'],
        'client_workflows' => ['user_site_grants','inspection_form_versions','inspection_form_submissions','inspection_form_photos','inspection_followups','client_requests','client_request_events','employee_availability','client_report_reviews','service_quotations','site_onboardings','mobilisation_tasks'],
        'photo_reports_and_delivery' => ['photo_report_templates','photo_reports','photo_report_items','client_report_schedules','client_report_runs','client_report_outbox'],
        'compliance' => ['compliance_items'],
        'equipment_handovers' => ['equipment_items','equipment_handovers'],
        'client_portal' => ['client_portal_access','published_site_reports'],
        'payroll_approvals' => ['attendance_approvals'],
        'escalation_worker' => ['escalation_rules','escalation_events'],
        'mobile_reports_and_diagnostics' => ['report_photos','mobile_diagnostics'],
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

