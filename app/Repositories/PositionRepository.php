<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class PositionRepository extends Repository
{
    private const SAFE_COLUMNS = 'position_id, company_id, organization_id, organizational_unit_id, position_code, position_name, position_category, hierarchy_rank, position_status, description, created_at, updated_at, deleted_at';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'positions';
    }

    /** @return array<int, array<string, mixed>> */
    public function listForCompany(): array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetchAll(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM positions WHERE company_id = :company_id AND deleted_at IS NULL ORDER BY position_id',
            ['company_id' => $companyId],
        );
    }

    /** @return array<string, mixed>|null */
    public function findByIdForCompany(string $positionId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM positions WHERE position_id = :position_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',
            ['position_id' => $positionId, 'company_id' => $companyId],
        );
    }

    public function organizationBelongsToCompany(int $organizationId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch(
            'SELECT organization_id FROM organizations WHERE organization_id = :organization_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',
            ['organization_id' => $organizationId, 'company_id' => $companyId],
        ) !== null;
    }

    public function unitBelongsToCompany(int $unitId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch(
            'SELECT organizational_unit_id FROM organizational_units WHERE organizational_unit_id = :unit_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',
            ['unit_id' => $unitId, 'company_id' => $companyId],
        ) !== null;
    }

    public function unitBelongsToOrganization(int $unitId, int $organizationId): bool
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch(
            'SELECT organizational_unit_id FROM organizational_units WHERE organizational_unit_id = :unit_id AND organization_id = :organization_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',
            ['unit_id' => $unitId, 'organization_id' => $organizationId, 'company_id' => $companyId],
        ) !== null;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute(
            'INSERT INTO positions (company_id, organization_id, organizational_unit_id, position_code, position_name, position_category, hierarchy_rank, position_status, description) '
            . 'VALUES (:company_id, :organization_id, :organizational_unit_id, :position_code, :position_name, :position_category, :hierarchy_rank, :position_status, :description)',
            [
                'company_id' => $companyId,
                'organization_id' => $data['organization_id'] ?? null,
                'organizational_unit_id' => $data['organizational_unit_id'] ?? null,
                'position_code' => $data['position_code'],
                'position_name' => $data['position_name'],
                'position_category' => $data['position_category'],
                'hierarchy_rank' => $data['hierarchy_rank'] ?? 100,
                'position_status' => $data['position_status'] ?? 'ACTIVE',
                'description' => $data['description'] ?? null,
            ],
        );

        $positionId = (string) $this->db->connection()->lastInsertId();
        $created = $this->findByIdForCompany($positionId);
        if ($created === null) {
            throw new \RuntimeException('Created position could not be loaded.');
        }

        return $created;
    }

    /** @param array<string, mixed> $data @return array<string, mixed>|null */
    public function updateForCompany(string $positionId, array $data): ?array
    {
        $companyId = $this->requireCompanyId();
        $assignments = [];
        $params = ['position_id' => $positionId, 'company_id' => $companyId];

        foreach (['organization_id', 'organizational_unit_id', 'position_code', 'position_name', 'position_category', 'hierarchy_rank', 'position_status', 'description'] as $column) {
            if (array_key_exists($column, $data)) {
                $assignments[] = $column . ' = :' . $column;
                $params[$column] = $data[$column];
            }
        }

        if ($assignments !== []) {
            $this->db->execute(
                'UPDATE positions SET ' . implode(', ', $assignments) . ' WHERE position_id = :position_id AND company_id = :company_id AND deleted_at IS NULL',
                $params,
            );
        }

        return $this->findByIdForCompany($positionId);
    }
}