<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;

final class GuardManagementService
{
    private const SAFE_UPDATE_FIELDS = ['assignment_status', 'starts_at', 'ends_at', 'is_primary', 'assignment_notes', 'position_id'];

    public function __construct(
        private readonly \Sentinel\Repositories\GuardRepository $guards,
        private readonly \Sentinel\Services\EmployeeAssignmentManagementService $assignments,
        private readonly \Sentinel\Repositories\PositionRepository $positions,
    ) {
    }

    public function listGuards(): array
    {
        try {
            return $this->guards->listForCompany();
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Guard service unavailable.', 'guard_service_unavailable', $exception);
        }
    }

    public function getGuard(string $employeeId): array
    {
        try {
            $guard = $this->guards->findByEmployeeIdForCompany($employeeId);
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Guard service unavailable.', 'guard_service_unavailable', $exception);
        }
        if ($guard === null) {
            throw HttpException::notFound('Guard not found.', 'guard_not_found');
        }

        return $guard;
    }

    public function registerGuard(array $input): array
    {
        $positionId = $this->positiveInt($input['position_id'] ?? null, 'position_id');
        $this->assertGuardPosition($positionId);

        try {
            $assignment = $this->assignments->createAssignment($input);
            $guard = $this->guards->findByAssignmentIdForCompany((string) $assignment['employee_assignment_id']);
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Guard service unavailable.', 'guard_service_unavailable', $exception);
        }
        if ($guard === null) {
            throw HttpException::notFound('Guard not found.', 'guard_not_found');
        }

        return $guard;
    }

    public function updateGuardAssignment(string $employeeId, array $input): array
    {
        $guard = $this->getGuard($employeeId);
        $unsupported = array_diff(array_keys($input), self::SAFE_UPDATE_FIELDS);
        if ($unsupported !== []) {
            throw HttpException::unprocessable('Only guard assignment fields may be updated.', 'invalid_guard_update');
        }
        if (isset($input['position_id'])) {
            $this->assertGuardPosition($this->positiveInt($input['position_id'], 'position_id'));
        }

        $updated = $this->assignments->updateAssignment((string) $guard['assignment_id'], $input);
        $result = $this->guards->findByAssignmentIdForCompany((string) $updated['employee_assignment_id']);
        if ($result === null) {
            throw HttpException::notFound('Guard not found.', 'guard_not_found');
        }

        return $result;
    }

    private function assertGuardPosition(int $positionId): void
    {
        try {
            $position = $this->positions->findByIdForCompany((string) $positionId);
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Guard service unavailable.', 'guard_service_unavailable', $exception);
        }
        if ($position === null) {
            throw HttpException::notFound('Position not found.', 'position_not_found');
        }
        if (($position['position_category'] ?? null) !== 'GUARD') {
            throw HttpException::unprocessable('Selected position is not a guard position.', 'invalid_guard_position');
        }
    }

    private function positiveInt(mixed $value, string $field): int
    {
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1) {
            throw HttpException::unprocessable(ucfirst($field) . ' is required.', 'invalid_' . $field);
        }
        return (int) $value;
    }
}