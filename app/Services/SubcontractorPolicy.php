<?php
declare(strict_types=1);
namespace Sentinel\Services;
use Sentinel\Core\{Database,RuntimeContext};
use Sentinel\Repositories\PermissionRepository;
final class SubcontractorPolicy
{
 public function __construct(private readonly Database $db,private readonly RuntimeContext $context,private readonly PermissionRepository $permissions) {}
 public function allowed(string $user,mixed $site=null):bool {try{$r=$this->db->fetch('SELECT c.* FROM subcontractor_users u JOIN subcontractor_companies c ON c.company_id=u.company_id AND c.subcontractor_id=u.subcontractor_id WHERE u.company_id=:company AND u.user_id=:user',['company'=>$this->context->requireTenant(),'user'=>$user]);if(!$r)return true;if(!$this->permissions->userHasPermission($user,'site_scope.restricted')||$r['state']!=='ACTIVE'||$r['licence_expires_on']<gmdate('Y-m-d')||$r['insurance_expires_on']<gmdate('Y-m-d'))return false;if($site===null)return true;return (bool)$this->db->fetch('SELECT approval_id FROM subcontractor_sites WHERE company_id=:company AND subcontractor_id=:id AND site_id=:site AND valid_until>=:today AND approved_revision=:revision',['company'=>$this->context->requireTenant(),'id'=>$r['subcontractor_id'],'site'=>$site,'today'=>gmdate('Y-m-d'),'revision'=>$r['revision']]);}catch(\PDOException){return false;}}
}
