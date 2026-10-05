<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\ReportingRepository;

final class ReportingRepositoryTest extends TestCase
{
    private function repository(): ReportingRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE attendance (attendance_id INTEGER, company_id INTEGER, employee_id INTEGER, site_id INTEGER, shift_date TEXT, clock_in_at TEXT, clock_out_at TEXT, attendance_status TEXT, source TEXT)');
        $db->execute("INSERT INTO attendance VALUES (1, 101, 7, 4, '2026-09-01', NULL, NULL, 'PRESENT', 'API'), (2, 202, 8, 5, '2026-09-01', NULL, NULL, 'PRESENT', 'API')");
        $context = new RuntimeContext(); $context->setTenantId(101);
        return new ReportingRepository($db, $context);
    }

    public function testAttendanceReportOnlyReturnsCurrentCompanyRows(): void
    {
        $rows = $this->repository()->report('attendance', ['limit' => 500]);
        self::assertSame([['attendance_id' => 1, 'employee_id' => 7, 'site_id' => 4, 'shift_date' => '2026-09-01', 'clock_in_at' => null, 'clock_out_at' => null, 'attendance_status' => 'PRESENT', 'source' => 'API']], $rows);
    }
}