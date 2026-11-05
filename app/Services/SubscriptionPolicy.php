<?php

declare(strict_types=1);

namespace Sentinel\Services;

final class SubscriptionPolicy
{
    /** @return array<string,array{name:string,description:string,trial_days:int,limits:array{users:?int,sites:?int}}> */
    public static function plans(): array
    {
        return [
            'FREE' => ['name' => 'Free', 'description' => 'Core digital security records for small teams, with unlimited record retention.', 'trial_days' => 0, 'limits' => ['users'=>10,'sites'=>3]],
            'ESSENTIAL' => ['name' => 'Essential', 'description' => 'Workforce administration, attendance, shifts and operational reporting.', 'trial_days' => 14, 'limits' => ['users'=>30,'sites'=>10]],
            'PROFESSIONAL' => ['name' => 'Professional', 'description' => 'Patrols, devices, GPS, geofences and supervisor workflows.', 'trial_days' => 14, 'limits' => ['users'=>100,'sites'=>50]],
            'ENTERPRISE' => ['name' => 'Enterprise', 'description' => 'All Sentinel modules, integration controls and rollout support.', 'trial_days' => 14, 'limits' => ['users'=>null,'sites'=>null]],
        ];
    }

    public static function isKnownPlan(string $plan): bool
    {
        return isset(self::plans()[$plan]);
    }

    public static function allows(string $plan, string $permission): bool
    {
        if ($plan === 'ENTERPRISE') return true;
        if ($permission === 'users.password_change') return true;
        // Recovery access is still explicitly controlled by RBAC on every plan.
        if (in_array($permission, ['support.view', 'support.manage', 'support.delegated_view', 'client_portal.read'], true)) return self::isKnownPlan($plan);
        $module = explode('.', $permission, 2)[0];
        if(in_array($module,['company_branding','company_workspace','workflow_languages','emergency_procedures'],true)||$permission==='key_register.self')return self::isKnownPlan($plan);
        if(in_array($module,['key_register','replacement_suggestions'],true))return in_array($plan,['ESSENTIAL','PROFESSIONAL','ENTERPRISE'],true);
        if(in_array($module,['site_access','site_workspace','site_scope'],true)||in_array($permission,['availability.self','client_requests.client','report_reviews.client','quotations.client'],true))return self::isKnownPlan($plan);
        if(in_array($module,['inspection_forms','client_requests','report_reviews','availability','quotations'],true))return in_array($plan,['ESSENTIAL','PROFESSIONAL','ENTERPRISE'],true);
        if($permission==='client_reports.schedule')return self::isKnownPlan($plan);
        if($module==='client_reports')return in_array($plan,['ESSENTIAL','PROFESSIONAL','ENTERPRISE'],true);
        if($permission==='service_levels.client_read')return self::isKnownPlan($plan);
        if(in_array($module,['site_instructions','handovers','retention'],true))return self::isKnownPlan($plan);
        if(in_array($module,['training','maintenance','service_levels'],true))return in_array($plan,['ESSENTIAL','PROFESSIONAL','ENTERPRISE'],true);
        if(in_array($module,['evidence','photo_reports'],true))return in_array($plan,['PROFESSIONAL','ENTERPRISE'],true);
        if(in_array($module,['readiness','device_health'],true))return self::isKnownPlan($plan);
        if($module==='gate_register')return self::isKnownPlan($plan);
        if(in_array($module,['rostering','billing'],true))return in_array($plan,['ESSENTIAL','PROFESSIONAL','ENTERPRISE'],true);
        if(in_array($module,['dispatch','lone_worker'],true))return in_array($plan,['PROFESSIONAL','ENTERPRISE'],true);
        $free = ['occurrence_book', 'sites', 'dashboard', 'subscriptions', 'incidents', 'visitors', 'vehicles', 'visitor_visits', 'access_events', 'inspections', 'alerts', 'notifications', 'people', 'employees', 'users', 'roles', 'permissions'];
        $essential = [...$free, 'compliance', 'equipment', 'client_portal', 'payroll_export', 'clients', 'branches', 'departments', 'positions', 'employee_assignments', 'guard_site_assignments', 'posts', 'contracts', 'guards', 'shift_assignments', 'attendance', 'reports'];
        $professional = [...$essential, 'managed_android', 'escalations', 'guard', 'patrol_routes', 'patrol_checkpoints', 'patrol_sessions', 'devices', 'device_assignments', 'device_sessions', 'locations', 'gps_locations', 'gps_events', 'geofences', 'supervisor'];
        return in_array($module, match ($plan) {'FREE' => $free, 'ESSENTIAL' => $essential, 'PROFESSIONAL' => $professional, default => []}, true);
    }
}
