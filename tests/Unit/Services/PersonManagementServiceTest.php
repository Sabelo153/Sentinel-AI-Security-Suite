<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\PersonRepository;
use Sentinel\Services\PersonManagementService;

final class PersonManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): PersonManagementService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE people (person_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, first_name TEXT NOT NULL, last_name TEXT NOT NULL, id_number TEXT, passport_number TEXT, phone TEXT, email TEXT, gender TEXT, date_of_birth TEXT, created_at TEXT, updated_at TEXT)');
        $db->execute('INSERT INTO people (company_id, first_name, last_name, email) VALUES (101, \'Alice\', \'A\', \'alice@example.test\'), (202, \'Bob\', \'B\', \'bob@example.test\')');

        return new PersonManagementService(new PersonRepository($db, $context));
    }

    public function testCreateNormalizesEmailAndIgnoresCompanyOverride(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $person = $this->service($context)->createPerson([
            'first_name' => 'Carol',
            'last_name' => 'C',
            'email' => 'Carol@Example.Test',
            'company_id' => 202,
        ]);

        self::assertSame(101, (int) $person['company_id']);
        self::assertSame('carol@example.test', $person['email']);
    }

    public function testCrossCompanyAndMissingPeopleUseSameNotFoundResponse(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);
        $errors = [];

        foreach (['2', '999'] as $personId) {
            try {
                $service->getPerson($personId);
                self::fail('Expected person not found exception.');
            } catch (HttpException $exception) {
                $errors[] = [$exception->statusCode(), $exception->errorCode(), $exception->getMessage()];
            }
        }

        self::assertSame($errors[0], $errors[1]);
        self::assertSame([404, 'person_not_found', 'Person not found.'], $errors[0]);
    }

    public function testValidationRejectsMissingNamesInvalidEmailAndImmutableFields(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        foreach ([
            ['last_name' => 'Missing first'],
            ['first_name' => 'Missing last'],
            ['first_name' => 'Valid', 'last_name' => 'Name', 'email' => 'bad-email'],
        ] as $input) {
            try {
                $service->createPerson($input);
                self::fail('Expected validation exception.');
            } catch (HttpException $exception) {
                self::assertSame(422, $exception->statusCode());
            }
        }

        try {
            $service->updatePerson('1', ['company_id' => 202, 'person_id' => 9]);
            self::fail('Expected immutable-field validation exception.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->statusCode());
        }
    }
}