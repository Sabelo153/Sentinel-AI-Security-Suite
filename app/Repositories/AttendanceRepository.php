<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class AttendanceRepository extends Repository
{
    private const COLUMNS = 'a.attendance_id, a.company_id, a.employee_id, a.shift_assignment_id, a.contract_id, a.site_id, a.post_id, a.shift_date, a.clock_in_at, a.clock_out_at, a.attendance_status, a.source, a.notes, a.created_at, a.updated_at, e.employee_number, pe.first_name, pe.last_name, s.site_name, sa.shift_name, sa.shift_start_at, sa.shift_end_at';

    public function __construct(Database $db, ?RuntimeContext $context = null) { parent::__construct($db, $context); }
    protected function table(): string { return 'attendance'; }

    public function listForCompany(): array
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetchAll('SELECT ' . self::COLUMNS . ' FROM attendance a INNER JOIN employees e ON e.employee_id = a.employee_id AND e.company_id = a.company_id INNER JOIN people pe ON pe.person_id = e.person_id AND pe.company_id = e.company_id LEFT JOIN sites s ON s.site_id = a.site_id AND s.company_id = a.company_id LEFT JOIN shift_assignments sa ON sa.shift_assignment_id = a.shift_assignment_id AND sa.company_id = a.company_id WHERE a.company_id = :company_id AND a.deleted_at IS NULL ORDER BY a.shift_date DESC, a.attendance_id DESC', ['company_id' => $companyId]);
    }

    public function findByIdForCompany(string $id): ?array
    {
        $companyId = $this->requireCompanyId();
        return $this->db->fetch('SELECT ' . self::COLUMNS . ' FROM attendance a INNER JOIN employees e ON e.employee_id = a.employee_id AND e.company_id = a.company_id INNER JOIN people pe ON pe.person_id = e.person_id AND pe.company_id = e.company_id LEFT JOIN sites s ON s.site_id = a.site_id AND s.company_id = a.company_id LEFT JOIN shift_assignments sa ON sa.shift_assignment_id = a.shift_assignment_id AND sa.company_id = a.company_id WHERE a.attendance_id = :id AND a.company_id = :company_id AND a.deleted_at IS NULL LIMIT 1', ['id' => $id, 'company_id' => $companyId]);
    }

    public function employeeBelongsToCompany(string $employeeId): bool { $companyId=$this->requireCompanyId(); return $this->db->fetch('SELECT employee_id FROM employees WHERE employee_id = :id AND company_id = :company_id LIMIT 1',['id'=>$employeeId,'company_id'=>$companyId])!==null; }
    public function siteBelongsToCompany(string $siteId): bool { $companyId=$this->requireCompanyId(); return $this->db->fetch('SELECT site_id FROM sites WHERE site_id = :id AND company_id = :company_id LIMIT 1',['id'=>$siteId,'company_id'=>$companyId])!==null; }
    public function contractBelongsToCompany(string $contractId): bool { $companyId=$this->requireCompanyId(); return $this->db->fetch('SELECT contract_id FROM contracts WHERE contract_id = :id AND company_id = :company_id LIMIT 1',['id'=>$contractId,'company_id'=>$companyId])!==null; }
    public function postBelongsToCompany(string $postId): bool { $companyId=$this->requireCompanyId(); return $this->db->fetch('SELECT post_id FROM posts WHERE post_id = :id AND company_id = :company_id LIMIT 1',['id'=>$postId,'company_id'=>$companyId])!==null; }
    public function shiftBelongsToCompany(string $shiftId): bool { $companyId=$this->requireCompanyId(); return $this->db->fetch('SELECT shift_assignment_id FROM shift_assignments WHERE shift_assignment_id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',['id'=>$shiftId,'company_id'=>$companyId])!==null; }
    public function shiftDetails(string $shiftId): ?array { $companyId=$this->requireCompanyId(); return $this->db->fetch('SELECT employee_id, site_id FROM shift_assignments WHERE shift_assignment_id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',['id'=>$shiftId,'company_id'=>$companyId]); }
    public function attendanceExistsForEmployeeDate(string $employeeId, string $shiftDate, ?string $excludeId=null): bool { $companyId=$this->requireCompanyId();$sql='SELECT attendance_id FROM attendance WHERE employee_id = :employee_id AND shift_date = :shift_date AND company_id = :company_id AND deleted_at IS NULL';$p=['employee_id'=>$employeeId,'shift_date'=>$shiftDate,'company_id'=>$companyId];if($excludeId!==null){$sql.=' AND attendance_id <> :exclude_id';$p['exclude_id']=$excludeId;}return $this->db->fetch($sql.' LIMIT 1',$p)!==null; }

    public function createForCompany(array $data): array
    {
        $companyId=$this->requireCompanyId();
        $this->db->execute('INSERT INTO attendance (attendance_ref, company_id, employee_id, shift_assignment_id, contract_id, site_id, post_id, shift_date, clock_in_at, clock_out_at, attendance_status, source, notes) VALUES (:attendance_ref, :company_id, :employee_id, :shift_assignment_id, :contract_id, :site_id, :post_id, :shift_date, :clock_in_at, :clock_out_at, :attendance_status, :source, :notes)',[
            'attendance_ref'=>substr(uuid4(),0,30),'company_id'=>$companyId,'employee_id'=>$data['employee_id'],'shift_assignment_id'=>$data['shift_assignment_id']??null,'contract_id'=>$data['contract_id']??null,'site_id'=>$data['site_id']??null,'post_id'=>$data['post_id']??null,'shift_date'=>$data['shift_date'],'clock_in_at'=>$data['clock_in_at']??null,'clock_out_at'=>$data['clock_out_at']??null,'attendance_status'=>$data['attendance_status']??'PRESENT','source'=>$data['source']??'MOBILE_APP','notes'=>$data['notes']??null,
        ]);
        $id=(string)$this->db->connection()->lastInsertId();$created=$this->findByIdForCompany($id);if($created===null)throw new \RuntimeException('Created attendance could not be loaded.');return$created;
    }

    public function updateForCompany(string $id,array $data): ?array
    {
        $companyId=$this->requireCompanyId();$assignments=[];$p=['id'=>$id,'company_id'=>$companyId];foreach(['clock_in_at','clock_out_at','attendance_status','notes'] as $c){if(array_key_exists($c,$data)){$assignments[]=$c.' = :'.$c;$p[$c]=$data[$c];}}if($assignments!==[])$this->db->execute('UPDATE attendance SET '.implode(', ',$assignments).' WHERE attendance_id = :id AND company_id = :company_id AND deleted_at IS NULL',$p);return$this->findByIdForCompany($id);
    }
}