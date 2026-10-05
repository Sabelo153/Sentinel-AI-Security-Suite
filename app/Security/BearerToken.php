<?php

declare(strict_types=1);

namespace Sentinel\Security;

use Sentinel\Core\HttpException;
use Sentinel\Core\Request;

final class BearerToken
{
    public static function fromRequest(Request $request): string
    {
        $authorization = $request->header('authorization');
        if (!is_string($authorization) || preg_match('/^Bearer[ \t]+([^ \t]+)$/', $authorization, $matches) !== 1) {
            throw new HttpException(401, 'Authentication failed.', 'authentication_failed');
        }

        return $matches[1];
    }
}