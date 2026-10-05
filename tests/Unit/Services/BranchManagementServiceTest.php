<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\BranchRepository;
use Sentinel\Services\BranchManagementService;

final class BranchManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): BranchManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE clients (client_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, client_name TEXT NOT NULL)');
        $db->execute('INSERT INTO clients (client_id, company_id, client_name) VALUES (1, 101, \'Acme\'), (2, 202, \'Other\')');
        $db->execute('CREATE TABLE branches (branch_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, client_id INTEGER NOT NULL, branch_name TEXT NOT NULL, phone TEXT, email TEXT, address TEXT, city TEXT, province TEXT, status TEXT NOT NULL, created_at TEXT, updated_at TEXT, UNIQUE(client_id, branch_name))');
        $db->execute('INSERT INTO branches (company_id, client_id, branch_name, status) VALUES (101, 1, \'A Branch\', \'ACTIVE\'), (202, 2, \'B Branch\', \'ACTIVE\')');

        return new BranchManagementService(new BranchRepository($db, $context));
    }

    public function testCreateAllowsSameCompanyClientAndIgnoresCompanyOverride(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $branch = $this->service($context)->createBranch(['client_id' => 1, 'branch_name' => 'New Branch', 'company_id' => 202]);

        self::assertSame(101, (int) $branch['company_id']);
        self::assertSame(1, (int) $branch['client_id']);
    }

    public function testCrossCompanyAndMissingClientsUseSameSafeResponse(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);
        $errors = [];

        foreach ([2, 999] as $clientId) {
            try {
                $service->createBranch(['client_id' => $clientId, 'branch_name' => 'Invalid']);
                self::fail('Expected client not found exception.');
            } catch (HttpException $exception) {
                $errors[] = [$exception->statusCode(), $exception->errorCode(), $exception->getMessage()];
            }
        }

        self::assertSame($errors[0], $errors[1]);
        self::assertSame([404, 'client_not_found', 'Client not found.'], $errors[0]);
    }

    public function testCrossCompanyAndMissingBranchesUseSameNotFoundResponse(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);
        $errors = [];

        foreach (['2', '999'] as $branchId) {
            try {
                $service->getBranch($branchId);
                self::fail('Expected branch not found exception.');
            } catch (HttpException $exception) {
                $errors[] = [$exception->statusCode(), $exception->errorCode(), $exception->getMessage()];
            }
        }

        self::assertSame($errors[0], $errors[1]);
        self::assertSame([404, 'branch_not_found', 'Branch not found.'], $errors[0]);
    }

    public function testValidationAndDuplicateConflictsAreSafe(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        try {
            $service->createBranch(['client_id' => 1]);
            self::fail('Expected branch-name validation exception.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->statusCode());
        }

        try {
            $service->createBranch(['client_id' => 1, 'branch_name' => 'A Branch']);
            self::fail('Expected conflict exception.');
        } catch (HttpException $exception) {
            self::assertSame(409, $exception->statusCode());
            self::assertSame('branch_conflict', $exception->errorCode());
        }
    }
}