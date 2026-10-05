<?php

declare(strict_types=1);

namespace Sentinel\Services;

use DateTimeImmutable;
use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\PatrolCheckpointEventRepository;

final class PatrolCheckpointEventManagementService
{
    private const EVENT_TYPES = ['VISITED', 'SKIPPED', 'FAILED'];

    public function __construct(private readonly PatrolCheckpointEventRepository $events)
    {
    }

    public function listEventsForSession(string $sessionId): array
    {
        try {
            if ($this->events->sessionDetails($sessionId) === null) {
                throw HttpException::notFound('Patrol session not found.', 'patrol_session_not_found');
            }
            return $this->events->listForSession($sessionId);
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw $this->unavailable($e);
        }
    }

    public function getEvent(string $eventId): array
    {
        try {
            $event = $this->events->findByIdForCompany($eventId);
        } catch (PDOException $e) {
            throw $this->unavailable($e);
        }
        if ($event === null) {
            throw HttpException::notFound('Patrol checkpoint event not found.', 'patrol_checkpoint_event_not_found');
        }
        return $event;
    }

    public function recordEvent(string $sessionId, array $input): array
    {
        $data = $this->validate($input);
        try {
            $session = $this->events->sessionDetails($sessionId);
            if ($session === null) {
                throw HttpException::notFound('Patrol session not found.', 'patrol_session_not_found');
            }
            if ($session['status'] !== 'IN_PROGRESS') {
                throw HttpException::conflict('Patrol session is already terminal.', 'invalid_patrol_session_state');
            }
            $checkpoint = $this->events->checkpointDetails((string) $data['patrol_checkpoint_id']);
            if ($checkpoint === null) {
                throw HttpException::notFound('Patrol checkpoint not found.', 'patrol_checkpoint_not_found');
            }
            if ((string) $checkpoint['patrol_route_id'] !== (string) $session['patrol_route_id']) {
                throw HttpException::unprocessable('Checkpoint does not belong to the patrol route.', 'invalid_patrol_checkpoint_event_relationship');
            }
            $recorded = $this->parse($data['recorded_at']);
            $started = $this->parse((string) $session['started_at']);
            if ($recorded === null || $started === null || $recorded < $started) {
                throw HttpException::unprocessable('Checkpoint event time is invalid.', 'invalid_patrol_checkpoint_event_timestamp');
            }
            $data['patrol_session_id'] = $sessionId;
            return $this->events->createForCompany($data);
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw $this->unavailable($e);
        }
    }

    private function validate(array $input): array
    {
        $allowed = ['patrol_checkpoint_id', 'event_type', 'recorded_at', 'event_notes'];
        if (array_diff(array_keys($input), $allowed) !== []) {
            throw HttpException::unprocessable('Invalid patrol checkpoint event fields.', 'invalid_patrol_checkpoint_event_request');
        }
        foreach (['patrol_checkpoint_id', 'recorded_at'] as $field) {
            if (!array_key_exists($field, $input)) {
                throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is required.', 'invalid_' . $field);
            }
        }
        if ((!is_int($input['patrol_checkpoint_id']) && !(is_string($input['patrol_checkpoint_id']) && ctype_digit($input['patrol_checkpoint_id']))) || (int) $input['patrol_checkpoint_id'] < 1) {
            throw HttpException::unprocessable('Patrol checkpoint id is invalid.', 'invalid_patrol_checkpoint_id');
        }
        $data = $input;
        $data['patrol_checkpoint_id'] = (int) $data['patrol_checkpoint_id'];
        $data['event_type'] ??= 'VISITED';
        if (!is_string($data['event_type']) || !in_array($data['event_type'], self::EVENT_TYPES, true)) {
            throw HttpException::unprocessable('Event type is invalid.', 'invalid_patrol_checkpoint_event_type');
        }
        if (!is_string($data['recorded_at']) || $this->parse($data['recorded_at']) === null) {
            throw HttpException::unprocessable('Recorded time is invalid.', 'invalid_patrol_checkpoint_event_timestamp');
        }
        if (array_key_exists('event_notes', $data) && $data['event_notes'] !== null && (!is_string($data['event_notes']) || strlen($data['event_notes']) > 1000)) {
            throw HttpException::unprocessable('Event notes are invalid.', 'invalid_event_notes');
        }
        return $data;
    }

    private function parse(string $value): ?DateTimeImmutable
    {
        try {
            return $value === '' ? null : new DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function unavailable(PDOException $e): HttpException
    {
        return new HttpException(503, 'Patrol checkpoint event service unavailable.', 'patrol_checkpoint_event_service_unavailable', $e);
    }
}
