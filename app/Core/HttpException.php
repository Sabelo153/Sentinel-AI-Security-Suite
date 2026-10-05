<?php

declare(strict_types=1);

namespace Sentinel\Core;

/**
 * Controlled HTTP-facing exception. The constructor message is always safe to
 * show to a client; internal detail must go through the optional previous
 * exception / logger instead, never into $message.
 */
class HttpException extends \RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $safeMessage,
        private readonly ?string $errorCode = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($safeMessage, 0, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public static function badRequest(string $message = 'Bad request.', ?string $errorCode = null): self
    {
        return new self(400, $message, $errorCode);
    }

    public static function unauthorized(string $message = 'Unauthorized.', ?string $errorCode = null): self
    {
        return new self(401, $message, $errorCode);
    }

    public static function forbidden(string $message = 'Forbidden.', ?string $errorCode = null): self
    {
        return new self(403, $message, $errorCode);
    }

    public static function notFound(string $message = 'Not found.', ?string $errorCode = null): self
    {
        return new self(404, $message, $errorCode);
    }

    public static function methodNotAllowed(string $message = 'Method not allowed.', ?string $errorCode = null): self
    {
        return new self(405, $message, $errorCode);
    }

    public static function unprocessable(string $message = 'Unprocessable entity.', ?string $errorCode = null): self
    {
        return new self(422, $message, $errorCode);
    }

    public static function conflict(string $message = 'Conflict.', ?string $errorCode = null): self
    {
        return new self(409, $message, $errorCode);
    }

    public static function tooManyRequests(string $message = 'Too many requests.', ?string $errorCode = null): self
    {
        return new self(429, $message, $errorCode);
    }

    public static function serverError(string $message = 'Internal server error.', ?string $errorCode = null): self
    {
        return new self(500, $message, $errorCode);
    }

    public static function serviceUnavailable(string $message = 'Service unavailable.', ?string $errorCode = null): self
    {
        return new self(503, $message, $errorCode);
    }
}
