<?php
declare(strict_types=1);
namespace Sentinel\Services;
use DateTimeImmutable;
use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\ContractRepository;
final class ContractManagementService
{
    private const STATUSES=['DRAFT','ACTIVE','SUSPENDED','EXPIRED','TERMINATED','ARCHIVED'];
    public function __construct(private readonly ContractRepository $contracts){}
    public function listContracts():array{try{return$this->contracts->listForCompany();}catch(PDOException$e){throw$this->unavailable($e);}}
    public function getContract(string$id):array{try{$r=$this->contracts->findByIdForCompany($id);}catch(PDOException$e){throw$this->unavailable($e);}if($r===null)throw HttpException::notFound('Contract not found.','contract_not_found');return$r;}
    public function createContract(array$i):array{$d=$this->validate($i,false);try{$this->refs($d);if($this->contracts->numberExists($d['contract_number']))throw HttpException::conflict('Contract number already exists.','contract_number_conflict');return$this->contracts->createForCompany($d);}catch(HttpException$e){throw$e;}catch(PDOException$e){throw$this->unavailable($e);}}
    public function updateContract(string$id,array$i):array{$existing=$this->getContract($id);$d=$this->validate($i,true);$effective=array_merge($existing,$d);try{$this->refs($effective);if(isset($d['contract_number'])&&$this->contracts->numberExists($d['contract_number'],$id))throw HttpException::conflict('Contract number already exists.','contract_number_conflict');$r=$this->contracts->updateForCompany($id,$d);}catch(HttpException$e){throw$e;}catch(PDOException$e){throw$this->unavailable($e);}if($r===null)throw HttpException::notFound('Contract not found.','contract_not_found');return$r;}
    private function validate(array$i,bool$update):array{$allowed=['client_id','contract_number','contract_name','start_date','end_date','monthly_value','sla_tier','required_guard_count','shift_requirements_json','status'];if(array_diff(array_keys($i),$allowed)!==[])throw HttpException::unprocessable('Invalid contract fields.','invalid_contract_request');$d=$i;foreach(['client_id','required_guard_count']as$f)if(isset($d[$f])&&((!is_int($d[$f])&&!(is_string($d[$f])&&ctype_digit($d[$f])))||(int)$d[$f]<1))throw HttpException::unprocessable('Contract field is invalid.','invalid_'.$f);foreach(['contract_number'=>100,'contract_name'=>255]as$f=>$max)if(isset($d[$f])&&(!is_string($d[$f])||$d[$f]===''||strlen($d[$f])>$max))throw HttpException::unprocessable('Contract field is invalid.','invalid_'.$f);foreach(['start_date','end_date']as$f)if(isset($d[$f])&&!$this->date($d[$f]))throw HttpException::unprocessable('Contract date is invalid.','invalid_'.$f);if(!$update)foreach(['client_id','contract_number','contract_name','start_date']as$f)if(!array_key_exists($f,$d))throw HttpException::unprocessable('Contract field is required.','invalid_'.$f);if(isset($d['monthly_value'])&&!is_numeric($d['monthly_value']))throw HttpException::unprocessable('Monthly value is invalid.','invalid_monthly_value');if(isset($d['status'])&&!in_array($d['status'],self::STATUSES,true))throw HttpException::unprocessable('Contract status is invalid.','invalid_contract_status');return$d;}
    private function refs(array$d):void{if(!$this->contracts->clientBelongsToCompany((string)$d['client_id']))throw HttpException::notFound('Client not found.','client_not_found');}
    private function date(mixed$v):bool{if(!is_string($v))return false;$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);return$d!==false&&$d->format('Y-m-d')===$v;}
    private function unavailable(PDOException$e):HttpException{return new HttpException(503,'Contract service unavailable.','contract_service_unavailable',$e);}
}