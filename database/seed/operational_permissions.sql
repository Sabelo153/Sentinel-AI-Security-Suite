-- Review in an isolated database first. Set @sentinel_company_id explicitly.
-- This file does not grant roles or rename legacy permissions.
-- Preflight: any row below is a naming conflict requiring review.
SELECT p.permission_code AS existing_code, seed.permission_code AS requested_code
FROM permissions p JOIN (
SELECT 'learning.view' AS permission_code, 'learning' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'learning.manage' AS permission_code, 'learning' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'learning.self' AS permission_code, 'learning' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'lost_found.view' AS permission_code, 'lost_found' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'lost_found.manage' AS permission_code, 'lost_found' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'lost_found.dispose' AS permission_code, 'lost_found' AS module_name, 'dispose' AS action_name
UNION ALL
SELECT 'investigations.view' AS permission_code, 'investigations' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'investigations.manage' AS permission_code, 'investigations' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'investigations.review' AS permission_code, 'investigations' AS module_name, 'review' AS action_name
UNION ALL
SELECT 'stock.view' AS permission_code, 'stock' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'stock.manage' AS permission_code, 'stock' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'stock.adjust' AS permission_code, 'stock' AS module_name, 'adjust' AS action_name
UNION ALL
SELECT 'stock.self' AS permission_code, 'stock' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'alarm_response.view' AS permission_code, 'alarm_response' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'alarm_response.manage' AS permission_code, 'alarm_response' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'guard_transport.view' AS permission_code, 'guard_transport' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'guard_transport.manage' AS permission_code, 'guard_transport' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'post_orders.view' AS permission_code, 'post_orders' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'post_orders.manage' AS permission_code, 'post_orders' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'continuity.view' AS permission_code, 'continuity' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'continuity.manage' AS permission_code, 'continuity' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'equipment_checks.view' AS permission_code, 'equipment_checks' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'equipment_checks.manage' AS permission_code, 'equipment_checks' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'control_handover.view' AS permission_code, 'control_handover' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'control_handover.manage' AS permission_code, 'control_handover' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'extra_services.view' AS permission_code, 'extra_services' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'extra_services.manage' AS permission_code, 'extra_services' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'client_mobilisation.view' AS permission_code, 'client_mobilisation' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'client_mobilisation.manage' AS permission_code, 'client_mobilisation' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'risk_assessments.view' AS permission_code, 'risk_assessments' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'risk_assessments.manage' AS permission_code, 'risk_assessments' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'subcontractors.view' AS permission_code, 'subcontractors' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'subcontractors.manage' AS permission_code, 'subcontractors' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'alarm_response.self' AS permission_code, 'alarm_response' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'guard_transport.self' AS permission_code, 'guard_transport' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'post_orders.self' AS permission_code, 'post_orders' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'continuity.self' AS permission_code, 'continuity' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'extra_services.client' AS permission_code, 'extra_services' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'client_mobilisation.client' AS permission_code, 'client_mobilisation' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'risk_assessments.client' AS permission_code, 'risk_assessments' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'operations_analytics.view' AS permission_code, 'operations_analytics' AS module_name, 'view' AS action_name
UNION ALL
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
SELECT 'dispatch.view' AS permission_code, 'dispatch' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'dispatch.manage' AS permission_code, 'dispatch' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'lone_worker.view' AS permission_code, 'lone_worker' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'lone_worker.manage' AS permission_code, 'lone_worker' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'rostering.view' AS permission_code, 'rostering' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'rostering.manage' AS permission_code, 'rostering' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'gate_register.view' AS permission_code, 'gate_register' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'gate_register.manage' AS permission_code, 'gate_register' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'billing.view' AS permission_code, 'billing' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'billing.manage' AS permission_code, 'billing' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'device_health.view' AS permission_code, 'device_health' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'readiness.view' AS permission_code, 'readiness' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'readiness.manage' AS permission_code, 'readiness' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'site_instructions.view' AS permission_code, 'site_instructions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'site_instructions.manage' AS permission_code, 'site_instructions' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'handovers.view' AS permission_code, 'handovers' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'handovers.manage' AS permission_code, 'handovers' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'training.view' AS permission_code, 'training' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'training.manage' AS permission_code, 'training' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'evidence.view' AS permission_code, 'evidence' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'evidence.manage' AS permission_code, 'evidence' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'service_levels.view' AS permission_code, 'service_levels' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'shift_offers.view' AS permission_code, 'shift_offers' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'shift_offers.manage' AS permission_code, 'shift_offers' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'shift_offers.self' AS permission_code, 'shift_offers' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'visitor_invites.view' AS permission_code, 'visitor_invites' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'visitor_invites.manage' AS permission_code, 'visitor_invites' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'visitor_invites.client' AS permission_code, 'visitor_invites' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'visitor_invites.gate' AS permission_code, 'visitor_invites' AS module_name, 'gate' AS action_name
UNION ALL
SELECT 'fleet.view' AS permission_code, 'fleet' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'fleet.manage' AS permission_code, 'fleet' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'fleet.self' AS permission_code, 'fleet' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'renewals.view' AS permission_code, 'renewals' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'renewals.manage' AS permission_code, 'renewals' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'renewals.client' AS permission_code, 'renewals' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'backups.view' AS permission_code, 'backups' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'backups.manage' AS permission_code, 'backups' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'backups.run' AS permission_code, 'backups' AS module_name, 'run' AS action_name
UNION ALL
SELECT 'pilot_setup.view' AS permission_code, 'pilot_setup' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'pilot_setup.manage' AS permission_code, 'pilot_setup' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'business_ops.run' AS permission_code, 'business_ops' AS module_name, 'run' AS action_name
UNION ALL
SELECT 'radio.self' AS permission_code, 'radio' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'radio.manage' AS permission_code, 'radio' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'control_map.view' AS permission_code, 'control_map' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'service_reviews.view' AS permission_code, 'service_reviews' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'service_reviews.manage' AS permission_code, 'service_reviews' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'service_reviews.client' AS permission_code, 'service_reviews' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'integrations.deliver' AS permission_code, 'integrations' AS module_name, 'deliver' AS action_name
UNION ALL
SELECT 'integrations.view' AS permission_code, 'integrations' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'integrations.manage' AS permission_code, 'integrations' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'customer_support.view' AS permission_code, 'customer_support' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'customer_support.manage' AS permission_code, 'customer_support' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'customer_support.self' AS permission_code, 'customer_support' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'release_notices.view' AS permission_code, 'release_notices' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'release_notices.manage' AS permission_code, 'release_notices' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'operational_alerts.view' AS permission_code, 'operational_alerts' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'operational_alerts.manage' AS permission_code, 'operational_alerts' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'key_register.view' AS permission_code, 'key_register' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'key_register.manage' AS permission_code, 'key_register' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'key_register.self' AS permission_code, 'key_register' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'replacement_suggestions.view' AS permission_code, 'replacement_suggestions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'replacement_suggestions.manage' AS permission_code, 'replacement_suggestions' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'emergency_procedures.view' AS permission_code, 'emergency_procedures' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'emergency_procedures.manage' AS permission_code, 'emergency_procedures' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'workflow_languages.self' AS permission_code, 'workflow_languages' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'workflow_languages.view' AS permission_code, 'workflow_languages' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'workflow_languages.manage' AS permission_code, 'workflow_languages' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'company_branding.read' AS permission_code, 'company_branding' AS module_name, 'read' AS action_name
UNION ALL
SELECT 'company_workspace.view' AS permission_code, 'company_workspace' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'company_workspace.manage' AS permission_code, 'company_workspace' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'site_access.view' AS permission_code, 'site_access' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'site_access.manage' AS permission_code, 'site_access' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'site_workspace.view' AS permission_code, 'site_workspace' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'inspection_forms.view' AS permission_code, 'inspection_forms' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'inspection_forms.manage' AS permission_code, 'inspection_forms' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'client_requests.view' AS permission_code, 'client_requests' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'client_requests.manage' AS permission_code, 'client_requests' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'client_requests.client' AS permission_code, 'client_requests' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'availability.view' AS permission_code, 'availability' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'availability.manage' AS permission_code, 'availability' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'availability.self' AS permission_code, 'availability' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'report_reviews.view' AS permission_code, 'report_reviews' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'report_reviews.client' AS permission_code, 'report_reviews' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'quotations.view' AS permission_code, 'quotations' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'quotations.manage' AS permission_code, 'quotations' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'quotations.client' AS permission_code, 'quotations' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'site_scope.restricted' AS permission_code, 'site_scope' AS module_name, 'restricted' AS action_name
UNION ALL
SELECT 'service_levels.manage' AS permission_code, 'service_levels' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'maintenance.view' AS permission_code, 'maintenance' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'maintenance.manage' AS permission_code, 'maintenance' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'retention.view' AS permission_code, 'retention' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'retention.manage' AS permission_code, 'retention' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'evidence.export' AS permission_code, 'evidence' AS module_name, 'export' AS action_name
UNION ALL
SELECT 'service_levels.client_read' AS permission_code, 'service_levels' AS module_name, 'client_read' AS action_name
UNION ALL
SELECT 'retention.hold' AS permission_code, 'retention' AS module_name, 'hold' AS action_name
UNION ALL
SELECT 'photo_reports.view' AS permission_code, 'photo_reports' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'photo_reports.manage' AS permission_code, 'photo_reports' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'photo_reports.export' AS permission_code, 'photo_reports' AS module_name, 'export' AS action_name
UNION ALL
SELECT 'client_reports.view' AS permission_code, 'client_reports' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'client_reports.manage' AS permission_code, 'client_reports' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'client_reports.schedule' AS permission_code, 'client_reports' AS module_name, 'schedule' AS action_name
UNION ALL
SELECT 'support.view' AS permission_code, 'support' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'support.manage' AS permission_code, 'support' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'managed_android.view' AS permission_code, 'managed_android' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'managed_android.manage' AS permission_code, 'managed_android' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'compliance.view' AS permission_code, 'compliance' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'compliance.manage' AS permission_code, 'compliance' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'equipment.view' AS permission_code, 'equipment' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'equipment.manage' AS permission_code, 'equipment' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'escalations.view' AS permission_code, 'escalations' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'escalations.manage' AS permission_code, 'escalations' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'client_portal.view' AS permission_code, 'client_portal' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'client_portal.manage' AS permission_code, 'client_portal' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'client_portal.read' AS permission_code, 'client_portal' AS module_name, 'read' AS action_name
UNION ALL
SELECT 'payroll_export.view' AS permission_code, 'payroll_export' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'payroll_export.manage' AS permission_code, 'payroll_export' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'support.delegated_view' AS permission_code, 'support' AS module_name, 'delegated_view' AS action_name
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
SELECT 'learning.view' AS permission_code, 'learning' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'learning.manage' AS permission_code, 'learning' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'learning.self' AS permission_code, 'learning' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'lost_found.view' AS permission_code, 'lost_found' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'lost_found.manage' AS permission_code, 'lost_found' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'lost_found.dispose' AS permission_code, 'lost_found' AS module_name, 'dispose' AS action_name
UNION ALL
SELECT 'investigations.view' AS permission_code, 'investigations' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'investigations.manage' AS permission_code, 'investigations' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'investigations.review' AS permission_code, 'investigations' AS module_name, 'review' AS action_name
UNION ALL
SELECT 'stock.view' AS permission_code, 'stock' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'stock.manage' AS permission_code, 'stock' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'stock.adjust' AS permission_code, 'stock' AS module_name, 'adjust' AS action_name
UNION ALL
SELECT 'stock.self' AS permission_code, 'stock' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'alarm_response.view' AS permission_code, 'alarm_response' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'alarm_response.manage' AS permission_code, 'alarm_response' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'guard_transport.view' AS permission_code, 'guard_transport' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'guard_transport.manage' AS permission_code, 'guard_transport' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'post_orders.view' AS permission_code, 'post_orders' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'post_orders.manage' AS permission_code, 'post_orders' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'continuity.view' AS permission_code, 'continuity' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'continuity.manage' AS permission_code, 'continuity' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'equipment_checks.view' AS permission_code, 'equipment_checks' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'equipment_checks.manage' AS permission_code, 'equipment_checks' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'control_handover.view' AS permission_code, 'control_handover' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'control_handover.manage' AS permission_code, 'control_handover' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'extra_services.view' AS permission_code, 'extra_services' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'extra_services.manage' AS permission_code, 'extra_services' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'client_mobilisation.view' AS permission_code, 'client_mobilisation' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'client_mobilisation.manage' AS permission_code, 'client_mobilisation' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'risk_assessments.view' AS permission_code, 'risk_assessments' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'risk_assessments.manage' AS permission_code, 'risk_assessments' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'subcontractors.view' AS permission_code, 'subcontractors' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'subcontractors.manage' AS permission_code, 'subcontractors' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'alarm_response.self' AS permission_code, 'alarm_response' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'guard_transport.self' AS permission_code, 'guard_transport' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'post_orders.self' AS permission_code, 'post_orders' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'continuity.self' AS permission_code, 'continuity' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'extra_services.client' AS permission_code, 'extra_services' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'client_mobilisation.client' AS permission_code, 'client_mobilisation' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'risk_assessments.client' AS permission_code, 'risk_assessments' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'operations_analytics.view' AS permission_code, 'operations_analytics' AS module_name, 'view' AS action_name
UNION ALL
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
SELECT 'dispatch.view' AS permission_code, 'dispatch' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'dispatch.manage' AS permission_code, 'dispatch' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'lone_worker.view' AS permission_code, 'lone_worker' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'lone_worker.manage' AS permission_code, 'lone_worker' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'rostering.view' AS permission_code, 'rostering' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'rostering.manage' AS permission_code, 'rostering' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'gate_register.view' AS permission_code, 'gate_register' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'gate_register.manage' AS permission_code, 'gate_register' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'billing.view' AS permission_code, 'billing' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'billing.manage' AS permission_code, 'billing' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'device_health.view' AS permission_code, 'device_health' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'readiness.view' AS permission_code, 'readiness' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'readiness.manage' AS permission_code, 'readiness' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'site_instructions.view' AS permission_code, 'site_instructions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'site_instructions.manage' AS permission_code, 'site_instructions' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'handovers.view' AS permission_code, 'handovers' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'handovers.manage' AS permission_code, 'handovers' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'training.view' AS permission_code, 'training' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'training.manage' AS permission_code, 'training' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'evidence.view' AS permission_code, 'evidence' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'evidence.manage' AS permission_code, 'evidence' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'service_levels.view' AS permission_code, 'service_levels' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'shift_offers.view' AS permission_code, 'shift_offers' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'shift_offers.manage' AS permission_code, 'shift_offers' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'shift_offers.self' AS permission_code, 'shift_offers' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'visitor_invites.view' AS permission_code, 'visitor_invites' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'visitor_invites.manage' AS permission_code, 'visitor_invites' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'visitor_invites.client' AS permission_code, 'visitor_invites' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'visitor_invites.gate' AS permission_code, 'visitor_invites' AS module_name, 'gate' AS action_name
UNION ALL
SELECT 'fleet.view' AS permission_code, 'fleet' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'fleet.manage' AS permission_code, 'fleet' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'fleet.self' AS permission_code, 'fleet' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'renewals.view' AS permission_code, 'renewals' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'renewals.manage' AS permission_code, 'renewals' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'renewals.client' AS permission_code, 'renewals' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'backups.view' AS permission_code, 'backups' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'backups.manage' AS permission_code, 'backups' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'backups.run' AS permission_code, 'backups' AS module_name, 'run' AS action_name
UNION ALL
SELECT 'pilot_setup.view' AS permission_code, 'pilot_setup' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'pilot_setup.manage' AS permission_code, 'pilot_setup' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'business_ops.run' AS permission_code, 'business_ops' AS module_name, 'run' AS action_name
UNION ALL
SELECT 'radio.self' AS permission_code, 'radio' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'radio.manage' AS permission_code, 'radio' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'control_map.view' AS permission_code, 'control_map' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'service_reviews.view' AS permission_code, 'service_reviews' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'service_reviews.manage' AS permission_code, 'service_reviews' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'service_reviews.client' AS permission_code, 'service_reviews' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'integrations.deliver' AS permission_code, 'integrations' AS module_name, 'deliver' AS action_name
UNION ALL
SELECT 'integrations.view' AS permission_code, 'integrations' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'integrations.manage' AS permission_code, 'integrations' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'customer_support.view' AS permission_code, 'customer_support' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'customer_support.manage' AS permission_code, 'customer_support' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'customer_support.self' AS permission_code, 'customer_support' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'release_notices.view' AS permission_code, 'release_notices' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'release_notices.manage' AS permission_code, 'release_notices' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'operational_alerts.view' AS permission_code, 'operational_alerts' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'operational_alerts.manage' AS permission_code, 'operational_alerts' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'key_register.view' AS permission_code, 'key_register' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'key_register.manage' AS permission_code, 'key_register' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'key_register.self' AS permission_code, 'key_register' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'replacement_suggestions.view' AS permission_code, 'replacement_suggestions' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'replacement_suggestions.manage' AS permission_code, 'replacement_suggestions' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'emergency_procedures.view' AS permission_code, 'emergency_procedures' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'emergency_procedures.manage' AS permission_code, 'emergency_procedures' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'workflow_languages.self' AS permission_code, 'workflow_languages' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'workflow_languages.view' AS permission_code, 'workflow_languages' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'workflow_languages.manage' AS permission_code, 'workflow_languages' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'company_branding.read' AS permission_code, 'company_branding' AS module_name, 'read' AS action_name
UNION ALL
SELECT 'company_workspace.view' AS permission_code, 'company_workspace' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'company_workspace.manage' AS permission_code, 'company_workspace' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'site_access.view' AS permission_code, 'site_access' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'site_access.manage' AS permission_code, 'site_access' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'site_workspace.view' AS permission_code, 'site_workspace' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'inspection_forms.view' AS permission_code, 'inspection_forms' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'inspection_forms.manage' AS permission_code, 'inspection_forms' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'client_requests.view' AS permission_code, 'client_requests' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'client_requests.manage' AS permission_code, 'client_requests' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'client_requests.client' AS permission_code, 'client_requests' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'availability.view' AS permission_code, 'availability' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'availability.manage' AS permission_code, 'availability' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'availability.self' AS permission_code, 'availability' AS module_name, 'self' AS action_name
UNION ALL
SELECT 'report_reviews.view' AS permission_code, 'report_reviews' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'report_reviews.client' AS permission_code, 'report_reviews' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'quotations.view' AS permission_code, 'quotations' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'quotations.manage' AS permission_code, 'quotations' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'quotations.client' AS permission_code, 'quotations' AS module_name, 'client' AS action_name
UNION ALL
SELECT 'site_scope.restricted' AS permission_code, 'site_scope' AS module_name, 'restricted' AS action_name
UNION ALL
SELECT 'service_levels.manage' AS permission_code, 'service_levels' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'maintenance.view' AS permission_code, 'maintenance' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'maintenance.manage' AS permission_code, 'maintenance' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'retention.view' AS permission_code, 'retention' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'retention.manage' AS permission_code, 'retention' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'evidence.export' AS permission_code, 'evidence' AS module_name, 'export' AS action_name
UNION ALL
SELECT 'service_levels.client_read' AS permission_code, 'service_levels' AS module_name, 'client_read' AS action_name
UNION ALL
SELECT 'retention.hold' AS permission_code, 'retention' AS module_name, 'hold' AS action_name
UNION ALL
SELECT 'photo_reports.view' AS permission_code, 'photo_reports' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'photo_reports.manage' AS permission_code, 'photo_reports' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'photo_reports.export' AS permission_code, 'photo_reports' AS module_name, 'export' AS action_name
UNION ALL
SELECT 'client_reports.view' AS permission_code, 'client_reports' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'client_reports.manage' AS permission_code, 'client_reports' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'client_reports.schedule' AS permission_code, 'client_reports' AS module_name, 'schedule' AS action_name
UNION ALL
SELECT 'support.view' AS permission_code, 'support' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'support.manage' AS permission_code, 'support' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'managed_android.view' AS permission_code, 'managed_android' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'managed_android.manage' AS permission_code, 'managed_android' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'compliance.view' AS permission_code, 'compliance' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'compliance.manage' AS permission_code, 'compliance' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'equipment.view' AS permission_code, 'equipment' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'equipment.manage' AS permission_code, 'equipment' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'escalations.view' AS permission_code, 'escalations' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'escalations.manage' AS permission_code, 'escalations' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'client_portal.view' AS permission_code, 'client_portal' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'client_portal.manage' AS permission_code, 'client_portal' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'client_portal.read' AS permission_code, 'client_portal' AS module_name, 'read' AS action_name
UNION ALL
SELECT 'payroll_export.view' AS permission_code, 'payroll_export' AS module_name, 'view' AS action_name
UNION ALL
SELECT 'payroll_export.manage' AS permission_code, 'payroll_export' AS module_name, 'manage' AS action_name
UNION ALL
SELECT 'support.delegated_view' AS permission_code, 'support' AS module_name, 'delegated_view' AS action_name
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
