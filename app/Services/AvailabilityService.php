<?php
declare(strict_types=1);
namespace Sentinel\Services;
use Sentinel\Core\{Database,RuntimeContext,HttpException};
final class AvailabilityService
{
    use TenantOperations;
    public function __construct(private readonly Database $db,private readonly RuntimeContext $context,private readonly AuditService $audit) {}
    public function list(bool $self=false): array {return $this->db->fetchAll('SELECT * FROM employee_availability WHERE company_id=:company'.($self?' AND employee_id=:employee':'').' ORDER BY starts_at DESC LIMIT 500',$self?['company'=>$this->company(),'employee'=>$this->employee()]:['company'=>$this->company()]);}
    public function request(array $input,bool $self=false): array {return $this->atomic(function()use($input,$self){
        $this->fields($input,$self?['request_type','starts_at','ends_at','reason']:['employee_id','request_type','starts_at','ends_at','reason']);$employee=$self?$this->employee():$this->row('employees','employee_id',$input['employee_id']??null)['employee_id'];$start=$this->at($input['starts_at']??null);$end=$this->at($input['ends_at']??null);if($end<=$start||strtotime($end.' UTC')-strtotime($start.' UTC')>366*86400||!in_array($input['request_type']??null,['LEAVE','UNAVAILABLE'],true))throw HttpException::unprocessable('Choose a valid leave/availability period of at most 366 days.');$this->db->execute('INSERT INTO employee_availability(company_id,employee_id,request_type,starts_at,ends_at,reason,created_by_user_id,created_at) VALUES(:company,:employee,:type,:start,:end,:reason,:actor,:now)',['company'=>$this->company(),'employee'=>$employee,'type'=>$input['request_type'],'start'=>$start,'end'=>$end,'reason'=>$this->text($input['reason']??null,1000),'actor'=>$this->actor(),'now'=>gmdate('Y-m-d H:i:s')]);return $this->created('employee_availability');
    });}
    public function assertAvailable(string $employee,string $start,string $end): void {
        if($this->db->fetch("SELECT availability_id FROM employee_availability WHERE company_id=:company AND employee_id=:employee AND state='APPROVED' AND starts_at<:end AND ends_at>:start LIMIT 1",['company'=>$this->company(),'employee'=>$employee,'start'=>$start,'end'=>$end]))throw HttpException::conflict('Employee has approved leave or unavailability during this shift.');
    }
    public function review(string $id,string $action,array $input,bool $self=false): array {return $this->atomic(function()use($id,$action,$input,$self){
        $this->fields($input,['expected_revision','notes']);$row=$this->row('employee_availability','availability_id',$id);if($self&&((string)$row['employee_id']!==$this->employee()||$action!=='cancel'))throw HttpException::notFound();if($this->number($input['expected_revision']??null)!==(int)$row['revision'])throw HttpException::conflict('Availability request changed. Refresh first.');$notes=$this->text($input['notes']??null,1000);
        if($action==='approve'){
            if($row['state']!=='REQUESTED')throw HttpException::conflict('Only a pending request can be approved.');
            if($this->db->fetch("SELECT shift_assignment_id FROM shift_assignments WHERE company_id=:company AND employee_id=:employee AND deleted_at IS NULL AND assignment_status IN ('PLANNED','ASSIGNED','CONFIRMED','IN_PROGRESS') AND shift_start_at<:end AND shift_end_at>:start LIMIT 1",['company'=>$this->company(),'employee'=>$row['employee_id'],'start'=>$row['starts_at'],'end'=>$row['ends_at']]))throw HttpException::conflict('Arrange relief or cancel overlapping duty before approving this absence.');$this->assertAvailable((string)$row['employee_id'],$row['starts_at'],$row['ends_at']);$state='APPROVED';
        }elseif($action==='reject'&&$row['state']==='REQUESTED')$state='REJECTED';elseif($action==='cancel'&&in_array($row['state'],['REQUESTED','APPROVED'],true))$state='CANCELLED';else throw HttpException::conflict('Invalid availability transition.');
        $this->db->execute('UPDATE employee_availability SET state=:state,revision=revision+1,reviewed_by_user_id=:actor,reviewed_at=:now,review_notes=:notes WHERE company_id=:company AND availability_id=:id',['state'=>$state,'actor'=>$this->actor(),'now'=>gmdate('Y-m-d H:i:s'),'notes'=>$notes,'company'=>$this->company(),'id'=>$id]);$this->audit->record('employee_availability',$action,$id);return ['state'=>$state,'revision'=>(int)$row['revision']+1];
    });}
}
