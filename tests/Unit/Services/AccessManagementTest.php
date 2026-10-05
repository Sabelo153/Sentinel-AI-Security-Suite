<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Services;
use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Application, Config, Database, HttpException, RuntimeContext};
use Sentinel\Repositories\{OperationalRepository, AuditRepository};
use Sentinel\Services\{AccessManagementService, AuditService};

final class AccessManagementTest extends TestCase
{
    private function fixture(): array
    {
        $db = new Database(new Config([]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]));
        foreach ([
            'sites (site_id INTEGER, company_id INTEGER, deleted_at TEXT)', 'employees (employee_id INTEGER, company_id INTEGER)', 'users (user_id INTEGER, company_id INTEGER)',
            'visitors (visitor_id INTEGER PRIMARY KEY AUTOINCREMENT, visitor_uuid TEXT UNIQUE, company_id INTEGER, first_name TEXT NOT NULL, last_name TEXT NOT NULL, contact_phone TEXT, contact_email TEXT, external_reference TEXT, visitor_status TEXT NOT NULL, deleted_at TEXT)',
            'vehicles (vehicle_id INTEGER PRIMARY KEY AUTOINCREMENT, vehicle_uuid TEXT UNIQUE, company_id INTEGER, registration_number TEXT NOT NULL, make TEXT, model TEXT, color TEXT, vehicle_status TEXT NOT NULL, deleted_at TEXT, UNIQUE(company_id,registration_number))',
            'visitor_visits (visitor_visit_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, visitor_id INTEGER, site_id INTEGER, host_employee_id INTEGER, authorized_by_user_id INTEGER, purpose TEXT, planned_arrival_at TEXT, planned_departure_at TEXT, arrival_at TEXT, departure_at TEXT, visit_status TEXT, created_by_user_id INTEGER)',
            'access_events (access_event_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, site_id INTEGER, vehicle_id INTEGER, visitor_visit_id INTEGER, employee_id INTEGER, event_type TEXT, direction TEXT, authorization_status TEXT, denied_reason TEXT, occurred_at TEXT, recorded_by_user_id INTEGER)',
            'audit_logs (audit_log_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, user_id INTEGER, entity_name TEXT, entity_id TEXT, action_name TEXT, action_status TEXT, ip_address TEXT, user_agent TEXT, old_values TEXT, new_values TEXT, metadata TEXT)',
        ] as $schema) $db->execute('CREATE TABLE ' . $schema);
        foreach (['sites' => 'site_id','employees' => 'employee_id','users' => 'user_id'] as $table => $key) $db->execute("INSERT INTO $table ($key,company_id) VALUES (1,101),(2,202)");
        $context = new RuntimeContext(); $context->setTenantId(101); $context->setUser('1');
        return [$db, $context, new AccessManagementService(new OperationalRepository($db, $context), $context, new AuditService(new AuditRepository($db, $context), $context))];
    }
    private function assertHttpStatus(int $status, callable $operation): void { try { $operation(); self::fail('Expected HTTP error'); } catch (HttpException $error) { self::assertSame($status, $error->statusCode()); } }
    public function testVisitorLifecycleAndChronology(): void
    {
        [$db, , $service] = $this->fixture();
        $visitor = $service->createRecord('visitors', ['first_name' => 'Jane','last_name' => 'Test']);
        $visit = $service->createRecord('visits', ['visitor_id' => $visitor['visitor_id'], 'site_id' => 1, 'purpose' => 'Meeting']); $id = (string) $visit['visitor_visit_id'];
        $this->assertHttpStatus(409, fn () => $service->transitionVisit($id, ['status' => 'CHECKED_IN']));
        self::assertSame('AUTHORIZED', $service->transitionVisit($id, ['status' => 'AUTHORIZED'])['visit_status']);
        $service->transitionVisit($id, ['status' => 'CHECKED_IN', 'occurred_at' => '2026-09-14 08:00:00']);
        $this->assertHttpStatus(422, fn () => $service->transitionVisit($id, ['status' => 'CHECKED_OUT','occurred_at' => '2026-09-14 07:00:00']));
        self::assertSame('CHECKED_OUT', $service->transitionVisit($id, ['status' => 'CHECKED_OUT','occurred_at' => '2026-09-14 09:00:00'])['visit_status']);
        $this->assertHttpStatus(409, fn () => $service->transitionVisit($id, ['status' => 'AUTHORIZED']));
        self::assertCount(5, $db->fetchAll('SELECT * FROM audit_logs')); self::assertSame(1, $visit['created_by_user_id']);
    }
    public function testBlockedVehiclesAndDeniedAccess(): void
    {
        [, , $service] = $this->fixture();
        $vehicle = $service->createRecord('vehicles', ['registration_number' => 'abc123','vehicle_status' => 'BLOCKED']);
        self::assertSame('ABC123', $vehicle['registration_number']);
        $input = ['site_id' => 1,'vehicle_id' => $vehicle['vehicle_id'],'event_type' => 'GATE','direction' => 'ENTRY','authorization_status' => 'AUTHORIZED','occurred_at' => '2026-09-14 08:00:00'];
        $this->assertHttpStatus(409, fn () => $service->createRecord('access', $input));
        $this->assertHttpStatus(422, fn () => $service->createRecord('access', [...$input, 'authorization_status' => 'DENIED']));
        self::assertSame('DENIED', $service->createRecord('access', [...$input, 'authorization_status' => 'DENIED','denied_reason' => 'Blocked registration'])['authorization_status']);
        $service->updateRecord('vehicles', (string) $vehicle['vehicle_id'], ['vehicle_status' => 'ACTIVE']);
        self::assertSame('AUTHORIZED', $service->createRecord('access', $input)['authorization_status']);
        $this->assertHttpStatus(409, fn () => $service->createRecord('vehicles', ['registration_number' => 'ABC123']));
    }
    public function testTenantIsolationAcrossEveryDomainAndForeignKey(): void
    {
        [, $context, $service] = $this->fixture();
        $visitor = $service->createRecord('visitors', ['first_name' => 'A','last_name' => 'B']);
        $vehicle = $service->createRecord('vehicles', ['registration_number' => 'A']);
        $visit = $service->createRecord('visits', ['visitor_id' => $visitor['visitor_id'],'site_id' => 1,'purpose' => 'Visit']);
        $access = $service->createRecord('access', ['vehicle_id' => $vehicle['vehicle_id'],'site_id' => 1,'event_type' => 'GATE','direction' => 'ENTRY','occurred_at' => '2026-09-14 08:00:00']);
        $context->setTenantId(202); $context->setUser('2');
        foreach (['visitors' => $visitor,'vehicles' => $vehicle,'visits' => $visit,'access' => $access] as $domain => $record) {
            self::assertSame([], $service->listRecords($domain, []));
            $this->assertHttpStatus(404, fn () => $service->getRecord($domain, (string) $record[OperationalRepository::TABLES[$domain][1]]));
        }
        $this->assertHttpStatus(404, fn () => $service->updateRecord('vehicles', (string) $vehicle['vehicle_id'], ['color' => 'red']));
        $this->assertHttpStatus(404, fn () => $service->transitionVisit((string) $visit['visitor_visit_id'], ['status' => 'AUTHORIZED']));
        $this->assertHttpStatus(404, fn () => $service->createRecord('visits', ['visitor_id' => $visitor['visitor_id'],'site_id' => 2,'purpose' => 'Visit']));
        $this->assertHttpStatus(404, fn () => $service->createRecord('access', ['vehicle_id' => $vehicle['vehicle_id'],'site_id' => 2,'event_type' => 'GATE','direction' => 'ENTRY','occurred_at' => '2026-09-14 08:00:00']));
    }
    public function testValidationAndAuditRollback(): void
    {
        [$db, , $service] = $this->fixture();
        foreach ([['company_id' => 202], ['first_name' => null], ['contact_email' => 'bad'], ['visitor_status' => null]] as $patch) $this->assertHttpStatus(422, fn () => $service->createRecord('visitors', ['first_name' => 'A','last_name' => 'B', ...$patch]));
        $db->execute('DROP TABLE audit_logs');
        $this->assertHttpStatus(503, fn () => $service->createRecord('visitors', ['first_name' => 'A','last_name' => 'B']));
        self::assertSame([], $service->listRecords('visitors', []));
    }
    public function testProtectedRoutes(): void
    {
        $routes = Application::create(dirname(__DIR__,3))->bootstrap()->router()->securityMetadata();
        $routes = array_values(array_filter($routes, static fn (array $r): bool => preg_match('#^/api/v1/(visitors|visitor-visits|vehicles|access-events)(/|$)#', $r['path']) === 1));
        self::assertCount(15, $routes);
        foreach ($routes as $route) { self::assertTrue($route['auth']); self::assertTrue($route['tenant']); self::assertNotEmpty($route['permission']); }
    }
}
