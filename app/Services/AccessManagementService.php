<?php
declare(strict_types=1);
namespace Sentinel\Services;

use Sentinel\Core\{HttpException, RuntimeContext};
use Sentinel\Repositories\OperationalRepository;

final class AccessManagementService
{
    private const FIELDS = [
        'visitors' => ['first_name' => 100, 'last_name' => 100, 'contact_phone' => 30, 'contact_email' => 255, 'external_reference' => 100, 'visitor_status' => ['ACTIVE','BLOCKED','ARCHIVED']],
        'vehicles' => ['registration_number' => 30, 'make' => 80, 'model' => 80, 'color' => 40, 'vehicle_status' => ['ACTIVE','BLOCKED','ARCHIVED']],
        'visits' => ['visitor_id' => 'id', 'site_id' => 'id', 'host_employee_id' => 'id', 'purpose' => 500, 'planned_arrival_at' => 'date', 'planned_departure_at' => 'date'],
        'access' => ['site_id' => 'id', 'vehicle_id' => 'id', 'visitor_visit_id' => 'id', 'employee_id' => 'id', 'event_type' => 100, 'direction' => ['ENTRY','EXIT'], 'authorization_status' => ['AUTHORIZED','DENIED','PENDING'], 'denied_reason' => 500, 'occurred_at' => 'date'],
    ];
    private const REQUIRED = ['visitors' => ['first_name','last_name'], 'vehicles' => ['registration_number'], 'visits' => ['visitor_id','site_id','purpose'], 'access' => ['site_id','event_type','direction','occurred_at']];
    private const FILTERS = ['visitors' => ['visitor_status'], 'vehicles' => ['vehicle_status','registration_number'], 'visits' => ['visitor_id','site_id','visit_status'], 'access' => ['site_id','vehicle_id','visitor_visit_id','employee_id','direction','authorization_status']];
    public function __construct(private readonly OperationalRepository $records, private readonly RuntimeContext $context, private readonly AuditService $audit) {}
    public function listRecords(string $domain, array $filters): array
    {
        $this->domain($domain); $this->allowed($filters, self::FILTERS[$domain]);
        foreach ($filters as $field => $value) {
            if (str_ends_with($field, '_id')) $filters[$field] = $this->id($value);
            elseif (!is_string($value) || strlen($value) > 100) throw HttpException::unprocessable('Invalid filter.');
        }
        return $this->safe(fn () => $this->records->list($domain, $filters, self::FILTERS[$domain]));
    }
    public function getRecord(string $domain, string $id): array
    {
        $this->domain($domain); $id = $this->id($id);
        return $this->safe(fn () => $this->records->find($domain, $id)) ?? throw HttpException::notFound('Record not found.');
    }
    public function createRecord(string $domain, array $input): array
    {
        $this->domain($domain); $data = $this->validate($domain, $input, false); $actor = $this->actor();
        if ($domain === 'visitors') { $data['visitor_uuid'] = uuid4(); $data['visitor_status'] ??= 'ACTIVE'; }
        if ($domain === 'vehicles') { $data['vehicle_uuid'] = uuid4(); $data['vehicle_status'] ??= 'ACTIVE'; $data['registration_number'] = strtoupper($data['registration_number']); }
        if ($domain === 'visits') { $data['visit_status'] = 'PRE_REGISTERED'; $data['created_by_user_id'] = $actor; }
        if ($domain === 'access') { $data['recorded_by_user_id'] = $actor; $data['authorization_status'] ??= 'PENDING'; }
        return $this->safe(fn () => $this->records->transaction(function () use ($domain, $data, $actor): array {
            $this->related('users', $actor); $this->relationships($domain, $data);
            $record = $this->records->insert($domain, $data, array_keys($data));
            $this->audit->record($domain, 'created', (string) $record[OperationalRepository::TABLES[$domain][1]]);
            return $record;
        }));
    }
    public function updateRecord(string $domain, string $id, array $input): array
    {
        if (!in_array($domain, ['visitors','vehicles'], true)) throw HttpException::methodNotAllowed();
        $id = $this->id($id); $data = $this->validate($domain, $input, true); $actor = $this->actor();
        if ($data === []) throw HttpException::unprocessable('No editable fields.');
        if (isset($data['registration_number'])) $data['registration_number'] = strtoupper($data['registration_number']);
        return $this->safe(fn () => $this->records->transaction(function () use ($domain, $id, $data, $actor): array {
            $this->related('users', $actor); $this->related($domain, $id);
            $record = $this->records->update($domain, $id, $data, array_keys(self::FIELDS[$domain]));
            $this->audit->record($domain, 'updated', $id); return $record;
        }));
    }
    public function transitionVisit(string $id, array $input): array
    {
        $this->allowed($input, ['status','occurred_at']); $id = $this->id($id); $actor = $this->actor();
        $status = $input['status'] ?? null;
        if (!is_string($status)) throw HttpException::unprocessable('A visit status is required.');
        $at = $this->date($input['occurred_at'] ?? gmdate('Y-m-d H:i:s'));
        $transitions = ['PRE_REGISTERED' => ['AUTHORIZED','CANCELLED','DENIED'], 'AUTHORIZED' => ['CHECKED_IN','CANCELLED','DENIED'], 'CHECKED_IN' => ['CHECKED_OUT'], 'CHECKED_OUT' => [], 'CANCELLED' => [], 'DENIED' => []];
        return $this->safe(fn () => $this->records->transaction(function () use ($id, $actor, $status, $at, $transitions): array {
            $this->related('users', $actor); $before = $this->related('visits', $id);
            if (!in_array($status, $transitions[$before['visit_status']] ?? [], true)) throw HttpException::conflict('Visit transition is not allowed.');
            $visitor = $this->related('visitors', (string) $before['visitor_id']);
            if (in_array($status, ['AUTHORIZED','CHECKED_IN'], true) && $visitor['visitor_status'] !== 'ACTIVE') throw HttpException::conflict('Visitor is not active.');
            $data = ['visit_status' => $status];
            if ($status === 'AUTHORIZED') $data['authorized_by_user_id'] = $actor;
            if ($status === 'CHECKED_IN') $data['arrival_at'] = $at;
            if ($status === 'CHECKED_OUT') {
                if ($at < $before['arrival_at']) throw HttpException::unprocessable('Departure precedes arrival.');
                $data['departure_at'] = $at;
            }
            $record = $this->records->update('visits', $id, $data, ['visit_status','authorized_by_user_id','arrival_at','departure_at']);
            $this->audit->record('visitor_visit', 'status_changed', $id, ['status' => $before['visit_status']], ['status' => $status]);
            return $record;
        }));
    }
    private function relationships(string $domain, array $data): void
    {
        foreach (['site_id' => 'sites', 'visitor_id' => 'visitors', 'host_employee_id' => 'employees', 'employee_id' => 'employees', 'vehicle_id' => 'vehicles', 'visitor_visit_id' => 'visits'] as $key => $target) {
            if (!isset($data[$key])) continue;
            $row = $this->related($target, (string) $data[$key]);
            if ($domain === 'visits' && $target === 'visitors' && $row['visitor_status'] !== 'ACTIVE') throw HttpException::conflict('Visitor is not active.');
            if ($domain === 'access' && $target === 'visits') {
                if ((string) $row['site_id'] !== $data['site_id']) throw HttpException::unprocessable('Visit belongs to a different site.');
                if ($data['authorization_status'] === 'AUTHORIZED' && !in_array($row['visit_status'], ['AUTHORIZED','CHECKED_IN','CHECKED_OUT'], true)) throw HttpException::conflict('Visit has not been authorized.');
                $visitor = $this->related('visitors', (string) $row['visitor_id']);
                if ($data['authorization_status'] === 'AUTHORIZED' && $data['direction'] === 'ENTRY' && $visitor['visitor_status'] !== 'ACTIVE') throw HttpException::conflict('Visitor entry is blocked.');
            }
            if ($domain === 'access' && $target === 'vehicles' && $data['authorization_status'] === 'AUTHORIZED' && $data['direction'] === 'ENTRY' && $row['vehicle_status'] !== 'ACTIVE') throw HttpException::conflict('Vehicle entry is blocked.');
        }
        if ($domain === 'access') {
            if (!isset($data['vehicle_id']) && !isset($data['visitor_visit_id']) && !isset($data['employee_id'])) throw HttpException::unprocessable('An access subject is required.');
            if ($data['authorization_status'] === 'DENIED' && empty($data['denied_reason'])) throw HttpException::unprocessable('Denied access requires a reason.');
            if ($data['authorization_status'] !== 'DENIED' && isset($data['denied_reason'])) throw HttpException::unprocessable('A denial reason requires denied status.');
        }
        if ($domain === 'visits' && isset($data['planned_arrival_at'], $data['planned_departure_at']) && $data['planned_departure_at'] < $data['planned_arrival_at']) throw HttpException::unprocessable('Planned departure precedes arrival.');
    }
    private function validate(string $domain, array $input, bool $update): array
    {
        $this->allowed($input, array_keys(self::FIELDS[$domain]));
        foreach (self::REQUIRED[$domain] as $field) if ((!$update || array_key_exists($field, $input)) && !isset($input[$field])) throw HttpException::unprocessable('Required field missing.');
        foreach ($input as $field => $value) {
            if ($value === null) { if (is_array(self::FIELDS[$domain][$field])) throw HttpException::unprocessable('Status cannot be null.'); continue; }
            $rule = self::FIELDS[$domain][$field];
            if ($rule === 'id') $input[$field] = $this->id($value);
            elseif ($rule === 'date') $input[$field] = $this->date($value);
            elseif (is_array($rule)) { if (!in_array($value, $rule, true)) throw HttpException::unprocessable('Invalid status.'); }
            elseif (!is_string($value) || trim($value) === '' || strlen($value) > $rule) throw HttpException::unprocessable('Invalid text field.');
            else $input[$field] = trim($value);
        }
        if (isset($input['contact_email']) && filter_var($input['contact_email'], FILTER_VALIDATE_EMAIL) === false) throw HttpException::unprocessable('Invalid email address.');
        return $input;
    }
    private function related(string $domain, string $id): array { return $this->records->find($domain, $id, true) ?? throw HttpException::notFound('Related record not found.'); }
    private function domain(string $domain): void { $this->context->requireTenant(); if (!isset(self::FIELDS[$domain])) throw HttpException::notFound(); }
    private function allowed(array $input, array $fields): void { $this->context->requireTenant(); if (array_diff(array_keys($input), $fields) !== []) throw HttpException::unprocessable('Unknown fields.'); }
    private function actor(): string { if (!$this->context->isAuthenticated()) throw HttpException::unauthorized(); return $this->id($this->context->user()); }
    private function id(mixed $value): string { if ((!is_int($value) && !is_string($value)) || !preg_match('/^[1-9][0-9]{0,18}$/D', (string) $value)) throw HttpException::unprocessable('Invalid identifier.'); return (string) $value; }
    private function date(mixed $value): string { $date = is_string($value) ? \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value) : false; if ($date === false || $date->format('Y-m-d H:i:s') !== $value) throw HttpException::unprocessable('Invalid UTC timestamp.'); return $value; }
    private function safe(callable $operation): mixed
    {
        try { return $operation(); } catch (\PDOException $error) {
            if (in_array((string) $error->getCode(), ['23000','23505','40001'], true)) throw HttpException::conflict('Record conflicts with current state.');
            throw HttpException::serviceUnavailable('Operational storage is unavailable.');
        }
    }
}
