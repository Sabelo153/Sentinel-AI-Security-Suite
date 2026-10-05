<?php

declare(strict_types=1);

namespace Sentinel\Services;

use DateTimeImmutable;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\ReportingRepository;

final class ReportingService
{
    private const REPORTS = ['attendance', 'shifts', 'guard-deployments', 'patrol-sessions', 'patrol-checkpoints', 'locations', 'geofences', 'contracts', 'posts', 'audit'];
    private const STATUSES = [
        'attendance' => ['PRESENT', 'LATE', 'ABSENT', 'OFF_DUTY'], 'shifts' => ['PLANNED', 'ASSIGNED', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED', 'MISSED', 'CANCELLED'],
        'guard-deployments' => ['PLANNED', 'ACTIVE', 'SUSPENDED', 'ENDED', 'CANCELLED'], 'patrol-sessions' => ['IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'ABORTED'],
        'geofences' => ['ACTIVE', 'INACTIVE', 'ARCHIVED'], 'contracts' => ['DRAFT', 'ACTIVE', 'SUSPENDED', 'EXPIRED', 'TERMINATED', 'ARCHIVED'], 'posts' => ['ACTIVE', 'INACTIVE', 'ARCHIVED'], 'audit' => ['SUCCESS', 'FAILED', 'DENIED'],
    ];
    private const FILTERS = [
        'attendance' => ['date_from', 'date_to', 'employee_id', 'site_id', 'status', 'limit'], 'shifts' => ['date_from', 'date_to', 'employee_id', 'site_id', 'status', 'limit'],
        'guard-deployments' => ['date_from', 'date_to', 'site_id', 'status', 'limit'], 'patrol-sessions' => ['date_from', 'date_to', 'route_id', 'employee_id', 'site_id', 'status', 'limit'],
        'patrol-checkpoints' => ['date_from', 'date_to', 'employee_id', 'site_id', 'event_type', 'limit'], 'locations' => ['date_from', 'date_to', 'employee_id', 'user_id', 'limit'],
        'geofences' => ['site_id', 'status', 'limit'], 'contracts' => ['date_from', 'date_to', 'status', 'limit'], 'posts' => ['site_id', 'status', 'limit'],
        'audit' => ['date_from', 'date_to', 'user_id', 'status', 'action', 'entity_name', 'limit'],
    ];
    private const CSV_COLUMNS = [
        'attendance' => ['attendance_id', 'employee_id', 'site_id', 'shift_date', 'clock_in_at', 'clock_out_at', 'attendance_status', 'source'], 'shifts' => ['shift_assignment_id', 'employee_id', 'contract_id', 'site_id', 'post_id', 'assignment_date', 'shift_name', 'shift_start_at', 'shift_end_at', 'assignment_status'],
        'guard-deployments' => ['guard_site_assignment_id', 'employee_assignment_id', 'site_id', 'deployment_status', 'starts_at', 'ends_at', 'assignment_notes'], 'patrol-sessions' => ['patrol_session_id', 'patrol_route_id', 'employee_assignment_id', 'shift_assignment_id', 'employee_id', 'site_id', 'status', 'started_at', 'completed_at', 'ended_at'],
        'patrol-checkpoints' => ['patrol_checkpoint_event_id', 'patrol_session_id', 'patrol_checkpoint_id', 'patrol_route_id', 'employee_id', 'site_id', 'event_type', 'recorded_at'], 'locations' => ['gps_location_id', 'employee_id', 'user_id', 'latitude', 'longitude', 'accuracy', 'provider', 'battery_level', 'signal_strength', 'mock_location', 'confidence', 'captured_at'],
        'geofences' => ['geofence_id', 'site_id', 'geofence_code', 'geofence_name', 'geofence_shape', 'center_latitude', 'center_longitude', 'radius_meters', 'geofence_status'], 'contracts' => ['contract_id', 'client_id', 'contract_number', 'contract_name', 'start_date', 'end_date', 'monthly_value', 'sla_tier', 'required_guard_count', 'status'],
        'posts' => ['post_id', 'contract_id', 'site_id', 'post_name', 'description', 'gps_lat', 'gps_long', 'status'], 'audit' => ['audit_log_id', 'user_id', 'entity_name', 'entity_id', 'action_name', 'action_status', 'occurred_at'],
    ];
    public function __construct(private readonly ReportingRepository $reports, private readonly CsvExportService $csv) {}
    /** @return array<int, array<string, mixed>> */
    public function report(string $name, array $filters): array { return $this->reports->report($this->reportName($name), $this->filters($name, $filters)); }
    public function csv(string $name, array $filters): string { $name = $this->reportName($name); return $this->csv->export($this->report($name, $filters), self::CSV_COLUMNS[$name]); }
    private function reportName(string $name): string { if (!in_array($name, self::REPORTS, true)) throw HttpException::notFound('Report not found.', 'report_not_found'); return $name; }
    private function filters(string $name, array $filters): array
    {
        if (array_diff(array_keys($filters), self::FILTERS[$name]) !== []) throw HttpException::unprocessable('Invalid report filters.', 'invalid_report_filters');
        foreach (['date_from', 'date_to'] as $field) if (isset($filters[$field]) && (!$this->date($filters[$field]))) throw HttpException::unprocessable('Report date is invalid.', 'invalid_' . $field);
        if (isset($filters['date_from'], $filters['date_to']) && $filters['date_from'] > $filters['date_to']) throw HttpException::unprocessable('Report date range is invalid.', 'invalid_date_range');
        if (isset($filters['status']) && (!isset(self::STATUSES[$name]) || !in_array($filters['status'], self::STATUSES[$name], true))) throw HttpException::unprocessable('Report status is invalid.', 'invalid_report_status');
        foreach (['employee_id', 'site_id', 'route_id', 'user_id'] as $field) if (isset($filters[$field]) && ((!is_int($filters[$field]) && !(is_string($filters[$field]) && ctype_digit($filters[$field]))) || (int) $filters[$field] < 1)) throw HttpException::unprocessable('Report identifier is invalid.', 'invalid_' . $field);
        foreach (['employee_id' => ['employees', 'employee_id'], 'site_id' => ['sites', 'site_id'], 'route_id' => ['patrol_routes', 'patrol_route_id'], 'user_id' => ['users', 'user_id']] as $field => [$table, $column]) if (isset($filters[$field]) && !$this->reports->belongsToCompany($table, $column, (int) $filters[$field])) throw HttpException::notFound('Report filter resource not found.', $field . '_not_found');
        if (isset($filters['limit']) && ((!is_int($filters['limit']) && !(is_string($filters['limit']) && ctype_digit($filters['limit']))) || (int) $filters['limit'] < 1 || (int) $filters['limit'] > 1000)) throw HttpException::unprocessable('Report limit is invalid.', 'invalid_report_limit');
        $filters['limit'] = (int) ($filters['limit'] ?? 500);
        return $filters;
    }
    private function date(mixed $value): bool { $date = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false; return $date !== false && $date->format('Y-m-d') === $value; }
}