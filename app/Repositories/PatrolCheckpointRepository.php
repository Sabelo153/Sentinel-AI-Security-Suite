<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class PatrolCheckpointRepository extends Repository
{
    private const COLUMNS = 'pc.patrol_checkpoint_id, pc.patrol_checkpoint_uuid, pc.company_id, pc.patrol_route_id, pc.post_id, pc.checkpoint_name, pc.checkpoint_order, pc.qr_code, pc.nfc_tag, pc.gps_lat, pc.gps_long, pc.expected_interval_minutes, pc.status, pc.created_at, pc.updated_at, p.post_name';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'patrol_checkpoints';
    }

    public function listForRoute(string $routeId): array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM patrol_checkpoints pc '
            . 'INNER JOIN patrol_routes pr ON pr.patrol_route_id = pc.patrol_route_id AND pr.company_id = pc.company_id AND pr.deleted_at IS NULL '
            . 'LEFT JOIN posts p ON p.post_id = pc.post_id AND p.company_id = pc.company_id AND p.deleted_at IS NULL '
            . 'WHERE pc.patrol_route_id = :route_id AND pc.company_id = :company_id AND pc.deleted_at IS NULL '
            . 'ORDER BY pc.checkpoint_order, pc.patrol_checkpoint_id',
            ['route_id' => $routeId, 'company_id' => $companyId],
        );
    }

    public function findByIdForCompany(string $checkpointId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM patrol_checkpoints pc '
            . 'INNER JOIN patrol_routes pr ON pr.patrol_route_id = pc.patrol_route_id AND pr.company_id = pc.company_id AND pr.deleted_at IS NULL '
            . 'LEFT JOIN posts p ON p.post_id = pc.post_id AND p.company_id = pc.company_id AND p.deleted_at IS NULL '
            . 'WHERE pc.patrol_checkpoint_id = :checkpoint_id AND pc.company_id = :company_id AND pc.deleted_at IS NULL LIMIT 1',
            ['checkpoint_id' => $checkpointId, 'company_id' => $companyId],
        );
    }

    public function routeBelongsToCompany(string $routeId): bool
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT patrol_route_id FROM patrol_routes WHERE patrol_route_id = :route_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',
            ['route_id' => $routeId, 'company_id' => $companyId],
        ) !== null;
    }

    public function routeDetails(string $routeId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT site_id FROM patrol_routes WHERE patrol_route_id = :route_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',
            ['route_id' => $routeId, 'company_id' => $companyId],
        );
    }

    public function postBelongsToSite(string $postId, string $siteId): bool
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT post_id FROM posts WHERE post_id = :post_id AND site_id = :site_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',
            ['post_id' => $postId, 'site_id' => $siteId, 'company_id' => $companyId],
        ) !== null;
    }

    public function sequenceExistsForRoute(string $routeId, int $sequence, ?string $excludeId = null): bool
    {
        $companyId = $this->requireCompanyId();
        $sql = 'SELECT pc.patrol_checkpoint_id FROM patrol_checkpoints pc INNER JOIN patrol_routes pr ON pr.patrol_route_id = pc.patrol_route_id AND pr.company_id = pc.company_id WHERE pc.patrol_route_id = :route_id AND pc.company_id = :company_id AND pc.checkpoint_order = :checkpoint_order AND pc.deleted_at IS NULL AND pr.deleted_at IS NULL';
        $params = ['route_id' => $routeId, 'company_id' => $companyId, 'checkpoint_order' => $sequence];

        if ($excludeId !== null) {
            $sql .= ' AND pc.patrol_checkpoint_id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params) !== null;
    }

    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute(
            'INSERT INTO patrol_checkpoints (patrol_checkpoint_uuid, company_id, patrol_route_id, post_id, checkpoint_name, checkpoint_order, qr_code, nfc_tag, gps_lat, gps_long, expected_interval_minutes, status) '
            . 'VALUES (:uuid, :company_id, :patrol_route_id, :post_id, :checkpoint_name, :checkpoint_order, :qr_code, :nfc_tag, :gps_lat, :gps_long, :expected_interval_minutes, :status)',
            [
                'uuid' => uuid4(),
                'company_id' => $companyId,
                'patrol_route_id' => $data['patrol_route_id'],
                'post_id' => $data['post_id'] ?? null,
                'checkpoint_name' => $data['checkpoint_name'],
                'checkpoint_order' => $data['checkpoint_order'],
                'qr_code' => $data['qr_code'] ?? null,
                'nfc_tag' => $data['nfc_tag'] ?? null,
                'gps_lat' => $data['gps_lat'] ?? null,
                'gps_long' => $data['gps_long'] ?? null,
                'expected_interval_minutes' => $data['expected_interval_minutes'] ?? null,
                'status' => $data['status'] ?? 'ACTIVE',
            ],
        );

        $checkpointId = (string) $this->db->connection()->lastInsertId();
        $created = $this->findByIdForCompany($checkpointId);
        if ($created === null) {
            throw new \RuntimeException('Created patrol checkpoint could not be loaded.');
        }

        return $created;
    }

    public function updateForCompany(string $checkpointId, array $data): ?array
    {
        $companyId = $this->requireCompanyId();
        $assignments = [];
        $params = ['checkpoint_id' => $checkpointId, 'company_id' => $companyId];

        foreach (['patrol_route_id', 'post_id', 'checkpoint_name', 'checkpoint_order', 'qr_code', 'nfc_tag', 'gps_lat', 'gps_long', 'expected_interval_minutes', 'status'] as $column) {
            if (array_key_exists($column, $data)) {
                $assignments[] = $column . ' = :' . $column;
                $params[$column] = $data[$column];
            }
        }

        if ($assignments !== []) {
            $this->db->execute(
                'UPDATE patrol_checkpoints SET ' . implode(', ', $assignments) . ' WHERE patrol_checkpoint_id = :checkpoint_id AND company_id = :company_id AND deleted_at IS NULL',
                $params,
            );
        }

        return $this->findByIdForCompany($checkpointId);
    }
}
