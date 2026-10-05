<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class EmployeeRepository extends Repository
{
    private const SAFE_COLUMNS = 'employee_id, company_id, person_id, employee_number, psira_number, hire_date, employment_status, created_at, updated_at';

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
            'SELECT ' . self::SAFE_COLUMNS . ' FROM employees WHERE company_id = :company_id ORDER BY employee_id',
            ['company_id' => $companyId],
        );
    }

    /** @return array<string, mixed>|null */
    public function findByIdForCompany(string $employeeId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM employees WHERE employee_id = :employee_id AND company_id = :company_id LIMIT 1',
            ['employee_id' => $employeeId, 'company_id' => $companyId],
        );
    }

    public function personBelongsToCompany(int $personId): bool
    {
        $companyId = $this->requireCompanyId();
        $row = $this->db->fetch(
            'SELECT person_id FROM people WHERE person_id = :person_id AND company_id = :company_id LIMIT 1',
            ['person_id' => $personId, 'company_id' => $companyId],
        );

        return $row !== null;
    }

    /** @param array{person_id: int, employee_number?: string|null, psira_number?: string|null, hire_date?: string|null} $data @return array<string, mixed> */
    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute(
            'INSERT INTO employees (company_id, person_id, employee_number, psira_number, hire_date, employment_status) '
            . 'VALUES (:company_id, :person_id, :employee_number, :psira_number, :hire_date, :employment_status)',
            [
                'company_id' => $companyId,
                'person_id' => $data['person_id'],
                'employee_number' => $data['employee_number'] ?? null,
                'psira_number' => $data['psira_number'] ?? null,
                'hire_date' => $data['hire_date'] ?? null,
                'employment_status' => 'ACTIVE',
            ],
        );

        $employeeId = (string) $this->db->connection()->lastInsertId();
        $created = $this->findByIdForCompany($employeeId);
        if ($created === null) {
            throw new \RuntimeException('Created employee could not be loaded.');
        }

        return $created;
    }

    /** @param array{employee_number?: string|null, psira_number?: string|null, hire_date?: string|null, employment_status?: string} $data @return array<string, mixed>|null */
    public function updateForCompany(string $employeeId, array $data): ?array
    {
        $companyId = $this->requireCompanyId();
        $assignments = [];
        $params = ['employee_id' => $employeeId, 'company_id' => $companyId];

        foreach (['employee_number', 'psira_number', 'hire_date', 'employment_status'] as $column) {
            if (array_key_exists($column, $data)) {
                $assignments[] = $column . ' = :' . $column;
                $params[$column] = $data[$column];
            }
        }

        if ($assignments !== []) {
            $this->db->execute(
                'UPDATE employees SET ' . implode(', ', $assignments) . ' WHERE employee_id = :employee_id AND company_id = :company_id',
                $params,
            );
        }

        return $this->findByIdForCompany($employeeId);
    }
}