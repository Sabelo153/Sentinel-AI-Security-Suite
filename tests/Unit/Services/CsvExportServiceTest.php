<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Sentinel\Services\CsvExportService;

final class CsvExportServiceTest extends TestCase
{
    public function testCsvUsesStableColumnsEscapesQuotesAndNeutralizesFormulas(): void
    {
        $csv = (new CsvExportService())->export([['name' => '=SUM(A1:A2)', 'note' => 'He said "hello"']]);
        self::assertSame("name,note\n'=SUM(A1:A2),\"He said \"\"hello\"\"\"\n", $csv);
    }

    public function testCsvCanEmitHeadersForAnEmptyReport(): void
    {
        self::assertSame("employee_id,status\n", (new CsvExportService())->export([], ['employee_id', 'status']));
    }
}