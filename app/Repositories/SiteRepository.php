<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class SiteRepository extends Repository
{
    private const SAFE_COLUMNS = 'site_id, company_id, client_id, branch_id, site_name, address, city, province, latitude, longitude, status, created_at, updated_at';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'sites';
    }

    /** @return array<int, array<string, mixed>> */
    public function listForCompany(): array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetchAll(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM sites WHERE company_id = :company_id ORDER BY site_id',
            ['company_id' => $companyId],
        );
    }

    /** @return array<string, mixed>|null */
    public function findByIdForCompany(string $siteId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM sites WHERE site_id = :site_id AND company_id = :company_id LIMIT 1',
            ['site_id' => $siteId, 'company_id' => $companyId],
        );
    }

    public function clientBelongsToCompany(int $clientId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch(
            'SELECT client_id FROM clients WHERE client_id = :client_id AND company_id = :company_id LIMIT 1',
            ['client_id' => $clientId, 'company_id' => $companyId],
        ) !== null;
    }

    public function branchBelongsToCompany(int $branchId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch(
            'SELECT branch_id FROM branches WHERE branch_id = :branch_id AND company_id = :company_id LIMIT 1',
            ['branch_id' => $branchId, 'company_id' => $companyId],
        ) !== null;
    }

    public function branchBelongsToClient(int $branchId, int $clientId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch(
            'SELECT branch_id FROM branches WHERE branch_id = :branch_id AND client_id = :client_id AND company_id = :company_id LIMIT 1',
            ['branch_id' => $branchId, 'client_id' => $clientId, 'company_id' => $companyId],
        ) !== null;
    }

    /** @param array{client_id: int, branch_id: int, site_name: string, address?: string|null, city?: string|null, province?: string|null, latitude?: float|null, longitude?: float|null} $data @return array<string, mixed> */
    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute(
            'INSERT INTO sites (company_id, client_id, branch_id, site_name, address, city, province, latitude, longitude, status) '
            . 'VALUES (:company_id, :client_id, :branch_id, :site_name, :address, :city, :province, :latitude, :longitude, :status)',
            [
                'company_id' => $companyId,
                'client_id' => $data['client_id'],
                'branch_id' => $data['branch_id'],
                'site_name' => $data['site_name'],
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status' => 'ONBOARDING',
            ],
        );

        $siteId = (string) $this->db->connection()->lastInsertId();
        $created = $this->findByIdForCompany($siteId);
        if ($created === null) {
            throw new \RuntimeException('Created site could not be loaded.');
        }

        return $created;
    }

    /** @param array{client_id?: int, branch_id?: int, site_name?: string, address?: string|null, city?: string|null, province?: string|null, latitude?: float|null, longitude?: float|null, status?: string} $data @return array<string, mixed>|null */
    public function updateForCompany(string $siteId, array $data): ?array
    {
        $companyId = $this->requireCompanyId();
        $assignments = [];
        $params = ['site_id' => $siteId, 'company_id' => $companyId];

        foreach (['client_id', 'branch_id', 'site_name', 'address', 'city', 'province', 'latitude', 'longitude', 'status'] as $column) {
            if (array_key_exists($column, $data)) {
                $assignments[] = $column . ' = :' . $column;
                $params[$column] = $data[$column];
            }
        }

        if ($assignments !== []) {
            $this->db->execute(
                'UPDATE sites SET ' . implode(', ', $assignments) . ' WHERE site_id = :site_id AND company_id = :company_id',
                $params,
            );
        }

        return $this->findByIdForCompany($siteId);
    }
}