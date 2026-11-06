<?php
declare(strict_types=1);
namespace Sentinel\Services;
final class SafeWebhookTransport implements WebhookTransport
{
    public static function validateUrl(string $url):string {$p=parse_url($url);$host=strtolower($p['host']??'');if(strlen($url)>1000||!filter_var($url,FILTER_VALIDATE_URL)||($p['scheme']??'')!=='https'||isset($p['user'],$p['pass'])||isset($p['user'])||isset($p['pass'])||isset($p['query'])||isset($p['fragment'])||(isset($p['port'])&&(int)$p['port']!==443)||!preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\.[a-z]{2,63}$/D',$host)||str_ends_with($host,'.local')||str_ends_with($host,'.internal'))throw new \InvalidArgumentException('Use an HTTPS public DNS endpoint on port 443 without credentials, query or fragment.');return $host;}
    public function send(string $url,string $body,array $headers):array {
        try{$host=self::validateUrl($url);}catch(\InvalidArgumentException){return ['state'=>'FAILED','status'=>null,'result'=>'Invalid endpoint.'];}
        if(!function_exists('curl_init'))return ['state'=>'FAILED','status'=>null,'result'=>'PHP cURL is unavailable.'];$ips=gethostbynamel($host);if(!$ips)return ['state'=>'RETRY','status'=>null,'result'=>'DNS resolution failed.'];foreach($ips as$ip)if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))return ['state'=>'FAILED','status'=>null,'result'=>'Endpoint resolves to a non-public address.'];
        $h=curl_init($url);curl_setopt_array($h,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_HTTPHEADER=>$headers,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>8,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROXY=>'',CURLOPT_RESOLVE=>[$host.':443:'.$ips[0]],CURLOPT_WRITEFUNCTION=>static fn($curl,string $chunk):int=>strlen($chunk)]);$ok=curl_exec($h);$error=curl_errno($h);$status=(int)curl_getinfo($h,CURLINFO_RESPONSE_CODE);curl_close($h);
        if($ok===false)return ['state'=>in_array($error,[7,6],true)?'RETRY':'UNCERTAIN','status'=>$status?:null,'result'=>'Transport failure; delivery may be uncertain.'];return ['state'=>$status>=200&&$status<300?'ACCEPTED':($status>=500||$status===429?'RETRY':'FAILED'),'status'=>$status,'result'=>'HTTP response recorded.'];
    }
}
