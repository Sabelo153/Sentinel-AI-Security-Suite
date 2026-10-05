<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\ContractRepository;
use Sentinel\Repositories\PostRepository;
use Sentinel\Services\ContractManagementService;
use Sentinel\Services\PostManagementService;

final class ContractPostManagementServiceTest extends TestCase
{
    private function database(): Database
    {
        $db = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $db->execute('CREATE TABLE clients (client_id INTEGER PRIMARY KEY, company_id INTEGER)');
        $db->execute('CREATE TABLE sites (site_id INTEGER PRIMARY KEY, company_id INTEGER, deleted_at TEXT)');
        $db->execute('CREATE TABLE contracts (contract_id INTEGER PRIMARY KEY AUTOINCREMENT, contract_uuid TEXT, company_id INTEGER, client_id INTEGER, contract_number TEXT, contract_name TEXT, start_date TEXT, end_date TEXT, monthly_value REAL, sla_tier TEXT, required_guard_count INTEGER, shift_requirements_json TEXT, status TEXT, created_by_user_id INTEGER, updated_by_user_id INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        $db->execute('CREATE TABLE posts (post_id INTEGER PRIMARY KEY AUTOINCREMENT, post_uuid TEXT, company_id INTEGER, contract_id INTEGER, site_id INTEGER, post_name TEXT, description TEXT, gps_lat REAL, gps_long REAL, status TEXT, created_by_user_id INTEGER, updated_by_user_id INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        $db->execute("INSERT INTO clients VALUES (1,101),(2,202)");
        $db->execute("INSERT INTO sites VALUES (1,101,NULL),(2,202,NULL)");
        $db->execute("INSERT INTO contracts (contract_uuid,company_id,client_id,contract_number,contract_name,start_date,status,deleted_at) VALUES ('c1',101,1,'C-1','One','2026-01-01','ACTIVE',NULL),('c2',202,2,'C-2','Two','2026-01-01','ACTIVE',NULL)");
        return $db;
    }

    public function testContractAndPostCrudUsesTenantAndCanonicalReferences(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $db = $this->database();
        $contracts = new ContractManagementService(new ContractRepository($db, $context));
        $posts = new PostManagementService(new PostRepository($db, $context));
        self::assertCount(1, $contracts->listContracts());
        $contract = $contracts->createContract(['client_id' => 1, 'contract_number' => 'C-3', 'contract_name' => 'Three', 'start_date' => '2026-02-01']);
        $post = $posts->createPost(['contract_id' => $contract['contract_id'], 'site_id' => 1, 'post_name' => 'Gate', 'gps_lat' => -29, 'gps_long' => 31]);
        self::assertSame('101', (string) $post['company_id']);
        self::assertSame('Gate', $posts->updatePost((string) $post['post_id'], ['description' => 'Main gate'])['post_name']);
    }

    public function testCrossCompanyRelationshipsAreHidden(): void
    {
        $context = new RuntimeContext(); $context->setTenantId(101); $db = $this->database();
        $contracts = new ContractManagementService(new ContractRepository($db, $context));
        $posts = new PostManagementService(new PostRepository($db, $context));
        try { $contracts->createContract(['client_id' => 2, 'contract_number' => 'X', 'contract_name' => 'X', 'start_date' => '2026-01-01']); self::fail('Expected hidden client.'); } catch (HttpException $e) { self::assertSame('client_not_found', $e->errorCode()); }
        try { $posts->createPost(['contract_id' => 2, 'site_id' => 1, 'post_name' => 'X']); self::fail('Expected hidden contract.'); } catch (HttpException $e) { self::assertSame('contract_not_found', $e->errorCode()); }
    }
}
