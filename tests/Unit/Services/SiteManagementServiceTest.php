<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\SiteRepository;
use Sentinel\Services\SiteManagementService;

final class SiteManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): SiteManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE clients (client_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)');
        $db->execute('INSERT INTO clients (client_id, company_id) VALUES (1, 101), (2, 202), (3, 101)');
        $db->execute('CREATE TABLE branches (branch_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, client_id INTEGER NOT NULL)');
        $db->execute('INSERT INTO branches (branch_id, company_id, client_id) VALUES (1, 101, 1), (2, 202, 2), (3, 101, 3)');
        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, client_id INTEGER NOT NULL, branch_id INTEGER NOT NULL, site_name TEXT NOT NULL, address TEXT, city TEXT, province TEXT, latitude REAL, longitude REAL, status TEXT NOT NULL, created_at TEXT, updated_at TEXT, UNIQUE(branch_id, site_name))');
        $db->execute('INSERT INTO sites (company_id, client_id, branch_id, site_name, status) VALUES (101, 1, 1, \'A Site\', \'ACTIVE\'), (202, 2, 2, \'B Site\', \'ACTIVE\')');

        return new SiteManagementService(new SiteRepository($db, $context));
    }

    public function testCreateAllowsConsistentDependenciesAndIgnoresCompanyOverride(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $site = $this->service($context)->createSite(['client_id' => 1, 'branch_id' => 1, 'site_name' => 'New Site', 'latitude' => 45.2, 'longitude' => -20.5, 'company_id' => 202]);

        self::assertSame(101, (int) $site['company_id']);
        self::assertSame(45.2, (float) $site['latitude']);
    }

    public function testCrossCompanyAndMissingDependenciesUseSameSafeResponses(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        foreach ([['client_id' => 2, 'branch_id' => 1], ['client_id' => 999, 'branch_id' => 1]] as $input) {
            try {
                $service->createSite($input + ['site_name' => 'Invalid']);
                self::fail('Expected client not found.');
            } catch (HttpException $exception) {
                self::assertSame(404, $exception->statusCode());
                self::assertSame('client_not_found', $exception->errorCode());
            }
        }
        foreach ([['client_id' => 1, 'branch_id' => 2], ['client_id' => 1, 'branch_id' => 999]] as $input) {
            try {
                $service->createSite($input + ['site_name' => 'Invalid']);
                self::fail('Expected branch not found.');
            } catch (HttpException $exception) {
                self::assertSame(404, $exception->statusCode());
                self::assertSame('branch_not_found', $exception->errorCode());
            }
        }
    }

    public function testSameCompanyInconsistentBranchClientPairIsRejected(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);

        try {
            $this->service($context)->createSite(['client_id' => 3, 'branch_id' => 1, 'site_name' => 'Mismatch']);
            self::fail('Expected relationship validation exception.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->statusCode());
            self::assertSame('invalid_site_relationship', $exception->errorCode());
        }
    }

    public function testCrossCompanyAndMissingSitesMatchAndValidationIsSafe(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);
        $errors = [];
        foreach (['2', '999'] as $siteId) {
            try {
                $service->getSite($siteId);
                self::fail('Expected site not found.');
            } catch (HttpException $exception) {
                $errors[] = [$exception->statusCode(), $exception->errorCode(), $exception->getMessage()];
            }
        }
        self::assertSame($errors[0], $errors[1]);

        foreach ([['client_id' => 1, 'branch_id' => 1, 'site_name' => 'Bad', 'latitude' => 91], ['client_id' => 1, 'branch_id' => 1, 'site_name' => 'Bad', 'status' => 'INVALID']] as $input) {
            try {
                $service->createSite($input);
                self::fail('Expected validation exception.');
            } catch (HttpException $exception) {
                self::assertSame(422, $exception->statusCode());
            }
        }
    }
}