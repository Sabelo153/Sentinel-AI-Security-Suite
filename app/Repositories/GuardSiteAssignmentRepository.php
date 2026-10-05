<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class GuardSiteAssignmentRepository extends Repository
{
    private const SAFE_COLUMNS = 'gsa.guard_site_assignment_id, gsa.company_id, gsa.employee_assignment_id, gsa.site_id, gsa.deployment_status, gsa.starts_at, gsa.ends_at, gsa.assignment_notes, gsa.created_at, gsa.updated_at, e.employee_id, e.employee_number, e.psira_number, pe.first_name, pe.last_name, p.position_id, p.position_code, p.position_name, p.position_category, s.site_name';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'guard_site_assignments';
    }

    /** @return array<int, array<string, mixed>> */
    public function listForCompany(): array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetchAll(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM guard_site_assignments gsa '
            . 'INNER JOIN employee_assignments ea ON ea.employee_assignment_id = gsa.employee_assignment_id AND ea.company_id = gsa.company_id '
            . 'INNER JOIN employees e ON e.employee_id = ea.employee_id AND e.company_id = ea.company_id '
            . 'INNER JOIN people pe ON pe.person_id = e.person_id AND pe.company_id = e.company_id '
            . 'INNER JOIN positions p ON p.position_id = ea.position_id AND p.company_id = ea.company_id '
            . 'INNER JOIN sites s ON s.site_id = gsa.site_id AND s.company_id = gsa.company_id '
            . 'WHERE gsa.company_id = :company_id AND gsa.deleted_at IS NULL '
            . 'AND ea.deleted_at IS NULL AND ea.assignment_status = :assignment_status '
            . 'AND e.employment_status = :employee_status AND p.deleted_at IS NULL '
            . 'AND p.position_status = :position_status AND p.position_category = :category '
            . 'ORDER BY gsa.guard_site_assignment_id',
            ['company_id' => $companyId, 'assignment_status' => 'ACTIVE', 'employee_status' => 'ACTIVE', 'position_status' => 'ACTIVE', 'category' => 'GUARD'],
        );
    }

    /** @return array<string, mixed>|null */
    public function findByIdForCompany(string $assignmentId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM guard_site_assignments gsa '
            . 'INNER JOIN employee_assignments ea ON ea.employee_assignment_id = gsa.employee_assignment_id AND ea.company_id = gsa.company_id '
            . 'INNER JOIN employees e ON e.employee_id = ea.employee_id AND e.company_id = ea.company_id '
            . 'INNER JOIN people pe ON pe.person_id = e.person_id AND pe.company_id = e.company_id '
            . 'INNER JOIN positions p ON p.position_id = ea.position_id AND p.company_id = ea.company_id '
            . 'INNER JOIN sites s ON s.site_id = gsa.site_id AND s.company_id = gsa.company_id '
            . 'WHERE gsa.guard_site_assignment_id = :assignment_id AND gsa.company_id = :company_id AND gsa.deleted_at IS NULL '
            . 'AND ea.deleted_at IS NULL AND ea.assignment_status = :assignment_status '
            . 'AND e.employment_status = :employee_status AND p.deleted_at IS NULL '
            . 'AND p.position_status = :position_status AND p.position_category = :category LIMIT 1',
            ['assignment_id' => $assignmentId, 'company_id' => $companyId, 'assignment_status' => 'ACTIVE', 'employee_status' => 'ACTIVE', 'position_status' => 'ACTIVE', 'category' => 'GUARD'],
        );
    }

    public function employeeAssignmentIsGuardForCompany(string $employeeAssignmentId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch(
            'SELECT ea.employee_assignment_id FROM employee_assignments ea '
            . 'INNER JOIN employees e ON e.employee_id = ea.employee_id AND e.company_id = ea.company_id '
            . 'INNER JOIN positions p ON p.position_id = ea.position_id AND p.company_id = ea.company_id '
            . 'WHERE ea.employee_assignment_id = :assignment_id AND ea.company_id = :company_id '
            . 'AND ea.deleted_at IS NULL AND ea.assignment_status = :assignment_status '
            . 'AND e.employment_status = :employee_status AND p.deleted_at IS NULL '
            . 'AND p.position_status = :position_status AND p.position_category = :category LIMIT 1',
            ['assignment_id' => $employeeAssignmentId, 'company_id' => $companyId, 'assignment_status' => 'ACTIVE', 'employee_status' => 'ACTIVE', 'position_status' => 'ACTIVE', 'category' => 'GUARD'],
        ) !== null;
    }

    public function siteBelongsToCompany(string $siteId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch('SELECT site_id FROM sites WHERE site_id = :site_id AND company_id = :company_id LIMIT 1', ['site_id' => $siteId, 'company_id' => $companyId]) !== null;
    }

    public function exactActiveDuplicateExists(string $employeeAssignmentId, string $siteId, string $startsAt, ?string $excludeId = null): bool
    {
        $companyId = $this->requireCompanyId();
        $sql = 'SELECT guard_site_assignment_id FROM guard_site_assignments WHERE company_id = :company_id AND employee_assignment_id = :employee_assignment_id AND site_id = :site_id AND starts_at = :starts_at AND deleted_at IS NULL';
        $params = ['company_id' => $companyId, 'employee_assignment_id' => $employeeAssignmentId, 'site_id' => $siteId, 'starts_at' => $startsAt];
        if ($excludeId !== null) {
            $sql .= ' AND guard_site_assignment_id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        return $this->db->fetch($sql . ' LIMIT 1', $params) !== null;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute(
            'INSERT INTO guard_site_assignments (company_id, employee_assignment_id, site_id, deployment_status, starts_at, ends_at, assignment_notes) '
            . 'VALUES (:company_id, :employee_assignment_id, :site_id, :deployment_status, :starts_at, :ends_at, :assignment_notes)',
            [
                'company_id' => $companyId,
                'employee_assignment_id' => $data['employee_assignment_id'],
                'site_id' => $data['site_id'],
                'deployment_status' => $data['deployment_status'] ?? 'PLANNED',
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'] ?? null,
                'assignment_notes' => $data['assignment_notes'] ?? null,
            ],
        );

        $id = (string) $this->db->connection()->lastInsertId();
        $created = $this->findByIdForCompany($id);
        if ($created === null) {
            throw new \RuntimeException('Created guard-site assignment could not be loaded.');
        }
        return $created;
    }

    /** @param array<string, mixed> $data @return array<string, mixed>|null */
    public function updateForCompany(string $assignmentId, array $data): ?array
    {
        $companyId = $this->requireCompanyId();
        $assignments = [];
        $params = ['assignment_id' => $assignmentId, 'company_id' => $companyId];
        foreach (['employee_assignment_id', 'site_id', 'deployment_status', 'starts_at', 'ends_at', 'assignment_notes'] as $column) {
            if (array_key_exists($column, $data)) {
                $assignments[] = $column . ' = :' . $column;
                $params[$column] = $data[$column];
            }
        }
        if ($assignments !== []) {
            $this->db->execute('UPDATE guard_site_assignments SET ' . implode(', ', $assignments) . ' WHERE guard_site_assignment_id = :assignment_id AND company_id = :company_id AND deleted_at IS NULL', $params);
        }
        return $this->findByIdForCompany($assignmentId);
    }
}