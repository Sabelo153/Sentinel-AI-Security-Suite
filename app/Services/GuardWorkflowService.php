<?php
declare(strict_types=1);
namespace Sentinel\Services;

use Sentinel\Core\{Container,Database,HttpException,RuntimeContext};

/** Guard identity is derived from the authenticated user, never submitted by the client. */
final class GuardWorkflowService
{
    public function __construct(private readonly Database $db,private readonly RuntimeContext $context,private readonly Container $services) {}

    public function workspace():array
    {
        return $this->safe(function():array{
            $actor=$this->actor();$params=['company_id'=>$this->context->requireTenant(),'employee_id'=>$actor['employee_id']];
            $shifts=$this->db->fetchAll('SELECT shift_assignment_id,site_id,shift_name,assignment_date,shift_start_at,shift_end_at,assignment_status FROM shift_assignments WHERE company_id=:company_id AND employee_id=:employee_id AND deleted_at IS NULL ORDER BY shift_start_at DESC LIMIT 100',$params);
            $assignments=$this->db->fetchAll("SELECT ea.employee_assignment_id,p.position_name FROM employee_assignments ea INNER JOIN positions p ON p.position_id=ea.position_id AND p.company_id=ea.company_id WHERE ea.company_id=:company_id AND ea.employee_id=:employee_id AND ea.assignment_status='ACTIVE' AND ea.deleted_at IS NULL AND p.position_category='GUARD' AND p.position_status='ACTIVE' AND p.deleted_at IS NULL",$params);
            $patrols=$this->db->fetchAll('SELECT ps.patrol_session_id,ps.patrol_route_id,ps.shift_assignment_id,ps.status,ps.started_at,ps.ended_at FROM patrol_sessions ps INNER JOIN employee_assignments ea ON ea.employee_assignment_id=ps.employee_assignment_id AND ea.company_id=ps.company_id WHERE ps.company_id=:company_id AND ea.employee_id=:employee_id AND ps.deleted_at IS NULL ORDER BY ps.started_at DESC LIMIT 100',$params);
            $attendance=$this->db->fetchAll('SELECT attendance_id,shift_assignment_id,shift_date,clock_in_at,clock_out_at FROM attendance WHERE company_id=:company_id AND employee_id=:employee_id AND deleted_at IS NULL ORDER BY shift_date DESC LIMIT 100',$params);
            $routes=$this->db->fetchAll("SELECT DISTINCT pr.patrol_route_id,pr.route_name,pr.site_id FROM patrol_routes pr INNER JOIN shift_assignments s ON s.site_id=pr.site_id AND s.company_id=pr.company_id WHERE pr.company_id=:company_id AND s.employee_id=:employee_id AND pr.status='ACTIVE' AND pr.deleted_at IS NULL AND s.deleted_at IS NULL AND s.assignment_status IN ('ASSIGNED','CONFIRMED','IN_PROGRESS') ORDER BY pr.patrol_route_id LIMIT 100",$params);
            return ['user_id'=>$actor['user_id'],'employee_id'=>$actor['employee_id'],'shifts'=>$shifts,'assignments'=>$assignments,'patrols'=>$patrols,'attendance'=>$attendance,'routes'=>$routes];
        });
    }

    public function patrolDetails(string $id): array
    {
        return $this->safe(function() use ($id): array {
            $actor=$this->actor();
            $this->ownPatrol($id, (string)$actor['employee_id']);
            $session=$this->services->make('patrol_session_management_service')->getSession($id);
            return ['session'=>$session,
                'checkpoints'=>array_map(static fn(array $row):array=>array_intersect_key($row,array_flip(['patrol_checkpoint_id','checkpoint_name','checkpoint_order','status','expected_interval_minutes'])), $this->services->make('patrol_checkpoint_management_service')->listCheckpointsForRoute((string)$session['patrol_route_id'])),
                'events'=>$this->services->make('patrol_checkpoint_event_management_service')->listEventsForSession($id)];
        });
    }

    public function patrolSchedules(array $input): array
    {
        return $this->safe(function() use ($input): array {
            $actor=$this->actor();
            return $this->services->make('patrol_schedule_service')->list($input,null,(int)$actor['employee_id']);
        });
    }

    public function command(array $input):array
    {
        $this->allowed($input,['operation_uuid','action','data']);
        if(!is_string($input['operation_uuid']??null)||preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/Di',$input['operation_uuid'])!==1)throw HttpException::unprocessable('A stable operation UUID is required.');
        if(!is_array($input['data']??null)||!in_array($input['action']??null,['clock_in','clock_out','start_patrol','finish_patrol','checkpoint','location','incident','occurrence'],true))throw HttpException::unprocessable('Invalid guard command.');
        $data=$input['data'];ksort($data);$hash=hash('sha256',json_encode([$input['action'],$data],JSON_THROW_ON_ERROR));
        return $this->safe(function()use($input,$data,$hash):array{
            $owns=!$this->db->inTransaction();if($owns)$this->db->beginTransaction();
            try{
                // Lock this actor before retry lookup and command execution to serialize retries.
                $actor=$this->actor(true);$params=['company_id'=>$this->context->requireTenant(),'user_id'=>$actor['user_id'],'operation_uuid'=>strtolower($input['operation_uuid'])];
                $receipt=$this->db->fetch('SELECT request_hash,response_json FROM mobile_operations WHERE company_id=:company_id AND user_id=:user_id AND operation_uuid=:operation_uuid',$params);
                if($receipt!==null){
                    if(!hash_equals($receipt['request_hash'],$hash))throw HttpException::conflict('Operation UUID was already used for a different command.');
                    if($receipt['response_json']===null)throw HttpException::conflict('Operation is still pending.');
                    $result=json_decode($receipt['response_json'],true,512,JSON_THROW_ON_ERROR);
                }else{
                    $this->db->execute('INSERT INTO mobile_operations (company_id,user_id,operation_uuid,request_hash) VALUES (:company_id,:user_id,:operation_uuid,:request_hash)',[...$params,'request_hash'=>$hash]);
                    $result=$this->execute($input['action'],$data,$actor);
                    $this->db->execute('UPDATE mobile_operations SET response_json=:response WHERE company_id=:company_id AND user_id=:user_id AND operation_uuid=:operation_uuid',[...$params,'response'=>json_encode($result,JSON_THROW_ON_ERROR)]);
                }
                if($owns)$this->db->commit();return$result;
            }catch(\Throwable $error){if($owns&&$this->db->inTransaction())$this->db->rollBack();throw$error;}
        });
    }

    private function execute(string $action,array $data,array $actor):array
    {
        $employee=(string)$actor['employee_id'];$company=$this->context->requireTenant();
        if(in_array($action,['incident','occurrence'],true)){
            $this->allowed($data,['shift_assignment_id','occurred_at','text','severity','photo_base64']);
            $shift=$this->ownShift($this->id($data['shift_assignment_id']??null),$employee);
            if($shift['assignment_status']==='CANCELLED')throw HttpException::conflict('Cannot report against a cancelled shift.');
            $at=$this->date($data['occurred_at']??null);
            if(!is_string($data['text']??null)||trim($data['text'])===''||mb_strlen($data['text'])>10000)throw HttpException::unprocessable('Report text is required (maximum 10000 characters).');
            $photo=$data['photo_base64']??null;
            if($photo!==null){
                if(!is_string($photo)||strlen($photo)>280000)throw HttpException::unprocessable('Photo is too large.');
                $bytes=base64_decode($photo,true);$size=$bytes===false?false:@getimagesizefromstring($bytes);
                if($size===false||$size[2]!==IMAGETYPE_JPEG||strlen($bytes)>200000||$size[0]>4096||$size[1]>4096)throw HttpException::unprocessable('Photo must be a JPEG below 200 KB and 4096 pixels.');
            }
            if($action==='incident'){
                $row=$this->services->make('incident_management_service')->createIncident(['site_id'=>$shift['site_id'],'employee_id'=>$employee,'reporter_user_id'=>$actor['user_id'],'shift_assignment_id'=>$shift['shift_assignment_id'],'incident_type'=>'GUARD_REPORT','incident_category'=>'FIELD','severity'=>$data['severity']??'MEDIUM','occurred_at'=>$at,'reported_at'=>gmdate('Y-m-d H:i:s'),'description'=>$data['text']]);
                $id=(string)$row['incident_id'];
            }else{
                $row=$this->services->make('occurrence_book_service')->createEntry(['site_id'=>$shift['site_id'],'employee_id'=>$employee,'shift_assignment_id'=>$shift['shift_assignment_id'],'entry_type'=>'GUARD_HANDOVER','occurred_at'=>$at,'entry_text'=>$data['text'],'handover_required'=>true]);
                $id=(string)$row['occurrence_entry_id'];
            }
            if($photo!==null)$this->db->execute('INSERT INTO report_photos (company_id,report_type,report_id,image_base64,created_by_user_id) VALUES (:company,:type,:id,:image,:actor)',['company'=>$company,'type'=>$action,'id'=>$id,'image'=>$photo,'actor'=>$actor['user_id']]);
            $this->services->make('audit_service')->record($action,'mobile_report_created',$id);
            return ['report_type'=>$action,'report_id'=>$id,'photo_attached'=>$photo!==null];
        }
        if($action==='clock_in'){
            $this->allowed($data,['shift_assignment_id','occurred_at']);$at=$this->date($data['occurred_at']??null);
            $shift=$this->ownShift($this->id($data['shift_assignment_id']??null),$employee);
            if(!in_array($shift['assignment_status'],['ASSIGNED','CONFIRMED','IN_PROGRESS'],true))throw HttpException::conflict('Shift is not open for attendance.');
            return $this->services->make('attendance_management_service')->createAttendance(['employee_id'=>$employee,'shift_assignment_id'=>$shift['shift_assignment_id'],'site_id'=>$shift['site_id'],'contract_id'=>$shift['contract_id'],'post_id'=>$shift['post_id'],'shift_date'=>$shift['assignment_date'],'clock_in_at'=>$at,'source'=>'MOBILE_APP']);
        }
        if($action==='clock_out'){
            $this->allowed($data,['attendance_id','occurred_at']);$id=$this->id($data['attendance_id']??null);$at=$this->date($data['occurred_at']??null);
            $row=$this->db->fetch('SELECT attendance_id,clock_out_at FROM attendance WHERE attendance_id=:id AND employee_id=:employee_id AND company_id=:company_id AND deleted_at IS NULL',['id'=>$id,'employee_id'=>$employee,'company_id'=>$company]);
            if($row===null)throw HttpException::notFound('Attendance not found.');if($row['clock_out_at']!==null)throw HttpException::conflict('Attendance is already closed.');
            return $this->services->make('attendance_management_service')->updateAttendance($id,['clock_out_at'=>$at]);
        }
        if($action==='start_patrol'){
            $this->allowed($data,['employee_assignment_id','patrol_route_id','shift_assignment_id','started_at']);
            foreach(['employee_assignment_id','patrol_route_id','shift_assignment_id']as$key)$data[$key]=$this->id($data[$key]??null);$data['started_at']=$this->date($data['started_at']??null);
            $shift=$this->ownShift($data['shift_assignment_id'],$employee);
            if(!in_array($shift['assignment_status'],['ASSIGNED','CONFIRMED','IN_PROGRESS'],true))throw HttpException::conflict('Shift is not active.');
            $assignment=$this->db->fetch('SELECT employee_assignment_id FROM employee_assignments WHERE employee_assignment_id=:id AND employee_id=:employee_id AND company_id=:company_id AND deleted_at IS NULL',['id'=>$data['employee_assignment_id'],'employee_id'=>$employee,'company_id'=>$company]);
            if($assignment===null)throw HttpException::notFound('Assignment not found.');
            return $this->services->make('patrol_session_management_service')->startSession($data);
        }
        if($action==='finish_patrol'){
            $this->allowed($data,['patrol_session_id','occurred_at']);$id=$this->id($data['patrol_session_id']??null);$at=$this->date($data['occurred_at']??null);$this->ownPatrol($id,$employee);
            return $this->services->make('patrol_session_management_service')->updateSession($id,['status'=>'COMPLETED','completed_at'=>$at,'ended_at'=>$at]);
        }
        if($action==='checkpoint'){
            $this->allowed($data,['patrol_session_id','method','code','recorded_at']);$id=$this->id($data['patrol_session_id']??null);$session=$this->ownPatrol($id,$employee);$at=$this->date($data['recorded_at']??null);
            $column=match($data['method']??null){'QR'=>'qr_code','NFC'=>'nfc_tag',default=>throw HttpException::unprocessable('Checkpoint method must be QR or NFC.')};
            if(!is_string($data['code']??null)||$data['code']===''||strlen($data['code'])>255)throw HttpException::unprocessable('Invalid checkpoint code.');
            $rows=$this->db->fetchAll("SELECT patrol_checkpoint_id FROM patrol_checkpoints WHERE company_id=:company_id AND patrol_route_id=:route_id AND $column=:code AND status='ACTIVE' AND deleted_at IS NULL LIMIT 2",['company_id'=>$company,'route_id'=>$session['patrol_route_id'],'code'=>$data['code']]);
            if($rows===[])throw HttpException::notFound('Checkpoint not found.');if(count($rows)!==1)throw HttpException::conflict('Checkpoint code is ambiguous.');
            return $this->services->make('patrol_checkpoint_event_management_service')->recordEvent($id,['patrol_checkpoint_id'=>$rows[0]['patrol_checkpoint_id'],'recorded_at'=>$at,'event_type'=>'VISITED','event_notes'=>'Captured by '.$data['method']]);
        }
        $this->allowed($data,['latitude','longitude','accuracy','captured_at','battery_level']);$data['captured_at']=$this->date($data['captured_at']??null);
        return $this->services->make('location_management_service')->recordLocation([...$data,'employee_id'=>$employee,'user_id'=>$actor['user_id'],'provider'=>'GPS']);
    }
    private function actor(bool $lock=false):array
    {
        if(!$this->context->isAuthenticated())throw HttpException::unauthorized();
        $suffix=$lock&&$this->db->connection()->getAttribute(\PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';
        return $this->db->fetch("SELECT u.user_id,u.employee_id FROM users u INNER JOIN employees e ON e.employee_id=u.employee_id AND e.company_id=u.company_id WHERE u.user_id=:user_id AND u.company_id=:company_id AND u.status='ACTIVE' AND e.employment_status='ACTIVE'".$suffix,['user_id'=>$this->context->user(),'company_id'=>$this->context->requireTenant()])??throw HttpException::forbidden('Active employee account required.');
    }
    private function ownShift(string $id,string $employee):array{return$this->db->fetch('SELECT * FROM shift_assignments WHERE shift_assignment_id=:id AND employee_id=:employee_id AND company_id=:company_id AND deleted_at IS NULL',['id'=>$id,'employee_id'=>$employee,'company_id'=>$this->context->requireTenant()])??throw HttpException::notFound('Shift not found.');}
    private function ownPatrol(string $id,string $employee):array{return$this->db->fetch('SELECT ps.* FROM patrol_sessions ps INNER JOIN employee_assignments ea ON ea.employee_assignment_id=ps.employee_assignment_id AND ea.company_id=ps.company_id WHERE ps.patrol_session_id=:id AND ea.employee_id=:employee_id AND ps.company_id=:company_id AND ps.deleted_at IS NULL',['id'=>$id,'employee_id'=>$employee,'company_id'=>$this->context->requireTenant()])??throw HttpException::notFound('Patrol not found.');}
    private function allowed(array $data,array $fields):void{$this->context->requireTenant();if(array_diff(array_keys($data),$fields)!==[])throw HttpException::unprocessable('Unknown guard command fields.');}
    private function id(mixed $value):string{if((!is_int($value)&&!is_string($value))||!preg_match('/^[1-9][0-9]{0,18}$/D',(string)$value))throw HttpException::unprocessable('Invalid identifier.');return(string)$value;}
    private function date(mixed $value):string{$date=is_string($value)?\DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$value,new \DateTimeZone('UTC')):false;if($date===false||$date->format('Y-m-d H:i:s')!==$value||$date->getTimestamp()>time()+300)throw HttpException::unprocessable('Invalid UTC event timestamp.');return$value;}
    private function safe(callable $operation):mixed{try{return$operation();}catch(\PDOException){throw HttpException::serviceUnavailable('Guard workflow storage is unavailable.');}}
}
