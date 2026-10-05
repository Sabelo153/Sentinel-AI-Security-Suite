<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\SiteRepository;

final class SiteManagementService
{
    private const STATUSES = ['ONBOARDING', 'ACTIVE', 'SUSPENDED', 'CONTRACT_EXPIRED', 'CLOSED', 'INACTIVE'];

    public function __construct(private readonly SiteRepository $sites, private readonly ?SubscriptionService $subscriptions = null)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listSites(): array
    {
        try {
            return $this->sites->listForCompany();
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Site service unavailable.', 'site_service_unavailable', $exception);
        }
    }

    /** @return array<string, mixed> */
    public function getSite(string $siteId): array
    {
        try {
            $site = $this->sites->findByIdForCompany($siteId);
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Site service unavailable.', 'site_service_unavailable', $exception);
        }
        if ($site === null) {
            throw HttpException::notFound('Site not found.', 'site_not_found');
        }

        return $site;
    }

    /** @return array<string, mixed> */
    public function createSite(array $input): array
    {
        $this->subscriptions?->assertCanCreate('sites');
        $data = $this->validatedFields($input, false);
        $this->validateDependencies($data);
        if (!isset($data['site_name'])) {
            throw HttpException::unprocessable('Site name is required.', 'invalid_site_name');
        }

        try {
            return $this->sites->createForCompany($data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Site could not be created.', 'site_conflict');
        }
    }

    /** @return array<string, mixed> */
    public function updateSite(string $siteId, array $input): array
    {
        try {
            if ($this->sites->findByIdForCompany($siteId) === null) {
                throw HttpException::notFound('Site not found.', 'site_not_found');
            }
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Site service unavailable.', 'site_service_unavailable', $exception);
        }

        $data = $this->validatedFields($input, true);
        $this->validateDependencies($data);
        if ($data === []) {
            throw HttpException::unprocessable('No editable site fields were provided.', 'invalid_site_update');
        }

        try {
            $updated = $this->sites->updateForCompany($siteId, $data);
        } catch (PDOException $exception) {
            throw HttpException::conflict('Site could not be updated.', 'site_conflict');
        }
        if ($updated === null) {
            throw HttpException::notFound('Site not found.', 'site_not_found');
        }

        return $updated;
    }

    /** @return array<string, mixed> */
    private function validatedFields(array $input, bool $update): array
    {
        $fields = [];
        foreach (['client_id', 'branch_id', 'site_name', 'address', 'city', 'province', 'latitude', 'longitude', 'status'] as $field) {
            if (array_key_exists($field, $input)) {
                $fields[$field] = $input[$field];
            }
        }
        foreach (['client_id', 'branch_id'] as $field) {
            if (array_key_exists($field, $fields)) {
                $fields[$field] = $this->positiveInt($fields[$field], $field);
            }
        }
        if (array_key_exists('site_name', $fields)) {
            $fields['site_name'] = $this->requiredString($fields['site_name'], 'site_name', 255);
        }
        foreach (['address' => null, 'city' => 100, 'province' => 100] as $field => $maxLength) {
            if (array_key_exists($field, $fields) && $fields[$field] !== null) {
                $fields[$field] = $this->optionalString($fields[$field], $field, $maxLength);
            }
        }
        foreach (['latitude' => -90.0, 'longitude' => -180.0] as $field => $minimum) {
            if (array_key_exists($field, $fields) && $fields[$field] !== null) {
                if (!is_numeric($fields[$field])) {
                    throw HttpException::unprocessable(ucfirst($field) . ' is invalid.', 'invalid_' . $field);
                }
                $value = (float) $fields[$field];
                $maximum = $field === 'latitude' ? 90.0 : 180.0;
                if ($value < $minimum || $value > $maximum) {
                    throw HttpException::unprocessable(ucfirst($field) . ' is invalid.', 'invalid_' . $field);
                }
                $fields[$field] = $value;
            }
        }
        if (array_key_exists('status', $fields) && (!is_string($fields['status']) || !in_array($fields['status'], self::STATUSES, true))) {
            throw HttpException::unprocessable('Status is invalid.', 'invalid_site_status');
        }
        if (!$update && !isset($fields['site_name'])) {
            return [];
        }

        return $fields;
    }

    /** @param array<string, mixed> $data */
    private function validateDependencies(array $data): void
    {
        try {
            if (isset($data['client_id']) && !$this->sites->clientBelongsToCompany($data['client_id'])) {
                throw HttpException::notFound('Client not found.', 'client_not_found');
            }
            if (isset($data['branch_id']) && !$this->sites->branchBelongsToCompany($data['branch_id'])) {
                throw HttpException::notFound('Branch not found.', 'branch_not_found');
            }
            if (isset($data['client_id'], $data['branch_id']) && !$this->sites->branchBelongsToClient($data['branch_id'], $data['client_id'])) {
                throw HttpException::unprocessable('Client and branch relationship is invalid.', 'invalid_site_relationship');
            }
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Site service unavailable.', 'site_service_unavailable', $exception);
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

    private function optionalString(mixed $value, string $field, ?int $maxLength): string
    {
        if (!is_string($value) || ($maxLength !== null && strlen(trim($value)) > $maxLength)) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }

        return trim($value);
    }
}
