<?php
declare(strict_types=1);
namespace Sentinel\Security;
use Sentinel\Core\HttpException;
final class NfcUid
{
    public static function normalize(mixed $value): string {
        if(!is_string($value)||strlen($value)>64)throw HttpException::unprocessable('Use the tag hardware UID in hexadecimal.');
        $uid=strtolower(preg_replace('/[:\s-]/','',$value));
        if(!preg_match('/^[a-f0-9]+$/D',$uid)||!in_array(strlen($uid),[8,14,16,20],true))throw HttpException::unprocessable('NFC UID must contain 4, 7, 8 or 10 hexadecimal bytes.');return $uid;
    }
}
