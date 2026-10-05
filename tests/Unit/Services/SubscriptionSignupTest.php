<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Config, Database, HttpException, RuntimeContext};
use Sentinel\Services\{SignupService, SubscriptionPolicy, SubscriptionService};

final class SubscriptionSignupTest extends TestCase
{
    private function fixture(): array
    {
        $db = new Database(new Config([]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]));
        foreach ([
            'companies (company_id INTEGER PRIMARY KEY AUTOINCREMENT, company_name TEXT UNIQUE, phone TEXT, email TEXT, country TEXT, status TEXT)',
            'people (person_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, first_name TEXT, last_name TEXT, phone TEXT, email TEXT)',
            'employees (employee_id INTEGER PRIMARY KEY AUTOINCREMENT, person_id INTEGER, company_id INTEGER, employee_number TEXT, hire_date TEXT, employment_status TEXT)',
            'users (user_id INTEGER PRIMARY KEY AUTOINCREMENT, user_uuid TEXT, company_id INTEGER, employee_id INTEGER, username TEXT, email TEXT, password_hash TEXT, status TEXT)',
            'roles (role_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, role_name TEXT, role_description TEXT, is_system_role INTEGER, status TEXT)',
            'user_roles (user_role_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, user_id INTEGER, role_id INTEGER, assigned_by_user_id INTEGER)',
            'clients (client_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, client_name TEXT, status TEXT)',
            'branches (branch_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, client_id INTEGER, branch_name TEXT, status TEXT)',
            'sites (site_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, client_id INTEGER, branch_id INTEGER, site_name TEXT, status TEXT)',
            'company_subscriptions (company_subscription_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER UNIQUE, plan_code TEXT, subscription_status TEXT, trial_ends_at TEXT, current_period_ends_at TEXT)',
            'permissions (permission_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, permission_code TEXT, permission_name TEXT, module_name TEXT, action_name TEXT, permission_description TEXT, status TEXT)',
            'role_permissions (role_permission_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER, role_id INTEGER, permission_id INTEGER, granted_by_user_id INTEGER)',
        ] as $schema) $db->execute('CREATE TABLE '.$schema);
        return [$db,new SignupService($db)];
    }

    private function input(string $plan='FREE'): array
    {
        return ['company_name'=>'Acme Security','first_name'=>'Ava','last_name'=>'Owner','email'=>'owner@acme.test','phone'=>'123','password'=>'correct horse battery','site_name'=>'HQ','plan_code'=>$plan,'billing_market'=>'ZA'];
    }

    private function routes(): array
    {
        return array_map(static fn(string $p):array=>['permission'=>$p], ['occurrence_book.view','occurrence_book.create','sites.view','dashboard.view','subscriptions.view','users.password_change','incidents.view','guard.self']);
    }

    public function testFreeSignupCreatesCompleteTenantAndOnlyFreePermissions(): void
    {
        [$db,$service]=$this->fixture();
        $result=$service->signup($this->input(),$this->routes());
        self::assertSame('FREE',$result['plan_code']); self::assertSame('ZA',$result['billing_market']); self::assertSame('ACTIVE',$result['subscription_status']);
        self::assertSame('South Africa',$db->fetch('SELECT country FROM companies')['country']);
        self::assertCount(1,$db->fetchAll('SELECT * FROM companies')); self::assertCount(1,$db->fetchAll('SELECT * FROM people')); self::assertCount(1,$db->fetchAll('SELECT * FROM employees')); self::assertCount(1,$db->fetchAll('SELECT * FROM users')); self::assertCount(1,$db->fetchAll('SELECT * FROM sites'));
        $codes=array_column($db->fetchAll('SELECT permission_code FROM permissions ORDER BY permission_code'),'permission_code');
        self::assertSame(['dashboard.view','incidents.view','occurrence_book.create','occurrence_book.view','sites.view','subscriptions.view','users.password_change'],$codes);
        self::assertNotContains('guard.self',$codes);
        self::assertTrue(password_verify('correct horse battery',(string)$db->fetch('SELECT password_hash FROM users')['password_hash']));
    }

    public function testPaidPlanStartsTrialAndPolicyBlocksHigherTierFeatures(): void
    {
        [$db,$service]=$this->fixture(); $result=$service->signup($this->input('ESSENTIAL'),$this->routes());
        self::assertSame('TRIALING',$result['subscription_status']); self::assertNotNull($result['trial_ends_at']);
        $context=new RuntimeContext();$context->setTenantId((int)$result['company_id']);$subscriptions=new SubscriptionService($db,$context);
        self::assertTrue($subscriptions->allows('incidents.view')); self::assertFalse($subscriptions->allows('guard.self'));
        $db->execute('UPDATE company_subscriptions SET trial_ends_at = :past', ['past'=>'2020-01-01 00:00:00']);
        self::assertFalse($subscriptions->allows('incidents.view')); self::assertTrue($subscriptions->allows('subscriptions.view')); self::assertTrue($subscriptions->allows('users.password_change'));
    }

    public function testValidationAndDuplicateRegistrationAreControlled(): void
    {
        [, $service]=$this->fixture();
        foreach ([['password'=>'short'],['email'=>'bad'],['plan_code'=>'UNKNOWN'],['billing_market'=>'XX']] as $patch) {
            try{$service->signup([...$this->input(),...$patch],$this->routes());self::fail('Expected validation error');}catch(HttpException $e){self::assertSame(422,$e->statusCode());}
        }
        $service->signup($this->input(),$this->routes());
        try{$service->signup($this->input(),$this->routes());self::fail('Expected conflict');}catch(HttpException $e){self::assertSame(409,$e->statusCode());}
    }

    public function testTierDefinitionsAreOrderedFromFreeToComplete(): void
    {
        self::assertSame(['FREE','ESSENTIAL','PROFESSIONAL','ENTERPRISE'],array_keys(SubscriptionPolicy::plans()));
        self::assertTrue(SubscriptionPolicy::allows('FREE','occurrence_book.view'));
        self::assertTrue(SubscriptionPolicy::allows('FREE','incidents.view'));
        self::assertFalse(SubscriptionPolicy::allows('FREE','guard.self'));
        self::assertFalse(SubscriptionPolicy::allows('ESSENTIAL','locations.view'));
        self::assertTrue(SubscriptionPolicy::allows('PROFESSIONAL','locations.view'));
        self::assertTrue(SubscriptionPolicy::allows('ENTERPRISE','integrations.manage'));
    }

    public function testFreeLimitsAllowGrowthBeforeRequestingUpgrade(): void
    {
        [$db,$service]=$this->fixture();$result=$service->signup($this->input(),$this->routes());$context=new RuntimeContext();$context->setTenantId((int)$result['company_id']);$subscriptions=new SubscriptionService($db,$context);
        for($i=2;$i<=10;$i++)$db->execute('INSERT INTO users (user_uuid,company_id,employee_id,username,email,password_hash,status) VALUES (:uuid,:company_id,:employee_id,:username,:email,:hash,:status)',['uuid'=>(string)$i,'company_id'=>$result['company_id'],'employee_id'=>$i,'username'=>'u'.$i,'email'=>'u'.$i.'@test','hash'=>'x','status'=>'ACTIVE']);
        try{$subscriptions->assertCanCreate('users');self::fail('Expected seat limit');}catch(HttpException $e){self::assertSame(409,$e->statusCode());self::assertSame('subscription_limit_reached',$e->errorCode());}
        $row=$db->fetch('SELECT client_id,branch_id FROM sites LIMIT 1');
        for($i=2;$i<=3;$i++)$db->execute('INSERT INTO sites (company_id,client_id,branch_id,site_name,status) VALUES (:company_id,:client_id,:branch_id,:name,:status)',['company_id'=>$result['company_id'],'client_id'=>$row['client_id'],'branch_id'=>$row['branch_id'],'name'=>'Site '.$i,'status'=>'ACTIVE']);
        try{$subscriptions->assertCanCreate('sites');self::fail('Expected site limit');}catch(HttpException $e){self::assertSame(409,$e->statusCode());}
    }
}
