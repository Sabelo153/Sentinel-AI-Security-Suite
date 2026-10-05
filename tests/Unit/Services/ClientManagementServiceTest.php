<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\ClientRepository;
use Sentinel\Services\ClientService;

final class ClientManagementServiceTest extends TestCase
{
    private function service(RuntimeContext $context): ClientService
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE clients (client_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, client_name TEXT NOT NULL, contact_person TEXT, phone TEXT, email TEXT, address TEXT, city TEXT, province TEXT, postal_code TEXT, country TEXT, contract_start TEXT, contract_end TEXT, status TEXT NOT NULL, created_at TEXT, updated_at TEXT, UNIQUE(company_id, client_name))');
        $db->execute('INSERT INTO clients (company_id, client_name, email, status) VALUES (101, \'Acme\', \'acme@example.test\', \'ACTIVE\'), (202, \'Other\', \'other@example.test\', \'ACTIVE\')');

        return new ClientService(new ClientRepository($db, $context));
    }

    public function testCreateNormalizesEmailAndIgnoresCompanyOverride(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $client = $this->service($context)->createClient([
            'client_name' => 'New Client',
            'email' => 'New@Example.Test',
            'company_id' => 202,
        ]);

        self::assertSame(101, (int) $client['company_id']);
        self::assertSame('new@example.test', $client['email']);
    }

    public function testCrossCompanyAndMissingClientsUseSameNotFoundResponse(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);
        $errors = [];

        foreach (['2', '999'] as $clientId) {
            try {
                $service->getClient($clientId);
                self::fail('Expected client not found exception.');
            } catch (HttpException $exception) {
                $errors[] = [$exception->statusCode(), $exception->errorCode(), $exception->getMessage()];
            }
        }

        self::assertSame($errors[0], $errors[1]);
        self::assertSame([404, 'client_not_found', 'Client not found.'], $errors[0]);
    }

    public function testValidationRejectsMissingNameInvalidEmailAndImmutableFields(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);
        $service = $this->service($context);

        foreach ([
            ['email' => 'bad@example.test'],
            ['client_name' => 'Valid', 'email' => 'bad-email'],
        ] as $input) {
            try {
                $service->createClient($input);
                self::fail('Expected validation exception.');
            } catch (HttpException $exception) {
                self::assertSame(422, $exception->statusCode());
            }
        }

        try {
            $service->updateClient('1', ['company_id' => 202, 'client_id' => 9]);
            self::fail('Expected immutable-field validation exception.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->statusCode());
        }
    }

    public function testDuplicateClientNameReturnsSafeConflict(): void
    {
        $context = new RuntimeContext();
        $context->setTenantId(101);

        try {
            $this->service($context)->createClient(['client_name' => 'Acme']);
            self::fail('Expected conflict exception.');
        } catch (HttpException $exception) {
            self::assertSame(409, $exception->statusCode());
            self::assertSame('client_conflict', $exception->errorCode());
            self::assertSame('Client could not be created.', $exception->getMessage());
        }
    }
}