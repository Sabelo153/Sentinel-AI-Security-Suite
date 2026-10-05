<?php
declare(strict_types=1);
namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Core\HttpException;

final class OccurrenceBookRepository
{
    public function __construct(private readonly Database $db, private readonly RuntimeContext $context) {}

    public function listForCompany(array $filters): array
    {
        $where = ['company_id = :company_id'];
        $params = ['company_id' => $this->context->requireTenant()];
        foreach (['site_id', 'shift_assignment_id', 'entry_type', 'handover_required'] as $field) {
            if (array_key_exists($field, $filters)) {
                $where[] = $field . ' = :' . $field;
                $params[$field] = $filters[$field];
            }
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $field => $operator) {
            if (isset($filters[$field])) {
                $where[] = 'occurred_at ' . $operator . ' :' . $field;
                $params[$field] = $filters[$field];
            }
        }
        return $this->db->fetchAll('SELECT * FROM occurrence_entries WHERE ' . implode(' AND ', $where) . ' ORDER BY occurred_at DESC, occurrence_entry_id DESC LIMIT 500', $params);
    }

    public function find(string $id): ?array
    {
        return $this->db->fetch('SELECT * FROM occurrence_entries WHERE company_id = :company_id AND occurrence_entry_id = :id', ['company_id' => $this->context->requireTenant(), 'id' => $id]);
    }

    public function revisions(string $id): array
    {
        return $this->db->fetchAll('SELECT r.* FROM occurrence_entry_revisions r INNER JOIN occurrence_entries e ON e.occurrence_entry_id = r.occurrence_entry_id AND e.company_id = r.company_id WHERE r.company_id = :company_id AND r.occurrence_entry_id = :id ORDER BY r.revision_number', ['company_id' => $this->context->requireTenant(), 'id' => $id]);
    }

    public function relationship(string $kind, string $id): ?array
    {
        [$table, $key] = match ($kind) {
            'site' => ['sites', 'site_id'],
            'employee' => ['employees', 'employee_id'],
            'user' => ['users', 'user_id'],
            'shift' => ['shift_assignments', 'shift_assignment_id'],
            default => throw new \InvalidArgumentException('Unknown relationship'),
        };
        return $this->db->fetch("SELECT * FROM $table WHERE $key = :id AND company_id = :company_id", ['id' => $id, 'company_id' => $this->context->requireTenant()]);
    }

    public function transaction(callable $operation): mixed
    {
        $this->context->requireTenant();
        if ($this->db->inTransaction()) return $operation();
        $this->db->beginTransaction();
        try {
            $result = $operation();
            $this->db->commit();
            return $result;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function create(array $data): array
    {
        $fields = ['occurrence_entry_uuid', 'site_id', 'employee_id', 'shift_assignment_id', 'entry_reference', 'entry_type', 'occurred_at', 'entry_text', 'handover_required', 'handover_reference', 'created_by_user_id'];
        $params = ['company_id' => $this->context->requireTenant()];
        foreach ($fields as $field) $params[$field] = $data[$field];
        $this->db->execute('INSERT INTO occurrence_entries (' . implode(',', array_keys($params)) . ') VALUES (:' . implode(',:', array_keys($params)) . ')', $params);
        return $this->find((string) $this->db->connection()->lastInsertId()) ?? throw new \RuntimeException('Entry unavailable');
    }

    public function revise(string $id, int $expected, string $text, string $reason, string $actor): array
    {
        // Serialize revisions on the parent row; SQLite serializes writes itself.
        $suffix = $this->db->connection()->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $params = ['company_id' => $this->context->requireTenant(), 'id' => $id];
        $entry = $this->db->fetch('SELECT * FROM occurrence_entries WHERE company_id = :company_id AND occurrence_entry_id = :id' . $suffix, $params);
        if ($entry === null) throw HttpException::notFound('Occurrence entry not found.', 'occurrence_not_found');
        $last = $this->db->fetch('SELECT COALESCE(MAX(revision_number), 0) AS revision FROM occurrence_entry_revisions WHERE company_id = :company_id AND occurrence_entry_id = :id', $params);
        if ((int) $last['revision'] !== $expected) throw HttpException::conflict('Entry has changed. Reload before revising.', 'occurrence_revision_conflict');
        if ($entry['entry_text'] === $text) throw HttpException::unprocessable('Revision must change the entry text.', 'invalid_occurrence_revision');
        $this->db->execute('INSERT INTO occurrence_entry_revisions (company_id, occurrence_entry_id, revision_number, previous_entry_text, revision_reason, revised_by_user_id) VALUES (:company_id, :id, :revision, :previous, :reason, :actor)', [...$params, 'revision' => $expected + 1, 'previous' => $entry['entry_text'], 'reason' => $reason, 'actor' => $actor]);
        $this->db->execute('UPDATE occurrence_entries SET entry_text = :text WHERE company_id = :company_id AND occurrence_entry_id = :id', [...$params, 'text' => $text]);
        return $this->find($id) ?? throw new \RuntimeException('Entry unavailable');
    }
}
