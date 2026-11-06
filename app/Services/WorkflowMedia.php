<?php
declare(strict_types=1);
namespace Sentinel\Services;
use Sentinel\Core\HttpException;
final class WorkflowMedia
{
    public static function image(mixed $encoded,bool $signature=false):array
    {
        $limit=$signature?65536:2097152;
        if(!is_string($encoded)||strlen($encoded)>(int)ceil($limit*4/3)+4)throw HttpException::unprocessable('Image is missing or exceeds its size limit.');
        $bytes=base64_decode($encoded,true);$size=$bytes===false?false:@getimagesizefromstring($bytes);
        if(!$size||strlen($bytes)>$limit||!in_array($size[2],$signature?[IMAGETYPE_PNG]:[IMAGETYPE_PNG,IMAGETYPE_JPEG],true)||$size[0]>($signature?1024:6000)||$size[1]>($signature?512:6000))throw HttpException::unprocessable($signature?'Use a drawn PNG signature up to 64 KiB and 1024 × 512 pixels.':'Use a JPEG/PNG photo up to 2 MiB and 6000 × 6000 pixels.');
        return ['base64'=>base64_encode($bytes),'sha256'=>hash('sha256',$bytes),'mime'=>$size['mime']];
    }
}
