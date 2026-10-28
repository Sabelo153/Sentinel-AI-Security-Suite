-- Review in an isolated database first. Set @sentinel_company_id explicitly.
-- This file does not grant roles or rename legacy permissions.
-- Preflight: any row below is a naming conflict requiring review.
SELECT p.permission_code AS existing_code, seed.permission_code AS requested_code
FROM permissions p JOIN (
SELECT 'access_events.create' AS permission_code, 'access_events' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'access_events.view' AS permission_code, 'access_events' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'alerts.create' AS permission_code, 'alerts' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'alerts.manage' AS permission_code, 'alerts' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'alerts.view' AS permission_code, 'alerts' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'attendance.create' AS permission_code, 'attendance' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'attendance.update' AS permission_code, 'attendance' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'attendance.view' AS permission_code, 'attendance' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'branches.create' AS permission_code, 'branches' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'branches.update' AS permission_code, 'branches' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'branches.view' AS permission_code, 'branches' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'clients.create' AS permission_code, 'clients' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'clients.update' AS permission_code, 'clients' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'clients.view' AS permission_code, 'clients' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'contracts.create' AS permission_code, 'contracts' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'contracts.update' AS permission_code, 'contracts' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'contracts.view' AS permission_code, 'contracts' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'dashboard.view' AS permission_code, 'dashboard' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'departments.create' AS permission_code, 'departments' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'departments.update' AS permission_code, 'departments' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'departments.view' AS permission_code, 'departments' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'device_assignments.manage' AS permission_code, 'device_assignments' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'device_assignments.view' AS permission_code, 'device_assignments' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'device_sessions.manage' AS permission_code, 'device_sessions' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'device_sessions.view' AS permission_code, 'device_sessions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'devices.create' AS permission_code, 'devices' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'devices.update' AS permission_code, 'devices' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'devices.view' AS permission_code, 'devices' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'employee_assignments.create' AS permission_code, 'employee_assignments' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'employee_assignments.update' AS permission_code, 'employee_assignments' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'employee_assignments.view' AS permission_code, 'employee_assignments' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'employees.create' AS permission_code, 'employees' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'employees.update' AS permission_code, 'employees' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'employees.view' AS permission_code, 'employees' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'geofences.create' AS permission_code, 'geofences' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'geofences.update' AS permission_code, 'geofences' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'geofences.view' AS permission_code, 'geofences' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'guard.self' AS permission_code, 'guard' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'guard_site_assignments.create' AS permission_code, 'guard_site_assignments' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'guard_site_assignments.update' AS permission_code, 'guard_site_assignments' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'guard_site_assignments.view' AS permission_code, 'guard_site_assignments' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'guards.create' AS permission_code, 'guards' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'guards.update' AS permission_code, 'guards' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'guards.view' AS permission_code, 'guards' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'incidents.create' AS permission_code, 'incidents' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'incidents.update' AS permission_code, 'incidents' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'incidents.view' AS permission_code, 'incidents' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'inspections.create' AS permission_code, 'inspections' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'inspections.review' AS permission_code, 'inspections' AS module_name, 'review' AS action_name
UNION ALL
SELECT 'inspections.update' AS permission_code, 'inspections' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'inspections.view' AS permission_code, 'inspections' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'locations.create' AS permission_code, 'locations' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'locations.view' AS permission_code, 'locations' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'notifications.create' AS permission_code, 'notifications' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'notifications.view' AS permission_code, 'notifications' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'occurrence_book.create' AS permission_code, 'occurrence_book' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'occurrence_book.revise' AS permission_code, 'occurrence_book' AS module_name, 'revise' AS action_name
UNION ALL
SELECT 'occurrence_book.view' AS permission_code, 'occurrence_book' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'patrol_checkpoint_events.create' AS permission_code, 'patrol_checkpoint_events' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'patrol_checkpoint_events.view' AS permission_code, 'patrol_checkpoint_events' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'patrol_checkpoints.create' AS permission_code, 'patrol_checkpoints' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'patrol_checkpoints.update' AS permission_code, 'patrol_checkpoints' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'patrol_checkpoints.view' AS permission_code, 'patrol_checkpoints' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'patrol_routes.create' AS permission_code, 'patrol_routes' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'patrol_routes.update' AS permission_code, 'patrol_routes' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'patrol_routes.view' AS permission_code, 'patrol_routes' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'patrol_sessions.create' AS permission_code, 'patrol_sessions' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'patrol_sessions.update' AS permission_code, 'patrol_sessions' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'patrol_sessions.view' AS permission_code, 'patrol_sessions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'people.create' AS permission_code, 'people' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'people.update' AS permission_code, 'people' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'people.view' AS permission_code, 'people' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'permissions.view' AS permission_code, 'permissions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'positions.create' AS permission_code, 'positions' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'positions.update' AS permission_code, 'positions' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'positions.view' AS permission_code, 'positions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'posts.create' AS permission_code, 'posts' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'posts.update' AS permission_code, 'posts' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'posts.view' AS permission_code, 'posts' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'reports.view' AS permission_code, 'reports' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'roles.assign_permissions' AS permission_code, 'roles' AS module_name, 'assign_permissions' AS action_name
UNION ALL
SELECT 'roles.create' AS permission_code, 'roles' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'roles.update' AS permission_code, 'roles' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'roles.view' AS permission_code, 'roles' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'shift_assignments.create' AS permission_code, 'shift_assignments' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'shift_assignments.update' AS permission_code, 'shift_assignments' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'shift_assignments.view' AS permission_code, 'shift_assignments' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'sites.create' AS permission_code, 'sites' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'sites.update' AS permission_code, 'sites' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'sites.view' AS permission_code, 'sites' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'subscriptions.view' AS permission_code, 'subscriptions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'supervisor.view' AS permission_code, 'supervisor' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'system.test' AS permission_code, 'system' AS module_name, 'test' AS action_name
UNION ALL
SELECT 'user_roles.assign' AS permission_code, 'user_roles' AS module_name, 'assign' AS action_name
UNION ALL
SELECT 'user_roles.view' AS permission_code, 'user_roles' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'users.create' AS permission_code, 'users' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'users.password_change' AS permission_code, 'users' AS module_name, 'password_change' AS action_name
UNION ALL
SELECT 'users.unlock' AS permission_code, 'users' AS module_name, 'unlock' AS action_name
UNION ALL
SELECT 'users.update' AS permission_code, 'users' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'support.view' AS permission_code, 'support' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'support.manage' AS permission_code, 'support' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'users.view' AS permission_code, 'users' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'vehicles.create' AS permission_code, 'vehicles' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'vehicles.update' AS permission_code, 'vehicles' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'vehicles.view' AS permission_code, 'vehicles' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'visitor_visits.create' AS permission_code, 'visitor_visits' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'visitor_visits.manage' AS permission_code, 'visitor_visits' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'visitor_visits.view' AS permission_code, 'visitor_visits' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'visitors.create' AS permission_code, 'visitors' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'visitors.update' AS permission_code, 'visitors' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'visitors.view' AS permission_code, 'visitors' AS module_name, 'view' AS action_name
) seed ON p.module_name=seed.module_name AND p.action_name=seed.action_name
WHERE p.company_id=@sentinel_company_id AND p.permission_code<>seed.permission_code;

INSERT INTO permissions (company_id,permission_code,permission_name,module_name,action_name,permission_description,status)
SELECT c.company_id,seed.permission_code,seed.permission_code,seed.module_name,seed.action_name,'Canonical protected API permission','ACTIVE'
FROM companies c JOIN (
SELECT 'access_events.create' AS permission_code, 'access_events' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'access_events.view' AS permission_code, 'access_events' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'alerts.create' AS permission_code, 'alerts' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'alerts.manage' AS permission_code, 'alerts' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'alerts.view' AS permission_code, 'alerts' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'attendance.create' AS permission_code, 'attendance' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'attendance.update' AS permission_code, 'attendance' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'attendance.view' AS permission_code, 'attendance' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'branches.create' AS permission_code, 'branches' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'branches.update' AS permission_code, 'branches' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'branches.view' AS permission_code, 'branches' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'clients.create' AS permission_code, 'clients' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'clients.update' AS permission_code, 'clients' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'clients.view' AS permission_code, 'clients' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'contracts.create' AS permission_code, 'contracts' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'contracts.update' AS permission_code, 'contracts' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'contracts.view' AS permission_code, 'contracts' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'dashboard.view' AS permission_code, 'dashboard' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'departments.create' AS permission_code, 'departments' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'departments.update' AS permission_code, 'departments' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'departments.view' AS permission_code, 'departments' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'device_assignments.manage' AS permission_code, 'device_assignments' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'device_assignments.view' AS permission_code, 'device_assignments' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'device_sessions.manage' AS permission_code, 'device_sessions' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'device_sessions.view' AS permission_code, 'device_sessions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'devices.create' AS permission_code, 'devices' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'devices.update' AS permission_code, 'devices' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'devices.view' AS permission_code, 'devices' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'employee_assignments.create' AS permission_code, 'employee_assignments' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'employee_assignments.update' AS permission_code, 'employee_assignments' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'employee_assignments.view' AS permission_code, 'employee_assignments' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'employees.create' AS permission_code, 'employees' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'employees.update' AS permission_code, 'employees' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'employees.view' AS permission_code, 'employees' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'geofences.create' AS permission_code, 'geofences' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'geofences.update' AS permission_code, 'geofences' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'geofences.view' AS permission_code, 'geofences' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'guard.self' AS permission_code, 'guard' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'guard_site_assignments.create' AS permission_code, 'guard_site_assignments' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'guard_site_assignments.update' AS permission_code, 'guard_site_assignments' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'guard_site_assignments.view' AS permission_code, 'guard_site_assignments' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'guards.create' AS permission_code, 'guards' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'guards.update' AS permission_code, 'guards' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'guards.view' AS permission_code, 'guards' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'incidents.create' AS permission_code, 'incidents' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'incidents.update' AS permission_code, 'incidents' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'incidents.view' AS permission_code, 'incidents' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'inspections.create' AS permission_code, 'inspections' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'inspections.review' AS permission_code, 'inspections' AS module_name, 'review' AS action_name
UNION ALL
SELECT 'inspections.update' AS permission_code, 'inspections' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'inspections.view' AS permission_code, 'inspections' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'locations.create' AS permission_code, 'locations' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'locations.view' AS permission_code, 'locations' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'notifications.create' AS permission_code, 'notifications' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'notifications.view' AS permission_code, 'notifications' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'occurrence_book.create' AS permission_code, 'occurrence_book' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'occurrence_book.revise' AS permission_code, 'occurrence_book' AS module_name, 'revise' AS action_name
UNION ALL
SELECT 'occurrence_book.view' AS permission_code, 'occurrence_book' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'patrol_checkpoint_events.create' AS permission_code, 'patrol_checkpoint_events' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'patrol_checkpoint_events.view' AS permission_code, 'patrol_checkpoint_events' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'patrol_checkpoints.create' AS permission_code, 'patrol_checkpoints' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'patrol_checkpoints.update' AS permission_code, 'patrol_checkpoints' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'patrol_checkpoints.view' AS permission_code, 'patrol_checkpoints' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'patrol_routes.create' AS permission_code, 'patrol_routes' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'patrol_routes.update' AS permission_code, 'patrol_routes' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'patrol_routes.view' AS permission_code, 'patrol_routes' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'patrol_sessions.create' AS permission_code, 'patrol_sessions' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'patrol_sessions.update' AS permission_code, 'patrol_sessions' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'patrol_sessions.view' AS permission_code, 'patrol_sessions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'people.create' AS permission_code, 'people' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'people.update' AS permission_code, 'people' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'people.view' AS permission_code, 'people' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'permissions.view' AS permission_code, 'permissions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'positions.create' AS permission_code, 'positions' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'positions.update' AS permission_code, 'positions' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'positions.view' AS permission_code, 'positions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'posts.create' AS permission_code, 'posts' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'posts.update' AS permission_code, 'posts' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'posts.view' AS permission_code, 'posts' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'reports.view' AS permission_code, 'reports' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'roles.assign_permissions' AS permission_code, 'roles' AS module_name, 'assign_permissions' AS action_name
UNION ALL
SELECT 'roles.create' AS permission_code, 'roles' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'roles.update' AS permission_code, 'roles' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'roles.view' AS permission_code, 'roles' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'shift_assignments.create' AS permission_code, 'shift_assignments' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'shift_assignments.update' AS permission_code, 'shift_assignments' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'shift_assignments.view' AS permission_code, 'shift_assignments' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'sites.create' AS permission_code, 'sites' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'sites.update' AS permission_code, 'sites' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'sites.view' AS permission_code, 'sites' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'subscriptions.view' AS permission_code, 'subscriptions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'supervisor.view' AS permission_code, 'supervisor' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'system.test' AS permission_code, 'system' AS module_name, 'test' AS action_name
UNION ALL
SELECT 'user_roles.assign' AS permission_code, 'user_roles' AS module_name, 'assign' AS action_name
UNION ALL
SELECT 'user_roles.view' AS permission_code, 'user_roles' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'users.create' AS permission_code, 'users' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'users.password_change' AS permission_code, 'users' AS module_name, 'password_change' AS action_name
UNION ALL
SELECT 'users.unlock' AS permission_code, 'users' AS module_name, 'unlock' AS action_name
UNION ALL
SELECT 'users.update' AS permission_code, 'users' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'support.view' AS permission_code, 'support' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'support.manage' AS permission_code, 'support' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'users.view' AS permission_code, 'users' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'vehicles.create' AS permission_code, 'vehicles' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'vehicles.update' AS permission_code, 'vehicles' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'vehicles.view' AS permission_code, 'vehicles' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'visitor_visits.create' AS permission_code, 'visitor_visits' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'visitor_visits.manage' AS permission_code, 'visitor_visits' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'visitor_visits.view' AS permission_code, 'visitor_visits' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'visitors.create' AS permission_code, 'visitors' AS module_name, 'create' AS action_name
UNION ALL
SELECT 'visitors.update' AS permission_code, 'visitors' AS module_name, 'update' AS action_name
UNION ALL
SELECT 'visitors.view' AS permission_code, 'visitors' AS module_name, 'view' AS action_name
) seed ON 1=1
WHERE c.company_id=@sentinel_company_id
AND NOT EXISTS (SELECT 1 FROM permissions p WHERE p.company_id=c.company_id AND (p.permission_code=seed.permission_code OR (p.module_name=seed.module_name AND p.action_name=seed.action_name)));
