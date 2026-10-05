-- Defines the database changes in 001_roles_seed.

USE sentinel_ai_security;

INSERT INTO roles (company_id, role_name, role_description, is_system_role, status)
SELECT c.company_id, seed.role_name, seed.role_description, TRUE, 'ACTIVE'
FROM companies c
JOIN (
    SELECT 'Super Administrator' AS role_name, 'Full system access across tenant scope' AS role_description
    UNION ALL SELECT 'Company Administrator', 'Manages tenant configuration, users, and policies'
    UNION ALL SELECT 'Operations Manager', 'Oversees daily operations and site-level workflows'
    UNION ALL SELECT 'Site Manager', 'Manages one or more operational sites'
    UNION ALL SELECT 'Supervisor', 'Supervises guards and approves operational actions'
    UNION ALL SELECT 'Guard', 'Frontline security operations user'
    UNION ALL SELECT 'Receptionist', 'Visitor and front-desk workflow access'
    UNION ALL SELECT 'Client', 'Read-focused portal access for customer visibility'
    UNION ALL SELECT 'Auditor', 'Read-only compliance and audit reporting access'
) AS seed
ON 1 = 1
LEFT JOIN roles r
    ON r.company_id = c.company_id
   AND r.role_name = seed.role_name
WHERE r.role_id IS NULL;
