<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\EmployeeRepository;

final class EmployeeManagementService
{
    private const STATUSES = ['ACTIVE', 'SUSPENDED', 'TERMINATED'];

    public function __construct(private readonly EmployeeRepository $employees)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listEmployees(): array
    {
        try {
            return $this->employees->listForCompany();
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Employee service unavailable.', 'employee_service_unavailable', $exception);
        }
    }

    /** @return array<string, mixed> */
    public function getEmployee(string $employeeId): array
    {
        try {
            $employee = $this->employees->findByIdForCompany($employeeId);
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Employee service unavailable.', 'employee_service_unavailable', $exception);
        }
        if ($employee === null) {
            throw HttpException::notFound('Employee not found.', 'employee_not_found');
        }

        return $employee;
    }

    /** @return array<string, mixed> */
    public function createEmployee(array $input): array
    {
        $personId = $this->requiredPositiveInt($input, 'person_id');
        try {
            if (!$this->employees->personBelongsToCompany($personId)) {
                throw HttpException::notFound('Person not found.', 'person_not_found');
            }
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Employee service unavailable.', 'employee_service_unavailable', $exception);
        }

        $data = $this->validatedFields($input, false);
        $data['person_id'] = $personId;

        try {
            return $this->employees->createForCompany($data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Employee could not be created.', 'employee_conflict');
        }
    }

    /** @return array<string, mixed> */
    public function updateEmployee(string $employeeId, array $input): array
    {
        try {
            if ($this->employees->findByIdForCompany($employeeId) === null) {
                throw HttpException::notFound('Employee not found.', 'employee_not_found');
            }
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Employee service unavailable.', 'employee_service_unavailable', $exception);
        }

        $data = $this->validatedFields($input, true);
        if ($data === []) {
            throw HttpException::unprocessable('No editable employee fields were provided.', 'invalid_employee_update');
        }

        try {
            $updated = $this->employees->updateForCompany($employeeId, $data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Employee could not be updated.', 'employee_conflict');
        }

        if ($updated === null) {
            throw HttpException::notFound('Employee not found.', 'employee_not_found');
        }

        return $updated;
    }

    /** @return array<string, mixed> */
    private function validatedFields(array $input, bool $update): array
    {
        $fields = [];
        foreach (['employee_number', 'psira_number', 'hire_date', 'employment_status'] as $field) {
            if (!$update || array_key_exists($field, $input)) {
                if ($field === 'employment_status' && !$update) {
                    continue;
                }
                $fields[$field] = $input[$field] ?? null;
            }
        }

        foreach (['employee_number', 'psira_number'] as $field) {
            if (array_key_exists($field, $fields) && $fields[$field] !== null) {
                if (!is_string($fields[$field])) {
                    throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
                }
                $fields[$field] = trim($fields[$field]);
                if ($fields[$field] === '' || strlen($fields[$field]) > 50) {
                    throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
                }
            }
        }

        if (array_key_exists('hire_date', $fields) && $fields['hire_date'] !== null) {
            if (!is_string($fields['hire_date']) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $fields['hire_date']) !== 1) {
                throw HttpException::unprocessable('Hire date is invalid.', 'invalid_hire_date');
            }
        }

        if (array_key_exists('employment_status', $fields)) {
            if (!is_string($fields['employment_status']) || !in_array($fields['employment_status'], self::STATUSES, true)) {
                throw HttpException::unprocessable('Employment status is invalid.', 'invalid_employment_status');
            }
        }

        return $fields;
    }

    private function requiredPositiveInt(array $input, string $field): int
    {
        $value = $input[$field] ?? null;
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1) {
            throw HttpException::unprocessable(ucfirst($field) . ' is required.', 'invalid_' . $field);
        }

        return (int) $value;
    }
}