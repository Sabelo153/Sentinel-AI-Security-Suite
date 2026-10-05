<?php

declare(strict_types=1);

namespace Sentinel\Core;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;

/**
 * Thin Monolog wrapper. Redacts likely-sensitive context keys (passwords,
 * tokens, secrets) before they ever reach a handler.
 */
final class Logger
{
    private const SENSITIVE_KEY_FRAGMENTS = [
        'password', 'secret', 'token', 'authorization', 'jwt', 'api_key', 'apikey',
    ];

    private const REDACTED = '***REDACTED***';

    private Monolog $monolog;

    public function __construct(Config $config, ?HandlerInterface $handler = null)
    {
        $channel = (string) $config->get('logging.channel', 'application');
        $this->monolog = new Monolog($channel);
        $this->monolog->pushHandler($handler ?? new StreamHandler('php://stderr', $this->resolveLevel(
            (string) $config->get('logging.level', 'info')
        )));
    }

    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void
    {
        $this->monolog->debug($message, $this->redact($context));
    }

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void
    {
        $this->monolog->info($message, $this->redact($context));
    }

    /** @param array<string, mixed> $context */
    public function notice(string $message, array $context = []): void
    {
        $this->monolog->notice($message, $this->redact($context));
    }

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void
    {
        $this->monolog->warning($message, $this->redact($context));
    }

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void
    {
        $this->monolog->error($message, $this->redact($context));
    }

    /** @param array<string, mixed> $context */
    public function critical(string $message, array $context = []): void
    {
        $this->monolog->critical($message, $this->redact($context));
    }

    private function resolveLevel(string $level): Level
    {
        return match (strtolower($level)) {
            'debug' => Level::Debug,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Info,
        };
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function redact(array $context): array
    {
        $redacted = [];

        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $redacted[$key] = $this->redact($value);
                continue;
            }

            $redacted[$key] = $this->isSensitiveKey((string) $key) ? self::REDACTED : $value;
        }

        return $redacted;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
