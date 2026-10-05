<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class ClientRepository extends Repository
{
	private const SAFE_COLUMNS = 'client_id, company_id, client_name, contact_person, phone, email, address, city, province, postal_code, country, contract_start, contract_end, status, created_at, updated_at';

	public function __construct(Database $db, ?RuntimeContext $context = null)
	{
		parent::__construct($db, $context);
	}

	protected function table(): string
	{
		return 'clients';
	}

	/** @return array<int, array<string, mixed>> */
	public function listForCompany(): array
	{
		$companyId = $this->requireCompanyId();

		return $this->db->fetchAll(
			'SELECT ' . self::SAFE_COLUMNS . ' FROM clients WHERE company_id = :company_id ORDER BY client_id',
			['company_id' => $companyId],
		);
	}

	/** @return array<string, mixed>|null */
	public function findByIdForCompany(string $clientId): ?array
	{
		$companyId = $this->requireCompanyId();

		return $this->db->fetch(
			'SELECT ' . self::SAFE_COLUMNS . ' FROM clients WHERE client_id = :client_id AND company_id = :company_id LIMIT 1',
			['client_id' => $clientId, 'company_id' => $companyId],
		);
	}

	/** @param array<string, mixed> $data @return array<string, mixed> */
	public function createForCompany(array $data): array
	{
		$companyId = $this->requireCompanyId();
		$this->db->execute(
			'INSERT INTO clients (company_id, client_name, contact_person, phone, email, address, city, province, postal_code, country, contract_start, contract_end, status) '
			. 'VALUES (:company_id, :client_name, :contact_person, :phone, :email, :address, :city, :province, :postal_code, :country, :contract_start, :contract_end, :status)',
			[
				'company_id' => $companyId,
				'client_name' => $data['client_name'],
				'contact_person' => $data['contact_person'] ?? null,
				'phone' => $data['phone'] ?? null,
				'email' => $data['email'] ?? null,
				'address' => $data['address'] ?? null,
				'city' => $data['city'] ?? null,
				'province' => $data['province'] ?? null,
				'postal_code' => $data['postal_code'] ?? null,
				'country' => $data['country'] ?? null,
				'contract_start' => $data['contract_start'] ?? null,
				'contract_end' => $data['contract_end'] ?? null,
				'status' => 'ACTIVE',
			],
		);

		$clientId = (string) $this->db->connection()->lastInsertId();
		$created = $this->findByIdForCompany($clientId);
		if ($created === null) {
			throw new \RuntimeException('Created client could not be loaded.');
		}

		return $created;
	}

	/** @param array<string, mixed> $data @return array<string, mixed>|null */
	public function updateForCompany(string $clientId, array $data): ?array
	{
		$companyId = $this->requireCompanyId();
		$assignments = [];
		$params = ['client_id' => $clientId, 'company_id' => $companyId];

		foreach (['client_name', 'contact_person', 'phone', 'email', 'address', 'city', 'province', 'postal_code', 'country', 'contract_start', 'contract_end', 'status'] as $column) {
			if (array_key_exists($column, $data)) {
				$assignments[] = $column . ' = :' . $column;
				$params[$column] = $data[$column];
			}
		}

		if ($assignments !== []) {
			$this->db->execute(
				'UPDATE clients SET ' . implode(', ', $assignments) . ' WHERE client_id = :client_id AND company_id = :company_id',
				$params,
			);
		}

		return $this->findByIdForCompany($clientId);
	}
}
