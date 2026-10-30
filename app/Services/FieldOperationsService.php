<?php
declare(strict_types=1);
namespace Sentinel\Services;

use Sentinel\Core\{Database,HttpException,RuntimeContext};

final class FieldOperationsService
{
    public function __construct(private readonly Database $db,private readonly RuntimeContext $context,private readonly AuditService $audit,private readonly SupervisorAttentionService $attention,private readonly AlertNotificationService $notifications){}
    private function company():int{return $this->context->requireTenant();}
    private function actor():string{if(!$this->context->isAuthenticated())throw HttpException::unauthorized();return (string)$this->context->user();}
    private function fields(array $input,array $allowed):void{if(array_diff(array_keys($input),$allowed))throw HttpException::unprocessable('Unknown fields.');}
    private function text(mixed $value,int $max=500):string{if(!is_string($value)||trim($value)===''||mb_strlen($value)>$max)throw HttpException::unprocessable('Required text is missing or too long.');return trim($value);}
    private function date(mixed $value):string{if(!is_string($value)||!($date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value))||$date->format('Y-m-d')!==$value)throw HttpException::unprocessable('Use a valid date in YYYY-MM-DD format.');return $value;}
    private function related(string $table,string $key,mixed $id):array{
        if((!is_string($id)&&!is_int($id))||!preg_match('/^[1-9][0-9]{0,18}$/D',(string)$id))throw HttpException::unprocessable('Invalid identifier.');
        // Callers supply table/key exclusively from source constants, never request input.
        return $this->db->fetch("SELECT * FROM $table WHERE $key=:id AND company_id=:company",['id'=>$id,'company'=>$this->company()])??throw HttpException::notFound('Related record not found.');
    }
    public function list(string $resource):array{
        $queries=[
            'compliance'=>'SELECT compliance_id,employee_id,requirement_name,reference_number,due_on,verified_at,verified_by_user_id FROM compliance_items WHERE company_id=:company ORDER BY due_on,compliance_id LIMIT 500',
            'equipment'=>'SELECT equipment_id,asset_code,equipment_name FROM equipment_items WHERE company_id=:company ORDER BY equipment_id DESC LIMIT 500',
            'handovers'=>'SELECT handover_id,equipment_id,employee_id,site_id,issued_at,returned_at,issue_condition,return_condition FROM equipment_handovers WHERE company_id=:company ORDER BY handover_id DESC LIMIT 500',
            'escalation-rules'=>'SELECT rule_id,recipient_user_id,backup_user_id,escalate_minutes,active FROM escalation_rules WHERE company_id=:company ORDER BY rule_id DESC LIMIT 100',
            'escalations'=>'SELECT escalation_id,event_type,site_id,message,created_at,acknowledged_at,acknowledged_by_user_id,escalated_at FROM escalation_events WHERE company_id=:company ORDER BY escalation_id DESC LIMIT 500',
            'portal-access'=>'SELECT user_id,client_id FROM client_portal_access WHERE company_id=:company ORDER BY user_id,client_id LIMIT 500',
        ];
        if(!isset($queries[$resource]))throw HttpException::notFound();return $this->db->fetchAll($queries[$resource],['company'=>$this->company()]);
    }
    public function createCompliance(array $input):array{
        $this->fields($input,['employee_id','requirement_name','reference_number','due_on']);$employee=$this->related('employees','employee_id',$input['employee_id']??null);
        $this->db->execute('INSERT INTO compliance_items(company_id,employee_id,requirement_name,reference_number,due_on) VALUES(:company,:employee,:name,:reference,:due)',['company'=>$this->company(),'employee'=>$employee['employee_id'],'name'=>$this->text($input['requirement_name']??null,150),'reference'=>isset($input['reference_number'])?$this->text($input['reference_number'],150):null,'due'=>$this->date($input['due_on']??null)]);
        return $this->created('compliance');
    }
    public function verifyCompliance(string $id):array{
        $this->related('compliance_items','compliance_id',$id);$this->db->execute('UPDATE compliance_items SET verified_at=:now,verified_by_user_id=:actor WHERE company_id=:company AND compliance_id=:id',['now'=>gmdate('Y-m-d H:i:s'),'actor'=>$this->actor(),'company'=>$this->company(),'id'=>$id]);$this->audit->record('compliance','manually_verified',$id);return ['verified'=>true,'note'=>'Records a manual evidence review; it is not automatic PSiRA verification.'];
    }
    public function renewCompliance(string $id,array $input):array{
        $this->fields($input,['due_on','reference_number']);$this->related('compliance_items','compliance_id',$id);
        $this->db->execute('UPDATE compliance_items SET due_on=:due,reference_number=:reference,verified_at=NULL,verified_by_user_id=NULL WHERE company_id=:company AND compliance_id=:id',['due'=>$this->date($input['due_on']??null),'reference'=>$this->text($input['reference_number']??null,150),'company'=>$this->company(),'id'=>$id]);$this->audit->record('compliance','renewed_pending_review',$id);return ['renewed'=>true,'verification_required'=>true];
    }
    public function createEquipment(array $input):array{
        $this->fields($input,['asset_code','equipment_name']);$this->db->execute('INSERT INTO equipment_items(company_id,asset_code,equipment_name) VALUES(:company,:code,:name)',['company'=>$this->company(),'code'=>$this->text($input['asset_code']??null,100),'name'=>$this->text($input['equipment_name']??null,150)]);return $this->created('equipment');
    }
    public function issue(array $input):array{
        $this->fields($input,['equipment_id','employee_id','site_id','issue_condition']);$item=$this->related('equipment_items','equipment_id',$input['equipment_id']??null);
        $employee=$this->related('employees','employee_id',$input['employee_id']??null);$site=$this->related('sites','site_id',$input['site_id']??null);
        if(($employee['employment_status']??null)!=='ACTIVE'||in_array($site['status']??'ACTIVE',['CLOSED','INACTIVE','SUSPENDED','CONTRACT_EXPIRED'],true))throw HttpException::unprocessable('Equipment requires an active employee and an available site.');
        // Serialize issue/return on the asset to prevent two open handovers.
        $this->lockAsset((string)$item['equipment_id']);
        $currentRead=$this->db->connection()->getAttribute(\PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';
        if($this->db->fetch('SELECT handover_id FROM equipment_handovers WHERE company_id=:company AND equipment_id=:id AND returned_at IS NULL'.$currentRead,['company'=>$this->company(),'id'=>$item['equipment_id']]))throw HttpException::conflict('Equipment is already issued.');
        $this->db->execute('INSERT INTO equipment_handovers(company_id,equipment_id,employee_id,site_id,issued_at,issue_condition,issued_by_user_id) VALUES(:company,:equipment,:employee,:site,:now,:condition,:actor)',['company'=>$this->company(),'equipment'=>$item['equipment_id'],'employee'=>$employee['employee_id'],'site'=>$site['site_id'],'now'=>gmdate('Y-m-d H:i:s'),'condition'=>$this->text($input['issue_condition']??null),'actor'=>$this->actor()]);return $this->created('equipment_handover');
    }
    private function lockAsset(string $id):void{if($this->db->connection()->getAttribute(\PDO::ATTR_DRIVER_NAME)==='mysql')$this->db->fetch('SELECT equipment_id FROM equipment_items WHERE company_id=:company AND equipment_id=:id FOR UPDATE',['company'=>$this->company(),'id'=>$id]);}
    public function returnEquipment(string $id,array $input):array{
        $this->fields($input,['return_condition']);$row=$this->related('equipment_handovers','handover_id',$id);$this->lockAsset((string)$row['equipment_id']);
        $count=$this->db->execute('UPDATE equipment_handovers SET returned_at=:now,return_condition=:condition,returned_by_user_id=:actor WHERE company_id=:company AND handover_id=:id AND returned_at IS NULL',['now'=>gmdate('Y-m-d H:i:s'),'condition'=>$this->text($input['return_condition']??null),'actor'=>$this->actor(),'company'=>$this->company(),'id'=>$id]);
        if($count!==1)throw HttpException::conflict('Handover already returned.');$this->audit->record('equipment_handover','returned',$id);return ['returned'=>true];
    }
    private function attendance(string $id):array{return $this->db->fetch('SELECT attendance_id,employee_id,site_id,shift_date,clock_in_at,clock_out_at FROM attendance WHERE company_id=:company AND attendance_id=:id AND deleted_at IS NULL',['company'=>$this->company(),'id'=>$id])??throw HttpException::notFound('Attendance not found.');}
    public static function attendanceHash(array $row):string{return hash('sha256',json_encode(array_intersect_key($row,array_flip(['attendance_id','employee_id','site_id','shift_date','clock_in_at','clock_out_at'])),JSON_THROW_ON_ERROR));}
    public function approveAttendance(string $id):array{
        $row=$this->attendance($id);$this->hours($row);
        $this->db->execute('DELETE FROM attendance_approvals WHERE company_id=:company AND attendance_id=:id',['company'=>$this->company(),'id'=>$id]);
        $this->db->execute('INSERT INTO attendance_approvals(company_id,attendance_id,approved_by_user_id,approved_at,record_hash) VALUES(:company,:id,:actor,:now,:hash)',['company'=>$this->company(),'id'=>$id,'actor'=>$this->actor(),'now'=>gmdate('Y-m-d H:i:s'),'hash'=>self::attendanceHash($row)]);$this->audit->record('attendance','payroll_approved',$id);return ['approved'=>true];
    }
    private function hours(array $row):string{
        if(empty($row['clock_in_at'])||empty($row['clock_out_at']))throw HttpException::unprocessable('Only completed attendance can be approved.');
        $seconds=strtotime($row['clock_out_at'].' UTC')-strtotime($row['clock_in_at'].' UTC');if($seconds<=0||$seconds>48*3600)throw HttpException::unprocessable('Review invalid or unusually long attendance before approval.');return number_format($seconds/3600,2,'.','');
    }
    public function payroll(array $input):array{
        $this->fields($input,['date_from','date_to']);$from=$this->date($input['date_from']??null);$to=$this->date($input['date_to']??null);if($from>$to||strtotime($to)-strtotime($from)>93*86400)throw HttpException::unprocessable('Choose a range of at most 94 days.');
        $rows=$this->db->fetchAll('SELECT a.attendance_id,a.employee_id,a.site_id,a.shift_date,a.clock_in_at,a.clock_out_at,p.record_hash,p.approved_by_user_id,p.approved_at FROM attendance a JOIN attendance_approvals p ON p.attendance_id=a.attendance_id AND p.company_id=a.company_id WHERE a.company_id=:company AND a.deleted_at IS NULL AND a.shift_date>=:from AND a.shift_date<=:to ORDER BY a.shift_date,a.attendance_id LIMIT 10001',['company'=>$this->company(),'from'=>$from,'to'=>$to]);
        if(count($rows)>10000)throw HttpException::unprocessable('Too many rows. Choose a shorter payroll range.');$result=[];$stale=0;
        foreach($rows as$row){if(!hash_equals($row['record_hash'],self::attendanceHash($row))){$stale++;continue;}$row['worked_hours']=$this->hours($row);unset($row['record_hash']);$result[]=$row;}
        return ['rows'=>$result,'stale_approvals_excluded'=>$stale,'note'=>'Worked hours only. Breaks, overtime, wages and statutory calculations belong to your payroll system.'];
    }
    public function grantPortal(array $input):array{
        $this->fields($input,['user_id','client_id']);$user=$this->related('users','user_id',$input['user_id']??null);$client=$this->related('clients','client_id',$input['client_id']??null);
        if($this->db->fetch('SELECT user_id FROM client_portal_access WHERE company_id=:company AND user_id=:user AND client_id=:client',['company'=>$this->company(),'user'=>$user['user_id'],'client'=>$client['client_id']]))throw HttpException::conflict('Portal access already granted.');
        $this->db->execute('INSERT INTO client_portal_access(company_id,user_id,client_id) VALUES(:company,:user,:client)',['company'=>$this->company(),'user'=>$user['user_id'],'client'=>$client['client_id']]);$this->audit->record('client_portal','access_granted',(string)$user['user_id'],[],['client_id'=>$client['client_id']]);return ['granted'=>true];
    }
    public function revokePortal(array $input):array{
        $this->fields($input,['user_id','client_id']);$this->related('users','user_id',$input['user_id']??null);$this->related('clients','client_id',$input['client_id']??null);
        $this->db->execute('DELETE FROM client_portal_access WHERE company_id=:company AND user_id=:user AND client_id=:client',['company'=>$this->company(),'user'=>$input['user_id'],'client'=>$input['client_id']]);$this->audit->record('client_portal','access_revoked',(string)$input['user_id'],[],['client_id'=>$input['client_id']]);return ['revoked'=>true];
    }
    public function publish(array $input):array{
        $this->fields($input,['site_id','title','report_text']);$site=$this->related('sites','site_id',$input['site_id']??null);
        $this->related('clients','client_id',$site['client_id']);
        $this->db->execute('INSERT INTO published_site_reports(company_id,client_id,site_id,title,report_text,published_by_user_id,published_at) VALUES(:company,:client,:site,:title,:text,:actor,:now)',['company'=>$this->company(),'client'=>$site['client_id'],'site'=>$site['site_id'],'title'=>$this->text($input['title']??null,150),'text'=>$this->text($input['report_text']??null,20000),'actor'=>$this->actor(),'now'=>gmdate('Y-m-d H:i:s')]);return $this->created('published_site_report');
    }
    public function reportDraft(array $input):array{
        $this->fields($input,['site_id','date_from','date_to']);$site=$this->related('sites','site_id',$input['site_id']??null);$from=$this->date($input['date_from']??null);$to=$this->date($input['date_to']??null);if($from>$to||strtotime($to)-strtotime($from)>31*86400)throw HttpException::unprocessable('Choose a report range of at most 32 days.');
        $end=gmdate('Y-m-d',strtotime($to.' UTC')+86400).' 00:00:00';
        $patrols=$this->db->fetchAll('SELECT p.status,COUNT(*) AS total FROM patrol_sessions p JOIN patrol_routes r ON r.patrol_route_id=p.patrol_route_id AND r.company_id=p.company_id WHERE p.company_id=:company AND r.site_id=:site AND p.deleted_at IS NULL AND p.started_at>=:from AND p.started_at<:to GROUP BY p.status',['company'=>$this->company(),'site'=>$site['site_id'],'from'=>$from.' 00:00:00','to'=>$end]);$counts=[];foreach($patrols as$row)$counts[$row['status']]=(int)$row['total'];
        $issues=(int)$this->db->fetch("SELECT COUNT(*) AS total FROM incidents WHERE company_id=:company AND site_id=:site AND deleted_at IS NULL AND status IN ('OPEN','INVESTIGATING')",['company'=>$this->company(),'site'=>$site['site_id']])['total'];
        return ['site_id'=>$site['site_id'],'title'=>'Site report: '.$from.' to '.$to,'report_text'=>"Site: ".$site['site_name']."\nReporting dates (UTC): $from to $to\nPatrols started in range and currently completed: ".($counts['COMPLETED']??0)."\nPatrols started in range and currently in progress: ".($counts['IN_PROGRESS']??0)."\nOpen/investigating incidents at draft time: $issues\nDraft generated: ".gmdate('Y-m-d H:i:s')." UTC\n\nSupervisor review and client-appropriate follow-up: [complete before publication]",'published'=>false,'note'=>'Counts reflect current stored statuses, not historical status as of the date range. Review before publishing.'];
    }
    public function portal():array{
        return $this->db->fetchAll('SELECT r.report_id,r.site_id,r.title,r.report_text,r.published_at FROM published_site_reports r JOIN client_portal_access a ON a.client_id=r.client_id AND a.company_id=r.company_id WHERE r.company_id=:company AND a.user_id=:actor ORDER BY r.report_id DESC LIMIT 200',['company'=>$this->company(),'actor'=>$this->actor()]);
    }
    public function createRule(array $input):array{
        $this->fields($input,['recipient_user_id','backup_user_id','escalate_minutes']);$first=$this->related('users','user_id',$input['recipient_user_id']??null);$backup=$this->related('users','user_id',$input['backup_user_id']??null);$minutes=filter_var($input['escalate_minutes']??null,FILTER_VALIDATE_INT);
        if($first['status']!=='ACTIVE'||$backup['status']!=='ACTIVE'||$first['user_id']===$backup['user_id']||$minutes===false||$minutes<1||$minutes>1440)throw HttpException::unprocessable('Choose two active recipients and an escalation delay between 1 and 1440 minutes.');
        $this->db->execute('INSERT INTO escalation_rules(company_id,recipient_user_id,backup_user_id,escalate_minutes) VALUES(:company,:first,:backup,:minutes)',['company'=>$this->company(),'first'=>$first['user_id'],'backup'=>$backup['user_id'],'minutes'=>$minutes]);return $this->created('escalation_rule');
    }
    public function disableRule(string $id):array{$this->related('escalation_rules','rule_id',$id);$this->db->execute('UPDATE escalation_rules SET active=0 WHERE company_id=:company AND rule_id=:id',['company'=>$this->company(),'id'=>$id]);$this->audit->record('escalation_rule','disabled',$id);return ['disabled'=>true];}
    public function acknowledge(string $id):array{$this->related('escalation_events','escalation_id',$id);$this->db->execute('UPDATE escalation_events SET acknowledged_at=:now,acknowledged_by_user_id=:actor WHERE company_id=:company AND escalation_id=:id AND acknowledged_at IS NULL',['company'=>$this->company(),'id'=>$id,'actor'=>$this->actor(),'now'=>gmdate('Y-m-d H:i:s')]);$this->audit->record('escalation','acknowledged',$id);return ['acknowledged'=>true];}
    /** Run within a tenant transaction under a scheduled worker. In-app delivery only. */
    public function escalationTick():array{
        if(!$this->db->inTransaction())throw new \LogicException('Escalation worker requires a transaction.');
        if($this->db->connection()->getAttribute(\PDO::ATTR_DRIVER_NAME)==='mysql')$this->db->fetch('SELECT company_id FROM companies WHERE company_id=:company FOR UPDATE',['company'=>$this->company()]);
        $rules=$this->db->fetchAll('SELECT * FROM escalation_rules WHERE company_id=:company AND active=1 ORDER BY rule_id LIMIT 20',['company'=>$this->company()]);
        $findings=$this->attention->attention()['findings'];
        foreach($this->db->fetchAll('SELECT compliance_id,due_on FROM compliance_items WHERE company_id=:company AND due_on<=:due ORDER BY due_on LIMIT 100',['company'=>$this->company(),'due'=>gmdate('Y-m-d',time()+30*86400)])as$item)$findings[]=['type'=>'COMPLIANCE_DUE','compliance_id'=>$item['compliance_id'],'due_at'=>$item['due_on'],'message'=>'A compliance requirement is due within 30 days or overdue. Review its evidence and renewal date.'];
        $created=0;$escalated=0;
        foreach($rules as$rule){foreach(array_slice($findings,0,500)as$finding){$key=hash('sha256',json_encode($finding));if($this->db->fetch('SELECT escalation_id FROM escalation_events WHERE company_id=:company AND rule_id=:rule AND event_key=:key',['company'=>$this->company(),'rule'=>$rule['rule_id'],'key'=>$key]))continue;
            $this->db->execute('INSERT INTO escalation_events(company_id,rule_id,event_key,event_type,site_id,message,created_at) VALUES(:company,:rule,:key,:type,:site,:message,:now)',['company'=>$this->company(),'rule'=>$rule['rule_id'],'key'=>$key,'type'=>$finding['type'],'site'=>$finding['site_id']??null,'message'=>$finding['message'],'now'=>gmdate('Y-m-d H:i:s')]);$this->notify((string)$rule['recipient_user_id'],$finding['type'],$finding['message']);$created++;
        }
        $currentRead=$this->db->connection()->getAttribute(\PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';
        $pending=$this->db->fetchAll('SELECT escalation_id,event_type,message FROM escalation_events WHERE company_id=:company AND rule_id=:rule AND acknowledged_at IS NULL AND escalated_at IS NULL AND created_at<=:deadline ORDER BY escalation_id LIMIT 500'.$currentRead,['company'=>$this->company(),'rule'=>$rule['rule_id'],'deadline'=>gmdate('Y-m-d H:i:s',time()-(int)$rule['escalate_minutes']*60)]);
        foreach($pending as$event){$this->notify((string)$rule['backup_user_id'],$event['event_type'],'Unacknowledged: '.$event['message']);$this->db->execute('UPDATE escalation_events SET escalated_at=:now WHERE company_id=:company AND escalation_id=:id',['now'=>gmdate('Y-m-d H:i:s'),'company'=>$this->company(),'id'=>$event['escalation_id']]);$escalated++;}}
        $this->audit->record('escalation','worker_completed',null,[],['created'=>$created,'escalated'=>$escalated]);return ['created'=>$created,'escalated'=>$escalated,'channel'=>'IN_APP'];
    }
    private function notify(string $user,string $type,string $message):void{$this->notifications->createNotification(['recipient_user_id'=>$user,'event_type'=>$type,'severity'=>'HIGH','title'=>'Sentinel operational attention','message'=>$message,'channel'=>'IN_APP']);}
    private function created(string $entity):array{$id=(string)$this->db->connection()->lastInsertId();$this->audit->record($entity,'created',$id);return ['id'=>$id,'created'=>true];}
}
