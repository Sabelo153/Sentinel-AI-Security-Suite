<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\GuardSiteAssignmentRepository;
use Sentinel\Services\GuardSiteAssignmentManagementService;

final class GuardSiteAssignmentManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): GuardSiteAssignmentManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY, company_id INTEGER, first_name TEXT, last_name TEXT, phone TEXT, email TEXT)'); $db->execute('INSERT INTO people VALUES (1,101,\'Guard\',\'One\',NULL,NULL),(2,101,\'Supervisor\',\'Two\',NULL,NULL),(3,202,\'Other\',\'Guard\',NULL,NULL)');
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, company_id INTEGER, person_id INTEGER, employee_number TEXT, psira_number TEXT, employment_status TEXT)'); $db->execute('INSERT INTO employees VALUES (1,101,1,\'E1\',NULL,\'ACTIVE\'),(2,101,2,\'E2\',\'PSIRA-ONLY\',\'ACTIVE\'),(3,202,3,\'E3\',NULL,\'ACTIVE\')');
        $db->execute('CREATE TABLE positions (position_id INTEGER PRIMARY KEY, company_id INTEGER, position_code TEXT, position_name TEXT, position_category TEXT, position_status TEXT, deleted_at TEXT)'); $db->execute('INSERT INTO positions VALUES (1,101,\'G\',\'Guard\',\'GUARD\',\'ACTIVE\',NULL),(2,101,\'S\',\'Supervisor\',\'SUPERVISOR\',\'ACTIVE\',NULL),(3,202,\'G2\',\'Guard\',\'GUARD\',\'ACTIVE\',NULL)');
        $db->execute('CREATE TABLE employee_assignments (employee_assignment_id INTEGER PRIMARY KEY, company_id INTEGER, employee_id INTEGER, position_id INTEGER, assignment_status TEXT, deleted_at TEXT)'); $db->execute('INSERT INTO employee_assignments VALUES (1,101,1,1,\'ACTIVE\',NULL),(2,101,2,2,\'ACTIVE\',NULL),(3,202,3,3,\'ACTIVE\',NULL)');
        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY, company_id INTEGER, client_id INTEGER, branch_id INTEGER, site_name TEXT)'); $db->execute('INSERT INTO sites VALUES (1,101,10,20,\'A Site\'),(2,202,30,40,\'B Site\')');
        $db->execute('CREATE TABLE guard_site_assignments (guard_site_assignment_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, employee_assignment_id INTEGER, site_id INTEGER, deployment_status TEXT NOT NULL, starts_at TEXT NOT NULL, ends_at TEXT, assignment_notes TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        $db->execute('INSERT INTO guard_site_assignments (company_id,employee_assignment_id,site_id,deployment_status,starts_at,ends_at) VALUES (101,1,1,\'ACTIVE\',\'2026-01-01 00:00:00\',\'2026-12-31 23:59:59\')');
        return new GuardSiteAssignmentManagementService(new GuardSiteAssignmentRepository($db, $context));
    }

    public function testCreateDefaultAndEndedDateRules(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        $created = $service->createAssignment(['employee_assignment_id'=>1,'site_id'=>1,'starts_at'=>'2026-02-01 08:00:00']);
        self::assertSame('PLANNED', $created['deployment_status']);
        try { $service->createAssignment(['employee_assignment_id'=>1,'site_id'=>1,'starts_at'=>'2026-03-01 08:00:00','deployment_status'=>'ENDED']); self::fail('Expected ended-date validation.'); }
        catch (HttpException $exception) { self::assertSame('invalid_ends_at', $exception->errorCode()); }
        try { $service->createAssignment(['employee_assignment_id'=>1,'site_id'=>1,'starts_at'=>'2026-04-01 08:00:00','ends_at'=>'2026-03-01 08:00:00']); self::fail('Expected date validation.'); }
        catch (HttpException $exception) { self::assertSame('invalid_date_relationship', $exception->errorCode()); }
    }

    public function testGuardSiteTenantAndClassificationSecurity(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        foreach ([2, 999] as $assignmentId) {
            try { $service->createAssignment(['employee_assignment_id'=>$assignmentId,'site_id'=>1,'starts_at'=>'2026-02-01 00:00:00']); self::fail('Expected guard assignment failure.'); }
            catch (HttpException $exception) { self::assertSame('guard_assignment_not_found', $exception->errorCode()); }
        }
        foreach ([2, 999] as $siteId) {
            try { $service->createAssignment(['employee_assignment_id'=>1,'site_id'=>$siteId,'starts_at'=>'2026-02-01 00:00:00']); self::fail('Expected site failure.'); }
            catch (HttpException $exception) { self::assertSame('site_not_found', $exception->errorCode()); }
        }
    }

    public function testDuplicateAndEffectivePatchValidation(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        try { $service->createAssignment(['employee_assignment_id'=>1,'site_id'=>1,'starts_at'=>'2026-01-01 00:00:00']); self::fail('Expected duplicate conflict.'); }
        catch (HttpException $exception) { self::assertSame(409, $exception->statusCode()); }
        try { $service->updateAssignment('1', ['starts_at'=>'2027-01-01 00:00:00']); self::fail('Expected effective date validation.'); }
        catch (HttpException $exception) { self::assertSame('invalid_date_relationship', $exception->errorCode()); }
        $updated = $service->updateAssignment('1', ['assignment_notes'=>'updated']);
        self::assertSame('updated', $updated['assignment_notes']);
    }
}