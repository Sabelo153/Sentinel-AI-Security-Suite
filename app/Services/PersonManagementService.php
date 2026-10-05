<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;

final class PersonManagementService
{
    private const GENDERS = ['MALE', 'FEMALE', 'OTHER'];

    public function __construct(private readonly \Sentinel\Repositories\PersonRepository $people)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listPeople(): array
    {
        try {
            return $this->people->listForCompany();
        } catch (PDOException $exception) {
            throw new \Sentinel\Core\HttpException(503, 'People service unavailable.', 'people_service_unavailable', $exception);
        }
    }

    /** @return array<string, mixed> */
    public function getPerson(string $personId): array
    {
        try {
            $person = $this->people->findByIdForCompany($personId);
        } catch (PDOException $exception) {
            throw new \Sentinel\Core\HttpException(503, 'People service unavailable.', 'people_service_unavailable', $exception);
        }
        if ($person === null) {
            throw HttpException::notFound('Person not found.', 'person_not_found');
        }

        return $person;
    }

    /** @return array<string, mixed> */
    public function createPerson(array $input): array
    {
        $data = $this->validatedFields($input, false);
        if (!isset($data['first_name'], $data['last_name'])) {
            throw HttpException::unprocessable('First name and last name are required.', 'invalid_person_name');
        }

        try {
            return $this->people->createForCompany($data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Person could not be created.', 'person_conflict');
        }
    }

    /** @return array<string, mixed> */
    public function updatePerson(string $personId, array $input): array
    {
        try {
            if ($this->people->findByIdForCompany($personId) === null) {
                throw HttpException::notFound('Person not found.', 'person_not_found');
            }
        } catch (PDOException $exception) {
            throw new \Sentinel\Core\HttpException(503, 'People service unavailable.', 'people_service_unavailable', $exception);
        }

        $data = $this->validatedFields($input, true);
        if ($data === []) {
            throw HttpException::unprocessable('No editable person fields were provided.', 'invalid_person_update');
        }

        try {
            $updated = $this->people->updateForCompany($personId, $data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Person could not be updated.', 'person_conflict');
        }
        if ($updated === null) {
            throw HttpException::notFound('Person not found.', 'person_not_found');
        }

        return $updated;
    }

    /** @return array<string, mixed> */
    private function validatedFields(array $input, bool $update): array
    {
        $fields = [];
        foreach (['first_name', 'last_name', 'id_number', 'passport_number', 'phone', 'email', 'gender', 'date_of_birth'] as $field) {
            if ($update && !array_key_exists($field, $input)) {
                continue;
            }
            if (!$update && !array_key_exists($field, $input)) {
                continue;
            }
            $fields[$field] = $input[$field];
        }

        foreach (['first_name', 'last_name'] as $field) {
            if (array_key_exists($field, $fields)) {
                $fields[$field] = $this->requiredString($fields[$field], $field, 100);
            }
        }

        foreach (['id_number', 'passport_number', 'phone'] as $field) {
            if (array_key_exists($field, $fields) && $fields[$field] !== null) {
                $fields[$field] = $this->optionalString($fields[$field], $field, 30);
            }
        }

        if (array_key_exists('email', $fields) && $fields['email'] !== null) {
            $fields['email'] = strtolower($this->optionalString($fields['email'], 'email', 255));
            if (filter_var($fields['email'], FILTER_VALIDATE_EMAIL) === false) {
                throw HttpException::unprocessable('Email is invalid.', 'invalid_email');
            }
        }

        if (array_key_exists('gender', $fields) && $fields['gender'] !== null) {
            if (!is_string($fields['gender']) || !in_array($fields['gender'], self::GENDERS, true)) {
                throw HttpException::unprocessable('Gender is invalid.', 'invalid_gender');
            }
        }

        if (array_key_exists('date_of_birth', $fields) && $fields['date_of_birth'] !== null) {
            if (!is_string($fields['date_of_birth']) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $fields['date_of_birth']) !== 1) {
                throw HttpException::unprocessable('Date of birth is invalid.', 'invalid_date_of_birth');
            }
        }

        return $fields;
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
}