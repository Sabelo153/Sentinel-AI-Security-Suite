<?php
declare(strict_types=1);
namespace Sentinel\Services;

use Sentinel\Core\{Database, HttpException, RuntimeContext};

/** A window expects one patrol start. No record is rewritten when it becomes missed. */
final class PatrolScheduleService
{
    public function __construct(private readonly Database $db, private readonly RuntimeContext $context, private readonly ?AuditService $audit = null) {}

    public function list(array $input = [], ?\DateTimeImmutable $clock = null, ?int $employeeId = null): array
    {
        if (array_diff(array_keys($input), ['before_id']) !== []) throw HttpException::unprocessable('Unknown schedule filter.');
        $company = $this->context->requireTenant();
        $params = ['company' => $company];
        $where = '';
        if (isset($input['before_id'])) { $params['before'] = $this->positive($input['before_id']); $where = ' AND w.patrol_schedule_id < :before'; }
        if ($employeeId !== null) { $params['employee'] = $employeeId; $where .= ' AND s.employee_id=:employee'; }
        try {
            $rows = $this->db->fetchAll("SELECT w.*, r.route_name, s.shift_name, s.employee_id, s.assignment_status AS shift_status,
                (SELECT MIN(p.patrol_session_id) FROM patrol_sessions p
                 INNER JOIN employee_assignments ea ON ea.employee_assignment_id=p.employee_assignment_id AND ea.company_id=p.company_id
                 WHERE p.company_id=w.company_id AND p.patrol_route_id=w.patrol_route_id
                 AND p.shift_assignment_id=w.shift_assignment_id AND ea.employee_id=s.employee_id
                 AND p.deleted_at IS NULL AND p.started_at>=w.starts_at AND p.started_at<w.ends_at) AS patrol_session_id
                FROM patrol_schedule_windows w
                INNER JOIN patrol_routes r ON r.patrol_route_id=w.patrol_route_id AND r.company_id=w.company_id
                INNER JOIN shift_assignments s ON s.shift_assignment_id=w.shift_assignment_id AND s.company_id=w.company_id
                WHERE w.company_id=:company{$where} ORDER BY w.patrol_schedule_id DESC LIMIT 201", $params);
            $more = count($rows) > 200; $rows = array_slice($rows, 0, 200);
            $now = ($clock ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            foreach ($rows as &$row) {
                $session = $row['patrol_session_id'] === null ? null : $this->db->fetch('SELECT status, started_at, ended_at FROM patrol_sessions WHERE company_id=:company AND patrol_session_id=:id AND deleted_at IS NULL', ['company'=>$company,'id'=>$row['patrol_session_id']]);
                $row['status'] = $row['cancelled_at'] !== null ? 'CANCELLED' : ($session['status'] ?? ($now < $row['starts_at'] ? 'UPCOMING' : ($now >= $row['ends_at'] ? 'MISSED' : 'DUE')));
                $row['patrol_started_at'] = $session['started_at'] ?? null;
                $row['patrol_ended_at'] = $session['ended_at'] ?? null;
            }
            unset($row);
            return ['items'=>$rows, 'next_before_id'=>$more ? end($rows)['patrol_schedule_id'] : null, 'evaluated_at'=>$now];
        } catch (\PDOException) { throw HttpException::serviceUnavailable('Patrol schedule is unavailable.'); }
    }

    public function create(array $input): array
    {
        if (array_diff(array_keys($input), ['patrol_route_id','shift_assignment_id','starts_at','ends_at']) !== []) throw HttpException::unprocessable('Unknown schedule fields.');
        $route = $this->positive($input['patrol_route_id'] ?? null); $shift = $this->positive($input['shift_assignment_id'] ?? null);
        $start = $this->date($input['starts_at'] ?? null); $end = $this->date($input['ends_at'] ?? null);
        if ($end <= $start) throw HttpException::unprocessable('The end of the start window must follow its beginning.');
        $company = $this->context->requireTenant();
        try {
            $this->db->beginTransaction();
            // Serialize overlapping-window checks for the same shift on MySQL.
            $lock = $this->db->connect()->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $s = $this->db->fetch('SELECT * FROM shift_assignments WHERE company_id=:company AND shift_assignment_id=:id AND deleted_at IS NULL'.$lock,['company'=>$company,'id'=>$shift]);
            $r = $this->db->fetch('SELECT site_id FROM patrol_routes WHERE company_id=:company AND patrol_route_id=:id AND status=\'ACTIVE\' AND deleted_at IS NULL',['company'=>$company,'id'=>$route]);
            if (!$s || !$r) throw HttpException::notFound('Shift or active route not found.');
            if ((string)$s['site_id'] !== (string)$r['site_id'] || !in_array($s['assignment_status'],['ASSIGNED','CONFIRMED','IN_PROGRESS'],true)) throw HttpException::unprocessable('Choose an assigned shift at the route site.');
            if ($start < $s['shift_start_at'] || $end > $s['shift_end_at']) throw HttpException::unprocessable('The patrol start window must be within the shift.');
            $params = ['company'=>$company,'route'=>$route,'shift'=>$shift,'start'=>$start,'end'=>$end];
            if ($this->db->fetch('SELECT patrol_schedule_id FROM patrol_schedule_windows WHERE company_id=:company AND patrol_route_id=:route AND shift_assignment_id=:shift AND cancelled_at IS NULL AND starts_at<:end AND ends_at>:start LIMIT 1',$params)) throw HttpException::conflict('This patrol already has an overlapping start window.');
            $this->db->execute('INSERT INTO patrol_schedule_windows (company_id,patrol_route_id,shift_assignment_id,starts_at,ends_at) VALUES (:company,:route,:shift,:start,:end)',$params);
            $id = $this->db->connect()->lastInsertId();
            $this->audit?->record('patrol_schedule_windows','CREATE',$id,[],$input);
            $this->db->commit();
            return ['patrol_schedule_id'=>$id]+$input;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($e instanceof \PDOException) throw HttpException::serviceUnavailable('Patrol schedule could not be saved.');
            throw $e;
        }
    }

    public function cancel(string $id): array
    {
        $id = (string)$this->positive($id); $company = $this->context->requireTenant();
        try {
            $this->db->beginTransaction();
            $row = $this->db->fetch('SELECT * FROM patrol_schedule_windows WHERE company_id=:company AND patrol_schedule_id=:id',['company'=>$company,'id'=>$id]);
            if (!$row) throw HttpException::notFound('Patrol schedule not found.');
            // Do not erase missed history by cancelling windows after they have opened.
            $now = gmdate('Y-m-d H:i:s');
            if ($row['cancelled_at'] === null && $row['starts_at'] <= $now) throw HttpException::conflict('Only upcoming patrol windows can be cancelled.');
            $this->db->execute('UPDATE patrol_schedule_windows SET cancelled_at=:now WHERE company_id=:company AND patrol_schedule_id=:id AND cancelled_at IS NULL',['now'=>$now,'company'=>$company,'id'=>$id]);
            $this->audit?->record('patrol_schedule_windows','CANCEL',$id,$row,['cancelled_at'=>$row['cancelled_at'] ?? $now]);
            $this->db->commit(); return ['patrol_schedule_id'=>$id,'status'=>'CANCELLED'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($e instanceof \PDOException) throw HttpException::serviceUnavailable('Patrol schedule could not be cancelled.');
            throw $e;
        }
    }
    private function positive(mixed $value): int
    {
        if ((!is_int($value) && !is_string($value)) || !ctype_digit((string)$value) || (int)$value<1) throw HttpException::unprocessable('Choose a valid record.');
        return (int)$value;
    }
    private function date(mixed $value): string
    {
        $date = is_string($value) ? \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$value,new \DateTimeZone('UTC')) : false;
        if (!$date || $date->format('Y-m-d H:i:s') !== $value) throw HttpException::unprocessable('Use a valid UTC date and time.');
        return $value;
    }
}
