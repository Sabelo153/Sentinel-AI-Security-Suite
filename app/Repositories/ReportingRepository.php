<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class ReportingRepository extends Repository
{
    public function __construct(Database $db, ?RuntimeContext $context = null) { parent::__construct($db, $context); }
    protected function table(): string { return 'audit_logs'; }

    /** @return array<int, array<string, mixed>> */
    public function report(string $name, array $filters): array
    {
        $definitions = [
            'attendance' => ['attendance a', 'a.company_id', 'a.attendance_id, a.employee_id, a.site_id, a.shift_date, a.clock_in_at, a.clock_out_at, a.attendance_status, a.source', 'a.shift_date', 'a.attendance_status'],
            'shifts' => ['shift_assignments s', 's.company_id', 's.shift_assignment_id, s.employee_id, s.contract_id, s.site_id, s.post_id, s.assignment_date, s.shift_name, s.shift_start_at, s.shift_end_at, s.assignment_status', 's.assignment_date', 's.assignment_status'],
            'guard-deployments' => ['guard_site_assignments g', 'g.company_id', 'g.guard_site_assignment_id, g.employee_assignment_id, g.site_id, g.deployment_status, g.starts_at, g.ends_at, g.assignment_notes', 'g.starts_at', 'g.deployment_status'],
            'patrol-sessions' => ['patrol_sessions p INNER JOIN patrol_routes r ON r.patrol_route_id = p.patrol_route_id AND r.company_id = p.company_id INNER JOIN employee_assignments ea ON ea.employee_assignment_id = p.employee_assignment_id AND ea.company_id = p.company_id', 'p.company_id', 'p.patrol_session_id, p.patrol_route_id, p.employee_assignment_id, p.shift_assignment_id, ea.employee_id, r.site_id, p.status, p.started_at, p.completed_at, p.ended_at', 'p.started_at', 'p.status'],
            'patrol-checkpoints' => ['patrol_checkpoint_events e INNER JOIN patrol_sessions p ON p.patrol_session_id = e.patrol_session_id AND p.company_id = e.company_id INNER JOIN patrol_routes r ON r.patrol_route_id = p.patrol_route_id AND r.company_id = p.company_id INNER JOIN employee_assignments ea ON ea.employee_assignment_id = p.employee_assignment_id AND ea.company_id = p.company_id', 'e.company_id', 'e.patrol_checkpoint_event_id, e.patrol_session_id, e.patrol_checkpoint_id, p.patrol_route_id, ea.employee_id, r.site_id, e.event_type, e.recorded_at', 'e.recorded_at', 'e.event_type'],
            'locations' => ['gps_locations l', 'l.company_id', 'l.gps_location_id, l.employee_id, l.user_id, l.latitude, l.longitude, l.accuracy, l.provider, l.battery_level, l.signal_strength, l.mock_location, l.confidence, l.captured_at', 'l.captured_at', null],
            'geofences' => ['geofences g', 'g.company_id', 'g.geofence_id, g.site_id, g.geofence_code, g.geofence_name, g.geofence_shape, g.center_latitude, g.center_longitude, g.radius_meters, g.geofence_status', null, 'g.geofence_status'],
            'contracts' => ['contracts c', 'c.company_id', 'c.contract_id, c.client_id, c.contract_number, c.contract_name, c.start_date, c.end_date, c.monthly_value, c.sla_tier, c.required_guard_count, c.status', 'c.start_date', 'c.status'],
            'posts' => ['posts p', 'p.company_id', 'p.post_id, p.contract_id, p.site_id, p.post_name, p.description, p.gps_lat, p.gps_long, p.status', null, 'p.status'],
            'audit' => ['audit_logs a', 'a.company_id', 'a.audit_log_id, a.user_id, a.entity_name, a.entity_id, a.action_name, a.action_status, a.occurred_at', 'a.occurred_at', 'a.action_status'],
        ];
        [$table, $companyColumn, $columns, $dateColumn, $statusColumn] = $definitions[$name];
        $where = [$companyColumn . ' = :company_id'];
        $parameters = ['company_id' => $this->requireCompanyId()];
        foreach (['employee_id', 'site_id', 'route_id', 'user_id', 'status', 'event_type', 'action', 'entity_name'] as $filter) {
            if (!array_key_exists($filter, $filters)) continue;
            $column = match ($filter) {
                'route_id' => $name === 'patrol-sessions' ? 'p.patrol_route_id' : null,
                'status' => $statusColumn,
                'action' => $name === 'audit' ? 'a.action_name' : null,
                default => str_contains($columns, ', ' . $filter) || str_starts_with($columns, explode('_', $filter)[0] . '.') ? null : null,
            };
            $column ??= match ($filter) {
                'employee_id' => $name === 'locations' ? 'l.employee_id' : ($name === 'attendance' ? 'a.employee_id' : ($name === 'shifts' ? 's.employee_id' : (str_starts_with($name, 'patrol-') ? 'ea.employee_id' : null))),
                'site_id' => match ($name) { 'attendance' => 'a.site_id', 'shifts' => 's.site_id', 'guard-deployments', 'geofences' => 'g.site_id', 'posts' => 'p.site_id', 'patrol-sessions', 'patrol-checkpoints' => 'r.site_id', default => null },
                'user_id' => $name === 'locations' ? 'l.user_id' : ($name === 'audit' ? 'a.user_id' : null),
                'event_type' => $name === 'patrol-checkpoints' ? 'e.event_type' : null,
                'entity_name' => $name === 'audit' ? 'a.entity_name' : null,
                default => $column,
            };
            if ($column !== null) { $where[] = $column . ' = :' . $filter; $parameters[$filter] = $filters[$filter]; }
        }
        if ($dateColumn !== null) {
            foreach (['date_from' => '>=', 'date_to' => '<='] as $filter => $operator) if (isset($filters[$filter])) { $where[] = $dateColumn . ' ' . $operator . ' :' . $filter; $parameters[$filter] = $filters[$filter]; }
        }
        $parameters['limit'] = $filters['limit'];
        return $this->db->fetchAll('SELECT ' . $columns . ' FROM ' . $table . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . ($dateColumn ?? '1') . ' DESC LIMIT :limit', $parameters);
    }

    public function belongsToCompany(string $table, string $idColumn, int $id): bool
    {
        return $this->db->fetch('SELECT ' . $idColumn . ' FROM ' . $table . ' WHERE ' . $idColumn . ' = :id AND company_id = :company_id LIMIT 1', ['id' => $id, 'company_id' => $this->requireCompanyId()]) !== null;
    }
}