<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Services;
use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Application, Config, Database, HttpException, RuntimeContext};
use Sentinel\Repositories\{OperationalRepository, AuditRepository};
use Sentinel\Services\{InspectionManagementService, AuditService};
final class InspectionManagementTest extends TestCase
{
    private function fixture(): array
    {
        $db = new Database(new Config([]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]));
        foreach ([
            'sites (site_id INTEGER,company_id INTEGER,deleted_at TEXT)','employees (employee_id INTEGER,company_id INTEGER)','users (user_id INTEGER,company_id INTEGER)',
            'inspections (inspection_id INTEGER PRIMARY KEY AUTOINCREMENT,inspection_uuid TEXT,company_id INTEGER,site_id INTEGER,inspector_employee_id INTEGER,inspector_user_id INTEGER,inspection_type TEXT,status TEXT,overall_outcome TEXT,scheduled_at TEXT,started_at TEXT,completed_at TEXT,findings_summary TEXT,corrective_action_summary TEXT,follow_up_at TEXT,notes TEXT,created_by_user_id INTEGER,updated_by_user_id INTEGER,deleted_at TEXT)',
            'inspection_items (inspection_item_id INTEGER PRIMARY KEY AUTOINCREMENT,company_id INTEGER,inspection_id INTEGER,checklist_code TEXT,checklist_label TEXT,item_result TEXT,finding TEXT,corrective_action TEXT,item_status TEXT,due_at TEXT,resolved_at TEXT,UNIQUE(inspection_id,checklist_code))',
            'audit_logs (audit_log_id INTEGER PRIMARY KEY AUTOINCREMENT,company_id INTEGER,user_id INTEGER,entity_name TEXT,entity_id TEXT,action_name TEXT,action_status TEXT,ip_address TEXT,user_agent TEXT,old_values TEXT,new_values TEXT,metadata TEXT)',
        ] as $schema) $db->execute('CREATE TABLE '.$schema);
        foreach (['sites'=>'site_id','employees'=>'employee_id','users'=>'user_id'] as $table=>$key) $db->execute("INSERT INTO $table ($key,company_id) VALUES (1,101),(2,202)");
        $context = new RuntimeContext(); $context->setTenantId(101); $context->setUser('1');
        return [$db,$context,new InspectionManagementService(new OperationalRepository($db,$context),$context,new AuditService(new AuditRepository($db,$context),$context))];
    }
    private function assertHttpStatus(int $status, callable $operation): void { try { $operation(); self::fail('Expected HTTP error'); } catch (HttpException $error) { self::assertSame($status,$error->statusCode()); } }
    public function testChecklistCompletionAndSeparateReview(): void
    {
        [$db,,$service] = $this->fixture();
        $id = (string) $service->createInspection(['site_id'=>1,'inspection_type'=>'SAFETY'])['inspection_id'];
        $this->assertHttpStatus(409,fn()=>$service->transition($id,['status'=>'COMPLETED']));
        $service->transition($id,['status'=>'IN_PROGRESS','occurred_at'=>'2026-09-14 08:00:00']);
        $this->assertHttpStatus(422,fn()=>$service->transition($id,['status'=>'COMPLETED']));
        $item = $service->saveItem($id,null,['checklist_code'=>'EXIT','checklist_label'=>'Exit clear','item_result'=>'PASS']);
        $service->updateInspection($id,['overall_outcome'=>'PASS']);
        $this->assertHttpStatus(422,fn()=>$service->transition($id,['status'=>'COMPLETED','occurred_at'=>'2026-09-14 07:00:00']));
        self::assertSame('COMPLETED',$service->transition($id,['status'=>'COMPLETED','occurred_at'=>'2026-09-14 09:00:00'])['status']);
        $this->assertHttpStatus(409,fn()=>$service->saveItem($id,(string)$item['inspection_item_id'],['finding'=>'Late change']));
        $this->assertHttpStatus(422,fn()=>$service->transition($id,['status'=>'REVIEWED']));
        self::assertSame('REVIEWED',$service->transition($id,[],true)['status']);
        self::assertCount(6,$db->fetchAll('SELECT * FROM audit_logs'));
    }
    public function testTenantAndParentIsolation(): void
    {
        [,$context,$service] = $this->fixture();
        $id = (string)$service->createInspection(['site_id'=>1,'inspection_type'=>'SAFETY'])['inspection_id'];
        $other = (string)$service->createInspection(['site_id'=>1,'inspection_type'=>'SAFETY'])['inspection_id'];
        $item = $service->saveItem($id,null,['checklist_code'=>'A','checklist_label'=>'A']);
        $this->assertHttpStatus(404,fn()=>$service->saveItem($other,(string)$item['inspection_item_id'],['item_result'=>'PASS']));
        foreach (['site_id','inspector_employee_id','inspector_user_id'] as $key) $this->assertHttpStatus(404,fn()=>$service->createInspection(['site_id'=>1,'inspection_type'=>'SAFETY',$key=>2]));
        $context->setTenantId(202); $context->setUser('2');
        self::assertSame([],$service->listInspections([]));
        $this->assertHttpStatus(404,fn()=>$service->getInspection($id));
        $this->assertHttpStatus(404,fn()=>$service->items($id));
        $this->assertHttpStatus(404,fn()=>$service->transition($id,['status'=>'IN_PROGRESS']));
        $this->assertHttpStatus(404,fn()=>$service->updateInspection($id,['notes'=>'Attempt']));
    }
    public function testValidationDuplicateChecklistAndAuditRollback(): void
    {
        [$db,,$service] = $this->fixture();
        $this->assertHttpStatus(422,fn()=>$service->createInspection(['site_id'=>1,'inspection_type'=>'A','company_id'=>202]));
        $id = (string)$service->createInspection(['site_id'=>1,'inspection_type'=>'A'])['inspection_id'];
        $this->assertHttpStatus(422,fn()=>$service->saveItem($id,null,['checklist_code'=>'A','checklist_label'=>'A','item_result'=>'FAIL']));
        $service->saveItem($id,null,['checklist_code'=>'A','checklist_label'=>'A']);
        $this->assertHttpStatus(409,fn()=>$service->saveItem($id,null,['checklist_code'=>'A','checklist_label'=>'A']));
        $db->execute('DROP TABLE audit_logs');
        $this->assertHttpStatus(503,fn()=>$service->transition($id,['status'=>'IN_PROGRESS']));
        self::assertSame('DRAFT',$service->getInspection($id)['status']);
    }
    public function testRoutesAndReviewPermission(): void
    {
        $routes = Application::create(dirname(__DIR__,3))->bootstrap()->router()->securityMetadata();
        $routes = array_values(array_filter($routes,static fn(array $r):bool=>str_starts_with($r['path'],'/api/v1/inspections')));
        self::assertCount(9,$routes);
        foreach($routes as $route){ self::assertTrue($route['auth']); self::assertTrue($route['tenant']); if(str_ends_with($route['path'],'/review')) self::assertSame('inspections.review',$route['permission']); }
    }
}
