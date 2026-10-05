<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\BranchRepository;

final class BranchManagementService
{
    private const STATUSES = ['ACTIVE', 'INACTIVE'];

    public function __construct(private readonly BranchRepository $branches)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listBranches(): array
    {
        try {
            return $this->branches->listForCompany();
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Branch service unavailable.', 'branch_service_unavailable', $exception);
        }
    }

    /** @return array<string, mixed> */
    public function getBranch(string $branchId): array
    {
        try {
            $branch = $this->branches->findByIdForCompany($branchId);
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Branch service unavailable.', 'branch_service_unavailable', $exception);
        }
        if ($branch === null) {
            throw HttpException::notFound('Branch not found.', 'branch_not_found');
        }

        return $branch;
    }

    /** @return array<string, mixed> */
    public function createBranch(array $input): array
    {
        $clientId = $this->requiredPositiveInt($input, 'client_id');
        $this->assertClientBelongsToCompany($clientId);
        $data = $this->validatedFields($input, false);
        if (!isset($data['branch_name'])) {
            throw HttpException::unprocessable('Branch name is required.', 'invalid_branch_name');
        }
        $data['client_id'] = $clientId;

        try {
            return $this->branches->createForCompany($data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Branch could not be created.', 'branch_conflict');
        }
    }

    /** @return array<string, mixed> */
    public function updateBranch(string $branchId, array $input): array
    {
        try {
            if ($this->branches->findByIdForCompany($branchId) === null) {
                throw HttpException::notFound('Branch not found.', 'branch_not_found');
            }
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Branch service unavailable.', 'branch_service_unavailable', $exception);
        }

        $data = $this->validatedFields($input, true);
        if (array_key_exists('client_id', $data)) {
            $this->assertClientBelongsToCompany($data['client_id']);
        }
        if ($data === []) {
            throw HttpException::unprocessable('No editable branch fields were provided.', 'invalid_branch_update');
        }

        try {
            $updated = $this->branches->updateForCompany($branchId, $data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Branch could not be updated.', 'branch_conflict');
        }
        if ($updated === null) {
            throw HttpException::notFound('Branch not found.', 'branch_not_found');
        }

        return $updated;
    }

    /** @return array<string, mixed> */
    private function validatedFields(array $input, bool $update): array
    {
        $fields = [];
        foreach (['branch_name', 'phone', 'email', 'address', 'city', 'province', 'status'] as $field) {
            if (array_key_exists($field, $input)) {
                $fields[$field] = $input[$field];
            }
        }

        if (array_key_exists('branch_name', $fields)) {
            $fields['branch_name'] = $this->requiredString($fields['branch_name'], 'branch_name', 255);
        }
        foreach (['phone' => 30, 'address' => null, 'city' => 100, 'province' => 100] as $field => $maxLength) {
            if (array_key_exists($field, $fields) && $fields[$field] !== null) {
                $fields[$field] = $this->optionalString($fields[$field], $field, $maxLength);
            }
        }
        if (array_key_exists('email', $fields) && $fields['email'] !== null) {
            $fields['email'] = strtolower($this->optionalString($fields['email'], 'email', 255));
            if (filter_var($fields['email'], FILTER_VALIDATE_EMAIL) === false) {
                throw HttpException::unprocessable('Email is invalid.', 'invalid_email');
            }
        }
        if (array_key_exists('status', $fields) && (!is_string($fields['status']) || !in_array($fields['status'], self::STATUSES, true))) {
            throw HttpException::unprocessable('Status is invalid.', 'invalid_branch_status');
        }

        if (!$update && !isset($fields['branch_name'])) {
            return [];
        }

        return $fields;
    }

    private function assertClientBelongsToCompany(int $clientId): void
    {
        try {
            if (!$this->branches->clientBelongsToCompany($clientId)) {
                throw HttpException::notFound('Client not found.', 'client_not_found');
            }
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Branch service unavailable.', 'branch_service_unavailable', $exception);
        }
    }

    private function requiredPositiveInt(array $input, string $field): int
    {
        $value = $input[$field] ?? null;
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is required.', 'invalid_' . $field);
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

    private function optionalString(mixed $value, string $field, ?int $maxLength): string
    {
        if (!is_string($value) || ($maxLength !== null && strlen(trim($value)) > $maxLength)) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }

        return trim($value);
    }
}