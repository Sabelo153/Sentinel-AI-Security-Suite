<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PatrolRouteRepository;

final class PatrolRouteRepositoryTest extends TestCase
{
    private function repository(RuntimeContext $context): PatrolRouteRepository
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY, company_id INTEGER, site_name TEXT)');
        $db->execute("INSERT INTO sites VALUES (1,101,'A Site'),(2,202,'B Site')");
        $db->execute('CREATE TABLE posts (post_id INTEGER PRIMARY KEY, company_id INTEGER, site_id INTEGER, post_name TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO posts VALUES (1,101,1,'A Post',NULL),(2,202,2,'B Post',NULL)");
        $db->execute('CREATE TABLE patrol_routes (patrol_route_id INTEGER PRIMARY KEY AUTOINCREMENT, patrol_route_uuid TEXT NOT NULL, company_id INTEGER, site_id INTEGER, post_id INTEGER, route_name TEXT, route_code TEXT, expected_duration_minutes INTEGER, status TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, UNIQUE(site_id,route_code))');
        $db->execute("INSERT INTO patrol_routes VALUES (1,'route-a',101,1,1,'Night Route','NIGHT',30,'ACTIVE','2026-09-11','2026-09-11',NULL),(2,'route-b',202,2,2,'B Route','NIGHT',30,'ACTIVE','2026-09-11','2026-09-11',NULL),(3,'route-deleted',101,1,1,'Deleted','DELETED',30,'ARCHIVED','2026-09-11','2026-09-11','2026-09-11')");

        return new PatrolRouteRepository($db, $context);
    }

    public function testRouteReadsAreTenantScopedAndExcludeSoftDeleted(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertCount(1, $repository->listForCompany());
        self::assertSame('Night Route', $repository->findByIdForCompany('1')['route_name']);
        self::assertNull($repository->findByIdForCompany('2'));
        self::assertNull($repository->findByIdForCompany('3'));
    }

    public function testCreateUsesRuntimeTenantAndUpdateIsScoped(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        $created = $repository->createForCompany([
            'company_id' => 202,
            'site_id' => 1,
            'post_id' => 1,
            'route_name' => 'Morning Route',
            'route_code' => 'MORNING',
            'expected_duration_minutes' => 20,
            'status' => 'ACTIVE',
        ]);
        self::assertSame('101', (string) $created['company_id']);

        $updated = $repository->updateForCompany('1', ['route_name' => 'Updated Route']);
        self::assertSame('Updated Route', $updated['route_name']);
        self::assertNull($repository->updateForCompany('2', ['route_name' => 'No access']));
    }

    public function testReferenceAndDuplicateHelpersAreTenantScoped(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $repository = $this->repository($context);

        self::assertTrue($repository->siteBelongsToCompany('1'));
        self::assertFalse($repository->siteBelongsToCompany('2'));
        self::assertTrue($repository->postBelongsToSite('1', '1'));
        self::assertFalse($repository->postBelongsToSite('2', '1'));
        self::assertTrue($repository->routeCodeExists('1', 'NIGHT'));
        self::assertFalse($repository->routeCodeExists('1', 'NIGHT', '1'));
    }
}
