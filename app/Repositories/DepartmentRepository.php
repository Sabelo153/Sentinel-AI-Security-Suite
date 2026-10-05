<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class DepartmentRepository extends Repository
{
    private const SAFE_COLUMNS = 'department_id, company_id, client_id, branch_id, department_name, department_code, status, created_at, updated_at';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'departments';
    }

    /** @return array<int, array<string, mixed>> */
    public function listForCompany(): array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetchAll(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM departments WHERE company_id = :company_id ORDER BY department_id',
            ['company_id' => $companyId],
        );
    }

    /** @return array<string, mixed>|null */
    public function findByIdForCompany(string $departmentId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM departments WHERE department_id = :department_id AND company_id = :company_id LIMIT 1',
            ['department_id' => $departmentId, 'company_id' => $companyId],
        );
    }

    public function clientBelongsToCompany(int $clientId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch(
            'SELECT client_id FROM clients WHERE client_id = :client_id AND company_id = :company_id LIMIT 1',
            ['client_id' => $clientId, 'company_id' => $companyId],
        ) !== null;
    }

    public function branchBelongsToCompany(int $branchId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch(
            'SELECT branch_id FROM branches WHERE branch_id = :branch_id AND company_id = :company_id LIMIT 1',
            ['branch_id' => $branchId, 'company_id' => $companyId],
        ) !== null;
    }

    /** @param array{department_name: string, department_code?: string|null, client_id?: int|null, branch_id?: int|null} $data @return array<string, mixed> */
    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute(
            'INSERT INTO departments (company_id, client_id, branch_id, department_name, department_code, status) '
            . 'VALUES (:company_id, :client_id, :branch_id, :department_name, :department_code, :status)',
            [
                'company_id' => $companyId,
                'client_id' => $data['client_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'department_name' => $data['department_name'],
                'department_code' => $data['department_code'] ?? null,
                'status' => 'ACTIVE',
            ],
        );

        $departmentId = (string) $this->db->connection()->lastInsertId();
        $created = $this->findByIdForCompany($departmentId);
        if ($created === null) {
            throw new \RuntimeException('Created department could not be loaded.');
        }

        return $created;
    }

    /** @param array{department_name?: string, department_code?: string|null, client_id?: int|null, branch_id?: int|null, status?: string} $data @return array<string, mixed>|null */
    public function updateForCompany(string $departmentId, array $data): ?array
    {
        $companyId = $this->requireCompanyId();
        $assignments = [];
        $params = ['department_id' => $departmentId, 'company_id' => $companyId];

        foreach (['department_name', 'department_code', 'client_id', 'branch_id', 'status'] as $column) {
            if (array_key_exists($column, $data)) {
                $assignments[] = $column . ' = :' . $column;
                $params[$column] = $data[$column];
            }
        }

        if ($assignments !== []) {
            $this->db->execute(
                'UPDATE departments SET ' . implode(', ', $assignments) . ' WHERE department_id = :department_id AND company_id = :company_id',
                $params,
            );
        }

        return $this->findByIdForCompany($departmentId);
    }
}