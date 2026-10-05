<?php
declare(strict_types=1);
namespace Sentinel\Repositories;

use Sentinel\Core\{Database, RuntimeContext};

/** SQL identifiers come only from the reviewed domain map, never from requests. */
final class OperationalRepository
{
    public const TABLES = [
        'visitors' => ['visitors', 'visitor_id', true], 'visits' => ['visitor_visits', 'visitor_visit_id', false],
        'vehicles' => ['vehicles', 'vehicle_id', true], 'access' => ['access_events', 'access_event_id', false],
        'inspections' => ['inspections', 'inspection_id', true], 'inspection-items' => ['inspection_items', 'inspection_item_id', false],
        'alerts' => ['alerts', 'alert_id', false], 'notifications' => ['notifications', 'notification_id', false],
        'deliveries' => ['notification_deliveries', 'notification_delivery_id', false],
        'sites' => ['sites', 'site_id', true], 'employees' => ['employees', 'employee_id', false], 'users' => ['users', 'user_id', false],
    ];
    public function __construct(private readonly Database $db, private readonly RuntimeContext $context) {}
    public function transaction(callable $operation): mixed
    {
        $this->context->requireTenant(); if ($this->db->inTransaction()) return $operation();
        $this->db->beginTransaction();
        try { $result = $operation(); $this->db->commit(); return $result; }
        catch (\Throwable $error) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $error; }
    }
    public function find(string $domain, string $id, bool $lock = false): ?array
    {
        [$table, $key, $soft] = self::TABLES[$domain];
        $suffix = $lock && $this->db->connection()->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        return $this->db->fetch("SELECT * FROM $table WHERE $key = :id AND company_id = :company_id" . ($soft ? ' AND deleted_at IS NULL' : '') . $suffix, ['id' => $id, 'company_id' => $this->context->requireTenant()]);
    }
    public function list(string $domain, array $filters, array $allowed): array
    {
        [$table, $key, $soft] = self::TABLES[$domain];
        $where = ['company_id = :company_id']; $params = ['company_id' => $this->context->requireTenant()];
        if ($soft) $where[] = 'deleted_at IS NULL';
        foreach ($allowed as $field) if (array_key_exists($field, $filters)) { $where[] = "$field = :$field"; $params[$field] = $filters[$field]; }
        return $this->db->fetchAll("SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY $key DESC LIMIT 500", $params);
    }
    public function insert(string $domain, array $data, array $allowed): array
    {
        [$table, $key] = self::TABLES[$domain];
        $params = ['company_id' => $this->context->requireTenant()];
        foreach ($allowed as $field) if (array_key_exists($field, $data)) $params[$field] = $data[$field];
        $this->db->execute("INSERT INTO $table (" . implode(',', array_keys($params)) . ') VALUES (:' . implode(',:', array_keys($params)) . ')', $params);
        return $this->find($domain, (string) $this->db->connection()->lastInsertId()) ?? throw new \RuntimeException('Created record unavailable');
    }
    public function update(string $domain, string $id, array $data, array $allowed): array
    {
        [$table, $key, $soft] = self::TABLES[$domain];
        $params = ['company_id' => $this->context->requireTenant(), 'id' => $id]; $sets = [];
        foreach ($allowed as $field) if (array_key_exists($field, $data)) { $sets[] = "$field = :$field"; $params[$field] = $data[$field]; }
        if ($sets !== []) $this->db->execute("UPDATE $table SET " . implode(',', $sets) . " WHERE $key = :id AND company_id = :company_id" . ($soft ? ' AND deleted_at IS NULL' : ''), $params);
        return $this->find($domain, $id) ?? throw new \RuntimeException('Updated record unavailable');
    }
}
