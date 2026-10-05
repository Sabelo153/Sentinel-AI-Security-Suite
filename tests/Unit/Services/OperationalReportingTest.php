<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Services;
use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Config,Database,HttpException,RuntimeContext};
use Sentinel\Services\{OperationalReportingService,CsvExportService};
final class OperationalReportingTest extends TestCase
{
    public function testReportsAreTenantScopedBoundedAndCsvSafe():void
    {
        $db=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:',null,null,[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE occurrence_entries (occurrence_entry_id INTEGER,company_id INTEGER,entry_reference TEXT,site_id INTEGER,entry_type TEXT,occurred_at TEXT,handover_required INTEGER)');
        $db->execute("INSERT INTO occurrence_entries VALUES (1,101,'=FORMULA',1,'HANDOVER','2026-09-14 08:00:00',1),(2,202,'SECRET',2,'HANDOVER','2026-09-14 08:00:00',1),(3,101,'NEXT',1,'NOTE','2026-09-15 00:00:00',0)");
        $context=new RuntimeContext();$context->setTenantId(101);$service=new OperationalReportingService($db,$context,new CsvExportService());
        $filters=['date_from'=>'2026-09-14','date_to'=>'2026-09-14','site_id'=>1];$rows=$service->report('occurrence-book',$filters);
        self::assertCount(1,$rows);self::assertSame(1,$rows[0]['occurrence_entry_id']);
        self::assertStringContainsString("'=FORMULA",$service->csv('occurrence-book',$filters));self::assertStringNotContainsString('SECRET',$service->csv('occurrence-book',[]));
        self::assertCount(1,$service->report('occurrence-book',['limit'=>1]));
        foreach([['company_id'=>202],['limit'=>1001],['date_from'=>'2026-02-30'],['status'=>'OPEN']]as$bad){try{$service->report('occurrence-book',$bad);self::fail('Expected validation error');}catch(HttpException $e){self::assertSame(422,$e->statusCode());}}
    }
    public function testDashboardCountsOnlyCurrentCompany():void
    {
        $db=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:',null,null,[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]));
        foreach(['incidents (company_id INTEGER,status TEXT,deleted_at TEXT)','occurrence_entries (company_id INTEGER)','visitor_visits (company_id INTEGER,visit_status TEXT)','inspections (company_id INTEGER,status TEXT,deleted_at TEXT)','alerts (company_id INTEGER,status TEXT)']as$s)$db->execute('CREATE TABLE '.$s);
        $db->execute("INSERT INTO incidents VALUES (101,'OPEN',NULL),(202,'OPEN',NULL)");$db->execute('INSERT INTO occurrence_entries VALUES (101),(202)');$db->execute("INSERT INTO visitor_visits VALUES (101,'CHECKED_IN'),(202,'CHECKED_IN')");$db->execute("INSERT INTO inspections VALUES (101,'IN_PROGRESS',NULL),(202,'IN_PROGRESS',NULL)");$db->execute("INSERT INTO alerts VALUES (101,'OPEN'),(202,'OPEN')");
        $context=new RuntimeContext();$context->setTenantId(101);$summary=(new OperationalReportingService($db,$context,new CsvExportService()))->summary();self::assertCount(5,$summary);self::assertSame([1,1,1,1,1],array_values($summary));
    }
}
