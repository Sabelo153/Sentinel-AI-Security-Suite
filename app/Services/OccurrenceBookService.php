<?php
declare(strict_types=1);
namespace Sentinel\Services;

use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\OccurrenceBookRepository;

final class OccurrenceBookService
{
    public function __construct(private readonly OccurrenceBookRepository $entries, private readonly RuntimeContext $context, private readonly AuditService $audit) {}

    public function listEntries(array $filters = []): array
    {
        $this->allowed($filters, ['site_id', 'shift_assignment_id', 'entry_type', 'handover_required', 'date_from', 'date_to']);
        foreach (['site_id', 'shift_assignment_id'] as $key) if (array_key_exists($key, $filters)) $filters[$key] = $this->id($filters[$key]);
        if (array_key_exists('entry_type', $filters)) $filters['entry_type'] = $this->text($filters['entry_type'], 100);
        if (array_key_exists('handover_required', $filters)) {
            if (!in_array($filters['handover_required'], [true, false, 0, 1, '0', '1'], true)) throw HttpException::unprocessable('Invalid handover filter.');
            $filters['handover_required'] = (int) $filters['handover_required'];
        }
        foreach (['date_from', 'date_to'] as $key) if (array_key_exists($key, $filters)) $filters[$key] = $this->date($filters[$key]);
        if (isset($filters['date_from'], $filters['date_to']) && $filters['date_from'] > $filters['date_to']) throw HttpException::unprocessable('Invalid date range.');
        return $this->safe(fn () => $this->entries->listForCompany($filters));
    }

    public function getEntry(string $id): array
    {
        $id = $this->id($id);
        return $this->safe(fn () => $this->entries->find($id)) ?? throw HttpException::notFound('Occurrence entry not found.', 'occurrence_not_found');
    }

    public function revisions(string $id): array
    {
        $this->getEntry($id);
        return $this->safe(fn () => $this->entries->revisions($id));
    }

    public function createEntry(array $input): array
    {
        $this->allowed($input, ['site_id', 'employee_id', 'shift_assignment_id', 'entry_type', 'occurred_at', 'entry_text', 'handover_required', 'handover_reference']);
        $data = [
            'occurrence_entry_uuid' => uuid4(), 'entry_reference' => 'OB-' . uuid4(),
            'site_id' => $this->id($input['site_id'] ?? null),
            'employee_id' => isset($input['employee_id']) ? $this->id($input['employee_id']) : null,
            'shift_assignment_id' => isset($input['shift_assignment_id']) ? $this->id($input['shift_assignment_id']) : null,
            'entry_type' => $this->text($input['entry_type'] ?? null, 100),
            'occurred_at' => $this->date($input['occurred_at'] ?? null),
            'entry_text' => $this->text($input['entry_text'] ?? null, 65535),
            'handover_required' => $input['handover_required'] ?? false,
            'handover_reference' => isset($input['handover_reference']) ? $this->text($input['handover_reference'], 100) : null,
            'created_by_user_id' => $this->actor(),
        ];
        if (!is_bool($data['handover_required'])) throw HttpException::unprocessable('handover_required must be a boolean.');
        $data['handover_required'] = (int) $data['handover_required'];
        return $this->safe(fn () => $this->entries->transaction(function () use ($data): array {
            $this->relationships($data);
            $entry = $this->entries->create($data);
            $this->audit->record('occurrence_entry', 'created', (string) $entry['occurrence_entry_id'], [], ['site_id' => $entry['site_id']]);
            return $entry;
        }));
    }

    public function reviseEntry(string $id, array $input): array
    {
        $id = $this->id($id);
        $this->allowed($input, ['expected_revision', 'entry_text', 'revision_reason']);
        if (!is_int($input['expected_revision'] ?? null) || $input['expected_revision'] < 0) throw HttpException::unprocessable('expected_revision must be a nonnegative integer.');
        $text = $this->text($input['entry_text'] ?? null, 65535);
        $reason = $this->text($input['revision_reason'] ?? null, 500);
        $actor = $this->actor();
        return $this->safe(fn () => $this->entries->transaction(function () use ($id, $input, $text, $reason, $actor): array {
            if ($this->entries->relationship('user', $actor) === null) throw HttpException::forbidden('Actor is unavailable.');
            $entry = $this->entries->revise($id, $input['expected_revision'], $text, $reason, $actor);
            $this->audit->record('occurrence_entry', 'revised', $id, [], ['revision_number' => $input['expected_revision'] + 1]);
            return $entry;
        }));
    }

    private function relationships(array $data): void
    {
        foreach (['site_id' => 'site', 'employee_id' => 'employee', 'shift_assignment_id' => 'shift', 'created_by_user_id' => 'user'] as $field => $kind) {
            if ($data[$field] === null) continue;
            $row = $this->entries->relationship($kind, (string) $data[$field]);
            if ($row === null || ($row['deleted_at'] ?? null) !== null) throw HttpException::notFound('Related record not found.', 'occurrence_relationship_not_found');
            if ($kind === 'shift' && ((string) $row['site_id'] !== $data['site_id'] || ($data['employee_id'] !== null && (string) $row['employee_id'] !== $data['employee_id']))) throw HttpException::unprocessable('Shift does not match the entry site and employee.', 'invalid_occurrence_relationship');
        }
    }

    private function actor(): string
    {
        if (!$this->context->isAuthenticated()) throw HttpException::unauthorized();
        return $this->id($this->context->user());
    }
    private function id(mixed $value): string
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[1-9][0-9]{0,18}$/D', (string) $value)) throw HttpException::unprocessable('Invalid identifier.');
        return (string) $value;
    }
    private function text(mixed $value, int $max): string
    {
        if (!is_string($value) || trim($value) === '' || strlen($value) > $max) throw HttpException::unprocessable('Invalid occurrence text.');
        return trim($value);
    }
    private function date(mixed $value): string
    {
        if (!is_string($value)) throw HttpException::unprocessable('Expected a UTC timestamp (Y-m-d H:i:s).');
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new \DateTimeZone('UTC'));
        if ($date === false || $date->format('Y-m-d H:i:s') !== $value) throw HttpException::unprocessable('Expected a valid UTC timestamp (Y-m-d H:i:s).');
        return $value;
    }
    private function allowed(array $data, array $allowed): void
    {
        $this->context->requireTenant();
        if (array_diff(array_keys($data), $allowed) !== []) throw HttpException::unprocessable('Unknown occurrence fields.');
    }
    private function safe(callable $operation): mixed
    {
        try { return $operation(); }
        catch (\PDOException $error) {
            if (in_array((string) $error->getCode(), ['23000', '23505', '40001'], true)) throw HttpException::conflict('Occurrence record conflicts with current state.', 'occurrence_conflict');
            throw HttpException::serviceUnavailable('Occurrence storage is unavailable.', 'occurrence_unavailable');
        }
    }
}
