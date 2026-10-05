<?php
declare(strict_types=1);

// This check cannot connect to MySQL: the only connector is SQLite in memory.
require dirname(__DIR__).'/vendor/autoload.php';
use Sentinel\Core\{Config,Database,RuntimeContext};
use Sentinel\Repositories\OccurrenceBookRepository;

$db=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:',null,null,[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]));
$db->execute('CREATE TABLE occurrence_entries (occurrence_entry_id INTEGER PRIMARY KEY,company_id INTEGER,site_id INTEGER,entry_type TEXT,occurred_at TEXT,entry_text TEXT)');
$db->execute('CREATE INDEX idx_occurrence_company_site_occurred ON occurrence_entries(company_id,site_id,occurred_at)');
$db->beginTransaction();
for($i=1;$i<=10000;$i++)$db->execute('INSERT INTO occurrence_entries VALUES (:id,:company,1,\'NOTE\',:occurred,:text)',['id'=>$i,'company'=>$i<=5000?101:202,'occurred'=>'2026-09-14 '.sprintf('%02d:%02d:%02d',intdiv($i,3600)%24,intdiv($i,60)%60,$i%60),'text'=>str_repeat('training ',20)]);
$db->commit();$context=new RuntimeContext();$context->setTenantId(101);$repo=new OccurrenceBookRepository($db,$context);
$samples=[];
for($i=0;$i<55;$i++){
    $start=hrtime(true);$rows=$repo->listForCompany(['site_id'=>1]);$elapsed=(hrtime(true)-$start)/1e6;
    if(count($rows)!==500||array_unique(array_column($rows,'company_id'))!==[101])throw new RuntimeException('Tenant or output bound regression');
    if($i>=5)$samples[]=$elapsed;
}
sort($samples);
$plan=$db->fetchAll('EXPLAIN QUERY PLAN SELECT * FROM occurrence_entries WHERE company_id=101 AND site_id=1 ORDER BY occurred_at DESC,occurrence_entry_id DESC LIMIT 500');
$indexed=str_contains(json_encode($plan),'idx_occurrence_company_site_occurred');
if(!$indexed)throw new RuntimeException('Expected indexed query');
echo json_encode(['engine'=>'SQLite in-memory; not a production load test','rows'=>10000,'companies'=>2,'returned_rows'=>500,'samples'=>count($samples),'median_ms'=>$samples[25],'p95_ms'=>$samples[47],'max_ms'=>max($samples),'peak_memory_bytes'=>memory_get_peak_usage(true),'index_used'=>$indexed,'query_plan'=>$plan],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
