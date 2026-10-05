<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class DeviceRepository extends Repository
{
    private const COLUMNS = 'device_id, device_uuid, company_id, device_name, device_type, platform, device_identifier, device_status, last_seen_at, created_at, updated_at';

    public function __construct(Database $db, ?RuntimeContext $context = null) { parent::__construct($db, $context); }
    protected function table(): string { return 'devices'; }

    /** @return array<int, array<string, mixed>> */
    public function listForCompany(array $filters = []): array
    {
        $where = ['company_id = :company_id', 'deleted_at IS NULL']; $params = ['company_id' => $this->requireCompanyId()];
        foreach (['device_status', 'device_type', 'platform', 'device_identifier'] as $field) if (isset($filters[$field])) { $where[] = $field . ' = :' . $field; $params[$field] = $filters[$field]; }
        foreach (['last_seen_from' => '>=', 'last_seen_to' => '<='] as $field => $operator) if (isset($filters[$field])) { $where[] = 'last_seen_at ' . $operator . ' :' . $field; $params[$field] = $filters[$field]; }
        return $this->db->fetchAll('SELECT ' . self::COLUMNS . ' FROM devices WHERE ' . implode(' AND ', $where) . ' ORDER BY device_name, device_id', $params);
    }

    /** @return array<string, mixed>|null */
    public function findByIdForCompany(string $deviceId): ?array
    {
        return $this->db->fetch('SELECT ' . self::COLUMNS . ' FROM devices WHERE device_id = :device_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1', ['device_id' => $deviceId, 'company_id' => $this->requireCompanyId()]);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createForCompany(array $data): array
    {
        $this->db->execute('INSERT INTO devices (device_uuid, company_id, device_name, device_type, platform, device_identifier, device_status, last_seen_at) VALUES (:device_uuid, :company_id, :device_name, :device_type, :platform, :device_identifier, :device_status, :last_seen_at)', ['device_uuid' => $data['device_uuid'], 'company_id' => $this->requireCompanyId(), 'device_name' => $data['device_name'], 'device_type' => $data['device_type'], 'platform' => $data['platform'], 'device_identifier' => $data['device_identifier'], 'device_status' => $data['device_status'], 'last_seen_at' => $data['last_seen_at'] ?? null]);
        $device = $this->findByIdForCompany((string) $this->db->connection()->lastInsertId());
        if ($device === null) throw new \RuntimeException('Created device could not be loaded.');
        return $device;
    }

    /** @param array<string, mixed> $data @return array<string, mixed>|null */
    public function updateForCompany(string $deviceId, array $data): ?array
    {
        $sets = []; $params = ['device_id' => $deviceId, 'company_id' => $this->requireCompanyId()];
        foreach (['device_name', 'device_type', 'platform', 'device_identifier', 'device_status', 'last_seen_at'] as $field) if (array_key_exists($field, $data)) { $sets[] = $field . ' = :' . $field; $params[$field] = $data[$field]; }
        if ($sets !== []) $this->db->execute('UPDATE devices SET ' . implode(', ', $sets) . ' WHERE device_id = :device_id AND company_id = :company_id AND deleted_at IS NULL', $params);
        return $this->findByIdForCompany($deviceId);
    }
}