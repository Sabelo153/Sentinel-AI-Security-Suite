<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Application, Config, Database, HttpException, RuntimeContext};
use Sentinel\Repositories\{AuditRepository, OccurrenceBookRepository};
use Sentinel\Services\{AuditService, OccurrenceBookService};

final class OccurrenceBookTest extends TestCase
{
    private function fixture(): array
    {
        $db = new Database(new Config([]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]));
        foreach ([
            'sites (site_id INTEGER, company_id INTEGER)', 'employees (employee_id INTEGER, company_id INTEGER)', 'users (user_id INTEGER, company_id INTEGER)',
            'shift_assignments (shift_assignment_id INTEGER, company_id INTEGER, site_id INTEGER, employee_id INTEGER, deleted_at TEXT)',
            'occurrence_entries (occurrence_entry_id INTEGER PRIMARY KEY AUTOINCREMENT, occurrence_entry_uuid TEXT UNIQUE, company_id INTEGER, site_id INTEGER, employee_id INTEGER, shift_assignment_id INTEGER, entry_reference TEXT, entry_type TEXT, occurred_at TEXT, entry_text TEXT, handover_required INTEGER, handover_reference TEXT, created_by_user_id INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(company_id, entry_reference))',
            'occurrence_entry_revisions (occurrence_entry_revision_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, occurrence_entry_id INTEGER, revision_number INTEGER, previous_entry_text TEXT, revision_reason TEXT, revised_by_user_id INTEGER, revised_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(occurrence_entry_id, revision_number))',
            'audit_logs (audit_log_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, user_id INTEGER, entity_name TEXT, entity_id TEXT, action_name TEXT, action_status TEXT, ip_address TEXT, user_agent TEXT, old_values TEXT, new_values TEXT, metadata TEXT)',
        ] as $schema) $db->execute('CREATE TABLE ' . $schema);
        foreach (['sites' => 'site_id', 'employees' => 'employee_id', 'users' => 'user_id'] as $table => $key) $db->execute("INSERT INTO $table ($key, company_id) VALUES (1,101),(2,202)");
        $db->execute('INSERT INTO shift_assignments VALUES (1,101,1,1,NULL),(2,202,2,2,NULL),(3,101,9,1,NULL)');
        $context = new RuntimeContext(); $context->setTenantId(101); $context->setUser('1');
        $repo = new OccurrenceBookRepository($db, $context);
        return [$db, $context, new OccurrenceBookService($repo, $context, new AuditService(new AuditRepository($db, $context), $context))];
    }
    private function input(): array { return ['site_id' => 1, 'employee_id' => 1, 'shift_assignment_id' => 1, 'entry_type' => 'HANDOVER', 'occurred_at' => '2026-09-14 08:00:00', 'entry_text' => 'Initial entry', 'handover_required' => true]; }
    private function assertHttpStatus(int $expected, callable $operation): void
    {
        try { $operation(); self::fail('Expected controlled HTTP error'); } catch (HttpException $error) { self::assertSame($expected, $error->statusCode()); }
    }
    public function testCreatesAndRevisesWithPreservedHistoryAndAudits(): void
    {
        [$db, , $service] = $this->fixture();
        $entry = $service->createEntry($this->input()); $id = (string) $entry['occurrence_entry_id'];
        self::assertSame(101, $entry['company_id']); self::assertSame(1, $entry['created_by_user_id']);
        self::assertCount(1, $service->listEntries(['handover_required' => '1']));
        $service->reviseEntry($id, ['expected_revision' => 0, 'entry_text' => 'Corrected entry', 'revision_reason' => 'Correction']);
        $history = $service->revisions($id);
        self::assertCount(1, $history); self::assertSame('Initial entry', $history[0]['previous_entry_text']);
        self::assertSame('Corrected entry', $service->getEntry($id)['entry_text']);
        self::assertCount(2, $db->fetchAll('SELECT * FROM audit_logs'));
        $this->assertHttpStatus(409, fn () => $service->reviseEntry($id, ['expected_revision' => 0, 'entry_text' => 'Stale edit', 'revision_reason' => 'Correction']));
        self::assertCount(1, $service->revisions($id));
    }
    public function testCrossCompanyReadsRevisionsAndReferencesAreHidden(): void
    {
        [, $context, $service] = $this->fixture();
        $id = (string) $service->createEntry($this->input())['occurrence_entry_id'];
        foreach (['site_id', 'employee_id', 'shift_assignment_id'] as $key) $this->assertHttpStatus(404, fn () => $service->createEntry([...$this->input(), $key => 2]));
        $this->assertHttpStatus(422, fn () => $service->createEntry([...$this->input(), 'shift_assignment_id' => 3]));
        $context->setTenantId(202); $context->setUser('2');
        self::assertSame([], $service->listEntries());
        $this->assertHttpStatus(404, fn () => $service->getEntry($id));
        $this->assertHttpStatus(404, fn () => $service->revisions($id));
        $this->assertHttpStatus(404, fn () => $service->reviseEntry($id, ['expected_revision' => 0, 'entry_text' => 'Attempt', 'revision_reason' => 'Attempt']));
        $context->setTenantId(null); $this->assertHttpStatus(403, fn () => $service->listEntries());
    }
    public function testRejectsForgedOwnershipInvalidTimestampsAndEmptyContent(): void
    {
        [, , $service] = $this->fixture();
        foreach ([['company_id' => 202], ['created_by_user_id' => 2], ['entry_text' => ' '], ['occurred_at' => '2026-02-30 08:00:00'], ['occurred_at' => 'tomorrow'], ['handover_required' => 'false'], ['site_id' => []]] as $patch) $this->assertHttpStatus(422, fn () => $service->createEntry([...$this->input(), ...$patch]));
        $this->assertHttpStatus(422, fn () => $service->listEntries(['date_from' => '2026-09-15 00:00:00', 'date_to' => '2026-09-14 00:00:00']));
        self::assertSame([], $service->listEntries());
    }
    public function testAuditFailureRollsBackCreationAndRevision(): void
    {
        [$db, , $service] = $this->fixture();
        $id = (string) $service->createEntry($this->input())['occurrence_entry_id'];
        $db->execute('DROP TABLE audit_logs');
        $this->assertHttpStatus(503, fn () => $service->createEntry($this->input()));
        $this->assertHttpStatus(503, fn () => $service->reviseEntry($id, ['expected_revision' => 0, 'entry_text' => 'Change', 'revision_reason' => 'Correction']));
        self::assertCount(1, $service->listEntries()); self::assertSame([], $service->revisions($id));
        self::assertSame('Initial entry', $service->getEntry($id)['entry_text']);
    }
    public function testRoutesRequireAuthenticationTenantAndSpecificPermissions(): void
    {
        $routes = Application::create(dirname(__DIR__, 3))->bootstrap()->router()->securityMetadata();
        $routes = array_values(array_filter($routes, static fn (array $route): bool => str_starts_with($route['path'], '/api/v1/occurrence-entries')));
        self::assertCount(5, $routes);
        self::assertSame(['occurrence_book.view', 'occurrence_book.view', 'occurrence_book.view', 'occurrence_book.create', 'occurrence_book.revise'], array_column($routes, 'permission'));
        foreach ($routes as $route) { self::assertTrue($route['auth']); self::assertTrue($route['tenant']); }
    }
}
