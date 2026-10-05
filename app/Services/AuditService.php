<?php

declare(strict_types=1);

namespace Sentinel\Services;

use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\AuditRepository;

final class AuditService
{
    private const SENSITIVE = ['password', 'password_hash', 'token', 'jwt', 'secret', 'mfa', 'authorization', 'api_key', 'apikey'];

    public function __construct(private readonly AuditRepository $audits, private readonly RuntimeContext $context) {}

    public function record(string $entity, string $action, ?string $entityId = null, array $oldValues = [], array $newValues = [], array $metadata = [], string $status = 'SUCCESS'): void
    {
        $request = $this->context->request();
        $this->audits->record([
            'user_id' => is_scalar($this->context->user()) ? (string) $this->context->user() : null,
            'entity_name' => $entity,
            'entity_id' => $entityId,
            'action_name' => $action,
            'action_status' => $status,
            'ip_address' => $request?->server('REMOTE_ADDR'),
            'user_agent' => $request?->header('user-agent'),
            'old_values' => $this->encode($oldValues),
            'new_values' => $this->encode($newValues),
            'metadata' => $this->encode($metadata),
        ]);
    }

    private function encode(array $values): ?string
    {
        if ($values === []) return null;
        return json_encode($this->redact($values), JSON_THROW_ON_ERROR);
    }

    private function redact(array $values): array
    {
        $redacted = [];
        foreach ($values as $key => $value) {
            $normalized = strtolower((string) $key);
            $sensitive = false;
            foreach (self::SENSITIVE as $fragment) if (str_contains($normalized, $fragment)) { $sensitive = true; break; }
            $redacted[$key] = $sensitive ? '***REDACTED***' : (is_array($value) ? $this->redact($value) : $value);
        }
        return $redacted;
    }
}