# Milestone 1.5 - Identity & Access Database Code

This document centralizes all database-facing code for Milestone 1.5 (authentication, login audit, and RBAC permission checks).

## 1. Required Identity Tables

These are the tables used directly by the implementation:

- `users`
- `roles`
- `permissions`
- `user_roles`
- `role_permissions`
- `login_history`

Reference schema files:

- `database/schema/008_users.sql`
- `database/schema/009_roles.sql`
- `database/schema/010_permissions.sql`
- `database/schema/011_user_roles.sql`
- `database/schema/012_role_permissions.sql`
- `database/schema/020_login_history.sql`

## 2. Runtime SQL - Authentication

### 2.1 Find User by Email

```sql
SELECT * FROM users WHERE email = :email LIMIT 1;
```

### 2.2 Find User by Username

```sql
SELECT * FROM users WHERE username = :username LIMIT 1;
```

### 2.3 Find User by ID

```sql
SELECT * FROM users WHERE user_id = :user_id LIMIT 1;
```

### 2.4 Update Password

```sql
UPDATE users
SET password_hash = :password_hash,
    password_last_changed_at = NOW(),
    updated_at = NOW()
WHERE user_id = :user_id;
```

### 2.5 Update Last Login Success State

```sql
UPDATE users
SET last_login_at = NOW(),
    failed_login_attempts = 0,
    locked_until = NULL,
    updated_at = NOW()
WHERE user_id = :user_id;
```

### 2.6 Increment Failed Attempts + Lockout

```sql
UPDATE users
SET failed_login_attempts = failed_login_attempts + 1,
    locked_until = CASE
        WHEN failed_login_attempts + 1 >= 5 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
        ELSE locked_until
    END,
    status = CASE
        WHEN failed_login_attempts + 1 >= 5 THEN 'LOCKED'
        ELSE status
    END,
    updated_at = NOW()
WHERE user_id = :user_id;
```

### 2.7 Insert Login History (Success/Failed/Locked)

```sql
INSERT INTO login_history (
    company_id,
    user_id,
    login_at,
    ip_address,
    user_agent,
    auth_method,
    login_status,
    failure_reason,
    created_at,
    updated_at
) VALUES (
    :company_id,
    :user_id,
    NOW(),
    :ip_address,
    :user_agent,
    'PASSWORD',
    :login_status,
    :failure_reason,
    NOW(),
    NOW()
);
```

### 2.8 Mark Logout Time

```sql
UPDATE login_history
SET logout_at = NOW(),
    updated_at = NOW()
WHERE user_id = :user_id
  AND logout_at IS NULL
ORDER BY login_history_id DESC
LIMIT 1;
```

## 3. Runtime SQL - RBAC

### 3.1 Permission Check for Current User

```sql
SELECT 1
FROM user_roles ur
INNER JOIN roles r ON r.role_id = ur.role_id AND r.status = 'ACTIVE'
INNER JOIN role_permissions rp ON rp.role_id = r.role_id
INNER JOIN permissions p ON p.permission_id = rp.permission_id AND p.status = 'ACTIVE'
INNER JOIN users u ON u.user_id = ur.user_id
WHERE ur.user_id = :user_id
  AND p.permission_code = :permission_code
  AND u.status = 'ACTIVE'
LIMIT 1;
```

### 3.2 Assign Role to User

```sql
INSERT INTO user_roles (company_id, user_id, role_id, assigned_by_user_id, assigned_at, created_at, updated_at)
VALUES (:company_id, :user_id, :role_id, :assigned_by_user_id, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE assigned_by_user_id = VALUES(assigned_by_user_id), updated_at = NOW();
```

## 4. Seed SQL - Role/Permission Baseline

Existing seed files that supply RBAC baseline:

- `database/seed/001_roles_seed.sql`
- `database/seed/002_permissions_seed.sql`
- `database/seed/003_role_permissions_seed.sql`

The implementation uses `company.manage` for protected company routes.

## 5. Development Seed SQL - Demo Admin Role Assignment

Added file:

- `database/seed/development/009_demo_admin_role_assignment.sql`

SQL:

```sql
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
   AND r.role_name = 'Super Administrator'
WHERE u.email = 'admin@abcsecurity.local'
  AND NOT EXISTS (
      SELECT 1
      FROM user_roles ur
      WHERE ur.user_id = u.user_id
        AND ur.role_id = r.role_id
  );
```

## 6. Seed Plan Update

`database/seed/seed_plan.php` includes:

```php
'development/009_demo_admin_role_assignment.sql',
```

## 7. Run Commands

Run development seed profile:

```bash
php sentinel seed --profile=development --env=development
```

Dry-run first:

```bash
php sentinel seed --profile=development --env=development --dry-run
```

## 8. Endpoint-to-DB Mapping

- `POST /api/v1/login`
  - Reads `users`
  - Updates `users` login/lock state
  - Inserts into `login_history`
- `POST /api/v1/logout`
  - Updates `login_history.logout_at`
- `POST /api/v1/refresh`
  - JWT-only operation (no DB write)
- `GET /api/v1/me`
  - Reads `users`
- `GET /companies` and `GET /api/v1/companies`
  - Authenticates user from `users`
  - Authorizes through `user_roles -> role_permissions -> permissions`
