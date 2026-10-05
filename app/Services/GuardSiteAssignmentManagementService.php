<?php

declare(strict_types=1);

namespace Sentinel\Services;

use DateTimeImmutable;
use PDOException;
use Sentinel\Core\HttpException;

final class GuardSiteAssignmentManagementService
{
    private const STATUSES = ['PLANNED', 'ACTIVE', 'SUSPENDED', 'ENDED', 'CANCELLED'];
    private const FIELDS = ['employee_assignment_id', 'site_id', 'deployment_status', 'starts_at', 'ends_at', 'assignment_notes'];

    public function __construct(private readonly \Sentinel\Repositories\GuardSiteAssignmentRepository $assignments)
    {
    }

    public function listAssignments(): array
    {
        try {
            return $this->assignments->listForCompany();
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Guard-site assignment service unavailable.', 'guard_site_assignment_service_unavailable', $exception);
        }
    }

    public function getAssignment(string $assignmentId): array
    {
        try {
            $assignment = $this->assignments->findByIdForCompany($assignmentId);
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Guard-site assignment service unavailable.', 'guard_site_assignment_service_unavailable', $exception);
        }
        if ($assignment === null) {
            throw HttpException::notFound('Guard-site assignment not found.', 'guard_site_assignment_not_found');
        }
        return $assignment;
    }

    public function createAssignment(array $input): array
    {
        $data = $this->validateFields($input, false);
        $this->validateReferences($data);
        $this->validateDates($data);
        $this->rejectDuplicate($data);

        try {
            return $this->assignments->createForCompany($data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Guard-site assignment could not be created.', 'guard_site_assignment_conflict');
        }
    }

    public function updateAssignment(string $assignmentId, array $input): array
    {
        $existing = $this->getAssignment($assignmentId);
        $unsupported = array_diff(array_keys($input), self::FIELDS);
        if ($unsupported !== []) {
            throw HttpException::unprocessable('Only guard-site assignment fields may be updated.', 'invalid_guard_site_assignment_update');
        }

        $patch = $this->validateFields($input, true);
        $effective = array_merge($existing, $patch);
        $effective['employee_assignment_id'] = (string) $effective['employee_assignment_id'];
        $effective['site_id'] = (string) $effective['site_id'];
        $this->validateReferences($effective);
        $this->validateDates($effective);
        $this->rejectDuplicate($effective, $assignmentId);

        try {
            $updated = $this->assignments->updateForCompany($assignmentId, $patch);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Guard-site assignment could not be updated.', 'guard_site_assignment_conflict');
        }
        if ($updated === null) {
            throw HttpException::notFound('Guard-site assignment not found.', 'guard_site_assignment_not_found');
        }
        return $updated;
    }

    private function validateFields(array $input, bool $update): array
    {
        $fields = [];
        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $input)) {
                $fields[$field] = $input[$field];
            }
        }
        foreach (['employee_assignment_id', 'site_id'] as $field) {
            if (array_key_exists($field, $fields)) {
                $fields[$field] = $this->positiveInt($fields[$field], $field);
            }
        }
        if (array_key_exists('deployment_status', $fields) && (!is_string($fields['deployment_status']) || !in_array($fields['deployment_status'], self::STATUSES, true))) {
            throw HttpException::unprocessable('Deployment status is invalid.', 'invalid_deployment_status');
        }
        if (array_key_exists('starts_at', $fields)) {
            $fields['starts_at'] = $this->dateTimeString($fields['starts_at'], 'starts_at');
        }
        if (array_key_exists('ends_at', $fields) && $fields['ends_at'] !== null) {
            $fields['ends_at'] = $this->dateTimeString($fields['ends_at'], 'ends_at');
        }
        if (array_key_exists('assignment_notes', $fields) && $fields['assignment_notes'] !== null && !is_string($fields['assignment_notes'])) {
            throw HttpException::unprocessable('Assignment notes are invalid.', 'invalid_assignment_notes');
        }
        if (!$update) {
            foreach (['employee_assignment_id', 'site_id', 'starts_at'] as $required) {
                if (!array_key_exists($required, $fields)) {
                    throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $required)) . ' is required.', 'invalid_' . $required);
                }
            }
            $fields['deployment_status'] ??= 'PLANNED';
        }
        return $fields;
    }

    private function validateReferences(array $data): void
    {
        try {
            if (isset($data['employee_assignment_id']) && !$this->assignments->employeeAssignmentIsGuardForCompany((string) $data['employee_assignment_id'])) {
                throw HttpException::notFound('Guard assignment not found.', 'guard_assignment_not_found');
            }
            if (isset($data['site_id']) && !$this->assignments->siteBelongsToCompany((string) $data['site_id'])) {
                throw HttpException::notFound('Site not found.', 'site_not_found');
            }
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Guard-site assignment service unavailable.', 'guard_site_assignment_service_unavailable', $exception);
        }
    }

    private function validateDates(array $data): void
    {
        $startsAt = $this->parseDate($data['starts_at'] ?? null);
        $endsAt = $this->parseDate($data['ends_at'] ?? null);
        if ($startsAt === null) {
            throw HttpException::unprocessable('Starts at is required.', 'invalid_starts_at');
        }
        if (($data['deployment_status'] ?? null) === 'ENDED' && $endsAt === null) {
            throw HttpException::unprocessable('Ended deployments require ends_at.', 'invalid_ends_at');
        }
        if ($endsAt !== null && $endsAt < $startsAt) {
            throw HttpException::unprocessable('Ends at must not precede starts at.', 'invalid_date_relationship');
        }
    }

    private function rejectDuplicate(array $data, ?string $excludeId = null): void
    {
        try {
            if ($this->assignments->exactActiveDuplicateExists((string) $data['employee_assignment_id'], (string) $data['site_id'], (string) $data['starts_at'], $excludeId)) {
                throw HttpException::conflict('Guard-site assignment already exists.', 'guard_site_assignment_conflict');
            }
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Guard-site assignment service unavailable.', 'guard_site_assignment_service_unavailable', $exception);
        }
    }

    private function positiveInt(mixed $value, string $field): int
    {
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }
        return (int) $value;
    }

    private function dateTimeString(mixed $value, string $field): string
    {
        if (!is_string($value) || $this->parseDate($value) === null) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }
        return $value;
    }

    private function parseDate(mixed $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return new DateTimeImmutable((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}