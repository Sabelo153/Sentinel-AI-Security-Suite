<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class DashboardRepository extends Repository
{
    public function __construct(Database $db, ?RuntimeContext $context = null) { parent::__construct($db, $context); }
    protected function table(): string { return 'audit_logs'; }

    /** @return array<string, int> */
    public function summary(string $today): array
    {
        $companyId = $this->requireCompanyId();
        $counts = [
            'total_active_employees' => ['employees', 'employment_status = :active', ['active' => 'ACTIVE']],
            'total_active_guards' => ['employee_assignments ea INNER JOIN positions p ON p.position_id = ea.position_id AND p.company_id = ea.company_id', 'ea.assignment_status = :active AND ea.deleted_at IS NULL AND p.position_category = :guard AND p.position_status = :position_active AND p.deleted_at IS NULL', ['active' => 'ACTIVE', 'guard' => 'GUARD', 'position_active' => 'ACTIVE']],
            'active_guard_deployments' => ['guard_site_assignments', 'deployment_status = :active AND deleted_at IS NULL', ['active' => 'ACTIVE']],
            'active_sites' => ['sites', 'status = :active', ['active' => 'ACTIVE']],
            'shifts_today' => ['shift_assignments', 'assignment_date = :today AND deleted_at IS NULL', ['today' => $today]],
            'shifts_in_progress' => ['shift_assignments', 'assignment_status = :progress AND deleted_at IS NULL', ['progress' => 'IN_PROGRESS']],
            'attendance_today' => ['attendance', 'shift_date = :today AND deleted_at IS NULL', ['today' => $today]],
            'present_today' => ['attendance', 'shift_date = :today AND attendance_status = :status AND deleted_at IS NULL', ['today' => $today, 'status' => 'PRESENT']],
            'late_today' => ['attendance', 'shift_date = :today AND attendance_status = :status AND deleted_at IS NULL', ['today' => $today, 'status' => 'LATE']],
            'absent_today' => ['attendance', 'shift_date = :today AND attendance_status = :status AND deleted_at IS NULL', ['today' => $today, 'status' => 'ABSENT']],
            'patrols_in_progress' => ['patrol_sessions', 'status = :progress AND deleted_at IS NULL', ['progress' => 'IN_PROGRESS']],
            'patrols_completed_today' => ['patrol_sessions', 'DATE(completed_at) = :today AND status = :status AND deleted_at IS NULL', ['today' => $today, 'status' => 'COMPLETED']],
            'patrols_cancelled_or_aborted_today' => ['patrol_sessions', 'DATE(ended_at) = :today AND status IN (:cancelled, :aborted) AND deleted_at IS NULL', ['today' => $today, 'cancelled' => 'CANCELLED', 'aborted' => 'ABORTED']],
            'checkpoint_events_today' => ['patrol_checkpoint_events', 'DATE(recorded_at) = :today', ['today' => $today]],
            'active_contracts' => ['contracts', 'status = :active AND deleted_at IS NULL', ['active' => 'ACTIVE']],
            'active_posts' => ['posts', 'status = :active AND deleted_at IS NULL', ['active' => 'ACTIVE']],
            'active_geofences' => ['geofences', 'geofence_status = :active AND deleted_at IS NULL', ['active' => 'ACTIVE']],
        ];
        $result = [];
        foreach ($counts as $metric => [$table, $criteria, $parameters]) {
            $companyColumn = str_starts_with($table, 'employee_assignments') ? 'ea.company_id' : 'company_id';
            $row = $this->db->fetch('SELECT COUNT(*) AS total FROM ' . $table . ' WHERE ' . $companyColumn . ' = :company_id AND ' . $criteria, ['company_id' => $companyId, ...$parameters]);
            $result[$metric] = (int) ($row['total'] ?? 0);
        }
        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    public function recentActivity(int $limit): array
    {
        return $this->db->fetchAll('SELECT audit_log_id, user_id, entity_name, entity_id, action_name, action_status, occurred_at FROM audit_logs WHERE company_id = :company_id ORDER BY occurred_at DESC, audit_log_id DESC LIMIT :limit', ['company_id' => $this->requireCompanyId(), 'limit' => $limit]);
    }
}