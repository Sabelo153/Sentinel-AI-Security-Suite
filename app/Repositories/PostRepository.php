<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class PostRepository extends Repository
{
    private const COLUMNS = 'p.post_id, p.post_uuid, p.company_id, p.contract_id, p.site_id, p.post_name, p.description, p.gps_lat, p.gps_long, p.status, p.created_by_user_id, p.updated_by_user_id, p.created_at, p.updated_at, p.deleted_at';
    public function __construct(Database $db, ?RuntimeContext $context = null) { parent::__construct($db, $context); }
    protected function table(): string { return 'posts'; }
    public function listForCompany():array{$companyId=$this->requireCompanyId();return$this->db->fetchAll('SELECT '.self::COLUMNS.' FROM posts p WHERE p.company_id = :company_id AND p.deleted_at IS NULL ORDER BY p.post_name,p.post_id',['company_id'=>$companyId]);}
    public function findByIdForCompany(string $id):?array{$companyId=$this->requireCompanyId();return$this->db->fetch('SELECT '.self::COLUMNS.' FROM posts p WHERE p.post_id = :id AND p.company_id = :company_id AND p.deleted_at IS NULL LIMIT 1',['id'=>$id,'company_id'=>$companyId]);}
    public function siteBelongsToCompany(string $id):bool{$companyId=$this->requireCompanyId();return$this->db->fetch('SELECT site_id FROM sites WHERE site_id = :id AND company_id = :company_id LIMIT 1',['id'=>$id,'company_id'=>$companyId])!==null;}
    public function contractBelongsToCompany(string $id):bool{$companyId=$this->requireCompanyId();return$this->db->fetch('SELECT contract_id FROM contracts WHERE contract_id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',['id'=>$id,'company_id'=>$companyId])!==null;}
    public function createForCompany(array $data):array{$companyId=$this->requireCompanyId();$this->db->execute('INSERT INTO posts (post_uuid,company_id,contract_id,site_id,post_name,description,gps_lat,gps_long,status) VALUES (:uuid,:company_id,:contract_id,:site_id,:name,:description,:lat,:long,:status)',['uuid'=>uuid4(),'company_id'=>$companyId,'contract_id'=>$data['contract_id']??null,'site_id'=>$data['site_id'],'name'=>$data['post_name'],'description'=>$data['description']??null,'lat'=>$data['gps_lat']??null,'long'=>$data['gps_long']??null,'status'=>$data['status']??'ACTIVE']);$id=(string)$this->db->connection()->lastInsertId();$created=$this->findByIdForCompany($id);if($created===null)throw new \RuntimeException('Created post could not be loaded.');return$created;}
    public function updateForCompany(string $id,array $data):?array{$companyId=$this->requireCompanyId();$sets=[];$p=['id'=>$id,'company_id'=>$companyId];foreach(['contract_id','site_id','post_name','description','gps_lat','gps_long','status']as$f)if(array_key_exists($f,$data)){$sets[]=$f.' = :'.$f;$p[$f]=$data[$f];}if($sets!==[])$this->db->execute('UPDATE posts SET '.implode(', ',$sets).' WHERE post_id = :id AND company_id = :company_id AND deleted_at IS NULL',$p);return$this->findByIdForCompany($id);}
}