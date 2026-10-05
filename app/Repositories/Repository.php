<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

/**
 * Base CRUD primitives shared by concrete repositories. Deliberately does not
 * auto-scope every query by company_id: some tables are global/system tables,
 * so tenant scoping is opt-in via requireCompanyId() in the concrete
 * repository's own queries.
 */
abstract class Repository
{
    public function __construct(
        protected readonly Database $db,
        protected readonly ?RuntimeContext $context = null,
    ) {
    }

    /**
     * The physical table findById()/findAll() operate on.
     */
    abstract protected function table(): string;

    /**
     * Resolve the current tenant boundary. Fails closed rather than assuming
     * any company — see RuntimeContext::requireTenant().
     */
    protected function requireCompanyId(): int
    {
        if ($this->context === null) {
            throw new \RuntimeException('A RuntimeContext is required to resolve a company/tenant scope.');
        }

        return $this->context->requireTenant();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $table = $this->validateIdentifier($this->table());

        return $this->db->fetch("SELECT * FROM {$table} WHERE id = :id LIMIT 1", ['id' => $id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function findAll(): array
    {
        $table = $this->validateIdentifier($this->table());

        return $this->db->fetchAll("SELECT * FROM {$table}");
    }

    /** @param array<string, mixed> $data */
    public function insert(string $table, array $data): int
    {
        if ($data === []) {
            throw new \InvalidArgumentException('Cannot insert a row with no data.');
        }

        $table = $this->validateIdentifier($table);
        $columns = array_map(fn (string $column) => $this->validateIdentifier($column), array_keys($data));
        $placeholders = array_map(static fn (string $column) => ':' . $column, $columns);

        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $columns), implode(', ', $placeholders));
        $this->db->execute($sql, $data);

        return (int) $this->db->connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $whereParams
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        if ($data === []) {
            throw new \InvalidArgumentException('Cannot update a row with no data.');
        }

        $table = $this->validateIdentifier($table);
        $assignments = [];
        foreach (array_keys($data) as $column) {
            $safeColumn = $this->validateIdentifier($column);
            $assignments[] = "{$safeColumn} = :{$safeColumn}";
        }

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $table, implode(', ', $assignments), $where);

        return $this->db->execute($sql, [...$data, ...$whereParams]);
    }

    /** @param array<string, mixed> $whereParams */
    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $table = $this->validateIdentifier($table);
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);

        return $this->db->execute($sql, $whereParams);
    }

    /**
     * Table/column names can't be bound as PDO parameters, so anything used
     * to build SQL structurally must pass this allow-list instead.
     */
    protected function validateIdentifier(string $identifier): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
            throw new \InvalidArgumentException("Invalid SQL identifier: \"{$identifier}\".");
        }

        return $identifier;
    }
}
