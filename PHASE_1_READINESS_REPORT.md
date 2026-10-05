# Phase 1 Readiness Report

**Date:** 2026-08-17
**Status:** PHASE 1 READINESS INVESTIGATION COMPLETE
**Database Execution:** NOT APPROVED

## A. Migration Runner Assessment

### 1. Configuration and Discovery
- **Root Resolution:** The runner correctly identifies the project root and `database/` subdirectory. The previous concern about broken paths in `PROJECT_CONTEXT.md` is **invalid**; the runner's path logic is functional.
- **Environment Selection:** Successfully loads configurations from `database/config/environments/`. `testing.php` correctly targets `sentinel_ai_security_test`.
- **Schema Discovery:** Relies on `database/migrations/migration_plan.php`. This plan currently includes 34 files, but excludes 35-49.
- **Duplicate Execution:** Uses a `migrations` table to track applied files via name and SHA256 checksum. This mechanism is standard and functional.

### 2. Execution Behavior
- **Transaction Handling:** The runner wraps `runSqlBatch` in a transaction. **CRITICAL RISK:** MySQL/MariaDB performs an implicit commit for DDL statements (CREATE, ALTER, DROP). The runner's transaction wrapper will NOT roll back schema changes if a mid-batch statement fails.
- **Error Handling:** Catches `Throwable` and logs to `database/storage/logs/migrations.log`.
- **Rollback:** Uses an experimental regex-based rollback generator. This is considered **BRITTLE** and should not be relied upon for production state management.

### 3. Readiness Decision: **NOT READY**
- **Reason:** While structurally sound for testing, the implicit commit behavior and brittle rollback logic require hardening or explicit "disposable environment" policy before production use.

## B. SQL Static Compatibility Assessment

### 1. Syntax and Types
- **Dialect:** SQL files are compatible with MySQL 8.0+ and MariaDB 10.4+.
- **Primary Keys:** Consistent use of `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`.
- **Timestamps:** Consistent use of `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`.
- **Charset:** Canonical creation uses `utf8mb4`.

### 2. Consistency and Integrity
- **Multi-tenancy:** Every major entity correctly uses `company_id BIGINT UNSIGNED`. No instances of `tenant_id` were found in the `database/schema/` directory.
- **Foreign Keys:** Most tables correctly reference `companies(company_id)`.
- **Anomalies:**
    - `024_guard_device_sessions.sql` is present in the folder but **excluded** from the migration plan.
    - `028_auth_sessions.sql` creates a table named `refresh_tokens`. The naming is internally consistent but mismatching the filename's implication.
    - `000_run_all.sql` is a legacy script and should be ignored in favor of the PHP runner.

## C. Schema Dependency Graph

The safest execution order for the Phase 1 Core is:
1.  **Level 0 (Independent):** `001_create_database`, `002_companies`
2.  **Level 1 (Direct Company Dependency):** `003_clients`, `006_people`, `009_roles`, `010_permissions`, `015_company_settings`, `019_audit_logs`, `028_auth_sessions` (refresh_tokens), `029_password_reset_tokens`
3.  **Level 2 (Multi-Dependency):**
    - `004_branches` (companies, clients)
    - `007_employees` (companies, people)
    - `013_departments` (companies, clients, branches)
    - `021_contracts` (companies, clients)
4.  **Level 3 (User/Auth Core):**
    - `008_users` (companies, employees)
    - `034_employee_profiles` (employees, companies)
5.  **Level 4 (User Links):**
    - `011_user_roles` (companies, users, roles)
    - `012_role_permissions` (companies, roles, permissions, users)
    - `020_login_history` (companies, users)
6.  **Level 5 (Site/Operational Core):**
    - `005_sites` (companies, clients, branches)
7.  **Level 6 (Schema Alterations):**
    - `022_sites_add_contract` (sites, contracts, users)

**Circular Dependencies:** None identified. The self-reference in `finance_chart_of_accounts` (deferred) is handled via a nullable FK.

## D. Repository/Schema Compatibility

- **`apps/api/` Repositories:** **100% COMPATIBLE**. Repositories for Companies, Users, Employees, Sites, etc., correctly target the canonical table names and `company_id` columns.
- **`app/Repositories/` (Root/Legacy):** **INCOMPATIBLE**. These files (e.g., `GuardDeviceRepository.php`) still use `tenant_id` and reference tables like `guard_devices` which are NOT in the canonical schema.
- **Action Required:** Root repositories must be migrated to match the canonical API standards before they can interact with the Phase 1 database.

## E. company_id Consistency

| Category | Finding |
| --- | --- |
| **CANONICAL** | All `database/schema/*.sql` files and `apps/api/` repositories. |
| **LEGACY** | `app/Repositories/GuardDeviceRepository.php`, `app/Services/DevicePresenceService.php`. |
| **MIGRATION REQUIRED** | Root-level services and repositories must be updated to use `company_id`. |
| **AMBIGUOUS** | `PROJECT_CONTEXT.md` incorrectly referenced `tenant_id` as canonical. |

## F. Test Database Requirements

To safely validate the schema, a completely isolated environment is required:
- **Engine:** MySQL 8.0 or MariaDB 10.4.
- **Database Name:** `sentinel_ai_security_test`.
- **Credentials:** Dedicated user `sentinel_test` with `ALL PRIVILEGES` on `sentinel_ai_security_test` only.
- **Isolation Strategy:** The runner must be updated to support a `--fresh` flag that drops and recreates the database using `001_create_database.sql` before running the plan.
- **PHPUnit Integration:** A `BaseDatabaseTestCase` should be created to manage transaction nesting or database re-initialization.

## G. Issues Summary

### Blocking Issues
- **Runner DDL Safety:** Lack of transaction rollback for DDL makes partial failures difficult to recover from without manual intervention.
- **Legacy Repository Mismatch:** Root-level presence/tracking code will fail against the canonical schema.
- **Manifest Incompleteness:** Critical device session tables are currently excluded from the Phase 1 plan.

### Non-blocking Issues
- Filename/Table name mismatch in `028_auth_sessions.sql`.
- Experimental rollback generator in the runner.

## H. Final Readiness Decision

**PHASE 1 STATUS: NOT READY**

**Reasons:**
1.  **Environment:** A safe, automated "fresh" test database procedure is not yet implemented in the runner.
2.  **Scope:** The current `migration_plan.php` omits critical device-tracking tables required by existing services.
3.  **Consistency:** The mismatch between root repositories (`tenant_id`) and canonical schema (`company_id`) remains unresolved.

**Next Recommended Move:**
Update the migration runner to support "fresh" database initialization and synchronize the `migration_plan.php` with the required device-tracking tables.
