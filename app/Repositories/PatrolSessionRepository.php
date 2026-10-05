<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class PatrolSessionRepository extends Repository
{
    private const COLUMNS = 'ps.patrol_session_id, ps.patrol_session_uuid, ps.company_id, ps.patrol_route_id, ps.employee_assignment_id, ps.shift_assignment_id, ps.status, ps.started_at, ps.completed_at, ps.ended_at, ps.execution_notes, ps.created_at, ps.updated_at, pr.route_name, pr.route_code, pr.site_id, s.site_name, ea.employee_id, e.employee_number, pe.first_name, pe.last_name, p.position_name, sa.shift_name, sa.assignment_date, sa.shift_start_at, sa.shift_end_at';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'patrol_sessions';
    }

    public function listForCompany(): array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM patrol_sessions ps '
            . 'INNER JOIN patrol_routes pr ON pr.patrol_route_id = ps.patrol_route_id AND pr.company_id = ps.company_id '
            . 'INNER JOIN sites s ON s.site_id = pr.site_id AND s.company_id = pr.company_id '
            . 'INNER JOIN employee_assignments ea ON ea.employee_assignment_id = ps.employee_assignment_id AND ea.company_id = ps.company_id '
            . 'INNER JOIN employees e ON e.employee_id = ea.employee_id AND e.company_id = ea.company_id '
            . 'INNER JOIN people pe ON pe.person_id = e.person_id AND pe.company_id = e.company_id '
            . 'INNER JOIN positions p ON p.position_id = ea.position_id AND p.company_id = ea.company_id '
            . 'LEFT JOIN shift_assignments sa ON sa.shift_assignment_id = ps.shift_assignment_id AND sa.company_id = ps.company_id '
            . 'WHERE ps.company_id = :company_id AND ps.deleted_at IS NULL ORDER BY ps.started_at DESC, ps.patrol_session_id DESC',
            ['company_id' => $companyId],
        );
    }

    public function findByIdForCompany(string $sessionId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM patrol_sessions ps '
            . 'INNER JOIN patrol_routes pr ON pr.patrol_route_id = ps.patrol_route_id AND pr.company_id = ps.company_id '
            . 'INNER JOIN sites s ON s.site_id = pr.site_id AND s.company_id = pr.company_id '
            . 'INNER JOIN employee_assignments ea ON ea.employee_assignment_id = ps.employee_assignment_id AND ea.company_id = ps.company_id '
            . 'INNER JOIN employees e ON e.employee_id = ea.employee_id AND e.company_id = ea.company_id '
            . 'INNER JOIN people pe ON pe.person_id = e.person_id AND pe.company_id = e.company_id '
            . 'INNER JOIN positions p ON p.position_id = ea.position_id AND p.company_id = ea.company_id '
            . 'LEFT JOIN shift_assignments sa ON sa.shift_assignment_id = ps.shift_assignment_id AND sa.company_id = ps.company_id '
            . 'WHERE ps.patrol_session_id = :session_id AND ps.company_id = :company_id AND ps.deleted_at IS NULL LIMIT 1',
            ['session_id' => $sessionId, 'company_id' => $companyId],
        );
    }

    public function routeDetails(string $routeId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT patrol_route_id, site_id, status FROM patrol_routes WHERE patrol_route_id = :route_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',
            ['route_id' => $routeId, 'company_id' => $companyId],
        );
    }

    public function guardAssignmentDetails(string $assignmentId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ea.employee_assignment_id, ea.employee_id FROM employee_assignments ea '
            . 'INNER JOIN employees e ON e.employee_id = ea.employee_id AND e.company_id = ea.company_id '
            . 'INNER JOIN positions p ON p.position_id = ea.position_id AND p.company_id = ea.company_id '
            . 'WHERE ea.employee_assignment_id = :assignment_id AND ea.company_id = :company_id '
            . 'AND ea.assignment_status = :assignment_status AND ea.deleted_at IS NULL '
            . 'AND e.employment_status = :employment_status '
            . 'AND p.position_category = :position_category AND p.position_status = :position_status AND p.deleted_at IS NULL LIMIT 1',
            ['assignment_id' => $assignmentId, 'company_id' => $companyId, 'assignment_status' => 'ACTIVE', 'employment_status' => 'ACTIVE', 'position_category' => 'GUARD', 'position_status' => 'ACTIVE'],
        );
    }

    public function shiftDetails(string $shiftId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT shift_assignment_id, employee_id, site_id, shift_name, assignment_date, shift_start_at, shift_end_at FROM shift_assignments WHERE shift_assignment_id = :shift_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',
            ['shift_id' => $shiftId, 'company_id' => $companyId],
        );
    }

    public function activeSessionExistsForEmployeeAssignment(string $assignmentId, ?string $excludeSessionId = null): bool
    {
        $companyId = $this->requireCompanyId();
        $sql = 'SELECT patrol_session_id FROM patrol_sessions WHERE employee_assignment_id = :assignment_id AND company_id = :company_id AND status = :status AND deleted_at IS NULL';
        $params = ['assignment_id' => $assignmentId, 'company_id' => $companyId, 'status' => 'IN_PROGRESS'];

        if ($excludeSessionId !== null) {
            $sql .= ' AND patrol_session_id <> :exclude_session_id';
            $params['exclude_session_id'] = $excludeSessionId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params) !== null;
    }

    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute(
            'INSERT INTO patrol_sessions (patrol_session_uuid, company_id, patrol_route_id, employee_assignment_id, shift_assignment_id, status, started_at, completed_at, ended_at, execution_notes) '
            . 'VALUES (:uuid, :company_id, :route_id, :assignment_id, :shift_id, :status, :started_at, NULL, NULL, :execution_notes)',
            [
                'uuid' => uuid4(),
                'company_id' => $companyId,
                'route_id' => $data['patrol_route_id'],
                'assignment_id' => $data['employee_assignment_id'],
                'shift_id' => $data['shift_assignment_id'] ?? null,
                'status' => 'IN_PROGRESS',
                'started_at' => $data['started_at'],
                'execution_notes' => $data['execution_notes'] ?? null,
            ],
        );

        $id = (string) $this->db->connection()->lastInsertId();
        $created = $this->findByIdForCompany($id);
        if ($created === null) {
            throw new \RuntimeException('Created patrol session could not be loaded.');
        }

        return $created;
    }

    public function updateForCompany(string $sessionId, array $data): ?array
    {
        $companyId = $this->requireCompanyId();
        $assignments = [];
        $params = ['session_id' => $sessionId, 'company_id' => $companyId];

        foreach (['status', 'completed_at', 'ended_at', 'execution_notes'] as $column) {
            if (array_key_exists($column, $data)) {
                $assignments[] = $column . ' = :' . $column;
                $params[$column] = $data[$column];
            }
        }

        if ($assignments !== []) {
            $this->db->execute(
                'UPDATE patrol_sessions SET ' . implode(', ', $assignments) . ' WHERE patrol_session_id = :session_id AND company_id = :company_id AND deleted_at IS NULL',
                $params,
            );
        }

        return $this->findByIdForCompany($sessionId);
    }
}
