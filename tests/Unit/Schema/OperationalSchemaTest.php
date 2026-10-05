<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Schema;

use PHPUnit\Framework\TestCase;

final class OperationalSchemaTest extends TestCase
{
    public function testNewOperationalSchemasAreRegisteredAndUseCanonicalIdentity(): void
    {
        $root = dirname(__DIR__, 3);
        $runner = (string) file_get_contents($root . '/database/schema/000_run_all.sql');
        foreach (range(53, 62) as $number) {
            $files = glob($root . '/database/schema/' . str_pad((string) $number, 3, '0', STR_PAD_LEFT) . '_*.sql');
            self::assertCount(1, $files);
            $file = basename($files[0]); $sql = (string) file_get_contents($files[0]);
            self::assertStringContainsString('SOURCE ' . $file . ';', $runner);
            self::assertStringContainsString('company_id', $sql);
            self::assertDoesNotMatchRegularExpression('/\btenant_id\b|\bguard_id\b/i', $sql);
        }
    }

    public function testOperationalHistoryTablesHaveRequiredForeignKeysAndRetentionPolicies(): void
    {
        $root = dirname(__DIR__, 3);
        $sql = (string) file_get_contents($root . '/database/schema/053_devices.sql') . (string) file_get_contents($root . '/database/schema/054_device_assignments.sql') . (string) file_get_contents($root . '/database/schema/055_device_sessions.sql') . (string) file_get_contents($root . '/database/schema/056_incidents.sql') . (string) file_get_contents($root . '/database/schema/057_occurrence_book.sql') . (string) file_get_contents($root . '/database/schema/062_notifications.sql');
        self::assertStringContainsString('REFERENCES companies(company_id)', $sql);
        self::assertStringContainsString('REFERENCES employees(employee_id)', $sql);
        self::assertStringContainsString('REFERENCES shift_assignments(shift_assignment_id)', $sql);
        self::assertStringContainsString('REFERENCES patrol_sessions(patrol_session_id)', $sql);
        self::assertStringContainsString('ON DELETE RESTRICT', $sql);
        self::assertStringContainsString('ON DELETE SET NULL', $sql);
    }
}