<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;

final class EmployeeAssignmentManagementService
{
    private const STATUSES = ['PLANNED', 'ACTIVE', 'SUSPENDED', 'COMPLETED', 'CANCELLED'];

    public function __construct(private readonly \Sentinel\Repositories\EmployeeAssignmentRepository $assignments)
    {
    }

    public function authorityLevels(): array
    {
        try { return $this->assignments->authorityLevels(); }
        catch (PDOException) { throw HttpException::serviceUnavailable('Authority levels are unavailable.'); }
    }

    public function listAssignments(): array
    {
        try { return $this->assignments->listForCompany(); }
        catch (PDOException $exception) { throw new HttpException(503, 'Assignment service unavailable.', 'assignment_service_unavailable', $exception); }
    }

    public function getAssignment(string $assignmentId): array
    {
        try { $assignment = $this->assignments->findByIdForCompany($assignmentId); }
        catch (PDOException $exception) { throw new HttpException(503, 'Assignment service unavailable.', 'assignment_service_unavailable', $exception); }
        if ($assignment === null) { throw HttpException::notFound('Assignment not found.', 'assignment_not_found'); }
        return $assignment;
    }

    public function createAssignment(array $input): array
    {
        $data = $this->validateFields($input, false);
        $this->validateReferences($data);
        try { return $this->assignments->createForCompany($data); }
        catch (PDOException $exception) { throw HttpException::conflict('Assignment could not be created.', 'assignment_conflict'); }
    }

    public function updateAssignment(string $assignmentId, array $input): array
    {
        try {
            if ($this->assignments->findByIdForCompany($assignmentId) === null) { throw HttpException::notFound('Assignment not found.', 'assignment_not_found'); }
        } catch (PDOException $exception) { throw new HttpException(503, 'Assignment service unavailable.', 'assignment_service_unavailable', $exception); }
        $data = $this->validateFields($input, true);
        $this->validateReferences($data);
        if ($data === []) { throw HttpException::unprocessable('No editable assignment fields were provided.', 'invalid_assignment_update'); }
        try { $updated = $this->assignments->updateForCompany($assignmentId, $data); }
        catch (PDOException $exception) { throw HttpException::conflict('Assignment could not be updated.', 'assignment_conflict'); }
        if ($updated === null) { throw HttpException::notFound('Assignment not found.', 'assignment_not_found'); }
        return $updated;
    }

    private function validateFields(array $input, bool $update): array
    {
        $fields = [];
        foreach (['employee_id', 'user_id', 'organization_id', 'organizational_unit_id', 'position_id', 'authority_level_id', 'assignment_code', 'assignment_status', 'starts_at', 'ends_at', 'is_primary', 'assigned_by_user_id', 'assignment_notes'] as $field) {
            if (array_key_exists($field, $input)) { $fields[$field] = $input[$field]; }
        }
        foreach (['employee_id', 'user_id', 'organization_id', 'organizational_unit_id', 'position_id', 'authority_level_id', 'assigned_by_user_id'] as $field) {
            if (array_key_exists($field, $fields) && $fields[$field] !== null) { $fields[$field] = $this->positiveInt($fields[$field], $field); }
        }
        if (array_key_exists('assignment_code', $fields)) { $fields['assignment_code'] = $this->requiredString($fields['assignment_code'], 'assignment_code', 60); }
        if (array_key_exists('assignment_status', $fields) && (!is_string($fields['assignment_status']) || !in_array($fields['assignment_status'], self::STATUSES, true))) { throw HttpException::unprocessable('Assignment status is invalid.', 'invalid_assignment_status'); }
        foreach (['starts_at', 'ends_at'] as $field) {
            if (array_key_exists($field, $fields) && $fields[$field] !== null && (!is_string($fields[$field]) || strtotime($fields[$field]) === false)) { throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field); }
        }
        if (array_key_exists('is_primary', $fields) && !is_bool($fields['is_primary']) && !in_array($fields['is_primary'], [0, 1, '0', '1'], true)) { throw HttpException::unprocessable('is_primary is invalid.', 'invalid_is_primary'); }
        if (array_key_exists('assignment_notes', $fields) && $fields['assignment_notes'] !== null && !is_string($fields['assignment_notes'])) { throw HttpException::unprocessable('Assignment notes are invalid.', 'invalid_assignment_notes'); }
        if (!$update) {
            foreach (['employee_id', 'position_id', 'authority_level_id', 'assignment_code'] as $required) { if (!array_key_exists($required, $fields)) { throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $required)) . ' is required.', 'invalid_' . $required); } }
        }
        return $fields;
    }

    private function validateReferences(array $data): void
    {
        try {
            foreach ([['employee_id', 'employeeBelongsToCompany', 'Employee not found.', 'employee_not_found'], ['position_id', 'positionBelongsToCompany', 'Position not found.', 'position_not_found'], ['authority_level_id', 'authorityLevelIsUsable', 'Authority level not found.', 'authority_level_not_found']] as [$field, $method, $message, $code]) {
                if (isset($data[$field]) && !$this->assignments->{$method}($data[$field])) { throw HttpException::notFound($message, $code); }
            }
            foreach ([['user_id', 'User not found.', 'user_not_found'], ['assigned_by_user_id', 'User not found.', 'user_not_found'], ['organization_id', 'Organization not found.', 'organization_not_found'], ['organizational_unit_id', 'Organizational unit not found.', 'organizational_unit_not_found']] as [$field, $message, $code]) {
                if (isset($data[$field]) && !$this->assignments->{str_contains($field, 'user') ? 'userBelongsToCompany' : (str_contains($field, 'unit') ? 'unitBelongsToCompany' : 'organizationBelongsToCompany')}($data[$field])) { throw HttpException::notFound($message, $code); }
            }
            if (isset($data['organization_id'], $data['organizational_unit_id']) && !$this->assignments->unitBelongsToOrganization($data['organizational_unit_id'], $data['organization_id'])) { throw HttpException::unprocessable('Organization and unit relationship is invalid.', 'invalid_assignment_relationship'); }
            if (isset($data['position_id'])) {
                $scope = $this->assignments->positionScope($data['position_id']);
                if ($scope === null) { throw HttpException::notFound('Position not found.', 'position_not_found'); }
                if (isset($data['organization_id']) && $scope['organization_id'] !== null && $data['organization_id'] !== $scope['organization_id']) { throw HttpException::unprocessable('Position and organization relationship is invalid.', 'invalid_assignment_relationship'); }
                if (isset($data['organizational_unit_id']) && $scope['organizational_unit_id'] !== null && $data['organizational_unit_id'] !== $scope['organizational_unit_id']) { throw HttpException::unprocessable('Position and unit relationship is invalid.', 'invalid_assignment_relationship'); }
            }
        } catch (PDOException $exception) { throw new HttpException(503, 'Assignment service unavailable.', 'assignment_service_unavailable', $exception); }
    }

    private function positiveInt(mixed $value, string $field): int
    {
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1) { throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field); }
        return (int) $value;
    }

    private function requiredString(mixed $value, string $field, int $maxLength): string
    {
        if (!is_string($value) || trim($value) === '' || strlen(trim($value)) > $maxLength) { throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field); }
        return trim($value);
    }
}