-- Defines the database changes in 009_demo_admin_role_assignment.

USE sentinel_ai_security;

INSERT INTO user_roles (
    company_id,
    user_id,
    role_id,
    assigned_by_user_id,
    assigned_at,
    created_at,
    updated_at
)
SELECT
    u.company_id,
    u.user_id,
    r.role_id,
    NULL,
    NOW(),
    NOW(),
    NOW()
FROM users u
INNER JOIN roles r
    ON r.company_id = u.company_id
    AND r.role_name = 'System Administrator'
WHERE u.email = 'admin@abcsecurity.local'
  AND NOT EXISTS (
      SELECT 1
      FROM user_roles ur
      WHERE ur.user_id = u.user_id
        AND ur.role_id = r.role_id
  );