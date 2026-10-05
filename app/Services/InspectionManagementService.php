<?php
declare(strict_types=1);
namespace Sentinel\Services;
use Sentinel\Core\{HttpException, RuntimeContext};
use Sentinel\Repositories\OperationalRepository;

final class InspectionManagementService
{
    private const FIELDS = ['site_id' => 'id','inspector_employee_id' => 'id','inspector_user_id' => 'id','inspection_type' => 100,'scheduled_at' => 'date','findings_summary' => 65535,'corrective_action_summary' => 65535,'follow_up_at' => 'date','notes' => 65535,'overall_outcome' => ['PASS','FAIL','OBSERVATION','NA']];
    private const ITEM_FIELDS = ['checklist_code' => 100,'checklist_label' => 255,'item_result' => ['PASS','FAIL','NA','OBSERVATION'],'finding' => 65535,'corrective_action' => 65535,'item_status' => ['OPEN','IN_PROGRESS','RESOLVED','NOT_REQUIRED'],'due_at' => 'date','resolved_at' => 'date'];
    public function __construct(private readonly OperationalRepository $records, private readonly RuntimeContext $context, private readonly AuditService $audit) {}
    public function listInspections(array $filters): array
    {
        $this->allowed($filters, ['site_id','status','inspector_employee_id','inspector_user_id']);
        foreach ($filters as $key => $value) if ($key !== 'status') $filters[$key] = $this->id($value);
        if (isset($filters['status']) && !in_array($filters['status'], ['DRAFT','IN_PROGRESS','COMPLETED','REVIEWED','CANCELLED'], true)) throw HttpException::unprocessable('Invalid inspection status.');
        return $this->safe(fn () => $this->records->list('inspections', $filters, ['site_id','status','inspector_employee_id','inspector_user_id']));
    }
    public function getInspection(string $id): array { $id = $this->id($id); return $this->safe(fn () => $this->record('inspections', $id, false)); }
    public function items(string $id): array { $this->getInspection($id); return $this->safe(fn () => $this->records->list('inspection-items', ['inspection_id' => $id], ['inspection_id'])); }
    public function createInspection(array $input): array
    {
        $data = $this->validate($input, self::FIELDS);
        foreach (['site_id','inspection_type'] as $key) if (!isset($data[$key])) throw HttpException::unprocessable('Inspection site and type are required.');
        $actor = $this->actor(); $data += ['inspection_uuid' => uuid4(),'status' => 'DRAFT','created_by_user_id' => $actor,'updated_by_user_id' => $actor];
        return $this->safe(fn () => $this->records->transaction(function () use ($data, $actor): array {
            $this->record('users', $actor); $this->relationships($data);
            $row = $this->records->insert('inspections', $data, [...array_keys(self::FIELDS),'inspection_uuid','status','created_by_user_id','updated_by_user_id']);
            $this->audit->record('inspection','created',(string) $row['inspection_id']); return $row;
        }));
    }
    public function updateInspection(string $id, array $input): array
    {
        $id = $this->id($id); $data = $this->validate($input, self::FIELDS); $actor = $this->actor();
        if ($data === []) throw HttpException::unprocessable('No editable fields.');
        foreach (['site_id','inspection_type'] as $key) if (array_key_exists($key, $data) && $data[$key] === null) throw HttpException::unprocessable('Required inspection field cannot be null.');
        return $this->safe(fn () => $this->records->transaction(function () use ($id, $data, $actor): array {
            $before = $this->record('inspections', $id); $this->editable($before); $this->record('users', $actor);
            if ($before['status'] !== 'DRAFT' && isset($data['site_id']) && (string) $before['site_id'] !== $data['site_id']) throw HttpException::conflict('Started inspection cannot change site.');
            $this->relationships(array_merge($before, $data));
            $row = $this->records->update('inspections', $id, [...$data,'updated_by_user_id' => $actor], [...array_keys(self::FIELDS),'updated_by_user_id']);
            $this->audit->record('inspection','updated',$id); return $row;
        }));
    }
    public function saveItem(string $id, ?string $itemId, array $input): array
    {
        $id = $this->id($id); if ($itemId !== null) $itemId = $this->id($itemId);
        $data = $this->validate($input, self::ITEM_FIELDS); $actor = $this->actor();
        if ($data === []) throw HttpException::unprocessable('No checklist fields.');
        return $this->safe(fn () => $this->records->transaction(function () use ($id, $itemId, $data, $actor): array {
            $this->editable($this->record('inspections', $id)); $this->record('users', $actor);
            $before = $itemId === null ? [] : $this->record('inspection-items', $itemId);
            if ($itemId !== null && (string) $before['inspection_id'] !== $id) throw HttpException::notFound('Checklist item not found.');
            $effective = array_merge(['item_status' => 'OPEN'], $before, $data);
            foreach (['checklist_code','checklist_label','item_status'] as $key) if (!isset($effective[$key])) throw HttpException::unprocessable('Required checklist field missing.');
            if ($effective['item_status'] === 'RESOLVED' && empty($effective['resolved_at'])) throw HttpException::unprocessable('Resolved item requires a resolution timestamp.');
            if ($effective['item_status'] !== 'RESOLVED' && !empty($effective['resolved_at'])) throw HttpException::unprocessable('Resolution timestamp requires resolved status.');
            if (($effective['item_result'] ?? null) === 'FAIL' && empty($effective['finding'])) throw HttpException::unprocessable('Failed item requires a finding.');
            if ($itemId === null) {
                if (count($this->records->list('inspection-items', ['inspection_id' => $id], ['inspection_id'])) >= 500) throw HttpException::conflict('Inspection checklist limit reached.');
                $row = $this->records->insert('inspection-items', ['inspection_id' => $id, ...$effective], ['inspection_id',...array_keys(self::ITEM_FIELDS)]);
            } else $row = $this->records->update('inspection-items', $itemId, $data, array_keys(self::ITEM_FIELDS));
            $this->audit->record('inspection_item', $itemId === null ? 'created' : 'updated', (string) $row['inspection_item_id'], [], ['inspection_id' => $id]); return $row;
        }));
    }
    public function transition(string $id, array $input, bool $review = false): array
    {
        $id = $this->id($id); $this->allowed($input, $review ? [] : ['status','occurred_at']); $actor = $this->actor();
        $status = $review ? 'REVIEWED' : ($input['status'] ?? null);
        if (!is_string($status) || (!$review && !in_array($status, ['IN_PROGRESS','COMPLETED','CANCELLED'], true))) throw HttpException::unprocessable('Invalid inspection transition.');
        $at = $this->date($input['occurred_at'] ?? gmdate('Y-m-d H:i:s'));
        return $this->safe(fn () => $this->records->transaction(function () use ($id, $status, $at, $actor): array {
            $before = $this->record('inspections', $id); $this->record('users', $actor);
            $transitions = ['DRAFT' => ['IN_PROGRESS','CANCELLED'],'IN_PROGRESS' => ['COMPLETED','CANCELLED'],'COMPLETED' => ['REVIEWED'],'REVIEWED' => [],'CANCELLED' => []];
            if (!in_array($status, $transitions[$before['status']] ?? [], true)) throw HttpException::conflict('Inspection transition is not allowed.');
            $data = ['status' => $status, 'updated_by_user_id' => $actor];
            if ($status === 'IN_PROGRESS') $data['started_at'] = $at;
            if ($status === 'COMPLETED') {
                if ($at < $before['started_at']) throw HttpException::unprocessable('Completion precedes start.');
                $items = $this->records->list('inspection-items', ['inspection_id' => $id], ['inspection_id']);
                if ($items === [] || empty($before['overall_outcome'])) throw HttpException::unprocessable('Completion requires checklist results and an overall outcome.');
                foreach ($items as $item) {
                    if ($item['item_result'] === null) throw HttpException::unprocessable('All checklist items require a result.');
                    if ($item['item_result'] === 'FAIL' && $before['overall_outcome'] === 'PASS') throw HttpException::unprocessable('Failed checklist cannot have a passing overall outcome.');
                }
                $data['completed_at'] = $at;
            }
            $row = $this->records->update('inspections', $id, $data, ['status','updated_by_user_id','started_at','completed_at']);
            $this->audit->record('inspection','status_changed',$id,['status' => $before['status']],['status' => $status]); return $row;
        }));
    }
    private function relationships(array $data): void { foreach (['site_id' => 'sites','inspector_employee_id' => 'employees','inspector_user_id' => 'users'] as $key => $domain) if (isset($data[$key])) $this->record($domain,(string) $data[$key]); }
    private function editable(array $record): void { if (!in_array($record['status'], ['DRAFT','IN_PROGRESS'], true)) throw HttpException::conflict('Inspection is no longer editable.'); }
    private function record(string $domain, string $id, bool $lock = true): array { return $this->records->find($domain,$id,$lock) ?? throw HttpException::notFound('Record not found.'); }
    private function validate(array $input, array $rules): array
    {
        $this->allowed($input, array_keys($rules));
        foreach ($input as $key => $value) {
            if ($value === null) continue; $rule = $rules[$key];
            if ($rule === 'id') $input[$key] = $this->id($value);
            elseif ($rule === 'date') $input[$key] = $this->date($value);
            elseif (is_array($rule)) { if (!in_array($value,$rule,true)) throw HttpException::unprocessable('Invalid checklist value.'); }
            elseif (!is_string($value) || trim($value) === '' || strlen($value) > $rule) throw HttpException::unprocessable('Invalid inspection text.');
            else $input[$key] = trim($value);
        }
        return $input;
    }
    private function allowed(array $input, array $fields): void { $this->context->requireTenant(); if (array_diff(array_keys($input),$fields) !== []) throw HttpException::unprocessable('Unknown fields.'); }
    private function actor(): string { if (!$this->context->isAuthenticated()) throw HttpException::unauthorized(); return $this->id($this->context->user()); }
    private function id(mixed $value): string { if ((!is_int($value) && !is_string($value)) || !preg_match('/^[1-9][0-9]{0,18}$/D',(string) $value)) throw HttpException::unprocessable('Invalid identifier.'); return (string) $value; }
    private function date(mixed $value): string { $date = is_string($value) ? \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$value) : false; if ($date === false || $date->format('Y-m-d H:i:s') !== $value) throw HttpException::unprocessable('Invalid UTC timestamp.'); return $value; }
    private function safe(callable $operation): mixed { try { return $operation(); } catch (\PDOException $error) { if (in_array((string) $error->getCode(),['23000','23505','40001'],true)) throw HttpException::conflict('Inspection conflicts with current state.'); throw HttpException::serviceUnavailable('Inspection storage is unavailable.'); } }
}
