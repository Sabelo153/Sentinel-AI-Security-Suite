<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class BranchRepository extends Repository
{
    private const SAFE_COLUMNS = 'branch_id, company_id, client_id, branch_name, phone, email, address, city, province, status, created_at, updated_at';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'branches';
    }

    /** @return array<int, array<string, mixed>> */
    public function listForCompany(): array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetchAll(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM branches WHERE company_id = :company_id ORDER BY branch_id',
            ['company_id' => $companyId],
        );
    }

    /** @return array<string, mixed>|null */
    public function findByIdForCompany(string $branchId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM branches WHERE branch_id = :branch_id AND company_id = :company_id LIMIT 1',
            ['branch_id' => $branchId, 'company_id' => $companyId],
        );
    }

    public function clientBelongsToCompany(int $clientId): bool
    {
        $companyId = $this->requireCompanyId();
        $row = $this->db->fetch(
            'SELECT client_id FROM clients WHERE client_id = :client_id AND company_id = :company_id LIMIT 1',
            ['client_id' => $clientId, 'company_id' => $companyId],
        );

        return $row !== null;
    }

    /** @param array{client_id: int, branch_name: string, phone?: string|null, email?: string|null, address?: string|null, city?: string|null, province?: string|null} $data @return array<string, mixed> */
    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute(
            'INSERT INTO branches (company_id, client_id, branch_name, phone, email, address, city, province, status) '
            . 'VALUES (:company_id, :client_id, :branch_name, :phone, :email, :address, :city, :province, :status)',
            [
                'company_id' => $companyId,
                'client_id' => $data['client_id'],
                'branch_name' => $data['branch_name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'status' => 'ACTIVE',
            ],
        );

        $branchId = (string) $this->db->connection()->lastInsertId();
        $created = $this->findByIdForCompany($branchId);
        if ($created === null) {
            throw new \RuntimeException('Created branch could not be loaded.');
        }

        return $created;
    }

    /** @param array{client_id?: int, branch_name?: string, phone?: string|null, email?: string|null, address?: string|null, city?: string|null, province?: string|null, status?: string} $data @return array<string, mixed>|null */
    public function updateForCompany(string $branchId, array $data): ?array
    {
        $companyId = $this->requireCompanyId();
        $assignments = [];
        $params = ['branch_id' => $branchId, 'company_id' => $companyId];

        foreach (['client_id', 'branch_name', 'phone', 'email', 'address', 'city', 'province', 'status'] as $column) {
            if (array_key_exists($column, $data)) {
                $assignments[] = $column . ' = :' . $column;
                $params[$column] = $data[$column];
            }
        }

        if ($assignments !== []) {
            $this->db->execute(
                'UPDATE branches SET ' . implode(', ', $assignments) . ' WHERE branch_id = :branch_id AND company_id = :company_id',
                $params,
            );
        }

        return $this->findByIdForCompany($branchId);
    }
}