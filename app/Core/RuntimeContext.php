<?php

declare(strict_types=1);

namespace Sentinel\Core;

/**
 * Per-request execution state. Instance-based on purpose: there is no static
 * global, so one context can never leak into another request/test.
 *
 * Tenant isolation is security-sensitive: no method here ever guesses,
 * defaults, or derives a tenant from request input. A tenant is only ever
 * established by an explicit setTenantId() call performed by authentication/
 * middleware code after it has verified the caller's access.
 */
final class RuntimeContext
{
    private ?Request $request = null;
    private mixed $user = null;
    private ?int $tenantId = null;
    private string $requestId;
    private readonly string $environment;

    public function __construct(?string $requestId = null, string $environment = 'production')
    {
        $this->requestId = $this->sanitizeRequestId($requestId);
        $this->environment = $environment;
    }

    public function request(): ?Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function user(): mixed
    {
        return $this->user;
    }

    public function setUser(mixed $user): void
    {
        $this->user = $user;
    }

    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function setTenantId(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Fail closed (403) rather than silently defaulting to any tenant when
     * tenant-scoped code runs before a tenant boundary has been established.
     */
    public function requireTenant(): int
    {
        if ($this->tenantId === null) {
            throw HttpException::forbidden('No tenant context has been established for this request.', 'tenant_required');
        }

        return $this->tenantId;
    }

    public function requestId(): string
    {
        return $this->requestId;
    }

    public function environment(): string
    {
        return $this->environment;
    }

    /**
     * Clear request/user/tenant state and issue a fresh correlation ID, for
     * process-reuse scenarios (e.g. workers). Environment is unaffected.
     */
    public function reset(): void
    {
        $this->request = null;
        $this->user = null;
        $this->tenantId = null;
        $this->requestId = uuid4();
    }

    /**
     * A caller-supplied request ID is only trusted if it looks like a
     * reasonable correlation identifier; otherwise one is generated.
     */
    private function sanitizeRequestId(?string $requestId): string
    {
        if ($requestId !== null && preg_match('/^[A-Za-z0-9._-]{1,128}$/', $requestId) === 1) {
            return $requestId;
        }

        return uuid4();
    }
}
