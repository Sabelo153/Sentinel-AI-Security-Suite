<?php

declare(strict_types=1);

namespace Sentinel\Core;

/**
 * Immutable HTTP request snapshot. Carries no auth/tenant state — that is
 * layered on top by RuntimeContext once authentication runs.
 */
final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers lower-cased header names
     * @param array<string, mixed> $server
     */
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $headers,
        private readonly ?string $rawBody,
        private readonly array $server,
        private readonly ?string $requestId = null,
    ) {
    }

    /**
     * Build the request once from PHP superglobals + php://input. Reads the
     * request body exactly once to avoid exhausting the input stream.
     */
    public static function fromGlobals(): self
    {
        $server = $_SERVER;
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $rawBody = file_get_contents('php://input');

        return new self(
            $method,
            $path,
            $_GET,
            self::headersFromServer($server),
            $rawBody === false || $rawBody === '' ? null : $rawBody,
            $server,
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     * @param array<string, mixed> $server
     */
    public static function create(
        string $method,
        string $path,
        array $query = [],
        array $headers = [],
        ?string $body = null,
        array $server = [],
    ): self {
        return new self(strtoupper($method), $path, $query, self::normalizeHeaders($headers), $body, $server);
    }

    public function withRequestId(string $requestId): self
    {
        return new self($this->method, $this->path, $this->query, $this->headers, $this->rawBody, $this->server, $requestId);
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function queryParams(): array
    {
        return $this->query;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): ?string
    {
        return $this->rawBody;
    }

    public function assertJsonBodyIsAcceptable(int $maximumBytes): void
    {
        if ($this->rawBody === null) {
            return;
        }
        if (strlen($this->rawBody) > $maximumBytes) {
            throw new HttpException(413, 'Request body is too large.', 'request_too_large');
        }

        $contentType = strtolower(trim(explode(';', (string) $this->header('content-type'))[0]));
        if ($contentType !== '' && $contentType !== 'application/json' && !str_ends_with($contentType, '+json')) {
            throw new HttpException(415, 'Request content type must be application/json.', 'unsupported_media_type');
        }
    }

    /**
     * Decode a JSON body. Missing body decodes to an empty array; malformed
     * JSON throws rather than silently returning corrupted/partial data.
     *
     * @return array<mixed>
     */
    public function json(): array
    {
        if ($this->rawBody === null) {
            return [];
        }

        try {
            $decoded = json_decode($this->rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new HttpException(400, 'Request body is not valid JSON.', 'invalid_json', $e);
        }

        if (!is_array($decoded)) {
            throw new HttpException(400, 'Request body must be a JSON object or array.', 'invalid_json');
        }

        return $decoded;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private static function headersFromServer(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($name)] = (string) $value;
            }
        }

        if (isset($server['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $server['CONTENT_TYPE'];
        }
        if (isset($server['CONTENT_LENGTH'])) {
            $headers['content-length'] = (string) $server['CONTENT_LENGTH'];
        }

        return $headers;
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[strtolower($key)] = $value;
        }

        return $normalized;
    }
}
