-- Defines the database changes in 003_role_permissions_seed.

USE sentinel_ai_security;

INSERT INTO role_permissions (company_id, role_id, permission_id, granted_by_user_id)
SELECT
    r.company_id,
    r.role_id,
    p.permission_id,
    NULL AS granted_by_user_id
FROM roles r
JOIN permissions p
    ON p.company_id = r.company_id
JOIN (
    SELECT 'Super Administrator' AS role_name, 'user.create' AS permission_code
    UNION ALL SELECT 'Super Administrator', 'user.delete'
    UNION ALL SELECT 'Super Administrator', 'dashboard.view'
    UNION ALL SELECT 'Super Administrator', 'attendance.clock_in'
    UNION ALL SELECT 'Super Administrator', 'attendance.clock_out'
    UNION ALL SELECT 'Super Administrator', 'incident.create'
    UNION ALL SELECT 'Super Administrator', 'incident.approve'
    UNION ALL SELECT 'Super Administrator', 'visitor.manage'
    UNION ALL SELECT 'Super Administrator', 'patrol.manage'
    UNION ALL SELECT 'Super Administrator', 'vehicle.manage'
    UNION ALL SELECT 'Super Administrator', 'reports.generate'
    UNION ALL SELECT 'Super Administrator', 'company.manage'
    UNION ALL SELECT 'Super Administrator', 'ai.manage'
    UNION ALL SELECT 'Super Administrator', 'audit_logs.view'
    UNION ALL SELECT 'Super Administrator', 'billing.manage'
    UNION ALL SELECT 'Company Administrator', 'user.create'
    UNION ALL SELECT 'Company Administrator', 'dashboard.view'
    UNION ALL SELECT 'Company Administrator', 'company.manage'
    UNION ALL SELECT 'Company Administrator', 'audit_logs.view'
    UNION ALL SELECT 'Operations Manager', 'dashboard.view'
    UNION ALL SELECT 'Operations Manager', 'incident.create'
    UNION ALL SELECT 'Operations Manager', 'reports.generate'
    UNION ALL SELECT 'Supervisor', 'attendance.clock_in'
    UNION ALL SELECT 'Supervisor', 'attendance.clock_out'
    UNION ALL SELECT 'Supervisor', 'incident.approve'
    UNION ALL SELECT 'Guard', 'attendance.clock_in'
    UNION ALL SELECT 'Guard', 'attendance.clock_out'
    UNION ALL SELECT 'Receptionist', 'visitor.manage'
    UNION ALL SELECT 'Client', 'dashboard.view'
    UNION ALL SELECT 'Auditor', 'audit_logs.view'
) rp
    ON rp.role_name = r.role_name
   AND rp.permission_code = p.permission_code
LEFT JOIN role_permissions existing
    ON existing.role_id = r.role_id
   AND existing.permission_id = p.permission_id
WHERE existing.role_permission_id IS NULL;
