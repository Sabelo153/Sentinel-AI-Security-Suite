<?php
declare(strict_types=1);
namespace Sentinel\Services;
use Sentinel\Core\HttpException;
final class ReportSchedule
{
    public const DEFAULT_TIMES=['00:00','06:00','12:00','18:00'];
    public static function times(mixed $times): array {
        if(!is_array($times)||!array_is_list($times)||count($times)<1||count($times)>24)throw HttpException::unprocessable('Choose between one and 24 daily report times.');
        foreach($times as$t)if(!is_string($t)||!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$t))throw HttpException::unprocessable('Use local 24-hour HH:MM report times.');
        if(count(array_unique($times))!==count($times))throw HttpException::unprocessable('Remove duplicate report times.');sort($times);return $times;
    }
    public static function zone(mixed $zone): \DateTimeZone {if(!is_string($zone)||!in_array($zone,\DateTimeZone::listIdentifiers(),true))throw HttpException::unprocessable('Choose an IANA time zone such as Africa/Johannesburg.');return new \DateTimeZone($zone);}
    public static function slot(\DateTimeImmutable $instant,string $zone,array $times,bool $previous=false): \DateTimeImmutable {
        $tz=self::zone($zone);$times=self::times($times);$local=$instant->setTimezone($tz);$slots=[];
        for($day=-3;$day<=3;$day++){$date=$local->modify(($day>=0?'+':'').$day.' days')->format('Y-m-d');foreach($times as$time){$value=\DateTimeImmutable::createFromFormat('!Y-m-d H:i',$date.' '.$time,$tz);if(!$value||$value->format('Y-m-d H:i')!==$date.' '.$time)continue;$stamp=$value->getTimestamp();if($previous?$stamp<$instant->getTimestamp():$stamp>$instant->getTimestamp())$slots[$stamp]=$value;}}
        if(!$slots)throw new \RuntimeException('No report schedule slot found.');ksort($slots);$value=$previous?end($slots):reset($slots);return $value->setTimezone(new \DateTimeZone('UTC'));
    }
}
