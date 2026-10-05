<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class EmployeeAssignmentRepository extends Repository
{
    private const SAFE_COLUMNS = 'employee_assignment_id, company_id, employee_id, user_id, organization_id, organizational_unit_id, position_id, authority_level_id, assignment_code, assignment_status, starts_at, ends_at, is_primary, assigned_by_user_id, assignment_notes, created_at, updated_at, deleted_at';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'employee_assignments';
    }

    /** @return array<int, array<string, mixed>> */
    public function listForCompany(): array
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetchAll('SELECT ' . self::SAFE_COLUMNS . ' FROM employee_assignments WHERE company_id = :company_id AND deleted_at IS NULL ORDER BY employee_assignment_id', ['company_id' => $companyId]);
    }

    /** @return array<string, mixed>|null */
    public function findByIdForCompany(string $assignmentId): ?array
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch('SELECT ' . self::SAFE_COLUMNS . ' FROM employee_assignments WHERE employee_assignment_id = :assignment_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1', ['assignment_id' => $assignmentId, 'company_id' => $companyId]);
    }

    public function employeeBelongsToCompany(int $employeeId): bool
    {
        return $this->tenantRecordExists('employees', 'employee_id', $employeeId, '');
    }

    public function positionBelongsToCompany(int $positionId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch('SELECT position_id FROM positions WHERE position_id = :position_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1', ['position_id' => $positionId, 'company_id' => $companyId]) !== null;
    }

    public function authorityLevels(): array
    {
        $this->requireCompanyId();
        return $this->db->fetchAll("SELECT authority_level_id, authority_name, rank_value FROM authority_levels WHERE authority_status='ACTIVE' ORDER BY rank_value");
    }

    public function authorityLevelIsUsable(int $authorityLevelId): bool
    {
        return $this->db->fetch('SELECT authority_level_id FROM authority_levels WHERE authority_level_id = :authority_level_id AND authority_status = :status LIMIT 1', ['authority_level_id' => $authorityLevelId, 'status' => 'ACTIVE']) !== null;
    }

    public function userBelongsToCompany(int $userId): bool
    {
        return $this->tenantRecordExists('users', 'user_id', $userId, '');
    }

    public function organizationBelongsToCompany(int $organizationId): bool
    {
        return $this->tenantRecordExists('organizations', 'organization_id', $organizationId, ' AND deleted_at IS NULL');
    }

    public function unitBelongsToCompany(int $unitId): bool
    {
        return $this->tenantRecordExists('organizational_units', 'organizational_unit_id', $unitId, ' AND deleted_at IS NULL');
    }

    /** @return array{organization_id: int|null, organizational_unit_id: int|null, position_category: string}|null */
    public function positionScope(int $positionId): ?array
    {
        $companyId = $this->requireCompanyId();
        $row = $this->db->fetch('SELECT organization_id, organizational_unit_id, position_category FROM positions WHERE position_id = :position_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1', ['position_id' => $positionId, 'company_id' => $companyId]);
        return $row === null ? null : [
            'organization_id' => $row['organization_id'] === null ? null : (int) $row['organization_id'],
            'organizational_unit_id' => $row['organizational_unit_id'] === null ? null : (int) $row['organizational_unit_id'],
            'position_category' => (string) $row['position_category'],
        ];
    }

    public function unitBelongsToOrganization(int $unitId, int $organizationId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch('SELECT organizational_unit_id FROM organizational_units WHERE organizational_unit_id = :unit_id AND organization_id = :organization_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1', ['unit_id' => $unitId, 'organization_id' => $organizationId, 'company_id' => $companyId]) !== null;
    }

    public function assignmentHasPositionCategory(string $assignmentId, string $category): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch('SELECT ea.employee_assignment_id FROM employee_assignments ea INNER JOIN positions p ON p.position_id = ea.position_id AND p.company_id = ea.company_id WHERE ea.employee_assignment_id = :assignment_id AND ea.company_id = :company_id AND ea.deleted_at IS NULL AND p.deleted_at IS NULL AND p.position_category = :category LIMIT 1', ['assignment_id' => $assignmentId, 'company_id' => $companyId, 'category' => $category]) !== null;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute('INSERT INTO employee_assignments (company_id, employee_id, user_id, organization_id, organizational_unit_id, position_id, authority_level_id, assignment_code, assignment_status, starts_at, ends_at, is_primary, assigned_by_user_id, assignment_notes) VALUES (:company_id, :employee_id, :user_id, :organization_id, :organizational_unit_id, :position_id, :authority_level_id, :assignment_code, :assignment_status, :starts_at, :ends_at, :is_primary, :assigned_by_user_id, :assignment_notes)', [
            'company_id' => $companyId, 'employee_id' => $data['employee_id'], 'user_id' => $data['user_id'] ?? null, 'organization_id' => $data['organization_id'] ?? null, 'organizational_unit_id' => $data['organizational_unit_id'] ?? null, 'position_id' => $data['position_id'], 'authority_level_id' => $data['authority_level_id'], 'assignment_code' => $data['assignment_code'], 'assignment_status' => $data['assignment_status'] ?? 'ACTIVE', 'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null, 'is_primary' => $data['is_primary'] ?? true, 'assigned_by_user_id' => $data['assigned_by_user_id'] ?? null, 'assignment_notes' => $data['assignment_notes'] ?? null,
        ]);
        $id = (string) $this->db->connection()->lastInsertId();
        $created = $this->findByIdForCompany($id);
        if ($created === null) {
            throw new \RuntimeException('Created assignment could not be loaded.');
        }
        return $created;
    }

    /** @param array<string, mixed> $data @return array<string, mixed>|null */
    public function updateForCompany(string $assignmentId, array $data): ?array
    {
        $companyId = $this->requireCompanyId();
        $assignments = [];
        $params = ['assignment_id' => $assignmentId, 'company_id' => $companyId];
        foreach (['employee_id', 'user_id', 'organization_id', 'organizational_unit_id', 'position_id', 'authority_level_id', 'assignment_code', 'assignment_status', 'starts_at', 'ends_at', 'is_primary', 'assigned_by_user_id', 'assignment_notes'] as $column) {
            if (array_key_exists($column, $data)) {
                $assignments[] = $column . ' = :' . $column;
                $params[$column] = $data[$column];
            }
        }
        if ($assignments !== []) {
            $this->db->execute('UPDATE employee_assignments SET ' . implode(', ', $assignments) . ' WHERE employee_assignment_id = :assignment_id AND company_id = :company_id AND deleted_at IS NULL', $params);
        }
        return $this->findByIdForCompany($assignmentId);
    }

    private function tenantRecordExists(string $table, string $idColumn, int $id, string $suffix): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch("SELECT {$idColumn} FROM {$table} WHERE {$idColumn} = :record_id AND company_id = :company_id{$suffix} LIMIT 1", ['record_id' => $id, 'company_id' => $companyId]) !== null;
    }
}