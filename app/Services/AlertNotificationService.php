<?php
declare(strict_types=1);
namespace Sentinel\Services;
use Sentinel\Core\{HttpException, RuntimeContext};
use Sentinel\Repositories\OperationalRepository;

final class AlertNotificationService
{
    private const SEVERITIES = ['LOW','MEDIUM','HIGH','CRITICAL'];
    public function __construct(private readonly OperationalRepository $records, private readonly RuntimeContext $context, private readonly AuditService $audit) {}
    public function alerts(array $filters): array
    {
        $this->allowed($filters,['status','severity']);
        if(isset($filters['status'])) $this->enum($filters['status'],['OPEN','ACKNOWLEDGED','RESOLVED','DISMISSED']);
        if(isset($filters['severity'])) $this->enum($filters['severity'],self::SEVERITIES);
        return $this->safe(fn()=>$this->records->list('alerts',$filters,['status','severity']));
    }
    public function getAlert(string $id): array { return $this->safe(fn()=>$this->record('alerts',$this->id($id),false)); }
    public function createAlert(array $input): array
    {
        $this->allowed($input,['alert_type','severity','message','occurred_at','related_entity_type','related_entity_id','payload_json']); $actor=$this->actor();
        $data=['alert_type'=>$this->text($input['alert_type']??null,120),'severity'=>$this->enum($input['severity']??'LOW',self::SEVERITIES),'message'=>$this->text($input['message']??null,2000),'occurred_at'=>$this->date($input['occurred_at']??gmdate('Y-m-d H:i:s')),'status'=>'OPEN'];
        if(isset($input['related_entity_type'])||isset($input['related_entity_id'])) {
            $data['related_entity_type']=$this->enum($input['related_entity_type']??null,['visitors','visits','vehicles','access','inspections']);
            $data['related_entity_id']=$this->id($input['related_entity_id']??null);
        }
        if(isset($input['payload_json'])) {
            if(!is_array($input['payload_json'])) throw HttpException::unprocessable('Alert payload must be an object or array.');
            try{$data['payload_json']=json_encode($input['payload_json'],JSON_THROW_ON_ERROR,16);}catch(\JsonException){throw HttpException::unprocessable('Invalid alert payload.');}
            if(strlen($data['payload_json'])>16000) throw HttpException::unprocessable('Alert payload is too large.');
        }
        return $this->safe(fn()=>$this->records->transaction(function()use($data,$actor):array{
            $this->record('users',$actor);
            if(isset($data['related_entity_id']))$this->record($data['related_entity_type'],$data['related_entity_id']);
            $row=$this->records->insert('alerts',$data,['alert_type','severity','message','occurred_at','status','related_entity_type','related_entity_id','payload_json']);
            $this->audit->record('alert','created',(string)$row['alert_id']); return $row;
        }));
    }
    public function transitionAlert(string $id,array $input):array
    {
        $id=$this->id($id);$this->allowed($input,['status']);$actor=$this->actor();$status=$this->enum($input['status']??null,['ACKNOWLEDGED','RESOLVED','DISMISSED']);
        return $this->safe(fn()=>$this->records->transaction(function()use($id,$actor,$status):array{
            $before=$this->record('alerts',$id);$this->record('users',$actor);
            $allowed=['OPEN'=>['ACKNOWLEDGED','DISMISSED'],'ACKNOWLEDGED'=>['RESOLVED','DISMISSED'],'RESOLVED'=>[],'DISMISSED'=>[]];
            if(!in_array($status,$allowed[$before['status']]??[],true))throw HttpException::conflict('Alert transition is not allowed.');
            $data=['status'=>$status];$now=gmdate('Y-m-d H:i:s');
            if($status==='ACKNOWLEDGED')$data+=['acknowledged_at'=>$now,'acknowledged_by_user_id'=>$actor];
            else $data+=['resolved_at'=>$now,'resolved_by_user_id'=>$actor];
            $row=$this->records->update('alerts',$id,$data,['status','acknowledged_at','acknowledged_by_user_id','resolved_at','resolved_by_user_id']);
            $this->audit->record('alert','status_changed',$id,['status'=>$before['status']],['status'=>$status]);return $row;
        }));
    }
    public function inbox(array $filters=[]):array
    {
        $this->allowed($filters,[]);$actor=$this->actor();
        return $this->safe(fn()=>$this->records->list('notifications',['recipient_user_id'=>$actor],['recipient_user_id']));
    }
    public function getNotification(string $id):array
    {
        $actor=$this->actor();$id=$this->id($id);
        return $this->safe(function()use($id,$actor):array{$row=$this->record('notifications',$id,false);if((string)$row['recipient_user_id']!==$actor)throw HttpException::notFound('Notification not found.');return $row;});
    }
    public function createNotification(array $input):array
    {
        $this->allowed($input,['recipient_user_id','alert_id','event_type','severity','title','message','channel']);$actor=$this->actor();
        $channel=$this->enum($input['channel']??'IN_APP',['IN_APP','EMAIL','SMS','WHATSAPP']);
        if($channel!=='IN_APP')throw HttpException::serviceUnavailable('External delivery is not configured.','delivery_provider_unavailable');
        $data=['recipient_user_id'=>$this->id($input['recipient_user_id']??null),'event_type'=>$this->text($input['event_type']??null,120),'severity'=>$this->enum($input['severity']??'LOW',self::SEVERITIES),'title'=>$this->text($input['title']??null,255),'message'=>$this->text($input['message']??null,2000),'sent_at'=>gmdate('Y-m-d H:i:s')];
        if(isset($input['alert_id']))$data['alert_id']=$this->id($input['alert_id']);
        return $this->safe(fn()=>$this->records->transaction(function()use($data,$actor):array{
            $this->record('users',$actor);$recipient=$this->record('users',$data['recipient_user_id']);
            if(($recipient['status']??'ACTIVE')!=='ACTIVE')throw HttpException::conflict('Recipient is not active.');
            if(isset($data['alert_id']))$this->record('alerts',$data['alert_id']);
            $data['recipient_employee_id']=$recipient['employee_id'];$this->record('employees',(string)$recipient['employee_id']);
            $row=$this->records->insert('notifications',$data,['recipient_user_id','recipient_employee_id','alert_id','event_type','severity','title','message','sent_at']);
            $this->records->insert('deliveries',['notification_id'=>$row['notification_id'],'channel'=>'IN_APP','delivery_status'=>'SENT','attempt_count'=>1,'sent_at'=>$data['sent_at']],['notification_id','channel','delivery_status','attempt_count','sent_at']);
            $this->audit->record('notification','created',(string)$row['notification_id'],[],['channel'=>'IN_APP']);return $row;
        }));
    }
    public function markRead(string $id,array $input):array
    {
        $this->allowed($input,[]);$id=$this->id($id);$actor=$this->actor();
        return $this->safe(fn()=>$this->records->transaction(function()use($id,$actor):array{
            $row=$this->record('notifications',$id);
            if((string)$row['recipient_user_id']!==$actor)throw HttpException::notFound('Notification not found.');
            if($row['read_at']!==null)return $row;
            $row=$this->records->update('notifications',$id,['read_at'=>gmdate('Y-m-d H:i:s')],['read_at']);
            $this->audit->record('notification','read',$id);return $row;
        }));
    }
    public function deliveries(string $id):array { $this->getNotification($id);return $this->safe(fn()=>$this->records->list('deliveries',['notification_id'=>$id],['notification_id'])); }
    private function record(string $domain,string $id,bool $lock=true):array{return $this->records->find($domain,$id,$lock)??throw HttpException::notFound('Record not found.');}
    private function actor():string{if(!$this->context->isAuthenticated())throw HttpException::unauthorized();return $this->id($this->context->user());}
    private function allowed(array $input,array $fields):void{$this->context->requireTenant();if(array_diff(array_keys($input),$fields)!==[])throw HttpException::unprocessable('Unknown fields.');}
    private function id(mixed $value):string{if((!is_int($value)&&!is_string($value))||!preg_match('/^[1-9][0-9]{0,18}$/D',(string)$value))throw HttpException::unprocessable('Invalid identifier.');return(string)$value;}
    private function enum(mixed $value,array $allowed):string{if(!in_array($value,$allowed,true))throw HttpException::unprocessable('Invalid value.');return$value;}
    private function text(mixed $value,int $max):string{if(!is_string($value)||trim($value)===''||strlen($value)>$max)throw HttpException::unprocessable('Invalid text.');return trim($value);}
    private function date(mixed $value):string{$date=is_string($value)?\DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$value):false;if($date===false||$date->format('Y-m-d H:i:s')!==$value)throw HttpException::unprocessable('Invalid UTC timestamp.');return$value;}
    private function safe(callable $operation):mixed{try{return$operation();}catch(\PDOException $error){if(in_array((string)$error->getCode(),['23000','23505','40001'],true))throw HttpException::conflict('Record conflicts with current state.');throw HttpException::serviceUnavailable('Alert and notification storage is unavailable.');}}
}
