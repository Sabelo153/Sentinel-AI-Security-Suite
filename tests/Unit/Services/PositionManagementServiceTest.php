<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PositionRepository;
use Sentinel\Services\PositionManagementService;

final class PositionManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): PositionManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE organizations (organization_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, deleted_at TEXT)');
        $db->execute('INSERT INTO organizations VALUES (1, 101, NULL), (2, 202, NULL), (3, 101, NULL)');
        $db->execute('CREATE TABLE organizational_units (organizational_unit_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, organization_id INTEGER NOT NULL, deleted_at TEXT)');
        $db->execute('INSERT INTO organizational_units VALUES (1, 101, 1, NULL), (2, 202, 2, NULL), (3, 101, 3, NULL)');
        $db->execute('CREATE TABLE positions (position_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, organization_id INTEGER, organizational_unit_id INTEGER, position_code TEXT NOT NULL, position_name TEXT NOT NULL, position_category TEXT NOT NULL, hierarchy_rank INTEGER NOT NULL, position_status TEXT NOT NULL, description TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, UNIQUE(company_id, position_code))');
        $db->execute('INSERT INTO positions (company_id, organization_id, organizational_unit_id, position_code, position_name, position_category, hierarchy_rank, position_status) VALUES (101, 1, 1, \'A-G\', \'Guard\', \'GUARD\', 100, \'ACTIVE\'), (202, 2, 2, \'B-G\', \'Guard\', \'GUARD\', 100, \'ACTIVE\')');

        return new PositionManagementService(new PositionRepository($db, $context));
    }

    public function testGuardAndAllCanonicalCategoriesAreAccepted(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);
        foreach (['GUARD', 'SUPERVISOR', 'CONTROL_ROOM', 'MANAGEMENT', 'SUPPORT'] as $index => $category) {
            $position = $service->createPosition(['position_code' => 'C-' . $index, 'position_name' => $category, 'position_category' => $category]);
            self::assertSame($category, $position['position_category']);
        }
    }

    public function testCrossCompanyAndMissingReferencesUseSameSafeResponses(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);
        foreach ([2, 999] as $organizationId) {
            try {
                $service->createPosition(['organization_id' => $organizationId, 'position_code' => 'X-' . $organizationId, 'position_name' => 'X', 'position_category' => 'GUARD']);
                self::fail('Expected organization not found.');
            } catch (HttpException $exception) {
                self::assertSame(404, $exception->statusCode());
                self::assertSame('organization_not_found', $exception->errorCode());
            }
        }
        foreach ([2, 999] as $unitId) {
            try {
                $service->createPosition(['organizational_unit_id' => $unitId, 'position_code' => 'U-' . $unitId, 'position_name' => 'U', 'position_category' => 'GUARD']);
                self::fail('Expected unit not found.');
            } catch (HttpException $exception) {
                self::assertSame(404, $exception->statusCode());
                self::assertSame('organizational_unit_not_found', $exception->errorCode());
            }
        }
    }

    public function testUnitOrganizationMismatchAndValidationAreSafe(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        try {
            $service->createPosition(['organization_id' => 1, 'organizational_unit_id' => 3, 'position_code' => 'M', 'position_name' => 'Mismatch', 'position_category' => 'GUARD']);
            self::fail('Expected relationship exception.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->statusCode());
            self::assertSame('invalid_position_relationship', $exception->errorCode());
        }

        try {
            $service->createPosition(['position_code' => 'BAD', 'position_name' => 'Bad', 'position_category' => 'INVALID']);
            self::fail('Expected category exception.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->statusCode());
        }

        try {
            $service->createPosition(['position_code' => 'A-G', 'position_name' => 'Duplicate', 'position_category' => 'GUARD']);
            self::fail('Expected duplicate exception.');
        } catch (HttpException $exception) {
            self::assertSame(409, $exception->statusCode());
        }
    }
}