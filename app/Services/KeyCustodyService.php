<?php
declare(strict_types=1);
namespace Sentinel\Services;
use Sentinel\Core\{Database,RuntimeContext,HttpException};

final class KeyCustodyService
{
    use TenantOperations;
    public function __construct(private readonly Database $db,private readonly RuntimeContext $context,private readonly AuditService $audit,private readonly SiteAccessService $access) {}

    public function assets(array $input): array {
        $this->fields($input,['site_id']);$site=$this->access->requireSite($input['site_id']??null);
        $rows=$this->db->fetchAll('SELECT * FROM custody_assets WHERE company_id=:company AND site_id=:site ORDER BY asset_code LIMIT 500',['company'=>$this->company(),'site'=>$site['site_id']]);
        foreach($rows as&$r)$r['loans']=$this->loans((string)$r['asset_id']);unset($r);return $rows;
    }
    private function loans(string $asset): array {
        $rows=$this->db->fetchAll('SELECT loan_id,asset_id,employee_id,state,revision,due_at,issued_at,issued_by_user_id,accepted_at,accepted_by_user_id,recipient_signature_hash,return_requested_at,return_signature_hash,received_at,received_by_user_id,receiver_signature_hash FROM custody_loans WHERE company_id=:company AND asset_id=:asset ORDER BY loan_id DESC LIMIT 100',['company'=>$this->company(),'asset'=>$asset]);
        foreach($rows as&$r)$r['overdue']=in_array($r['state'],['PENDING_ACCEPTANCE','ISSUED','RETURN_PENDING'],true)&&$r['due_at']<gmdate('Y-m-d H:i:s');unset($r);return $rows;
    }
    public function mine(): array {
        $rows=$this->db->fetchAll("SELECT l.loan_id,l.asset_id,l.employee_id,l.state,l.revision,l.due_at,a.asset_code,a.asset_name,a.asset_type,a.site_id FROM custody_loans l JOIN custody_assets a ON a.company_id=l.company_id AND a.asset_id=l.asset_id WHERE l.company_id=:company AND l.employee_id=:employee AND l.state IN ('PENDING_ACCEPTANCE','ISSUED','RETURN_PENDING','LOST') ORDER BY l.loan_id DESC LIMIT 200",['company'=>$this->company(),'employee'=>$this->employee()]);
        foreach($rows as&$r)$r['overdue']=$r['state']!=='LOST'&&$r['due_at']<gmdate('Y-m-d H:i:s');unset($r);return $rows;
    }
    private function event(string $asset,?string $loan,string $type,string $notes): void {
        $this->db->execute('INSERT INTO custody_events(company_id,asset_id,loan_id,event_type,notes,actor_user_id,occurred_at) VALUES(:company,:asset,:loan,:type,:notes,:actor,:now)',['company'=>$this->company(),'asset'=>$asset,'loan'=>$loan,'type'=>$type,'notes'=>$notes,'actor'=>$this->actor(),'now'=>gmdate('Y-m-d H:i:s')]);
        $this->audit->record('key_custody',$type,$asset);
    }
    public function create(array $input): array {return $this->atomic(function()use($input){
        $this->fields($input,['site_id','asset_code','asset_name','asset_type']);$site=$this->access->requireSite($input['site_id']??null);$code=strtoupper($this->text($input['asset_code']??null,60));
        if(!preg_match('/^[A-Z0-9_-]+$/D',$code)||!in_array($input['asset_type']??null,['KEY','ACCESS_CARD'],true))throw HttpException::unprocessable('Use a numbered key/card code and supported type.');
        if($this->db->fetch('SELECT asset_id FROM custody_assets WHERE company_id=:company AND site_id=:site AND asset_code=:code',['company'=>$this->company(),'site'=>$site['site_id'],'code'=>$code]))throw HttpException::conflict('This key/card code already exists at the site.');
        $this->db->execute('INSERT INTO custody_assets(company_id,site_id,asset_code,asset_name,asset_type,created_by_user_id,created_at) VALUES(:company,:site,:code,:name,:type,:actor,:now)',['company'=>$this->company(),'site'=>$site['site_id'],'code'=>$code,'name'=>$this->text($input['asset_name']??null,150),'type'=>$input['asset_type'],'actor'=>$this->actor(),'now'=>gmdate('Y-m-d H:i:s')]);return $this->created('custody_asset');
    });}
    public function issue(string $id,array $input): array {return $this->atomic(function()use($id,$input){
        $this->fields($input,['expected_revision','employee_id','due_at','notes']);$asset=$this->row('custody_assets','asset_id',$id);$this->access->requireSite($asset['site_id']);
        if($asset['state']!=='AVAILABLE'||$this->number($input['expected_revision']??null)!==(int)$asset['revision'])throw HttpException::conflict('The key/card is unavailable or changed.');
        $employee=$this->row('employees','employee_id',$input['employee_id']??null);if($employee['employment_status']!=='ACTIVE')throw HttpException::unprocessable('Choose an active employee.');
        if(!$this->db->fetch("SELECT user_id FROM users WHERE company_id=:company AND employee_id=:employee AND status='ACTIVE'",['company'=>$this->company(),'employee'=>$employee['employee_id']]))throw HttpException::unprocessable('The recipient needs an active login to accept custody.');
        $due=$this->at($input['due_at']??null);if($due<=gmdate('Y-m-d H:i:s')||strtotime($due.' UTC')>time()+30*86400)throw HttpException::unprocessable('Choose a return deadline within the next 30 days.');
        $notes=$this->text($input['notes']??null,2000);$this->db->execute("INSERT INTO custody_loans(company_id,asset_id,employee_id,state,due_at,issued_by_user_id,issued_at) VALUES(:company,:asset,:employee,'PENDING_ACCEPTANCE',:due,:actor,:now)",['company'=>$this->company(),'asset'=>$id,'employee'=>$employee['employee_id'],'due'=>$due,'actor'=>$this->actor(),'now'=>gmdate('Y-m-d H:i:s')]);$loan=(string)$this->db->connection()->lastInsertId();
        $this->db->execute("UPDATE custody_assets SET state='RESERVED',revision=revision+1 WHERE company_id=:company AND asset_id=:id",['company'=>$this->company(),'id'=>$id]);$this->event($id,$loan,'ISSUE_REQUESTED',$notes);return ['id'=>$loan];
    });}
    private function signature(mixed $encoded): array {
        if(!is_string($encoded)||strlen($encoded)>90000)throw HttpException::unprocessable('A drawn PNG signature up to 64 KiB is required.');$bytes=base64_decode($encoded,true);$size=$bytes===false?false:@getimagesizefromstring($bytes);
        if(!$size||$size[2]!==IMAGETYPE_PNG||strlen($bytes)>65536||$size[0]>1024||$size[1]>512)throw HttpException::unprocessable('Use a PNG signature up to 64 KiB, 1024 by 512 pixels.');return [base64_encode($bytes),hash('sha256',$bytes)];
    }
    public function selfAction(string $id,string $action,array $input): array {return $this->atomic(function()use($id,$action,$input){
        $this->fields($input,['expected_revision','signature_base64','notes']);$loan=$this->row('custody_loans','loan_id',$id);if((string)$loan['employee_id']!==$this->employee())throw HttpException::notFound();$asset=$this->row('custody_assets','asset_id',$loan['asset_id']);
        if($this->number($input['expected_revision']??null)!==(int)$loan['revision'])throw HttpException::conflict('Custody changed. Refresh first.');
        [$signature,$hash]=$this->signature($input['signature_base64']??null);$notes=$this->text($input['notes']??null,2000);$now=gmdate('Y-m-d H:i:s');
        if($action==='accept'&&$loan['state']==='PENDING_ACCEPTANCE'&&$asset['state']==='RESERVED'){
            if($this->row('employees','employee_id',$loan['employee_id'])['employment_status']!=='ACTIVE')throw HttpException::forbidden('An inactive employee cannot accept a new issue.');
            if($loan['due_at']<=$now)throw HttpException::conflict('The issue deadline passed; ask the issuer to cancel and reissue.');
            $this->db->execute("UPDATE custody_loans SET state='ISSUED',revision=revision+1,accepted_at=:now,accepted_by_user_id=:actor,recipient_signature=:signature,recipient_signature_hash=:hash WHERE company_id=:company AND loan_id=:id",['now'=>$now,'actor'=>$this->actor(),'signature'=>$signature,'hash'=>$hash,'company'=>$this->company(),'id'=>$id]);$state='ISSUED';
        }elseif($action==='return'&&$loan['state']==='ISSUED'&&$asset['state']==='ISSUED'){
            $this->db->execute("UPDATE custody_loans SET state='RETURN_PENDING',revision=revision+1,return_requested_at=:now,return_signature=:signature,return_signature_hash=:hash WHERE company_id=:company AND loan_id=:id",['now'=>$now,'signature'=>$signature,'hash'=>$hash,'company'=>$this->company(),'id'=>$id]);$state='RETURN_PENDING';
        }else throw HttpException::conflict('This custody action is not available.');
        $this->db->execute('UPDATE custody_assets SET state=:state,revision=revision+1 WHERE company_id=:company AND asset_id=:id',['state'=>$state,'company'=>$this->company(),'id'=>$asset['asset_id']]);$this->event((string)$asset['asset_id'],$id,strtoupper($action),$notes);return ['state'=>$state];
    });}
    public function manageLoan(string $id,string $action,array $input): array {return $this->atomic(function()use($id,$action,$input){
        $this->fields($input,['expected_revision','notes','signature_base64']);$loan=$this->row('custody_loans','loan_id',$id);$asset=$this->row('custody_assets','asset_id',$loan['asset_id']);$this->access->requireSite($asset['site_id']);if($this->number($input['expected_revision']??null)!==(int)$loan['revision'])throw HttpException::conflict('Custody changed. Refresh first.');$notes=$this->text($input['notes']??null,2000);
        if($action==='receive'&&$loan['state']==='RETURN_PENDING'){
            if((string)$loan['accepted_by_user_id']===$this->actor()||(string)($this->row('users','user_id',$this->actor())['employee_id']??'')===(string)$loan['employee_id'])throw HttpException::forbidden('A different logged-in operator must confirm physical return.');
            [$signature,$hash]=$this->signature($input['signature_base64']??null);$this->db->execute("UPDATE custody_loans SET state='RETURNED',revision=revision+1,received_at=:now,received_by_user_id=:actor,receiver_signature=:signature,receiver_signature_hash=:hash WHERE company_id=:company AND loan_id=:id",['now'=>gmdate('Y-m-d H:i:s'),'actor'=>$this->actor(),'signature'=>$signature,'hash'=>$hash,'company'=>$this->company(),'id'=>$id]);$assetState='AVAILABLE';
        }elseif($action==='cancel'&&$loan['state']==='PENDING_ACCEPTANCE'){$this->db->execute("UPDATE custody_loans SET state='CANCELLED',revision=revision+1 WHERE company_id=:company AND loan_id=:id",['company'=>$this->company(),'id'=>$id]);$assetState='AVAILABLE';
        }elseif($action==='lost'&&in_array($loan['state'],['ISSUED','RETURN_PENDING','PENDING_ACCEPTANCE'],true)){$this->db->execute("UPDATE custody_loans SET state='LOST',revision=revision+1 WHERE company_id=:company AND loan_id=:id",['company'=>$this->company(),'id'=>$id]);$assetState='LOST';$incidentUuid=uuid4();$this->db->execute('INSERT INTO incidents(incident_uuid,company_id,site_id,employee_id,reporter_user_id,incident_number,incident_type,incident_category,severity,occurred_at,description,created_by_user_id) VALUES(:uuid,:company,:site,:employee,:actor,:number,:type,:category,:severity,:now,:description,:creator)',['uuid'=>$incidentUuid,'company'=>$this->company(),'site'=>$asset['site_id'],'employee'=>$loan['employee_id'],'actor'=>$this->actor(),'number'=>'KEY-LOSS-'.$id.'-'.substr($incidentUuid,0,8),'type'=>$asset['asset_type']==='ACCESS_CARD'?'LOST_ACCESS_CARD':'LOST_KEY','category'=>'ACCESS_CONTROL','severity'=>'HIGH','now'=>gmdate('Y-m-d H:i:s'),'description'=>'Lost '.$asset['asset_code'].': '.$notes,'creator'=>$this->actor()]);$incident=(string)$this->db->connection()->lastInsertId();$this->audit->record('incident','created_from_key_loss',$incident);$notes=mb_substr($notes,0,1900);$notes.=' Linked incident #'.$incident;
        }else throw HttpException::conflict('This custody transition is not available.');
        $this->db->execute('UPDATE custody_assets SET state=:state,revision=revision+1 WHERE company_id=:company AND asset_id=:id',['state'=>$assetState,'company'=>$this->company(),'id'=>$asset['asset_id']]);$this->event((string)$asset['asset_id'],$id,strtoupper($action),$notes);return ['asset_state'=>$assetState];
    });}
    public function assetAction(string $id,array $input): array {return $this->atomic(function()use($id,$input){
        $this->fields($input,['expected_revision','action','notes']);$asset=$this->row('custody_assets','asset_id',$id);$this->access->requireSite($asset['site_id']);if($this->number($input['expected_revision']??null)!==(int)$asset['revision'])throw HttpException::conflict('Asset changed.');
        $action=$input['action']??null;if($action==='recover'&&$asset['state']==='LOST'){$state='AVAILABLE';$this->db->execute("UPDATE custody_loans SET state='RECOVERED',revision=revision+1 WHERE company_id=:company AND asset_id=:asset AND state='LOST'",['company'=>$this->company(),'asset'=>$id]);}elseif($action==='retire'&&in_array($asset['state'],['AVAILABLE','LOST'],true))$state='RETIRED';else throw HttpException::conflict('Recover a lost item or retire an available/lost item.');
        $this->db->execute('UPDATE custody_assets SET state=:state,revision=revision+1 WHERE company_id=:company AND asset_id=:id',['state'=>$state,'company'=>$this->company(),'id'=>$id]);$this->event($id,null,strtoupper($action),$this->text($input['notes']??null,2000));return ['state'=>$state];
    });}
    public function history(string $id): array {$asset=$this->row('custody_assets','asset_id',$id);$this->access->requireSite($asset['site_id']);return $this->db->fetchAll('SELECT * FROM custody_events WHERE company_id=:company AND asset_id=:asset ORDER BY event_id',['company'=>$this->company(),'asset'=>$id]);}
    public function receipt(string $id,bool $self=false): array {
        $loan=$this->row('custody_loans','loan_id',$id);$asset=$this->row('custody_assets','asset_id',$loan['asset_id']);
        if($self){if((string)$loan['employee_id']!==$this->employee())throw HttpException::notFound();}else $this->access->requireSite($asset['site_id']);
        $signatures=[];foreach(['recipient','return','receiver']as$kind){$encoded=$loan[$kind.'_signature'];if($encoded===null)continue;$bytes=base64_decode($encoded,true);if($bytes===false||!hash_equals((string)$loan[$kind.'_signature_hash'],hash('sha256',$bytes)))throw HttpException::conflict('Custody signature failed integrity verification.');$signatures[$kind]=['image_base64'=>$encoded,'sha256'=>$loan[$kind.'_signature_hash']];}
        $this->audit->record('custody_receipt','viewed',$id);return ['loan_id'=>$id,'asset_code'=>$asset['asset_code'],'employee_id'=>$loan['employee_id'],'state'=>$loan['state'],'issued_at'=>$loan['issued_at'],'accepted_at'=>$loan['accepted_at'],'return_requested_at'=>$loan['return_requested_at'],'received_at'=>$loan['received_at'],'signatures'=>$signatures];
    }
}
