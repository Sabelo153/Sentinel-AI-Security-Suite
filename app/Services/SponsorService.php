<?php
declare(strict_types=1);
namespace Sentinel\Services;
use Sentinel\Core\Config;
final class SponsorService
{
    public function __construct(private readonly SubscriptionService $subscriptions,private readonly Config $config) {}
    public function placement():array {$subscription=$this->subscriptions->current();if(($subscription['plan_code']??null)!=='FREE'||!$this->config->get('sponsors.enabled',true))return ['enabled'=>false,'plan_code'=>$subscription['plan_code']??null,'campaign'=>null];$c=$this->config->get('sponsors.campaign',[]);foreach(['id','title','body','cta','url']as$k)if(!is_string($c[$k]??null)||trim($c[$k])===''||strlen($c[$k])>1000)return ['enabled'=>false,'campaign'=>null];$url=$c['url'];$relative=str_starts_with($url,'/')&&!str_starts_with($url,'//')&&!str_contains($url,'\\')&&!preg_match('/[\x00-\x20]/',$url);$p=parse_url($url);$external=filter_var($url,FILTER_VALIDATE_URL)&&($p['scheme']??'')==='https'&&!isset($p['user'])&&!isset($p['pass']);if(!$relative&&!$external)return ['enabled'=>false,'campaign'=>null];return ['enabled'=>true,'plan_code'=>'FREE','campaign'=>array_intersect_key($c,array_flip(['id','title','body','cta','url'])),'label'=>'Sponsored','refresh_after_seconds'=>15];}
}
