<?php
declare(strict_types=1);
namespace Sentinel\Services;

use Sentinel\Core\{Database, HttpException, RuntimeContext};

/** Device tokens are bound to one installation and company, independently of a guard login. */
final class ManagedAndroidService
{
    public function __construct(private readonly Database $db, private readonly RuntimeContext $context, private readonly AuditService $audit) {}

    public function list(): array
    {
        return $this->db->fetchAll('SELECT managed_device_id,device_name,installation_id,enrolled_user_id,revision,policy_json,last_seen_at,reported_revision,reported_state FROM managed_android WHERE company_id=:company ORDER BY managed_device_id DESC LIMIT 200', ['company'=>$this->context->requireTenant()]);
    }

    public function create(array $input): array
    {
        $this->fields($input,['device_name']);
        $name=$input['device_name']??null;
        if(!is_string($name)||trim($name)===''||mb_strlen($name)>120)throw HttpException::unprocessable('A device name up to 120 characters is required.');
        $enrollment=bin2hex(random_bytes(24));$recovery=bin2hex(random_bytes(16));$salt=bin2hex(random_bytes(16));
        $this->db->execute('INSERT INTO managed_android (company_id,device_name,enrollment_hash,enrollment_expires_at,policy_json,recovery_salt,recovery_hash,created_by_user_id) VALUES (:company,:name,:enrollment,:expires,:policy,:salt,:recovery,:actor)',[
            'company'=>$this->context->requireTenant(),'name'=>trim($name),'enrollment'=>hash('sha256',$enrollment),'expires'=>gmdate('Y-m-d H:i:s',time()+1800),'policy'=>json_encode(['enabled'=>false,'allowed_packages'=>['com.sentinel.guard'],'block_status_bar'=>true,'block_install'=>true]),'salt'=>$salt,'recovery'=>hash('sha256',$salt.':'.$recovery),'actor'=>$this->context->user(),
        ]);
        $id=(string)$this->db->connection()->lastInsertId();$this->audit->record('managed_android','created',$id);
        return ['managed_device_id'=>$id,'enrollment_code'=>$enrollment,'recovery_code'=>$recovery,'expires_in_seconds'=>1800,'instruction'=>'Store the offline recovery code securely before enabling kiosk mode. These codes are shown once.'];
    }

    public function policy(string $id,array $input): array
    {
        $this->fields($input,['expected_revision','enabled','allowed_packages','block_status_bar','block_install']);
        foreach(['enabled','block_status_bar','block_install']as$key)if(!is_bool($input[$key]??null))throw HttpException::unprocessable('Policy switches must be booleans.');
        $packages=$input['allowed_packages']??null;
        if(!is_array($packages)||!array_is_list($packages)||count($packages)>30)throw HttpException::unprocessable('Provide at most 30 Android package names.');
        foreach($packages as$package)if(!is_string($package)||strlen($package)>180||preg_match('/^[A-Za-z][A-Za-z0-9_]*(?:\.[A-Za-z][A-Za-z0-9_]*)+$/D',$package)!==1)throw HttpException::unprocessable('Invalid Android package name.');
        // System settings, generic browsers and installers can defeat a dedicated-device policy.
        if(array_intersect($packages,['com.android.settings','com.android.packageinstaller','com.google.android.packageinstaller','com.android.permissioncontroller']))throw HttpException::unprocessable('System settings and installers cannot be kiosk apps.');
        $revision=$input['expected_revision']??null;
        if(!is_int($revision)||$revision<1)throw HttpException::unprocessable('A current policy revision is required.');
        $policy=['enabled'=>$input['enabled'],'allowed_packages'=>array_values(array_unique(['com.sentinel.guard',...$packages])),'block_status_bar'=>$input['block_status_bar'],'block_install'=>$input['block_install']];
        $changed=$this->db->execute('UPDATE managed_android SET policy_json=:policy,revision=revision+1 WHERE managed_device_id=:id AND company_id=:company AND revision=:revision',['policy'=>json_encode($policy,JSON_THROW_ON_ERROR),'id'=>$id,'company'=>$this->context->requireTenant(),'revision'=>$revision]);
        if($changed!==1)throw HttpException::conflict('Policy changed or device not found. Refresh before retrying.');
        $this->audit->record('managed_android','policy_changed',$id,[],['revision'=>$revision+1,'enabled'=>$policy['enabled']]);
        return ['revision'=>$revision+1,'delivery'=>'Applies when the enrolled device next connects. Check its reported revision; saving is not proof of enforcement.'];
    }

    public function enroll(array $input): array
    {
        $this->fields($input,['enrollment_code','installation_id']);
        $uuid=$this->uuid($input['installation_id']??null);$code=$input['enrollment_code']??null;
        if(!is_string($code)||preg_match('/^[a-f0-9]{48}$/D',$code)!==1)throw HttpException::unprocessable('Invalid enrollment code.');
        $row=$this->db->fetch('SELECT managed_device_id FROM managed_android WHERE company_id=:company AND enrollment_hash=:hash AND installation_id IS NULL AND enrollment_expires_at>:now',['company'=>$this->context->requireTenant(),'hash'=>hash('sha256',$code),'now'=>gmdate('Y-m-d H:i:s')]);
        if($row===null)throw HttpException::forbidden('Enrollment code is expired or already used.');
        $token=bin2hex(random_bytes(32));
        $changed=$this->db->execute('UPDATE managed_android SET installation_id=:installation,device_token_hash=:token,enrolled_user_id=:actor,enrollment_hash=NULL WHERE managed_device_id=:id AND company_id=:company AND enrollment_hash=:hash AND installation_id IS NULL',['installation'=>$uuid,'token'=>hash('sha256',$token),'actor'=>$this->context->user(),'id'=>$row['managed_device_id'],'company'=>$this->context->requireTenant(),'hash'=>hash('sha256',$code)]);
        if($changed!==1)throw HttpException::conflict('Enrollment was already consumed.');
        $this->audit->record('managed_android','enrolled',(string)$row['managed_device_id']);
        return ['device_token'=>$token,'installation_id'=>$uuid];
    }

    public function replaceEnrollment(string $id,array $input):array
    {
        $this->fields($input,['confirm_device_id']);if(($input['confirm_device_id']??null)!==$id)throw HttpException::unprocessable('Type the managed device ID to confirm re-enrollment.');
        $row=$this->db->fetch('SELECT revision,installation_id,policy_json,reported_revision,reported_state FROM managed_android WHERE managed_device_id=:id AND company_id=:company',['id'=>$id,'company'=>$this->context->requireTenant()]);if(!$row)throw HttpException::notFound('Device not found.');
        $policy=json_decode($row['policy_json'],true,32,JSON_THROW_ON_ERROR);
        $neverEnabled=$row['installation_id']===null||((int)$row['revision']===1&&in_array($row['reported_state'],[null,'UNKNOWN','NOT_DEVICE_OWNER'],true));
        $released=(int)$row['reported_revision']>=(int)$row['revision']&&in_array($row['reported_state'],['RELEASED','RECOVERY_RELEASED'],true);
        if($policy['enabled']||(!$neverEnabled&&!$released))throw HttpException::conflict('Release the policy and confirm device acknowledgement before replacing enrollment. Use offline recovery if the phone is disconnected.');
        $code=bin2hex(random_bytes(24));$recovery=bin2hex(random_bytes(16));$salt=bin2hex(random_bytes(16));
        $changed=$this->db->execute("UPDATE managed_android SET enrollment_hash=:enrollment,enrollment_expires_at=:expires,installation_id=NULL,device_token_hash=NULL,enrolled_user_id=NULL,recovery_salt=:salt,recovery_hash=:recovery,reported_revision=0,reported_state='UNKNOWN',last_seen_at=NULL,revision=revision+1 WHERE managed_device_id=:id AND company_id=:company AND revision=:expected",['enrollment'=>hash('sha256',$code),'expires'=>gmdate('Y-m-d H:i:s',time()+1800),'salt'=>$salt,'recovery'=>hash('sha256',$salt.':'.$recovery),'id'=>$id,'company'=>$this->context->requireTenant(),'expected'=>$row['revision']]);if($changed!==1)throw HttpException::conflict('Device policy changed. Refresh before retrying.');
        $this->audit->record('managed_android','enrollment_replaced',$id);return ['managed_device_id'=>$id,'enrollment_code'=>$code,'recovery_code'=>$recovery,'expires_in_seconds'=>1800,'instruction'=>'Previous installation credential is revoked. Store the new offline recovery code securely.'];
    }

    /** Separate installation credential lets a signed-out/expired guard still receive a release policy. */
    public function poll(array $input): array
    {
        $this->fields($input,['device_token','installation_id','reported_revision','reported_state']);
        $uuid=$this->uuid($input['installation_id']??null);$token=$input['device_token']??null;
        if(!is_string($token)||preg_match('/^[a-f0-9]{64}$/D',$token)!==1)throw HttpException::unauthorized();
        $revision=$input['reported_revision']??0;$state=$input['reported_state']??'UNKNOWN';
        if(!is_int($revision)||$revision<0||!in_array($state,['UNKNOWN','NOT_DEVICE_OWNER','APPLIED','PARTIAL','RELEASED','RECOVERY_RELEASED'],true))throw HttpException::unprocessable('Invalid policy acknowledgement.');
        $row=$this->db->fetch('SELECT managed_device_id,company_id,enrolled_user_id,reported_state,revision,policy_json,recovery_salt,recovery_hash FROM managed_android WHERE installation_id=:installation AND device_token_hash=:token',['installation'=>$uuid,'token'=>hash('sha256',$token)]);
        if($row===null)throw HttpException::unauthorized();
        $owns=!$this->db->inTransaction();if($owns)$this->db->beginTransaction();
        try{
            $this->db->execute('UPDATE managed_android SET last_seen_at=:now,reported_revision=:revision,reported_state=:state WHERE managed_device_id=:id AND company_id=:company',['now'=>gmdate('Y-m-d H:i:s'),'revision'=>$revision,'state'=>$state,'id'=>$row['managed_device_id'],'company'=>$row['company_id']]);
            if($state==='RECOVERY_RELEASED'&&$row['reported_state']!==$state)$this->db->execute("INSERT INTO audit_logs(company_id,user_id,entity_name,entity_id,action_name,action_status,metadata) VALUES(:company,:user,'managed_android',:id,'device_reported_offline_recovery','SUCCESS',:metadata)",['company'=>$row['company_id'],'user'=>$row['enrolled_user_id'],'id'=>$row['managed_device_id'],'metadata'=>json_encode(['reported_revision'=>$revision])]);
            if($owns)$this->db->commit();
        }catch(\Throwable $error){if($owns&&$this->db->inTransaction())$this->db->rollBack();throw $error;}
        return ['revision'=>(int)$row['revision'],'policy'=>json_decode($row['policy_json'],true,32,JSON_THROW_ON_ERROR),'recovery_salt'=>$row['recovery_salt'],'recovery_hash'=>$row['recovery_hash']];
    }

    private function uuid(mixed $value):string
    {
        if(!is_string($value)||preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/Di',$value)!==1)throw HttpException::unprocessable('Invalid installation identifier.');return strtolower($value);
    }
    private function fields(array $input,array $allowed):void {if(array_diff(array_keys($input),$allowed))throw HttpException::unprocessable('Unknown device management fields.');}
}
