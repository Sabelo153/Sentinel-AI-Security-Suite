<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\DepartmentRepository;

final class DepartmentManagementService
{
    private const STATUSES = ['ACTIVE', 'INACTIVE'];

    public function __construct(private readonly DepartmentRepository $departments)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listDepartments(): array
    {
        try {
            return $this->departments->listForCompany();
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Department service unavailable.', 'department_service_unavailable', $exception);
        }
    }

    /** @return array<string, mixed> */
    public function getDepartment(string $departmentId): array
    {
        try {
            $department = $this->departments->findByIdForCompany($departmentId);
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Department service unavailable.', 'department_service_unavailable', $exception);
        }
        if ($department === null) {
            throw HttpException::notFound('Department not found.', 'department_not_found');
        }

        return $department;
    }

    /** @return array<string, mixed> */
    public function createDepartment(array $input): array
    {
        $data = $this->validatedFields($input, false);
        $this->validateForeignKeys($data);
        if (!isset($data['department_name'])) {
            throw HttpException::unprocessable('Department name is required.', 'invalid_department_name');
        }

        try {
            return $this->departments->createForCompany($data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Department could not be created.', 'department_conflict');
        }
    }

    /** @return array<string, mixed> */
    public function updateDepartment(string $departmentId, array $input): array
    {
        try {
            if ($this->departments->findByIdForCompany($departmentId) === null) {
                throw HttpException::notFound('Department not found.', 'department_not_found');
            }
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Department service unavailable.', 'department_service_unavailable', $exception);
        }

        $data = $this->validatedFields($input, true);
        $this->validateForeignKeys($data);
        if ($data === []) {
            throw HttpException::unprocessable('No editable department fields were provided.', 'invalid_department_update');
        }

        try {
            $updated = $this->departments->updateForCompany($departmentId, $data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Department could not be updated.', 'department_conflict');
        }
        if ($updated === null) {
            throw HttpException::notFound('Department not found.', 'department_not_found');
        }

        return $updated;
    }

    /** @return array<string, mixed> */
    private function validatedFields(array $input, bool $update): array
    {
        $fields = [];
        foreach (['department_name', 'department_code', 'client_id', 'branch_id', 'status'] as $field) {
            if (array_key_exists($field, $input)) {
                $fields[$field] = $input[$field];
            }
        }

        if (array_key_exists('department_name', $fields)) {
            $fields['department_name'] = $this->requiredString($fields['department_name'], 'department_name', 150);
        }
        if (array_key_exists('department_code', $fields) && $fields['department_code'] !== null) {
            $fields['department_code'] = $this->optionalString($fields['department_code'], 'department_code', 50);
        }
        foreach (['client_id', 'branch_id'] as $field) {
            if (array_key_exists($field, $fields) && $fields[$field] !== null) {
                $fields[$field] = $this->positiveInt($fields[$field], $field);
            }
        }
        if (array_key_exists('status', $fields) && (!is_string($fields['status']) || !in_array($fields['status'], self::STATUSES, true))) {
            throw HttpException::unprocessable('Status is invalid.', 'invalid_department_status');
        }
        if (!$update && !isset($fields['department_name'])) {
            return [];
        }

        return $fields;
    }

    /** @param array<string, mixed> $data */
    private function validateForeignKeys(array $data): void
    {
        try {
            if (isset($data['client_id']) && !$this->departments->clientBelongsToCompany($data['client_id'])) {
                throw HttpException::notFound('Client not found.', 'client_not_found');
            }
            if (isset($data['branch_id']) && !$this->departments->branchBelongsToCompany($data['branch_id'])) {
                throw HttpException::notFound('Branch not found.', 'branch_not_found');
            }
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Department service unavailable.', 'department_service_unavailable', $exception);
        }
    }

    private function requiredString(mixed $value, string $field, int $maxLength): string
    {
        if (!is_string($value) || trim($value) === '' || strlen(trim($value)) > $maxLength) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }

        return trim($value);
    }

    private function optionalString(mixed $value, string $field, int $maxLength): string
    {
        if (!is_string($value) || strlen(trim($value)) > $maxLength) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }

        return trim($value);
    }

    private function positiveInt(mixed $value, string $field): int
    {
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }

        return (int) $value;
    }
}