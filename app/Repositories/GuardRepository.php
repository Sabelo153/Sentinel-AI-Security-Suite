<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class GuardRepository extends Repository
{
    private const SAFE_COLUMNS = 'e.employee_id, e.employee_number, e.psira_number, e.employment_status, e.person_id, ea.employee_assignment_id AS assignment_id, ea.assignment_code, ea.assignment_status, ea.starts_at, ea.ends_at, ea.is_primary, ea.assignment_notes, p.position_id, p.position_code, p.position_name, p.position_category, pe.first_name, pe.last_name, pe.phone, pe.email';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'employees';
    }

    /** @return array<int, array<string, mixed>> */
    public function listForCompany(): array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetchAll(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM employees e '
            . 'INNER JOIN employee_assignments ea ON ea.employee_id = e.employee_id AND ea.company_id = e.company_id '
            . 'INNER JOIN positions p ON p.position_id = ea.position_id AND p.company_id = ea.company_id '
            . 'INNER JOIN people pe ON pe.person_id = e.person_id AND pe.company_id = e.company_id '
            . 'WHERE e.company_id = :company_id AND e.employment_status = :employee_status '
            . 'AND ea.assignment_status = :assignment_status AND ea.deleted_at IS NULL '
            . 'AND p.position_category = :category AND p.position_status = :position_status AND p.deleted_at IS NULL '
            . 'ORDER BY e.employee_id, ea.is_primary DESC, ea.employee_assignment_id',
            ['company_id' => $companyId, 'employee_status' => 'ACTIVE', 'assignment_status' => 'ACTIVE', 'category' => 'GUARD', 'position_status' => 'ACTIVE'],
        );
    }

    /** @return array<string, mixed>|null */
    public function findByEmployeeIdForCompany(string $employeeId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM employees e '
            . 'INNER JOIN employee_assignments ea ON ea.employee_id = e.employee_id AND ea.company_id = e.company_id '
            . 'INNER JOIN positions p ON p.position_id = ea.position_id AND p.company_id = ea.company_id '
            . 'INNER JOIN people pe ON pe.person_id = e.person_id AND pe.company_id = e.company_id '
            . 'WHERE e.employee_id = :employee_id AND e.company_id = :company_id AND e.employment_status = :employee_status '
            . 'AND ea.assignment_status = :assignment_status AND ea.deleted_at IS NULL '
            . 'AND p.position_category = :category AND p.position_status = :position_status AND p.deleted_at IS NULL '
            . 'ORDER BY ea.is_primary DESC, ea.employee_assignment_id LIMIT 1',
            ['employee_id' => $employeeId, 'company_id' => $companyId, 'employee_status' => 'ACTIVE', 'assignment_status' => 'ACTIVE', 'category' => 'GUARD', 'position_status' => 'ACTIVE'],
        );
    }

    /** @return array<string, mixed>|null */
    public function findByAssignmentIdForCompany(string $assignmentId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM employees e '
            . 'INNER JOIN employee_assignments ea ON ea.employee_id = e.employee_id AND ea.company_id = e.company_id '
            . 'INNER JOIN positions p ON p.position_id = ea.position_id AND p.company_id = ea.company_id '
            . 'INNER JOIN people pe ON pe.person_id = e.person_id AND pe.company_id = e.company_id '
            . 'WHERE ea.employee_assignment_id = :assignment_id AND ea.company_id = :company_id AND e.employment_status = :employee_status '
            . 'AND ea.assignment_status = :assignment_status AND ea.deleted_at IS NULL '
            . 'AND p.position_category = :category AND p.position_status = :position_status AND p.deleted_at IS NULL LIMIT 1',
            ['assignment_id' => $assignmentId, 'company_id' => $companyId, 'employee_status' => 'ACTIVE', 'assignment_status' => 'ACTIVE', 'category' => 'GUARD', 'position_status' => 'ACTIVE'],
        );
    }

    public function isGuardEmployee(string $employeeId): bool
    {
        return $this->findByEmployeeIdForCompany($employeeId) !== null;
    }
}