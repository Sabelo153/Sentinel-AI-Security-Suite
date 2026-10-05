<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class ShiftAssignmentRepository extends Repository
{
    private const COLUMNS = 'sa.shift_assignment_id, sa.company_id, sa.employee_id, sa.contract_id, sa.site_id, sa.post_id, sa.assignment_date, sa.shift_name, sa.shift_start_at, sa.shift_end_at, sa.assignment_status, sa.check_in_window_start, sa.check_in_window_end, sa.created_at, sa.updated_at, e.employee_number, e.psira_number, pe.first_name, pe.last_name, s.site_name, p.post_name';

    public function __construct(Database $db, ?RuntimeContext $context = null) { parent::__construct($db, $context); }
    protected function table(): string { return 'shift_assignments'; }

    public function listForCompany(): array
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetchAll('SELECT ' . self::COLUMNS . ' FROM shift_assignments sa INNER JOIN employees e ON e.employee_id = sa.employee_id AND e.company_id = sa.company_id INNER JOIN people pe ON pe.person_id = e.person_id AND pe.company_id = e.company_id LEFT JOIN sites s ON s.site_id = sa.site_id AND s.company_id = sa.company_id LEFT JOIN posts p ON p.post_id = sa.post_id AND p.company_id = sa.company_id WHERE sa.company_id = :company_id AND sa.deleted_at IS NULL ORDER BY sa.assignment_date, sa.shift_start_at, sa.shift_assignment_id', ['company_id' => $companyId]);
    }

    public function findByIdForCompany(string $id): ?array
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch('SELECT ' . self::COLUMNS . ' FROM shift_assignments sa INNER JOIN employees e ON e.employee_id = sa.employee_id AND e.company_id = sa.company_id INNER JOIN people pe ON pe.person_id = e.person_id AND pe.company_id = e.company_id LEFT JOIN sites s ON s.site_id = sa.site_id AND s.company_id = sa.company_id LEFT JOIN posts p ON p.post_id = sa.post_id AND p.company_id = sa.company_id WHERE sa.shift_assignment_id = :id AND sa.company_id = :company_id AND sa.deleted_at IS NULL LIMIT 1', ['id' => $id, 'company_id' => $companyId]);
    }

    public function employeeIsGuard(string $employeeId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch('SELECT ea.employee_assignment_id FROM employee_assignments ea INNER JOIN employees e ON e.employee_id = ea.employee_id AND e.company_id = ea.company_id INNER JOIN positions p ON p.position_id = ea.position_id AND p.company_id = ea.company_id WHERE ea.employee_id = :employee_id AND ea.company_id = :company_id AND ea.assignment_status = :assignment_status AND ea.deleted_at IS NULL AND e.employment_status = :employee_status AND p.position_category = :category AND p.position_status = :position_status AND p.deleted_at IS NULL LIMIT 1', ['employee_id' => $employeeId, 'company_id' => $companyId, 'assignment_status' => 'ACTIVE', 'employee_status' => 'ACTIVE', 'category' => 'GUARD', 'position_status' => 'ACTIVE']) !== null;
    }

    public function siteBelongsToCompany(string $siteId): bool { $companyId = $this->requireCompanyId(); return $this->db->fetch('SELECT site_id FROM sites WHERE site_id = :id AND company_id = :company_id LIMIT 1', ['id' => $siteId, 'company_id' => $companyId]) !== null; }
    public function postBelongsToSite(string $postId, string $siteId): bool { $companyId = $this->requireCompanyId(); return $this->db->fetch('SELECT post_id FROM posts WHERE post_id = :post_id AND site_id = :site_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1', ['post_id' => $postId, 'site_id' => $siteId, 'company_id' => $companyId]) !== null; }
    public function contractBelongsToCompany(string $contractId): bool { $companyId = $this->requireCompanyId(); return $this->db->fetch('SELECT contract_id FROM contracts WHERE contract_id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1', ['id' => $contractId, 'company_id' => $companyId]) !== null; }
    public function userBelongsToCompany(string $userId): bool { $companyId = $this->requireCompanyId(); return $this->db->fetch('SELECT user_id FROM users WHERE user_id = :id AND company_id = :company_id LIMIT 1', ['id' => $userId, 'company_id' => $companyId]) !== null; }

    public function overlappingShiftExists(string $employeeId, string $start, string $end, ?string $excludeId = null): bool
    {
        $companyId = $this->requireCompanyId();
        $sql = 'SELECT shift_assignment_id FROM shift_assignments WHERE company_id = :company_id AND employee_id = :employee_id AND deleted_at IS NULL AND assignment_status NOT IN (\'COMPLETED\', \'MISSED\', \'CANCELLED\') AND shift_start_at < :new_end AND shift_end_at > :new_start';
        $params = ['company_id' => $companyId, 'employee_id' => $employeeId, 'new_end' => $end, 'new_start' => $start];
        if ($excludeId !== null) { $sql .= ' AND shift_assignment_id <> :exclude_id'; $params['exclude_id'] = $excludeId; }
        return $this->db->fetch($sql . ' LIMIT 1', $params) !== null;
    }

    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute('INSERT INTO shift_assignments (shift_assignment_uuid, company_id, employee_id, contract_id, site_id, post_id, assignment_date, shift_name, shift_start_at, shift_end_at, assignment_status, check_in_window_start, check_in_window_end) VALUES (:uuid, :company_id, :employee_id, :contract_id, :site_id, :post_id, :assignment_date, :shift_name, :shift_start_at, :shift_end_at, :assignment_status, :check_in_window_start, :check_in_window_end)', [
            'uuid' => uuid4(), 'company_id' => $companyId, 'employee_id' => $data['employee_id'], 'contract_id' => $data['contract_id'] ?? null, 'site_id' => $data['site_id'] ?? null, 'post_id' => $data['post_id'] ?? null, 'assignment_date' => $data['assignment_date'], 'shift_name' => $data['shift_name'], 'shift_start_at' => $data['shift_start_at'], 'shift_end_at' => $data['shift_end_at'], 'assignment_status' => $data['assignment_status'] ?? 'PLANNED', 'check_in_window_start' => $data['check_in_window_start'] ?? null, 'check_in_window_end' => $data['check_in_window_end'] ?? null,
        ]);
        $id = (string) $this->db->connection()->lastInsertId(); $created = $this->findByIdForCompany($id); if ($created === null) { throw new \RuntimeException('Created shift assignment could not be loaded.'); } return $created;
    }

    public function updateForCompany(string $id, array $data): ?array
    {
        $companyId = $this->requireCompanyId(); $assignments = []; $params = ['id' => $id, 'company_id' => $companyId];
        foreach (['employee_id','contract_id','site_id','post_id','assignment_date','shift_name','shift_start_at','shift_end_at','assignment_status','check_in_window_start','check_in_window_end'] as $column) { if (array_key_exists($column, $data)) { $assignments[] = $column . ' = :' . $column; $params[$column] = $data[$column]; } }
        if ($assignments !== []) { $this->db->execute('UPDATE shift_assignments SET ' . implode(', ', $assignments) . ' WHERE shift_assignment_id = :id AND company_id = :company_id AND deleted_at IS NULL', $params); }
        return $this->findByIdForCompany($id);
    }
}