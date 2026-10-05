<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\DepartmentRepository;
use Sentinel\Services\DepartmentManagementService;

final class DepartmentManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): DepartmentManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE clients (client_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)');
        $db->execute('INSERT INTO clients (client_id, company_id) VALUES (1, 101), (2, 202)');
        $db->execute('CREATE TABLE branches (branch_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)');
        $db->execute('INSERT INTO branches (branch_id, company_id) VALUES (1, 101), (2, 202)');
        $db->execute('CREATE TABLE departments (department_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, client_id INTEGER, branch_id INTEGER, department_name TEXT NOT NULL, department_code TEXT, status TEXT NOT NULL, created_at TEXT, updated_at TEXT, UNIQUE(company_id, department_name), UNIQUE(company_id, department_code))');
        $db->execute('INSERT INTO departments (company_id, client_id, branch_id, department_name, status) VALUES (101, 1, 1, \'A Department\', \'ACTIVE\'), (202, 2, 2, \'B Department\', \'ACTIVE\')');

        return new DepartmentManagementService(new DepartmentRepository($db, $context));
    }

    public function testCreateAllowsSameCompanyDependenciesAndIgnoresCompanyOverride(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $department = $this->service($context)->createDepartment(['client_id' => 1, 'branch_id' => 1, 'department_name' => 'New Department', 'company_id' => 202]);

        self::assertSame(101, (int) $department['company_id']);
    }

    public function testCrossCompanyAndMissingDependenciesUseSameSafeResponses(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        foreach ([['client_id' => 2], ['client_id' => 999]] as $input) {
            try {
                $service->createDepartment($input + ['department_name' => 'Invalid']);
                self::fail('Expected client not found.');
            } catch (HttpException $exception) {
                self::assertSame(404, $exception->statusCode());
                self::assertSame('client_not_found', $exception->errorCode());
            }
        }
        foreach ([['branch_id' => 2], ['branch_id' => 999]] as $input) {
            try {
                $service->createDepartment($input + ['department_name' => 'Invalid']);
                self::fail('Expected branch not found.');
            } catch (HttpException $exception) {
                self::assertSame(404, $exception->statusCode());
                self::assertSame('branch_not_found', $exception->errorCode());
            }
        }
    }

    public function testCrossCompanyAndMissingDepartmentsMatchAndDuplicateIsConflict(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);
        $errors = [];
        foreach (['2', '999'] as $departmentId) {
            try {
                $service->getDepartment($departmentId);
                self::fail('Expected department not found.');
            } catch (HttpException $exception) {
                $errors[] = [$exception->statusCode(), $exception->errorCode(), $exception->getMessage()];
            }
        }
        self::assertSame($errors[0], $errors[1]);
        self::assertSame([404, 'department_not_found', 'Department not found.'], $errors[0]);

        try {
            $service->createDepartment(['department_name' => 'A Department']);
            self::fail('Expected duplicate conflict.');
        } catch (HttpException $exception) {
            self::assertSame(409, $exception->statusCode());
        }
    }
}