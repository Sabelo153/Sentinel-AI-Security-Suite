<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\PositionRepository;

final class PositionManagementService
{
    private const CATEGORIES = ['GUARD', 'SUPERVISOR', 'CONTROL_ROOM', 'MANAGEMENT', 'SUPPORT'];
    private const STATUSES = ['ACTIVE', 'INACTIVE', 'ARCHIVED'];

    public function __construct(private readonly PositionRepository $positions)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listPositions(): array
    {
        try {
            return $this->positions->listForCompany();
        } catch (PDOException $exception) {
            throw new \Sentinel\Core\HttpException(503, 'Position service unavailable.', 'position_service_unavailable', $exception);
        }
    }

    /** @return array<string, mixed> */
    public function getPosition(string $positionId): array
    {
        try {
            $position = $this->positions->findByIdForCompany($positionId);
        } catch (PDOException $exception) {
            throw new \Sentinel\Core\HttpException(503, 'Position service unavailable.', 'position_service_unavailable', $exception);
        }
        if ($position === null) {
            throw HttpException::notFound('Position not found.', 'position_not_found');
        }

        return $position;
    }

    /** @return array<string, mixed> */
    public function createPosition(array $input): array
    {
        $data = $this->validatedFields($input);
        $this->validateReferences($data);
        try {
            return $this->positions->createForCompany($data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Position could not be created.', 'position_conflict');
        }
    }

    /** @return array<string, mixed> */
    public function updatePosition(string $positionId, array $input): array
    {
        try {
            if ($this->positions->findByIdForCompany($positionId) === null) {
                throw HttpException::notFound('Position not found.', 'position_not_found');
            }
        } catch (PDOException $exception) {
            throw new \Sentinel\Core\HttpException(503, 'Position service unavailable.', 'position_service_unavailable', $exception);
        }

        $data = $this->validatedFields($input, true);
        $this->validateReferences($data);
        if ($data === []) {
            throw HttpException::unprocessable('No editable position fields were provided.', 'invalid_position_update');
        }

        try {
            $updated = $this->positions->updateForCompany($positionId, $data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Position could not be updated.', 'position_conflict');
        }
        if ($updated === null) {
            throw HttpException::notFound('Position not found.', 'position_not_found');
        }

        return $updated;
    }

    /** @return array<string, mixed> */
    private function validatedFields(array $input, bool $update = false): array
    {
        $fields = [];
        foreach (['organization_id', 'organizational_unit_id', 'position_code', 'position_name', 'position_category', 'hierarchy_rank', 'position_status', 'description'] as $field) {
            if (array_key_exists($field, $input)) {
                $fields[$field] = $input[$field];
            }
        }

        foreach (['organization_id', 'organizational_unit_id', 'hierarchy_rank'] as $field) {
            if (array_key_exists($field, $fields)) {
                $fields[$field] = $this->positiveInt($fields[$field], $field);
            }
        }
        foreach (['position_code' => 50, 'position_name' => 150] as $field => $maxLength) {
            if (array_key_exists($field, $fields)) {
                $fields[$field] = $this->requiredString($fields[$field], $field, $maxLength);
            }
        }
        if (array_key_exists('position_category', $fields) && (!is_string($fields['position_category']) || !in_array($fields['position_category'], self::CATEGORIES, true))) {
            throw HttpException::unprocessable('Position category is invalid.', 'invalid_position_category');
        }
        if (array_key_exists('position_status', $fields) && (!is_string($fields['position_status']) || !in_array($fields['position_status'], self::STATUSES, true))) {
            throw HttpException::unprocessable('Position status is invalid.', 'invalid_position_status');
        }
        if (array_key_exists('description', $fields) && $fields['description'] !== null && !is_string($fields['description'])) {
            throw HttpException::unprocessable('Description is invalid.', 'invalid_description');
        }

        if (!$update) {
            foreach (['position_code', 'position_name', 'position_category'] as $required) {
                if (!array_key_exists($required, $fields)) {
                    throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $required)) . ' is required.', 'invalid_' . $required);
                }
            }
        }

        return $fields;
    }

    /** @param array<string, mixed> $data */
    private function validateReferences(array $data): void
    {
        try {
            if (isset($data['organization_id']) && !$this->positions->organizationBelongsToCompany($data['organization_id'])) {
                throw HttpException::notFound('Organization not found.', 'organization_not_found');
            }
            if (isset($data['organizational_unit_id']) && !$this->positions->unitBelongsToCompany($data['organizational_unit_id'])) {
                throw HttpException::notFound('Organizational unit not found.', 'organizational_unit_not_found');
            }
            if (isset($data['organization_id'], $data['organizational_unit_id']) && !$this->positions->unitBelongsToOrganization($data['organizational_unit_id'], $data['organization_id'])) {
                throw HttpException::unprocessable('Organization and unit relationship is invalid.', 'invalid_position_relationship');
            }
        } catch (PDOException $exception) {
            throw new \Sentinel\Core\HttpException(503, 'Position service unavailable.', 'position_service_unavailable', $exception);
        }
    }

    private function positiveInt(mixed $value, string $field): int
    {
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }
        return (int) $value;
    }

    private function requiredString(mixed $value, string $field, int $maxLength): string
    {
        if (!is_string($value) || trim($value) === '' || strlen(trim($value)) > $maxLength) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }
        return trim($value);
    }
}