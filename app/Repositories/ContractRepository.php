<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class ContractRepository extends Repository
{
	private const COLUMNS = 'c.contract_id, c.contract_uuid, c.company_id, c.client_id, c.contract_number, c.contract_name, c.start_date, c.end_date, c.monthly_value, c.sla_tier, c.required_guard_count, c.shift_requirements_json, c.status, c.created_by_user_id, c.updated_by_user_id, c.created_at, c.updated_at, c.deleted_at';
	public function __construct(Database $db, ?RuntimeContext $context = null) { parent::__construct($db, $context); }
	protected function table(): string { return 'contracts'; }
	public function listForCompany(): array { $companyId=$this->requireCompanyId(); return $this->db->fetchAll('SELECT '.self::COLUMNS.' FROM contracts c WHERE c.company_id = :company_id AND c.deleted_at IS NULL ORDER BY c.start_date DESC, c.contract_id DESC',['company_id'=>$companyId]); }
	public function findByIdForCompany(string $id): ?array { $companyId=$this->requireCompanyId(); return $this->db->fetch('SELECT '.self::COLUMNS.' FROM contracts c WHERE c.contract_id = :id AND c.company_id = :company_id AND c.deleted_at IS NULL LIMIT 1',['id'=>$id,'company_id'=>$companyId]); }
	public function clientBelongsToCompany(string $id): bool { $companyId=$this->requireCompanyId(); return $this->db->fetch('SELECT client_id FROM clients WHERE client_id = :id AND company_id = :company_id LIMIT 1',['id'=>$id,'company_id'=>$companyId])!==null; }
	public function numberExists(string $number, ?string $excludeId=null): bool { $companyId=$this->requireCompanyId(); $sql='SELECT contract_id FROM contracts WHERE contract_number = :number AND company_id = :company_id AND deleted_at IS NULL'; $p=['number'=>$number,'company_id'=>$companyId]; if($excludeId!==null){$sql.=' AND contract_id <> :exclude_id';$p['exclude_id']=$excludeId;} return $this->db->fetch($sql.' LIMIT 1',$p)!==null; }
	public function createForCompany(array $data): array { $companyId=$this->requireCompanyId(); $this->db->execute('INSERT INTO contracts (contract_uuid, company_id, client_id, contract_number, contract_name, start_date, end_date, monthly_value, sla_tier, required_guard_count, shift_requirements_json, status) VALUES (:uuid,:company_id,:client_id,:number,:name,:start_date,:end_date,:monthly_value,:sla_tier,:guard_count,:requirements,:status)',['uuid'=>uuid4(),'company_id'=>$companyId,'client_id'=>$data['client_id'],'number'=>$data['contract_number'],'name'=>$data['contract_name'],'start_date'=>$data['start_date'],'end_date'=>$data['end_date']??null,'monthly_value'=>$data['monthly_value']??0,'sla_tier'=>$data['sla_tier']??null,'guard_count'=>$data['required_guard_count']??null,'requirements'=>$data['shift_requirements_json']??null,'status'=>$data['status']??'DRAFT']); $id=(string)$this->db->connection()->lastInsertId(); $created=$this->findByIdForCompany($id); if($created===null)throw new \RuntimeException('Created contract could not be loaded.'); return$created; }
	public function updateForCompany(string $id,array $data):?array { $companyId=$this->requireCompanyId();$allowed=['client_id','contract_number','contract_name','start_date','end_date','monthly_value','sla_tier','required_guard_count','shift_requirements_json','status'];$sets=[];$p=['id'=>$id,'company_id'=>$companyId];foreach($allowed as $f)if(array_key_exists($f,$data)){$sets[]=$f.' = :'.$f;$p[$f]=$data[$f];}if($sets!==[])$this->db->execute('UPDATE contracts SET '.implode(', ',$sets).' WHERE contract_id = :id AND company_id = :company_id AND deleted_at IS NULL',$p);return$this->findByIdForCompany($id); }
}
