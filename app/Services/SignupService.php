<?php

declare(strict_types=1);

namespace Sentinel\Services;

use Sentinel\Core\Database;
use Sentinel\Core\HttpException;

final class SignupService
{
    private const BILLING_MARKETS = [
        'ZA' => 'South Africa',
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'EU' => 'European Union',
        'AU' => 'Australia',
        'OTHER' => 'Other',
    ];

    public function __construct(private readonly Database $db) {}

    /** @param array<string,mixed> $input @param array<int,array<string,mixed>> $routes @return array<string,mixed> */
    public function signup(array $input, array $routes): array
    {
        $company = trim((string) ($input['company_name'] ?? ''));
        $first = trim((string) ($input['first_name'] ?? ''));
        $last = trim((string) ($input['last_name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $phone = trim((string) ($input['phone'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $plan = strtoupper(trim((string) ($input['plan_code'] ?? 'FREE')));
        $billingMarket = strtoupper(trim((string) ($input['billing_market'] ?? 'ZA')));
        $siteName = trim((string) ($input['site_name'] ?? 'Main Site')) ?: 'Main Site';
        if ($company === '' || $first === '' || $last === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw HttpException::unprocessable('Organisation, name and a valid email are required.', 'invalid_signup');
        if (mb_strlen($company) > 255 || mb_strlen($first) > 100 || mb_strlen($last) > 100 || mb_strlen($siteName) > 255) throw HttpException::unprocessable('One or more fields are too long.', 'invalid_signup');
        if (strlen($password) < 12) throw HttpException::unprocessable('Password must be at least 12 characters.', 'invalid_password');
        if (!SubscriptionPolicy::isKnownPlan($plan)) throw HttpException::unprocessable('Selected plan is not available.', 'invalid_plan');
        if (!array_key_exists($billingMarket, self::BILLING_MARKETS)) throw HttpException::unprocessable('Selected billing country or region is not available.', 'invalid_billing_market');
        if ($this->db->fetch('SELECT company_id FROM companies WHERE company_name = :name LIMIT 1', ['name' => $company]) !== null) throw HttpException::conflict('An organisation with that name already exists.', 'company_exists');
        if ($this->db->fetch('SELECT user_id FROM users WHERE email = :email LIMIT 1', ['email' => $email]) !== null) throw HttpException::conflict('An account with that email already exists.', 'email_exists');

        $pdo = $this->db->connection();
        $this->db->beginTransaction();
        try {
            $this->db->execute('INSERT INTO companies (company_name, phone, email, country, status) VALUES (:name,:phone,:email,:country,:status)', ['name'=>$company,'phone'=>$phone?:null,'email'=>$email,'country'=>self::BILLING_MARKETS[$billingMarket],'status'=>'ACTIVE']);
            $companyId = (string) $pdo->lastInsertId();
            $this->db->execute('INSERT INTO people (company_id,first_name,last_name,phone,email) VALUES (:company_id,:first,:last,:phone,:email)', ['company_id'=>$companyId,'first'=>$first,'last'=>$last,'phone'=>$phone?:null,'email'=>$email]);
            $personId = (string) $pdo->lastInsertId();
            $this->db->execute('INSERT INTO employees (person_id,company_id,employee_number,hire_date,employment_status) VALUES (:person_id,:company_id,:number,:hire_date,:status)', ['person_id'=>$personId,'company_id'=>$companyId,'number'=>'OWNER-001','hire_date'=>date('Y-m-d'),'status'=>'ACTIVE']);
            $employeeId = (string) $pdo->lastInsertId();
            $username = substr(preg_replace('/[^a-z0-9._-]/', '', explode('@', $email)[0]) ?: 'owner', 0, 80);
            $this->db->execute('INSERT INTO users (user_uuid,company_id,employee_id,username,email,password_hash,status) VALUES (:uuid,:company_id,:employee_id,:username,:email,:hash,:status)', ['uuid'=>uuid4(),'company_id'=>$companyId,'employee_id'=>$employeeId,'username'=>$username,'email'=>$email,'hash'=>password_hash($password,PASSWORD_DEFAULT),'status'=>'ACTIVE']);
            $userId = (string) $pdo->lastInsertId();
            $this->db->execute('INSERT INTO roles (company_id,role_name,role_description,is_system_role,status) VALUES (:company_id,:name,:description,:system,:status)', ['company_id'=>$companyId,'name'=>'Organisation Owner','description'=>'Initial organisation owner for the selected Sentinel plan.','system'=>1,'status'=>'ACTIVE']);
            $roleId = (string) $pdo->lastInsertId();
            $this->db->execute('INSERT INTO user_roles (company_id,user_id,role_id,assigned_by_user_id) VALUES (:company_id,:user_id,:role_id,:assigned_by)', ['company_id'=>$companyId,'user_id'=>$userId,'role_id'=>$roleId,'assigned_by'=>$userId]);

            $clientId = $this->insertAndId('INSERT INTO clients (company_id,client_name,status) VALUES (:company_id,:name,:status)', ['company_id'=>$companyId,'name'=>$company,'status'=>'ACTIVE']);
            $branchId = $this->insertAndId('INSERT INTO branches (company_id,client_id,branch_name,status) VALUES (:company_id,:client_id,:name,:status)', ['company_id'=>$companyId,'client_id'=>$clientId,'name'=>'Main Branch','status'=>'ACTIVE']);
            $this->db->execute('INSERT INTO sites (company_id,client_id,branch_id,site_name,status) VALUES (:company_id,:client_id,:branch_id,:name,:status)', ['company_id'=>$companyId,'client_id'=>$clientId,'branch_id'=>$branchId,'name'=>$siteName,'status'=>'ACTIVE']);

            $trialDays = SubscriptionPolicy::plans()[$plan]['trial_days'];
            $status = $trialDays > 0 ? 'TRIALING' : 'ACTIVE';
            $trialEnd = $trialDays > 0 ? date('Y-m-d H:i:s', time() + $trialDays * 86400) : null;
            $this->db->execute('INSERT INTO company_subscriptions (company_id,plan_code,subscription_status,trial_ends_at) VALUES (:company_id,:plan,:status,:trial_end)', ['company_id'=>$companyId,'plan'=>$plan,'status'=>$status,'trial_end'=>$trialEnd]);
            $permissions = array_values(array_unique(array_filter(array_column($routes, 'permission'), 'is_string')));
            foreach ($permissions as $permission) {
                if (!SubscriptionPolicy::allows($plan, $permission)) continue;
                [$module,$action] = array_pad(explode('.', $permission, 2), 2, 'access');
                $this->db->execute('INSERT INTO permissions (company_id,permission_code,permission_name,module_name,action_name,permission_description,status) VALUES (:company_id,:code,:name,:module,:action,:description,:status)', ['company_id'=>$companyId,'code'=>$permission,'name'=>$permission,'module'=>$module,'action'=>$action,'description'=>'Granted by Sentinel subscription plan.','status'=>'ACTIVE']);
                $permissionId = (string) $pdo->lastInsertId();
                $this->db->execute('INSERT INTO role_permissions (company_id,role_id,permission_id,granted_by_user_id) VALUES (:company_id,:role_id,:permission_id,:granted_by)', ['company_id'=>$companyId,'role_id'=>$roleId,'permission_id'=>$permissionId,'granted_by'=>$userId]);
            }
            $this->db->commit();
            return ['company_id'=>$companyId,'user_id'=>$userId,'username'=>$username,'plan_code'=>$plan,'billing_market'=>$billingMarket,'subscription_status'=>$status,'trial_ends_at'=>$trialEnd];
        } catch (HttpException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw HttpException::serviceUnavailable('Account creation is temporarily unavailable.', 'signup_unavailable');
        }
    }

    /** @param array<string,mixed> $params */
    private function insertAndId(string $sql, array $params): string
    {
        $this->db->execute($sql,$params);
        return (string) $this->db->connection()->lastInsertId();
    }
}
