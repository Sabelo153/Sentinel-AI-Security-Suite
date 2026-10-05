<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;

final class ClientService
{
	private const STATUSES = ['ACTIVE', 'INACTIVE'];

	public function __construct(private readonly \Sentinel\Repositories\ClientRepository $clients)
	{
	}

	/** @return array<int, array<string, mixed>> */
	public function listClients(): array
	{
		try {
			return $this->clients->listForCompany();
		} catch (PDOException $exception) {
			throw new HttpException(503, 'Client service unavailable.', 'client_service_unavailable', $exception);
		}
	}

	/** @return array<string, mixed> */
	public function getClient(string $clientId): array
	{
		try {
			$client = $this->clients->findByIdForCompany($clientId);
		} catch (PDOException $exception) {
			throw new HttpException(503, 'Client service unavailable.', 'client_service_unavailable', $exception);
		}
		if ($client === null) {
			throw HttpException::notFound('Client not found.', 'client_not_found');
		}

		return $client;
	}

	/** @return array<string, mixed> */
	public function createClient(array $input): array
	{
		$data = $this->validatedFields($input, false);
		if (!isset($data['client_name'])) {
			throw HttpException::unprocessable('Client name is required.', 'invalid_client_name');
		}

		try {
			return $this->clients->createForCompany($data);
		} catch (PDOException $exception) {
			throw HttpException::conflict('Client could not be created.', 'client_conflict');
		}
	}

	/** @return array<string, mixed> */
	public function updateClient(string $clientId, array $input): array
	{
		try {
			if ($this->clients->findByIdForCompany($clientId) === null) {
				throw HttpException::notFound('Client not found.', 'client_not_found');
			}
		} catch (PDOException $exception) {
			throw new HttpException(503, 'Client service unavailable.', 'client_service_unavailable', $exception);
		}

		$data = $this->validatedFields($input, true);
		if ($data === []) {
			throw HttpException::unprocessable('No editable client fields were provided.', 'invalid_client_update');
		}

		try {
			$updated = $this->clients->updateForCompany($clientId, $data);
		} catch (PDOException $exception) {
			throw HttpException::conflict('Client could not be updated.', 'client_conflict');
		}
		if ($updated === null) {
			throw HttpException::notFound('Client not found.', 'client_not_found');
		}

		return $updated;
	}

	/** @return array<string, mixed> */
	private function validatedFields(array $input, bool $update): array
	{
		$fields = [];
		foreach (['client_name', 'contact_person', 'phone', 'email', 'address', 'city', 'province', 'postal_code', 'country', 'contract_start', 'contract_end', 'status'] as $field) {
			if (array_key_exists($field, $input)) {
				$fields[$field] = $input[$field];
			}
		}

		if (array_key_exists('client_name', $fields)) {
			$fields['client_name'] = $this->requiredString($fields['client_name'], 'client_name', 255);
		}
		foreach (['contact_person' => 255, 'phone' => 30, 'address' => null, 'city' => 100, 'province' => 100, 'postal_code' => 20, 'country' => 100] as $field => $maxLength) {
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
		foreach (['contract_start', 'contract_end'] as $field) {
			if (array_key_exists($field, $fields) && $fields[$field] !== null && (!is_string($fields[$field]) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $fields[$field]) !== 1)) {
				throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
			}
		}
		if (array_key_exists('status', $fields) && (!is_string($fields['status']) || !in_array($fields['status'], self::STATUSES, true))) {
			throw HttpException::unprocessable('Status is invalid.', 'invalid_client_status');
		}

		if (!$update && !isset($fields['client_name'])) {
			return [];
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

	private function optionalString(mixed $value, string $field, ?int $maxLength): string
	{
		if (!is_string($value) || ($maxLength !== null && strlen(trim($value)) > $maxLength)) {
			throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
		}

		return trim($value);
	}
}
