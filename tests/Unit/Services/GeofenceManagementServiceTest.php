<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\GeofenceRepository;
use Sentinel\Services\GeofenceManagementService;

final class GeofenceManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): GeofenceManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY, company_id INTEGER, deleted_at TEXT)');
        $db->execute('CREATE TABLE geofences (geofence_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, site_id INTEGER, geofence_code TEXT, geofence_name TEXT, geofence_shape TEXT, center_latitude REAL, center_longitude REAL, radius_meters REAL, polygon_json TEXT, geofence_status TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO sites VALUES (1,101,NULL),(2,202,NULL)");
        $db->execute("INSERT INTO geofences (company_id,site_id,geofence_code,geofence_name,geofence_shape,center_latitude,center_longitude,radius_meters,geofence_status,deleted_at) VALUES (101,1,'HQ','HQ','CIRCLE',-29.0,31.0,1000,'ACTIVE',NULL),(202,2,'HQ','Other','CIRCLE',-30.0,30.0,1000,'ACTIVE',NULL)");
        return new GeofenceManagementService(new GeofenceRepository($db, $context));
    }

    public function testCrudIsTenantScopedAndCircleEvaluationIsCorrect(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        self::assertCount(1, $service->listGeofences());
        self::assertTrue($service->evaluate('1', -29.0, 31.0));
        self::assertFalse($service->evaluate('1', -29.02, 31.0));
        self::assertSame('Renamed HQ', $service->updateGeofence('1', ['geofence_name'=>'Renamed HQ'])['geofence_name']);
        try { $service->updateGeofence('1', ['company_id'=>202]); self::fail('Tenant changes must fail'); } catch (HttpException $e) { self::assertSame(422,$e->statusCode()); }
        $created = $service->createGeofence(['site_id' => 1, 'geofence_code' => 'YARD', 'geofence_name' => 'Yard', 'geofence_shape' => 'POLYGON', 'polygon_json' => json_encode([[-1,-1],[1,-1],[1,1],[-1,1]], JSON_THROW_ON_ERROR)]);
        self::assertSame('YARD', $created['geofence_code']);
        self::assertTrue($service->evaluate((string) $created['geofence_id'], 0, 0));
    }

    public function testCrossCompanyAndInvalidGeometryAreHiddenOrRejected(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $service = $this->service($context);
        try { $service->getGeofence('2'); self::fail('Expected hidden geofence.'); } catch (HttpException $e) { self::assertSame('geofence_not_found', $e->errorCode()); }
        try { $service->createGeofence(['site_id' => 2, 'geofence_code' => 'X', 'geofence_name' => 'X', 'geofence_shape' => 'CIRCLE', 'center_latitude' => 0, 'center_longitude' => 0, 'radius_meters' => 1]); self::fail('Expected hidden site.'); } catch (HttpException $e) { self::assertSame('site_not_found', $e->errorCode()); }
        try { $service->createGeofence(['site_id' => 1, 'geofence_code' => 'X', 'geofence_name' => 'X', 'geofence_shape' => 'CIRCLE', 'center_latitude' => 91, 'center_longitude' => 0, 'radius_meters' => 1]); self::fail('Expected invalid coordinate.'); } catch (HttpException $e) { self::assertSame(422, $e->statusCode()); }
    }
}
