<?php

declare(strict_types=1);

namespace Sentinel\Core;

/**
 * Converts exceptions at the HTTP process boundary into safe client responses.
 * Internal exception detail is logged and never included in the response body.
 */
final class HttpErrorResponder
{
    /** @param (callable(string): void)|null $logger */
    public static function respond(\Throwable $exception, ?callable $logger = null): Response
    {
        if ($exception instanceof HttpException) {
            return Response::json([
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode(),
            ], $exception->statusCode(), self::securityHeaders());
        }

        ($logger ?? static fn (string $message): bool => error_log($message))((string) $exception);

        return Response::json([
            'message' => 'Service temporarily unavailable.',
            'code' => 'service_unavailable',
        ], 503, self::securityHeaders());
    }

    /** @return array<string, string> */
    private static function securityHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'no-referrer',
        ];
    }
}
