<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class DeviceSessionRepository extends Repository
{
    private const COLUMNS = 'device_session_id, company_id, device_id, device_assignment_id, employee_id, employee_assignment_id, shift_assignment_id, site_id, session_status, started_at, ended_at, last_seen_at, created_at, updated_at';
    public function __construct(Database $db, ?RuntimeContext $context = null) { parent::__construct($db, $context); }
    protected function table(): string { return 'device_sessions'; }
    /** @return array<int,array<string,mixed>> */ public function listForCompany(array $filters=[]): array { $where=['company_id=:company_id'];$params=['company_id'=>$this->requireCompanyId()];foreach(['device_id','employee_id','session_status']as$field)if(isset($filters[$field])){$where[]=$field.'=:'.$field;$params[$field]=$filters[$field];}return $this->db->fetchAll('SELECT '.self::COLUMNS.' FROM device_sessions WHERE '.implode(' AND ',$where).' ORDER BY started_at DESC,device_session_id DESC',$params); }
    /** @return array<int,array<string,mixed>> */ public function listForDevice(string $deviceId): array { return $this->listForCompany(['device_id'=>$deviceId]); }
    /** @return array<string,mixed>|null */ public function findByIdForCompany(string $id): ?array { return $this->db->fetch('SELECT '.self::COLUMNS.' FROM device_sessions WHERE device_session_id=:id AND company_id=:company_id LIMIT 1',['id'=>$id,'company_id'=>$this->requireCompanyId()]); }
    /** @return array<string,mixed>|null */ public function activeSessionForDevice(string $deviceId): ?array { return $this->db->fetch('SELECT '.self::COLUMNS.' FROM device_sessions WHERE device_id=:device_id AND company_id=:company_id AND session_status=:status LIMIT 1',['device_id'=>$deviceId,'company_id'=>$this->requireCompanyId(),'status'=>'ACTIVE']); }
    /** @return array<string,mixed>|null */ public function device(string $id): ?array { return $this->db->fetch('SELECT device_id,device_status FROM devices WHERE device_id=:id AND company_id=:company_id AND deleted_at IS NULL LIMIT 1',['id'=>$id,'company_id'=>$this->requireCompanyId()]); }
    public function employeeExists(string $id): bool { return $this->db->fetch('SELECT employee_id FROM employees WHERE employee_id=:id AND company_id=:company_id LIMIT 1',['id'=>$id,'company_id'=>$this->requireCompanyId()])!==null; }
    /** @return array<string,mixed>|null */ public function deviceAssignment(string $id): ?array { return $this->db->fetch('SELECT device_id,employee_id,employee_assignment_id,assignment_status,ends_at FROM device_assignments WHERE device_assignment_id=:id AND company_id=:company_id LIMIT 1',['id'=>$id,'company_id'=>$this->requireCompanyId()]); }
    /** @return array<string,mixed>|null */ public function employeeAssignment(string $id): ?array { return $this->db->fetch('SELECT employee_id FROM employee_assignments WHERE employee_assignment_id=:id AND company_id=:company_id AND deleted_at IS NULL LIMIT 1',['id'=>$id,'company_id'=>$this->requireCompanyId()]); }
    /** @return array<string,mixed>|null */ public function shift(string $id): ?array { return $this->db->fetch('SELECT employee_id,site_id FROM shift_assignments WHERE shift_assignment_id=:id AND company_id=:company_id AND deleted_at IS NULL LIMIT 1',['id'=>$id,'company_id'=>$this->requireCompanyId()]); }
    public function siteExists(string $id): bool { return $this->db->fetch('SELECT site_id FROM sites WHERE site_id=:id AND company_id=:company_id LIMIT 1',['id'=>$id,'company_id'=>$this->requireCompanyId()])!==null; }
    /** @param array<string,mixed> $data @return array<string,mixed> */ public function createForCompany(array $data): array { $this->db->execute('INSERT INTO device_sessions (company_id,device_id,device_assignment_id,employee_id,employee_assignment_id,shift_assignment_id,site_id,session_status,started_at,ended_at,last_seen_at) VALUES (:company_id,:device_id,:device_assignment_id,:employee_id,:employee_assignment_id,:shift_assignment_id,:site_id,:session_status,:started_at,:ended_at,:last_seen_at)',['company_id'=>$this->requireCompanyId(),...$data]);$id=(string)$this->db->connection()->lastInsertId();$this->db->execute('UPDATE devices SET last_seen_at=:seen WHERE device_id=:device_id AND company_id=:company_id',['seen'=>$data['last_seen_at'],'device_id'=>$data['device_id'],'company_id'=>$this->requireCompanyId()]);return $this->findByIdForCompany($id)??throw new \RuntimeException('Created device session could not be loaded.'); }
    /** @param array<string,mixed> $data @return array<string,mixed>|null */ public function updateForCompany(string $id,array $data): ?array {$sets=[];$params=['id'=>$id,'company_id'=>$this->requireCompanyId()];foreach(['session_status','ended_at','last_seen_at']as$field)if(array_key_exists($field,$data)){$sets[]=$field.'=:'.$field;$params[$field]=$data[$field];}if($sets!==[])$this->db->execute('UPDATE device_sessions SET '.implode(',',$sets).' WHERE device_session_id=:id AND company_id=:company_id',$params);return $this->findByIdForCompany($id);}
}