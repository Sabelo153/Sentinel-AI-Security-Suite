<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\DeviceRepository;

final class DeviceManagementService
{
    private const STATUSES=['REGISTERED','ACTIVE','SUSPENDED','LOST','RETIRED']; private const FIELDS=['device_name','device_type','platform','device_identifier','device_status','last_seen_at'];
    public function __construct(private readonly DeviceRepository $devices,private readonly AuditService $audit){}
    public function listDevices(array $filters=[]):array{return $this->devices->listForCompany($this->filters($filters));} public function getDevice(string $id):array{return $this->devices->findByIdForCompany($id)??throw HttpException::notFound('Device not found.','device_not_found');}
    public function createDevice(array $input):array{$data=$this->fields($input,false);$data['device_uuid']=uuid4();try{$device=$this->devices->createForCompany($data);}catch(PDOException){throw HttpException::conflict('Device identifier is already in use.','device_conflict');}$this->audit('created',(string)$device['device_id'],[],$device);return $device;}
    public function updateDevice(string $id,array $input):array{$before=$this->getDevice($id);$data=$this->fields($input,true);if($data===[])throw HttpException::unprocessable('No editable device fields were provided.','invalid_device_update');if($before['device_status']==='RETIRED'&&($data['device_status']??'RETIRED')!=='RETIRED')throw HttpException::unprocessable('Retired devices cannot be reactivated.','invalid_device_transition');try{$device=$this->devices->updateForCompany($id,$data)??throw HttpException::notFound('Device not found.','device_not_found');}catch(PDOException){throw HttpException::conflict('Device identifier is already in use.','device_conflict');}$this->audit('updated',$id,$before,$device);return $device;}
    private function fields(array $input,bool $update):array{if(array_diff(array_keys($input),self::FIELDS)!==[])throw HttpException::unprocessable('Invalid device fields.','invalid_device_request');$data=$input;foreach(['device_name'=>120,'device_type'=>50,'platform'=>30,'device_identifier'=>255]as$f=>$max)if((!$update||array_key_exists($f,$data))&&(!is_string($data[$f]??null)||trim($data[$f])===''||strlen(trim($data[$f]))>$max))throw HttpException::unprocessable('Device field is invalid.','invalid_'.$f);foreach(['device_name','device_type','platform','device_identifier']as$f)if(isset($data[$f]))$data[$f]=trim($data[$f]);if(!$update)$data['device_status']??='REGISTERED';if(isset($data['device_status'])&&!in_array($data['device_status'],self::STATUSES,true))throw HttpException::unprocessable('Device status is invalid.','invalid_device_status');return$data;}
    private function filters(array $filters):array{$allowed=['device_status','device_type','platform','device_identifier','last_seen_from','last_seen_to'];if(array_diff(array_keys($filters),$allowed)!==[])throw HttpException::unprocessable('Invalid device filters.','invalid_device_filters');return$filters;} private function audit(string $action,string $id,array $old,array $new):void{try{$this->audit->record('device',$action,$id,$old,$new);}catch(\Throwable){}}
}