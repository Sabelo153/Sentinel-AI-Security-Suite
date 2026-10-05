<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\PatrolCheckpointRepository;

final class PatrolCheckpointManagementService
{
    private const STATUSES = ['ACTIVE', 'INACTIVE', 'ARCHIVED'];
    private const FIELDS = ['patrol_route_id', 'post_id', 'checkpoint_name', 'checkpoint_order', 'qr_code', 'nfc_tag', 'gps_lat', 'gps_long', 'expected_interval_minutes', 'status'];

    public function __construct(private readonly PatrolCheckpointRepository $checkpoints)
    {
    }

    public function listCheckpointsForRoute(string $routeId): array
    {
        try {
            if (!$this->checkpoints->routeBelongsToCompany($routeId)) {
                throw HttpException::notFound('Patrol route not found.', 'patrol_route_not_found');
            }

            return $this->checkpoints->listForRoute($routeId);
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw new HttpException(503, 'Patrol checkpoint service unavailable.', 'patrol_checkpoint_service_unavailable', $e);
        }
    }

    public function getCheckpoint(string $checkpointId): array
    {
        try {
            $checkpoint = $this->checkpoints->findByIdForCompany($checkpointId);
        } catch (PDOException $e) {
            throw new HttpException(503, 'Patrol checkpoint service unavailable.', 'patrol_checkpoint_service_unavailable', $e);
        }

        if ($checkpoint === null) {
            throw HttpException::notFound('Patrol checkpoint not found.', 'patrol_checkpoint_not_found');
        }

        return $checkpoint;
    }

    public function createCheckpoint(array $input): array
    {
        $data = $this->validate($input, false);
        $this->validateReferences($data);

        try {
            if ($this->checkpoints->sequenceExistsForRoute((string) $data['patrol_route_id'], $data['checkpoint_order'])) {
                throw HttpException::conflict('Patrol checkpoint already exists.', 'patrol_checkpoint_conflict');
            }

            return $this->checkpoints->createForCompany($data);
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw HttpException::conflict('Patrol checkpoint could not be created.', 'patrol_checkpoint_conflict');
        }
    }

    public function updateCheckpoint(string $checkpointId, array $input): array
    {
        $existing = $this->getCheckpoint($checkpointId);
        $patch = $this->validate($input, true);
        $effective = array_merge($existing, $patch);
        $this->validateReferences($effective);

        try {
            if ($this->checkpoints->sequenceExistsForRoute((string) $effective['patrol_route_id'], (int) $effective['checkpoint_order'], $checkpointId)) {
                throw HttpException::conflict('Patrol checkpoint already exists.', 'patrol_checkpoint_conflict');
            }

            $checkpoint = $this->checkpoints->updateForCompany($checkpointId, $patch);
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw HttpException::conflict('Patrol checkpoint could not be updated.', 'patrol_checkpoint_conflict');
        }

        if ($checkpoint === null) {
            throw HttpException::notFound('Patrol checkpoint not found.', 'patrol_checkpoint_not_found');
        }

        return $checkpoint;
    }

    private function validate(array $input, bool $update): array
    {
        $data = [];
        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field];
            }
        }

        foreach (['patrol_route_id', 'post_id', 'checkpoint_order'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = $this->positive($data[$field], $field);
            }
        }

        if (array_key_exists('checkpoint_name', $data) && (!is_string($data['checkpoint_name']) || trim($data['checkpoint_name']) === '' || strlen($data['checkpoint_name']) > 255)) {
            throw HttpException::unprocessable('Checkpoint name is invalid.', 'invalid_checkpoint_name');
        }
        foreach (['qr_code', 'nfc_tag'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && (!is_string($data[$field]) || strlen($data[$field]) > 255)) {
                throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
            }
        }
        foreach (['gps_lat', 'gps_long'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = $this->coordinate($data[$field], $field);
            }
        }
        if (array_key_exists('expected_interval_minutes', $data) && $data['expected_interval_minutes'] !== null) {
            $data['expected_interval_minutes'] = $this->nonNegative($data['expected_interval_minutes'], 'expected_interval_minutes');
        }
        if (array_key_exists('status', $data) && (!is_string($data['status']) || !in_array($data['status'], self::STATUSES, true))) {
            throw HttpException::unprocessable('Patrol checkpoint status is invalid.', 'invalid_patrol_checkpoint_status');
        }
        if (!$update) {
            foreach (['patrol_route_id', 'checkpoint_name', 'checkpoint_order'] as $required) {
                if (!array_key_exists($required, $data)) {
                    throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $required)) . ' is required.', 'invalid_' . $required);
                }
            }
            $data['status'] ??= 'ACTIVE';
        }

        return $data;
    }

    private function validateReferences(array $data): void
    {
        try {
            if (!$this->checkpoints->routeBelongsToCompany((string) $data['patrol_route_id'])) {
                throw HttpException::notFound('Patrol route not found.', 'patrol_route_not_found');
            }
            if (isset($data['post_id']) && $data['post_id'] !== null) {
                $route = $this->checkpoints->routeDetails((string) $data['patrol_route_id']);
                if ($route === null || !$this->checkpoints->postBelongsToSite((string) $data['post_id'], (string) $route['site_id'])) {
                    throw HttpException::unprocessable('Patrol checkpoint relationship is invalid.', 'invalid_patrol_checkpoint_relationship');
                }
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw new HttpException(503, 'Patrol checkpoint service unavailable.', 'patrol_checkpoint_service_unavailable', $e);
        }
    }

    private function positive(mixed $value, string $field): int
    {
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }

        return (int) $value;
    }

    private function nonNegative(mixed $value, string $field): int
    {
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 0) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }

        return (int) $value;
    }

    private function coordinate(mixed $value, string $field): float
    {
        if (!is_numeric($value)) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }

        $coordinate = (float) $value;
        $maximum = $field === 'gps_lat' ? 90.0 : 180.0;
        if ($coordinate < -$maximum || $coordinate > $maximum) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }

        return $coordinate;
    }
}
