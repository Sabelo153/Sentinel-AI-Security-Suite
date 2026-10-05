<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class PatrolRouteRepository extends Repository
{
    private const COLUMNS = 'pr.patrol_route_id, pr.patrol_route_uuid, pr.company_id, pr.site_id, pr.post_id, pr.route_name, pr.route_code, pr.expected_duration_minutes, pr.status, pr.created_at, pr.updated_at, s.site_name, p.post_name';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'patrol_routes';
    }

    public function listForCompany(): array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM patrol_routes pr '
            . 'INNER JOIN sites s ON s.site_id = pr.site_id AND s.company_id = pr.company_id '
            . 'LEFT JOIN posts p ON p.post_id = pr.post_id AND p.company_id = pr.company_id AND p.deleted_at IS NULL '
            . 'WHERE pr.company_id = :company_id AND pr.deleted_at IS NULL '
            . 'ORDER BY pr.patrol_route_id',
            ['company_id' => $companyId],
        );
    }

    public function findByIdForCompany(string $routeId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM patrol_routes pr '
            . 'INNER JOIN sites s ON s.site_id = pr.site_id AND s.company_id = pr.company_id '
            . 'LEFT JOIN posts p ON p.post_id = pr.post_id AND p.company_id = pr.company_id AND p.deleted_at IS NULL '
            . 'WHERE pr.patrol_route_id = :route_id AND pr.company_id = :company_id AND pr.deleted_at IS NULL LIMIT 1',
            ['route_id' => $routeId, 'company_id' => $companyId],
        );
    }

    public function siteBelongsToCompany(string $siteId): bool
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT site_id FROM sites WHERE site_id = :site_id AND company_id = :company_id LIMIT 1',
            ['site_id' => $siteId, 'company_id' => $companyId],
        ) !== null;
    }

    public function postBelongsToSite(string $postId, string $siteId): bool
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT post_id FROM posts WHERE post_id = :post_id AND site_id = :site_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',
            ['post_id' => $postId, 'site_id' => $siteId, 'company_id' => $companyId],
        ) !== null;
    }

    public function routeCodeExists(string $siteId, ?string $routeCode, ?string $excludeId = null): bool
    {
        if ($routeCode === null) {
            return false;
        }

        $companyId = $this->requireCompanyId();
        $sql = 'SELECT patrol_route_id FROM patrol_routes WHERE company_id = :company_id AND site_id = :site_id AND route_code = :route_code AND deleted_at IS NULL';
        $params = ['company_id' => $companyId, 'site_id' => $siteId, 'route_code' => $routeCode];

        if ($excludeId !== null) {
            $sql .= ' AND patrol_route_id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params) !== null;
    }

    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute(
            'INSERT INTO patrol_routes (patrol_route_uuid, company_id, site_id, post_id, route_name, route_code, expected_duration_minutes, status) '
            . 'VALUES (:uuid, :company_id, :site_id, :post_id, :route_name, :route_code, :expected_duration_minutes, :status)',
            [
                'uuid' => uuid4(),
                'company_id' => $companyId,
                'site_id' => $data['site_id'],
                'post_id' => $data['post_id'] ?? null,
                'route_name' => $data['route_name'],
                'route_code' => $data['route_code'] ?? null,
                'expected_duration_minutes' => $data['expected_duration_minutes'] ?? null,
                'status' => $data['status'] ?? 'ACTIVE',
            ],
        );

        $routeId = (string) $this->db->connection()->lastInsertId();
        $created = $this->findByIdForCompany($routeId);
        if ($created === null) {
            throw new \RuntimeException('Created patrol route could not be loaded.');
        }

        return $created;
    }

    public function updateForCompany(string $routeId, array $data): ?array
    {
        $companyId = $this->requireCompanyId();
        $assignments = [];
        $params = ['route_id' => $routeId, 'company_id' => $companyId];

        foreach (['site_id', 'post_id', 'route_name', 'route_code', 'expected_duration_minutes', 'status'] as $column) {
            if (array_key_exists($column, $data)) {
                $assignments[] = $column . ' = :' . $column;
                $params[$column] = $data[$column];
            }
        }

        if ($assignments !== []) {
            $this->db->execute(
                'UPDATE patrol_routes SET ' . implode(', ', $assignments) . ' WHERE patrol_route_id = :route_id AND company_id = :company_id AND deleted_at IS NULL',
                $params,
            );
        }

        return $this->findByIdForCompany($routeId);
    }
}
