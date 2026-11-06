<?php
declare(strict_types=1);
namespace Sentinel\Security;

/** RFC 6238 SHA-1 TOTP, 30-second steps. */
final class Totp
{
    public static function newSecret():string{
        $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$bits='';foreach(str_split(random_bytes(20))as$byte)$bits.=str_pad(decbin(ord($byte)),8,'0',STR_PAD_LEFT);
        $result='';foreach(str_split($bits,5)as$chunk)$result.=$alphabet[bindec(str_pad($chunk,5,'0'))];return $result;
    }
    public static function code(string $secret,int $counter,int $digits=6):string{
        $bits='';$alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';foreach(str_split($secret)as$char){$index=strpos($alphabet,$char);if($index===false)throw new \InvalidArgumentException('Invalid TOTP secret.');$bits.=str_pad(decbin($index),5,'0',STR_PAD_LEFT);}
        $key='';foreach(str_split($bits,8)as$chunk)if(strlen($chunk)===8)$key.=chr(bindec($chunk));
        $hash=hash_hmac('sha1',pack('N2',intdiv($counter,4294967296),$counter%4294967296),$key,true);$offset=ord($hash[19])&15;$binary=unpack('N',substr($hash,$offset,4))[1]&0x7fffffff;
        return str_pad((string)($binary%(10**$digits)),$digits,'0',STR_PAD_LEFT);
    }
    public static function counter(string $secret,string $code,?int $time=null):?int{
        if(preg_match('/^[0-9]{6}$/D',$code)!==1)return null;$counter=intdiv($time??time(),30);
        foreach([$counter,$counter-1,$counter+1]as$step)if($step>=0&&hash_equals(self::code($secret,$step),$code))return $step;return null;
    }
}
