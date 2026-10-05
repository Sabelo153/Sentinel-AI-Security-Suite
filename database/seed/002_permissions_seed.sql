-- Defines the database changes in 002_permissions_seed.

USE sentinel_ai_security;

INSERT INTO permissions (company_id, permission_code, permission_name, module_name, action_name, permission_description, status)
SELECT
    c.company_id,
    seed.permission_code,
    seed.permission_name,
    seed.module_name,
    seed.action_name,
    seed.permission_description,
    'ACTIVE'
FROM companies c
JOIN (
    SELECT 'user.create' AS permission_code, 'Create User' AS permission_name, 'users' AS module_name, 'create' AS action_name, 'Create new user accounts' AS permission_description
    UNION ALL SELECT 'user.delete', 'Delete User', 'users', 'delete', 'Delete existing user accounts'
    UNION ALL SELECT 'dashboard.view', 'View Dashboard', 'dashboard', 'view', 'Access dashboard summaries'
    UNION ALL SELECT 'attendance.clock_in', 'Clock In', 'attendance', 'clock_in', 'Record employee clock-in events'
    UNION ALL SELECT 'attendance.clock_out', 'Clock Out', 'attendance', 'clock_out', 'Record employee clock-out events'
    UNION ALL SELECT 'incident.create', 'Create Incident', 'incidents', 'create', 'Create a new incident'
    UNION ALL SELECT 'incident.approve', 'Approve Incident', 'incidents', 'approve', 'Approve incident closure or escalation'
    UNION ALL SELECT 'visitor.manage', 'Manage Visitors', 'visitors', 'manage', 'Create, update, and track visitors'
    UNION ALL SELECT 'patrol.manage', 'Manage Patrols', 'patrols', 'manage', 'Create and monitor patrol assignments'
    UNION ALL SELECT 'vehicle.manage', 'Manage Vehicles', 'vehicles', 'manage', 'Manage fleet and vehicle records'
    UNION ALL SELECT 'reports.generate', 'Generate Reports', 'reports', 'generate', 'Generate operational and compliance reports'
    UNION ALL SELECT 'company.manage', 'Manage Company', 'company', 'manage', 'Manage tenant-level company profile and settings'
    UNION ALL SELECT 'ai.manage', 'Manage AI', 'ai', 'manage', 'Configure AI and run AI-assisted operations'
    UNION ALL SELECT 'audit_logs.view', 'View Audit Logs', 'audit_logs', 'view', 'Access system audit logs'
    UNION ALL SELECT 'billing.manage', 'Manage Billing', 'billing', 'manage', 'Manage billing configuration and artifacts'
) AS seed
ON 1 = 1
LEFT JOIN permissions p
    ON p.company_id = c.company_id
   AND p.permission_code = seed.permission_code
WHERE p.permission_id IS NULL;
