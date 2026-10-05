<?php
declare(strict_types=1);
namespace Sentinel\Services;
use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\PostRepository;
final class PostManagementService
{
    private const STATUSES=['ACTIVE','INACTIVE','ARCHIVED'];
    public function __construct(private readonly PostRepository $posts){}
    public function listPosts():array{try{return$this->posts->listForCompany();}catch(PDOException$e){throw$this->unavailable($e);}}
    public function getPost(string$id):array{try{$r=$this->posts->findByIdForCompany($id);}catch(PDOException$e){throw$this->unavailable($e);}if($r===null)throw HttpException::notFound('Post not found.','post_not_found');return$r;}
    public function createPost(array$i):array{$d=$this->validate($i,false);try{$this->refs($d);return$this->posts->createForCompany($d);}catch(HttpException$e){throw$e;}catch(PDOException$e){throw$this->unavailable($e);}}
    public function updatePost(string$id,array$i):array{$existing=$this->getPost($id);$d=$this->validate($i,true);$effective=array_merge($existing,$d);try{$this->refs($effective);$r=$this->posts->updateForCompany($id,$d);}catch(HttpException$e){throw$e;}catch(PDOException$e){throw$this->unavailable($e);}if($r===null)throw HttpException::notFound('Post not found.','post_not_found');return$r;}
    private function validate(array$i,bool$update):array{$allowed=['contract_id','site_id','post_name','description','gps_lat','gps_long','status'];$d=array_intersect_key($i,array_flip($allowed));if(array_diff(array_keys($i),array_keys($d))!==[])throw HttpException::unprocessable('Invalid post fields.','invalid_post_request');foreach(['contract_id','site_id']as$f)if(isset($d[$f])&&((!is_int($d[$f])&&!(is_string($d[$f])&&ctype_digit($d[$f])))||(int)$d[$f]<1))throw HttpException::unprocessable('Post relationship is invalid.','invalid_'.$f);if(!$update)foreach(['site_id','post_name']as$f)if(!isset($d[$f]))throw HttpException::unprocessable('Post field is required.','invalid_'.$f);if(isset($d['post_name'])&&(!is_string($d['post_name'])||$d['post_name']===''||strlen($d['post_name'])>255))throw HttpException::unprocessable('Post name is invalid.','invalid_post_name');foreach(['gps_lat'=>[-90,90],'gps_long'=>[-180,180]]as$f=>$r)if(isset($d[$f])&&(!is_numeric($d[$f])||(float)$d[$f]<$r[0]||(float)$d[$f]>$r[1]))throw HttpException::unprocessable('Post coordinate is invalid.','invalid_'.$f);if(isset($d['status'])&&!in_array($d['status'],self::STATUSES,true))throw HttpException::unprocessable('Post status is invalid.','invalid_post_status');return$d;}
    private function refs(array$d):void{if(!$this->posts->siteBelongsToCompany((string)$d['site_id']))throw HttpException::notFound('Site not found.','site_not_found');if(isset($d['contract_id'])&&!$this->posts->contractBelongsToCompany((string)$d['contract_id']))throw HttpException::notFound('Contract not found.','contract_not_found');}
    private function unavailable(PDOException$e):HttpException{return new HttpException(503,'Post service unavailable.','post_service_unavailable',$e);}
}