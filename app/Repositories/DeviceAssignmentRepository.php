<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class DeviceAssignmentRepository extends Repository
{
    private const COLUMNS = 'device_assignment_id, company_id, device_id, employee_id, employee_assignment_id, starts_at, ends_at, assignment_status, assigned_by_user_id, returned_at, notes, created_at, updated_at';
    public function __construct(Database $db, ?RuntimeContext $context = null) { parent::__construct($db, $context); }
    protected function table(): string { return 'device_assignments'; }
    /** @return array<int, array<string, mixed>> */ public function listForDevice(string $deviceId): array { return $this->db->fetchAll('SELECT ' . self::COLUMNS . ' FROM device_assignments WHERE device_id = :device_id AND company_id = :company_id ORDER BY starts_at DESC, device_assignment_id DESC', ['device_id' => $deviceId, 'company_id' => $this->requireCompanyId()]); }
    /** @return array<string, mixed>|null */ public function findByIdForCompany(string $id): ?array { return $this->db->fetch('SELECT ' . self::COLUMNS . ' FROM device_assignments WHERE device_assignment_id = :id AND company_id = :company_id LIMIT 1', ['id' => $id, 'company_id' => $this->requireCompanyId()]); }
    /** @return array<string, mixed>|null */ public function activeAssignmentForDevice(string $deviceId): ?array { return $this->db->fetch('SELECT ' . self::COLUMNS . ' FROM device_assignments WHERE device_id = :device_id AND company_id = :company_id AND assignment_status = :status AND ends_at IS NULL LIMIT 1', ['device_id' => $deviceId, 'company_id' => $this->requireCompanyId(), 'status' => 'ACTIVE']); }
    public function deviceExists(string $id): bool { return $this->db->fetch('SELECT device_id FROM devices WHERE device_id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1', ['id' => $id, 'company_id' => $this->requireCompanyId()]) !== null; }
    public function employeeExists(string $id): bool { return $this->db->fetch('SELECT employee_id FROM employees WHERE employee_id = :id AND company_id = :company_id LIMIT 1', ['id' => $id, 'company_id' => $this->requireCompanyId()]) !== null; }
    /** @return array<string, mixed>|null */ public function employeeAssignment(string $id): ?array { return $this->db->fetch('SELECT employee_assignment_id, employee_id FROM employee_assignments WHERE employee_assignment_id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1', ['id' => $id, 'company_id' => $this->requireCompanyId()]); }
    /** @param array<string,mixed> $data @return array<string,mixed> */ public function createForCompany(array $data): array { $this->db->execute('INSERT INTO device_assignments (company_id,device_id,employee_id,employee_assignment_id,starts_at,ends_at,assignment_status,assigned_by_user_id,returned_at,notes) VALUES (:company_id,:device_id,:employee_id,:employee_assignment_id,:starts_at,:ends_at,:assignment_status,:assigned_by_user_id,:returned_at,:notes)', ['company_id'=>$this->requireCompanyId(),...$data]); return $this->findByIdForCompany((string)$this->db->connection()->lastInsertId()) ?? throw new \RuntimeException('Created device assignment could not be loaded.'); }
    /** @param array<string,mixed> $data @return array<string,mixed>|null */ public function updateForCompany(string $id,array $data): ?array { $sets=[];$params=['id'=>$id,'company_id'=>$this->requireCompanyId()];foreach(['employee_id','employee_assignment_id','starts_at','ends_at','assignment_status','returned_at','notes']as$field)if(array_key_exists($field,$data)){$sets[]=$field.'=:'.$field;$params[$field]=$data[$field];}if($sets!==[])$this->db->execute('UPDATE device_assignments SET '.implode(',',$sets).' WHERE device_assignment_id=:id AND company_id=:company_id',$params);return $this->findByIdForCompany($id); }
}