<?php
declare(strict_types=1);
namespace Sentinel\Services;
use Sentinel\Core\HttpException;
trait DeliveryOperations
{
    use TenantOperations;
    private function history(string $kind,mixed $id,string $action,string $notes):void {$this->db->execute('INSERT INTO service_delivery_history(company_id,entity_kind,entity_id,action_name,notes,actor_user_id,occurred_at) VALUES(:company,:kind,:id,:action,:notes,:actor,:now)',['company'=>$this->company(),'kind'=>$kind,'id'=>$id,'action'=>$action,'notes'=>$notes,'actor'=>$this->actor(),'now'=>gmdate('Y-m-d H:i:s')]);$this->audit->record($kind,$action,(string)$id);}
    private function events(string $kind,mixed $id):array {return $this->db->fetchAll('SELECT action_name,notes,actor_user_id,occurred_at FROM service_delivery_history WHERE company_id=:company AND entity_kind=:kind AND entity_id=:id ORDER BY history_id',['company'=>$this->company(),'kind'=>$kind,'id'=>$id]);}
    private function revision(array $r,array $input):void {if($this->number($input['expected_revision']??null)!==(int)$r['revision'])throw HttpException::conflict('Record changed. Refresh before updating.');}
    private function mapped(mixed $client):void {if(!$this->db->fetch('SELECT user_id FROM client_portal_access WHERE company_id=:company AND user_id=:actor AND client_id=:client',['company'=>$this->company(),'actor'=>$this->actor(),'client'=>$client]))throw HttpException::notFound();}
    private function operator(mixed $id,mixed $site,string $permission):array {$u=$this->row('users','user_id',$id);$p=new \Sentinel\Repositories\PermissionRepository($this->db,$this->context);if(!(new SubcontractorPolicy($this->db,$this->context,$p))->allowed((string)$u['user_id'],$site)||$u['status']!=='ACTIVE'||!$p->userHasPermission((string)$u['user_id'],$permission)||($p->userHasPermission((string)$u['user_id'],'site_scope.restricted')&&!$this->db->fetch('SELECT grant_id FROM user_site_grants WHERE company_id=:company AND user_id=:user AND site_id=:site',['company'=>$this->company(),'user'=>$u['user_id'],'site'=>$site])))throw HttpException::unprocessable('Choose an active operator with the required permission and site access.');return $u;}
    private function strings(mixed $value,int $max=30):array {if(!is_array($value)||!array_is_list($value)||count($value)<1||count($value)>$max)throw HttpException::unprocessable('Provide a bounded non-empty list.');return array_map(fn($v)=>$this->text($v,1000),$value);}
}
