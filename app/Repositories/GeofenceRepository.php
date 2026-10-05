<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class GeofenceRepository extends Repository
{
	private const COLUMNS = 'g.geofence_id, g.company_id, g.site_id, g.geofence_code, g.geofence_name, g.geofence_shape, g.center_latitude, g.center_longitude, g.radius_meters, g.polygon_json, g.geofence_status, g.created_at, g.updated_at, g.deleted_at';

	public function __construct(Database $db, ?RuntimeContext $context = null) { parent::__construct($db, $context); }
	protected function table(): string { return 'geofences'; }

	public function listForCompany(): array
	{
		$companyId = $this->requireCompanyId();
		return $this->db->fetchAll('SELECT ' . self::COLUMNS . ' FROM geofences g WHERE g.company_id = :company_id AND g.deleted_at IS NULL ORDER BY g.geofence_name, g.geofence_id', ['company_id' => $companyId]);
	}

	public function findByIdForCompany(string $id): ?array
	{
		$companyId = $this->requireCompanyId();
		return $this->db->fetch('SELECT ' . self::COLUMNS . ' FROM geofences g WHERE g.geofence_id = :id AND g.company_id = :company_id AND g.deleted_at IS NULL LIMIT 1', ['id' => $id, 'company_id' => $companyId]);
	}

	public function codeExists(string $code, ?string $excludeId = null): bool
	{
		$companyId = $this->requireCompanyId();
		$sql = 'SELECT geofence_id FROM geofences WHERE company_id = :company_id AND geofence_code = :code AND deleted_at IS NULL';
		$parameters = ['company_id' => $companyId, 'code' => $code];
		if ($excludeId !== null) { $sql .= ' AND geofence_id <> :exclude_id'; $parameters['exclude_id'] = $excludeId; }
		return $this->db->fetch($sql . ' LIMIT 1', $parameters) !== null;
	}

	public function siteBelongsToCompany(string $siteId): bool
	{
		$companyId = $this->requireCompanyId();
		return $this->db->fetch('SELECT site_id FROM sites WHERE site_id = :site_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1', ['site_id' => $siteId, 'company_id' => $companyId]) !== null;
	}

	public function createForCompany(array $data): array
	{
		$companyId = $this->requireCompanyId();
		$this->db->execute('INSERT INTO geofences (company_id, site_id, geofence_code, geofence_name, geofence_shape, center_latitude, center_longitude, radius_meters, polygon_json, geofence_status) VALUES (:company_id, :site_id, :code, :name, :shape, :latitude, :longitude, :radius, :polygon, :status)', ['company_id' => $companyId, 'site_id' => $data['site_id'] ?? null, 'code' => $data['geofence_code'], 'name' => $data['geofence_name'], 'shape' => $data['geofence_shape'], 'latitude' => $data['center_latitude'] ?? null, 'longitude' => $data['center_longitude'] ?? null, 'radius' => $data['radius_meters'] ?? null, 'polygon' => $data['polygon_json'] ?? null, 'status' => $data['geofence_status']]);
		$id = (string) $this->db->connection()->lastInsertId();
		$created = $this->findByIdForCompany($id);
		if ($created === null) throw new \RuntimeException('Created geofence could not be loaded.');
		return $created;
	}

	public function updateForCompany(string $id, array $data): ?array
	{
		$companyId = $this->requireCompanyId();
		$assignments = [];
		$parameters = ['id' => $id, 'company_id' => $companyId];
		foreach (['site_id', 'geofence_code', 'geofence_name', 'geofence_shape', 'center_latitude', 'center_longitude', 'radius_meters', 'polygon_json', 'geofence_status'] as $field) {
			if (!array_key_exists($field, $data)) continue;
			$column = $field === 'geofence_code' ? 'code' : ($field === 'geofence_name' ? 'name' : ($field === 'geofence_status' ? 'status' : $field));
			$assignments[] = $field . ' = :' . $column;
			$parameters[$column] = $data[$field];
		}
		if ($assignments !== []) $this->db->execute('UPDATE geofences SET ' . implode(', ', $assignments) . ' WHERE geofence_id = :id AND company_id = :company_id AND deleted_at IS NULL', $parameters);
		return $this->findByIdForCompany($id);
	}
}
