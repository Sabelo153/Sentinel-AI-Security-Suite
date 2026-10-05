<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PatrolRouteRepository;
use Sentinel\Services\PatrolRouteManagementService;

final class PatrolRouteManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): PatrolRouteManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY, company_id INTEGER, site_name TEXT)');
        $db->execute("INSERT INTO sites VALUES (1,101,'A Site'),(2,202,'B Site')");
        $db->execute('CREATE TABLE posts (post_id INTEGER PRIMARY KEY, company_id INTEGER, site_id INTEGER, post_name TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO posts VALUES (1,101,1,'A Post',NULL),(2,202,2,'B Post',NULL)");
        $db->execute('CREATE TABLE patrol_routes (patrol_route_id INTEGER PRIMARY KEY AUTOINCREMENT, patrol_route_uuid TEXT NOT NULL, company_id INTEGER, site_id INTEGER, post_id INTEGER, route_name TEXT, route_code TEXT, expected_duration_minutes INTEGER, status TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, UNIQUE(site_id,route_code))');
        $db->execute("INSERT INTO patrol_routes VALUES (1,'route-a',101,1,1,'Night Route','NIGHT',30,'ACTIVE','2026-09-11','2026-09-11',NULL)");

        return new PatrolRouteManagementService(new PatrolRouteRepository($db, $context));
    }

    public function testCreateAcceptsCanonicalStatusesAndTenantOverrideIsIgnored(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        foreach (['ACTIVE', 'INACTIVE', 'ARCHIVED'] as $index => $status) {
            $route = $service->createRoute([
                'company_id' => 202,
                'site_id' => 1,
                'route_name' => 'Route ' . $status,
                'route_code' => 'CODE-' . $index,
                'status' => $status,
            ]);
            self::assertSame('101', (string) $route['company_id']);
            self::assertSame($status, $route['status']);
        }
    }

    public function testRejectsCrossTenantSiteBadPostStatusAndDuplicateCode(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        $cases = [
            [['site_id' => 2, 'route_name' => 'Wrong Site'], 'site_not_found'],
            [['site_id' => 1, 'post_id' => 2, 'route_name' => 'Wrong Post'], 'invalid_patrol_route_relationship'],
            [['site_id' => 1, 'route_name' => 'Bad Status', 'status' => 'DRAFT'], 'invalid_patrol_route_status'],
        ];
        foreach ($cases as [$input, $code]) {
            try {
                $service->createRoute($input);
                self::fail('Expected validation error.');
            } catch (HttpException $exception) {
                self::assertSame($code, $exception->errorCode());
            }
        }

        try {
            $service->createRoute(['site_id' => 1, 'route_name' => 'Duplicate', 'route_code' => 'NIGHT']);
            self::fail('Expected duplicate route conflict.');
        } catch (HttpException $exception) {
            self::assertSame(409, $exception->statusCode());
            self::assertSame('patrol_route_conflict', $exception->errorCode());
        }
    }

    public function testUpdateUsesWhitelistAndEffectiveRelationshipValidation(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        $updated = $service->updateRoute('1', ['company_id' => 202, 'patrol_route_id' => 999, 'route_name' => 'Updated']);
        self::assertSame('101', (string) $updated['company_id']);
        self::assertSame('1', (string) $updated['patrol_route_id']);
        self::assertSame('Updated', $updated['route_name']);

        try {
            $service->updateRoute('1', ['site_id' => 2]);
            self::fail('Expected cross-tenant site rejection.');
        } catch (HttpException $exception) {
            self::assertSame('site_not_found', $exception->errorCode());
        }
    }
}
