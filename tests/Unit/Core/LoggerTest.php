<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Logger;

final class LoggerTest extends TestCase
{
    private function makeLogger(TestHandler $handler): Logger
    {
        $config = new Config(['logging' => ['channel' => 'test', 'level' => 'debug']]);

        return new Logger($config, $handler);
    }

    public function testInfoWritesRecordToHandler(): void
    {
        $handler = new TestHandler();
        $logger = $this->makeLogger($handler);

        $logger->info('User logged in', ['user_id' => 7]);

        self::assertTrue($handler->hasInfoThatContains('User logged in'));
        $record = $handler->getRecords()[0];
        self::assertSame(7, $record->context['user_id']);
    }

    public function testAllSeverityMethodsWriteAtExpectedLevel(): void
    {
        $handler = new TestHandler();
        $logger = $this->makeLogger($handler);

        $logger->debug('d');
        $logger->info('i');
        $logger->notice('n');
        $logger->warning('w');
        $logger->error('e');
        $logger->critical('c');

        self::assertTrue($handler->hasDebugThatContains('d'));
        self::assertTrue($handler->hasInfoThatContains('i'));
        self::assertTrue($handler->hasNoticeThatContains('n'));
        self::assertTrue($handler->hasWarningThatContains('w'));
        self::assertTrue($handler->hasErrorThatContains('e'));
        self::assertTrue($handler->hasCriticalThatContains('c'));
    }

    public function testRedactsSensitiveContextKeys(): void
    {
        $handler = new TestHandler();
        $logger = $this->makeLogger($handler);

        $logger->info('Login attempt', [
            'username' => 'jdoe',
            'password' => 'super-secret-value',
            'authorization' => 'Bearer super-secret-token',
            'jwt_secret' => 'another-secret',
        ]);

        $record = $handler->getRecords()[0];

        self::assertSame('jdoe', $record->context['username']);
        self::assertSame('***REDACTED***', $record->context['password']);
        self::assertSame('***REDACTED***', $record->context['authorization']);
        self::assertSame('***REDACTED***', $record->context['jwt_secret']);
        self::assertStringNotContainsString('super-secret-token', json_encode($record->context));
    }

    public function testRedactsNestedSensitiveKeys(): void
    {
        $handler = new TestHandler();
        $logger = $this->makeLogger($handler);

        $logger->info('Request received', [
            'headers' => ['authorization' => 'Bearer nested-secret'],
        ]);

        $record = $handler->getRecords()[0];

        self::assertSame('***REDACTED***', $record->context['headers']['authorization']);
    }
}
