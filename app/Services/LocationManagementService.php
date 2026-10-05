<?php

declare(strict_types=1);

namespace Sentinel\Services;

use DateTimeImmutable;
use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\LocationRepository;

final class LocationManagementService
{
    private const FIELDS = ['employee_id', 'user_id', 'business_object_id', 'latitude', 'longitude', 'accuracy', 'heading', 'speed', 'altitude', 'provider', 'battery_level', 'signal_strength', 'device_id', 'mock_location', 'confidence', 'captured_at'];

    public function __construct(private readonly LocationRepository $locations)
    {
    }

    public function listLocations(array $filters = []): array
    {
        try {
            return $this->locations->listForCompany($this->validateFilters($filters));
        } catch (PDOException $e) {
            throw $this->unavailable($e);
        }
    }

    public function listEmployeeLocations(string $employeeId, array $filters = []): array
    {
        $employeeId = $this->positive($employeeId, 'employee_id');
        try {
            if (!$this->locations->employeeBelongsToCompany((string) $employeeId)) {
                throw HttpException::notFound('Employee not found.', 'employee_not_found');
            }

            return $this->locations->listForEmployee((string) $employeeId, $this->validateFilters($filters));
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw $this->unavailable($e);
        }
    }

    public function getLatestEmployeeLocation(string $employeeId): array
    {
        $employeeId = $this->positive($employeeId, 'employee_id');
        try {
            if (!$this->locations->employeeBelongsToCompany((string) $employeeId)) {
                throw HttpException::notFound('Employee not found.', 'employee_not_found');
            }
            $location = $this->locations->latestForEmployee((string) $employeeId);
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw $this->unavailable($e);
        }
        if ($location === null) {
            throw HttpException::notFound('Location not found.', 'location_not_found');
        }

        return $location;
    }

    public function recordLocation(array $input): array
    {
        $data = $this->validateInput($input);
        try {
            foreach (['employee_id' => 'employeeBelongsToCompany', 'user_id' => 'userBelongsToCompany', 'business_object_id' => 'businessObjectBelongsToCompany'] as $field => $method) {
                if (isset($data[$field])) {
                    if (!$this->locations->{$method}((string) $data[$field])) {
                        throw HttpException::notFound(ucfirst(str_replace('_', ' ', $field)) . ' not found.', $field . '_not_found');
                    }
                }
            }

            return $this->locations->createForCompany($data);
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw $this->unavailable($e);
        }
    }

    private function validateInput(array $input): array
    {
        if (array_diff(array_keys($input), self::FIELDS) !== []) {
            throw HttpException::unprocessable('Invalid location fields.', 'invalid_location_request');
        }
        foreach (['latitude', 'longitude'] as $field) {
            if (!array_key_exists($field, $input)) {
                throw HttpException::unprocessable(ucfirst($field) . ' is required.', 'invalid_' . $field);
            }
        }
        $data = $input;
        foreach (['employee_id', 'user_id', 'business_object_id'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->positive($data[$field], $field);
            }
        }
        $data['latitude'] = $this->decimal($data['latitude'], 'latitude', -90, 90);
        $data['longitude'] = $this->decimal($data['longitude'], 'longitude', -180, 180);
        foreach (['accuracy', 'speed'] as $field) {
            if (isset($data[$field])) $data[$field] = $this->decimal($data[$field], $field, 0, null);
        }
        foreach (['battery_level', 'signal_strength', 'confidence'] as $field) {
            if (isset($data[$field])) $data[$field] = $this->decimal($data[$field], $field, 0, 100);
        }
        if (isset($data['heading'])) $data['heading'] = $this->decimal($data['heading'], 'heading', 0, 360);
        if (isset($data['altitude'])) $data['altitude'] = $this->decimal($data['altitude'], 'altitude', null, null);
        foreach (['provider' => 50, 'device_id' => 120] as $field => $max) {
            if (isset($data[$field]) && (!is_string($data[$field]) || strlen($data[$field]) > $max)) {
                throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
            }
        }
        if (isset($data['mock_location']) && !is_bool($data['mock_location']) && !in_array($data['mock_location'], [0, 1, '0', '1'], true)) {
            throw HttpException::unprocessable('Mock location is invalid.', 'invalid_mock_location');
        }
        if (isset($data['mock_location'])) $data['mock_location'] = (bool) $data['mock_location'];
        if (isset($data['captured_at']) && (!$this->parseDate($data['captured_at']))) {
            throw HttpException::unprocessable('Captured time is invalid.', 'invalid_captured_at');
        }
        $data['captured_at'] ??= date('Y-m-d H:i:s');

        return $data;
    }

    private function validateFilters(array $filters): array
    {
        $allowed = ['employee_id', 'user_id', 'business_object_id', 'captured_from', 'captured_to', 'limit'];
        if (array_diff(array_keys($filters), $allowed) !== []) throw HttpException::unprocessable('Invalid location filters.', 'invalid_location_filters');
        foreach (['employee_id', 'user_id', 'business_object_id'] as $field) if (isset($filters[$field])) $filters[$field] = $this->positive($filters[$field], $field);
        foreach (['captured_from', 'captured_to'] as $field) if (isset($filters[$field]) && !$this->parseDate($filters[$field])) throw HttpException::unprocessable('Location time filter is invalid.', 'invalid_' . $field);
        if (isset($filters['limit']) && ((!is_int($filters['limit']) && !(is_string($filters['limit']) && ctype_digit($filters['limit']))) || (int) $filters['limit'] < 1 || (int) $filters['limit'] > 500)) throw HttpException::unprocessable('Location limit is invalid.', 'invalid_location_limit');

        return $filters;
    }

    private function positive(mixed $value, string $field): int
    {
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1) throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        return (int) $value;
    }

    private function decimal(mixed $value, string $field, ?float $min, ?float $max): float
    {
        if (!is_numeric($value)) throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        $number = (float) $value;
        if (($min !== null && $number < $min) || ($max !== null && $number > $max)) throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        return $number;
    }

    private function parseDate(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') return null;
        try { return new DateTimeImmutable($value); } catch (\Throwable) { return null; }
    }

    private function unavailable(PDOException $e): HttpException
    {
        return new HttpException(503, 'Location service unavailable.', 'location_service_unavailable', $e);
    }
}