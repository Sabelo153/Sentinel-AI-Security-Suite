<?php

declare(strict_types=1);

namespace Sentinel\Core;

/**
 * HTTP response value object. Nothing is emitted until send() is called,
 * keeping the class usable in unit tests without a live HTTP server.
 */
final class Response
{
    /**
     * @param array<string, string> $headers
     */
    private function __construct(
        private int $status,
        private array $headers,
        private string $body,
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        try {
            $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            throw new HttpException(500, 'Failed to encode response body.', 'json_encode_failed', $e);
        }

        $headers['Content-Type'] = 'application/json; charset=utf-8';

        return new self($status, $headers, $body);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function raw(string $body, int $status = 200, array $headers = []): self
    {
        return new self($status, $headers, $body);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function withStatus(int $status): self
    {
        $clone = clone $this;
        $clone->status = $status;

        return $clone;
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * Emit status/headers/body. This is the only method with side effects.
     *
     * $statusEmitter/$headerEmitter are test seams: PHP's CLI SAPI does not
     * record real headers, so unit tests inject capturing callables instead
     * of relying on the (unavailable) global header state.
     *
     * @param (callable(int): void)|null $statusEmitter
     * @param (callable(string, string): void)|null $headerEmitter
     */
    public function send(?callable $statusEmitter = null, ?callable $headerEmitter = null): void
    {
        $statusEmitter ??= static function (int $status): void {
            if (!headers_sent()) {
                http_response_code($status);
            }
        };
        $headerEmitter ??= static function (string $name, string $value): void {
            if (!headers_sent()) {
                header("{$name}: {$value}");
            }
        };

        $statusEmitter($this->status);
        foreach ($this->headers as $name => $value) {
            $headerEmitter($name, $value);
        }

        echo $this->body;
    }
}
