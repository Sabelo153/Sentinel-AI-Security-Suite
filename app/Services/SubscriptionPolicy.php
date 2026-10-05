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
        $module = explode('.', $permission, 2)[0];
        $free = ['occurrence_book', 'sites', 'dashboard', 'subscriptions', 'incidents', 'visitors', 'vehicles', 'visitor_visits', 'access_events', 'inspections', 'alerts', 'notifications', 'people', 'employees', 'users', 'roles', 'permissions'];
        $essential = [...$free, 'clients', 'branches', 'departments', 'positions', 'employee_assignments', 'guard_site_assignments', 'posts', 'contracts', 'guards', 'shift_assignments', 'attendance', 'reports'];
        $professional = [...$essential, 'guard', 'patrol_routes', 'patrol_checkpoints', 'patrol_sessions', 'devices', 'device_assignments', 'device_sessions', 'locations', 'gps_locations', 'gps_events', 'geofences', 'supervisor'];
        return in_array($module, match ($plan) {'FREE' => $free, 'ESSENTIAL' => $essential, 'PROFESSIONAL' => $professional, default => []}, true);
    }
}
