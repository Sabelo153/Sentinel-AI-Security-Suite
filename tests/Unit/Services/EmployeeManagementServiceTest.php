<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\EmployeeRepository;
use Sentinel\Services\EmployeeManagementService;

final class EmployeeManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): EmployeeManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL)');
        $db->execute('INSERT INTO people (person_id, company_id) VALUES (1, 101), (2, 202), (3, 101)');
        $db->execute('CREATE TABLE employees (employee_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, person_id INTEGER NOT NULL, employee_number TEXT, psira_number TEXT, hire_date TEXT, employment_status TEXT, created_at TEXT, updated_at TEXT)');
        $db->execute('INSERT INTO employees (company_id, person_id, employee_number, employment_status) VALUES (101, 1, \'A-1\', \'ACTIVE\'), (202, 2, \'B-1\', \'ACTIVE\')');

        return new EmployeeManagementService(new EmployeeRepository($db, $context));
    }

    public function testCreateUsesTenantAndRejectsCrossCompanyOrMissingPersonIdentically(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        $created = $service->createEmployee([
            'person_id' => 3,
            'employee_number' => 'A-2',
            'company_id' => 202,
        ]);
        self::assertSame(101, (int) $created['company_id']);

        $errors = [];
        foreach ([2, 999] as $personId) {
            try {
                $service->createEmployee(['person_id' => $personId, 'company_id' => 202]);
                self::fail('Expected person not found exception.');
            } catch (HttpException $exception) {
                $errors[] = [$exception->statusCode(), $exception->errorCode(), $exception->getMessage()];
            }
        }

        self::assertSame($errors[0], $errors[1]);
        self::assertSame([404, 'person_not_found', 'Person not found.'], $errors[0]);
    }

    public function testCrossCompanyAndMissingEmployeesUseSameNotFoundResponse(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);
        $errors = [];

        foreach (['2', '999'] as $employeeId) {
            try {
                $service->getEmployee($employeeId);
                self::fail('Expected employee not found exception.');
            } catch (HttpException $exception) {
                $errors[] = [$exception->statusCode(), $exception->errorCode(), $exception->getMessage()];
            }
        }

        self::assertSame($errors[0], $errors[1]);
        self::assertSame([404, 'employee_not_found', 'Employee not found.'], $errors[0]);
    }

    public function testUpdateWhitelistRejectsImmutableFieldsAndValidatesStatus(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        try {
            $service->updateEmployee('1', ['company_id' => 202, 'employee_id' => 9, 'person_id' => 2]);
            self::fail('Expected invalid update exception.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->statusCode());
        }

        try {
            $service->updateEmployee('1', ['employment_status' => 'INVALID']);
            self::fail('Expected invalid status exception.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->statusCode());
        }
    }
}