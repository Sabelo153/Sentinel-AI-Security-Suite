<?php

declare(strict_types=1);

namespace Sentinel\Services;

use DateTimeImmutable;
use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\PatrolSessionRepository;

final class PatrolSessionManagementService
{
    private const STATUSES = ['IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'ABORTED'];
    private const TERMINAL_STATUSES = ['COMPLETED', 'CANCELLED', 'ABORTED'];
    private const CREATE_FIELDS = ['patrol_route_id', 'employee_assignment_id', 'shift_assignment_id', 'started_at', 'execution_notes'];
    private const UPDATE_FIELDS = ['status', 'completed_at', 'ended_at', 'execution_notes'];

    public function __construct(private readonly PatrolSessionRepository $sessions)
    {
    }

    public function listSessions(): array
    {
        try {
            return $this->sessions->listForCompany();
        } catch (PDOException $e) {
            throw $this->unavailable($e);
        }
    }

    public function getSession(string $sessionId): array
    {
        try {
            $session = $this->sessions->findByIdForCompany($sessionId);
        } catch (PDOException $e) {
            throw $this->unavailable($e);
        }
        if ($session === null) {
            throw HttpException::notFound('Patrol session not found.', 'patrol_session_not_found');
        }
        return $session;
    }

    public function startSession(array $input): array
    {
        $data = $this->validateCreate($input);
        $this->validateReferences($data);

        try {
            if ($this->sessions->activeSessionExistsForEmployeeAssignment((string) $data['employee_assignment_id'])) {
                throw HttpException::conflict('Guard already has an active patrol session.', 'patrol_session_conflict');
            }
            return $this->sessions->createForCompany($data);
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw new HttpException(503, 'Patrol session service unavailable.', 'patrol_session_service_unavailable', $e);
        }
    }

    public function updateSession(string $sessionId, array $input): array
    {
        $existing = $this->getSession($sessionId);
        $patch = $this->validateUpdate($input);
        $effective = array_merge($existing, $patch);
        $effective['_existing_status'] = (string) $existing['status'];
        $this->validateLifecycle($effective, true);

        try {
            $session = $this->sessions->updateForCompany($sessionId, $patch);
        } catch (PDOException $e) {
            throw new HttpException(503, 'Patrol session service unavailable.', 'patrol_session_service_unavailable', $e);
        }
        if ($session === null) {
            throw HttpException::notFound('Patrol session not found.', 'patrol_session_not_found');
        }
        return $session;
    }

    private function validateCreate(array $input): array
    {
        $forbidden = array_diff(array_keys($input), self::CREATE_FIELDS);
        if ($forbidden !== []) {
            throw HttpException::unprocessable('Only patrol session start fields are accepted.', 'invalid_patrol_session_request');
        }
        $data = [];
        foreach (self::CREATE_FIELDS as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field];
            }
        }
        foreach (['patrol_route_id', 'employee_assignment_id', 'shift_assignment_id'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = $this->positive($data[$field], $field);
            }
        }
        foreach (['patrol_route_id', 'employee_assignment_id', 'started_at'] as $field) {
            if (!array_key_exists($field, $data)) {
                throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is required.', 'invalid_' . $field);
            }
        }
        $data['started_at'] = $this->datetime($data['started_at'], 'started_at');
        if (array_key_exists('execution_notes', $data) && $data['execution_notes'] !== null && (!is_string($data['execution_notes']) || strlen($data['execution_notes']) > 1000)) {
            throw HttpException::unprocessable('Execution notes are invalid.', 'invalid_execution_notes');
        }
        return $data;
    }

    private function validateUpdate(array $input): array
    {
        $unsupported = array_diff(array_keys($input), self::UPDATE_FIELDS);
        if ($unsupported !== []) {
            throw HttpException::unprocessable('Patrol session identity fields cannot be changed.', 'invalid_patrol_session_update');
        }
        $patch = [];
        foreach (self::UPDATE_FIELDS as $field) {
            if (array_key_exists($field, $input)) {
                $patch[$field] = $input[$field];
            }
        }
        if (array_key_exists('status', $patch) && (!is_string($patch['status']) || !in_array($patch['status'], self::STATUSES, true))) {
            throw HttpException::unprocessable('Patrol session status is invalid.', 'invalid_patrol_session_status');
        }
        foreach (['completed_at', 'ended_at'] as $field) {
            if (array_key_exists($field, $patch) && $patch[$field] !== null) {
                $patch[$field] = $this->datetime($patch[$field], $field);
            }
        }
        if (array_key_exists('execution_notes', $patch) && $patch['execution_notes'] !== null && (!is_string($patch['execution_notes']) || strlen($patch['execution_notes']) > 1000)) {
            throw HttpException::unprocessable('Execution notes are invalid.', 'invalid_execution_notes');
        }
        return $patch;
    }

    private function validateReferences(array $data): void
    {
        try {
            $route = $this->sessions->routeDetails((string) $data['patrol_route_id']);
            if ($route === null || $route['status'] !== 'ACTIVE') {
                throw HttpException::notFound('Patrol route not found.', 'patrol_route_not_found');
            }
            $assignment = $this->sessions->guardAssignmentDetails((string) $data['employee_assignment_id']);
            if ($assignment === null) {
                throw HttpException::notFound('Guard assignment not found.', 'guard_assignment_not_found');
            }
            if (isset($data['shift_assignment_id']) && $data['shift_assignment_id'] !== null) {
                $shift = $this->sessions->shiftDetails((string) $data['shift_assignment_id']);
                if ($shift === null) {
                    throw HttpException::notFound('Shift assignment not found.', 'shift_assignment_not_found');
                }
                if ((string) $shift['employee_id'] !== (string) $assignment['employee_id'] || ($shift['site_id'] !== null && (string) $shift['site_id'] !== (string) $route['site_id'])) {
                    throw HttpException::unprocessable('Patrol session relationship is invalid.', 'invalid_patrol_session_relationship');
                }
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw $this->unavailable($e);
        }
    }

    private function validateLifecycle(array $state, bool $isUpdate): void
    {
        $status = (string) ($state['status'] ?? 'IN_PROGRESS');
        if (!in_array($status, self::STATUSES, true)) {
            throw HttpException::unprocessable('Patrol session status is invalid.', 'invalid_patrol_session_status');
        }
        $started = $this->parse($state['started_at'] ?? null);
        $completed = $this->parse($state['completed_at'] ?? null);
        $ended = $this->parse($state['ended_at'] ?? null);
        if ($started === null) {
            throw HttpException::unprocessable('Started time is invalid.', 'invalid_patrol_session_timestamps');
        }
        if ($isUpdate && isset($state['_existing_status']) && $state['_existing_status'] !== 'IN_PROGRESS' && $status !== $state['_existing_status']) {
            throw HttpException::conflict('Patrol session is already terminal.', 'invalid_patrol_session_state');
        }
        if ($status === 'IN_PROGRESS' && ($completed !== null || $ended !== null)) {
            throw HttpException::unprocessable('In-progress sessions cannot have terminal timestamps.', 'invalid_patrol_session_timestamps');
        }
        if ($status === 'COMPLETED' && ($completed === null || $ended === null)) {
            throw HttpException::unprocessable('Completed sessions require completion and end times.', 'invalid_patrol_session_timestamps');
        }
        if (in_array($status, ['CANCELLED', 'ABORTED'], true) && ($completed !== null || $ended === null)) {
            throw HttpException::unprocessable('Cancelled or aborted sessions have invalid timestamps.', 'invalid_patrol_session_timestamps');
        }
        if ($completed !== null && $completed < $started) {
            throw HttpException::unprocessable('Completion time is invalid.', 'invalid_patrol_session_timestamps');
        }
        if ($ended !== null && $ended < $started) {
            throw HttpException::unprocessable('End time is invalid.', 'invalid_patrol_session_timestamps');
        }
        if ($completed !== null && $ended !== null && $completed > $ended) {
            throw HttpException::unprocessable('Completion time must not exceed end time.', 'invalid_patrol_session_timestamps');
        }
    }

    private function positive(mixed $value, string $field): int
    {
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }
        return (int) $value;
    }

    private function datetime(mixed $value, string $field): string
    {
        if (!is_string($value) || $this->parse($value) === null) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }
        return $value;
    }

    private function parse(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function unavailable(PDOException $e): HttpException
    {
        return new HttpException(503, 'Patrol session service unavailable.', 'patrol_session_service_unavailable', $e);
    }
}
