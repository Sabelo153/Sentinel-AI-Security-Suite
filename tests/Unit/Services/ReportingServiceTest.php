<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\ReportingRepository;
use Sentinel\Services\CsvExportService;
use Sentinel\Services\ReportingService;

final class ReportingServiceTest extends TestCase
{
    private function service(): ReportingService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE attendance (attendance_id INTEGER, company_id INTEGER, employee_id INTEGER, site_id INTEGER, shift_date TEXT, clock_in_at TEXT, clock_out_at TEXT, attendance_status TEXT, source TEXT)');
        $db->execute('CREATE TABLE employees (employee_id INTEGER, company_id INTEGER)'); $db->execute('CREATE TABLE sites (site_id INTEGER, company_id INTEGER)');
        $db->execute("INSERT INTO employees VALUES (7, 101), (8, 202)"); $db->execute("INSERT INTO sites VALUES (4, 101), (5, 202)");
        $db->execute("INSERT INTO attendance VALUES (1, 101, 7, 4, '2026-09-01', NULL, NULL, 'PRESENT', 'API'), (2, 101, 7, 4, '2026-09-02', NULL, NULL, 'LATE', 'API')");
        $context = new RuntimeContext(); $context->setTenantId(101);
        return new ReportingService(new ReportingRepository($db, $context), new CsvExportService());
    }

    public function testAttendanceFiltersAreValidatedAndTenantOwned(): void
    {
        $service = $this->service();
        self::assertCount(1, $service->report('attendance', ['employee_id' => 7, 'date_from' => '2026-09-02', 'date_to' => '2026-09-02']));
        foreach ([['company_id' => 202], ['employee_id' => 8], ['status' => 'INVALID'], ['limit' => 1001]] as $filters) {
            try { $service->report('attendance', $filters); self::fail('Expected report filter rejection.'); } catch (HttpException $exception) { self::assertContains($exception->statusCode(), [404, 422]); }
        }
    }
}