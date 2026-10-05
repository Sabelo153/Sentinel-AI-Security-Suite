# Phase 1 Schema Manifest

**Status:** Investigation and planning only  
**Date:** 2026-08-17  
**No migrations or database commands were executed.**

## A. Migration runner findings

### A1. Intended canonical runner

The intended canonical runner is the PHP script `database/migrations/run_migrations.php`, normally invoked through the PowerShell wrapper `database/migrations/run_migrations.ps1`. `database/docs/MIGRATION_RUNNER_GUIDE.md` describes this pair as the supported migration path. There are no Composer migration scripts; `composer.json` contains only test scripts.

### A2. Actual paths and configuration

| Question | Finding |
| --- | --- |
| Runner root | `run_migrations.php` sets `$rootDir = dirname(__DIR__)`, which resolves to the repository’s `database/` directory. |
| Environment config expected | `loadEnvironmentConfig($rootDir, $environment)` expects `<rootDir>/config/environments/<env>.php`, resolving to `database/config/environments/<env>.php`. |
| Environment config present | `database/config/environments/development.php`, `testing.php`, `staging.php`, and `production.php` exist. |
| Connection chosen | Config is resolved from the selected environment, with `DB_HOST`, `DB_PORT`, `DB_USERNAME`/`DB_USER`, `DB_PASSWORD`, and `DB_DATABASE`/`DB_NAME` taking precedence if supplied by the process/.env. |
| Testing target configured | `database/config/environments/testing.php` names `sentinel_ai_security_test` on local MySQL/MariaDB defaults. This is configuration only; no connection was attempted. |
| Migration plan loaded | `<database>/migrations/migration_plan.php`. |
| Schema directory loaded | The plan lists relative `schema/*.sql` files; the runner resolves them beneath `<database>/`, therefore `database/schema/*.sql`. |
| Logs actually written | `logLine()` resolves to `database/storage/logs/migrations.log`, not the `database/logs/migrations.log` path claimed by `database/docs/MIGRATION_RUNNER_GUIDE.md`. |
| Dry run behavior | `--dry-run` validates only listed file existence/order and does not connect to the database or parse/execute SQL. |
| Normal run behavior | Connects through `mysqli`, creates the target database and `migrations` table if absent, then executes each planned SQL file as a multi-query transaction and records a checksum. |

### A3. Execution readiness

- The runner configuration-path discrepancy recorded in `PROJECT_CONTEXT.md` is **not confirmed by current source**: its actual root resolution points to the existing `database/config/environments/` directory.
- A normal execution would write: create a database/migrations table, execute schema SQL, record migration rows and append a log. It was not run because database execution is not approved.
- Success against an empty test database is **UNVERIFIED**. A runner dry-run would not answer SQL dialect, foreign-key, transaction or privilege questions.
- Rollback support is not safe evidence for production use: it derives rollback SQL heuristically and documentation says rollback is scaffolded; it must be validated separately before any use.

### A4. Schema/migration separation

- The current plan includes schema files 001–034, with an explicit order that is not purely numeric in all cases.
- Files 035–049 are present in `database/schema/` but omitted from both `migration_plan.php` and `000_run_all.sql`.
- Migration-only files 001–008 include tables not represented by the planned canonical schema, notably `guard_devices`, `device_sessions`, `device_location_history`, `device_heartbeat_history`, and `tracking_configuration`.
- Migration 008 alters those migration-only tracking tables; it cannot be safely applied until their canonical replacements exist.

## B. Canonical schema rules

1. `database/schema/` is the source directory, but **directory membership alone does not authorize execution**. This manifest is the proposed Phase 1 selection boundary.
2. Company-owned records use `company_id` referencing `companies.company_id`; `tenant_id` is not an approved canonical physical column.
3. Canonical primary keys use entity-specific `*_id` names. Generic `id` tracking keys from migration-only SQL are not adopted.
4. Tables are applied only after their referenced tables exist. `ALTER TABLE` files are applied after the target table and all introduced foreign-key targets.
5. INCLUDE means required for the approved Phase 1 core/API baseline; REVIEW means present but deferred pending a later domain/model decision; EXCLUDE means not part of the Phase 1 target because it conflicts with an approved future replacement.
6. No Phase 1 execution is authorized until the runner, exact environment, engine version, credentials/privileges, selected manifest and real database test procedure are explicitly approved.

## C. Complete schema manifest

| File | Order | Purpose / tables created or altered | Depends on | Model / important FKs | Conflicts | Status |
| --- | ---: | --- | --- | --- | --- | --- |
| `000_run_all.sql` | N/A | MySQL client convenience `SOURCE` list | MySQL client only | N/A | Omits 035–049; not used by PHP runner | EXCLUDE |
| `001_create_database.sql` | 1 | Creates/selects canonical database | None | N/A | Database name is adapted by runner | INCLUDE |
| `002_companies.sql` | 2 | `companies` | 001 | `company_id` tenant boundary | None | INCLUDE |
| `003_clients.sql` | 3 | `clients` | companies | `company_id` → companies | None | INCLUDE |
| `004_branches.sql` | 4 | `branches` | companies, clients | company/client FKs | Client/company consistency needs service validation | INCLUDE |
| `005_sites.sql` | 5 | `sites` | companies, clients, branches | company/client/branch FKs | Contract link is added later by 022 | INCLUDE |
| `006_people.sql` | 6 | `people` | companies | company FK | None | INCLUDE |
| `007_employees.sql` | 7 | `employees` | companies, people | company/person FKs | Guard identity remains a later decision | INCLUDE |
| `008_users.sql` | 8 | `users` | companies, employees | company/employee FKs | Requires one employee per user; platform-admin model unresolved | INCLUDE |
| `009_roles.sql` | 9 | `roles` | companies | company FK | Global vs company-local permission/role policy unresolved | INCLUDE |
| `010_permissions.sql` | 10 | `permissions` | companies | company FK | Permission definitions are company-scoped, while platform catalog policy remains unresolved | INCLUDE |
| `011_user_roles.sql` | 11 | `user_roles` | companies, users, roles | company/user/role/assigner FKs | Unique key lacks `company_id`; must rely on relationship integrity | INCLUDE |
| `012_role_permissions.sql` | 12 | `role_permissions` | companies, users, roles, permissions | company/role/permission/granter FKs | Permission repository query does not currently scope company | INCLUDE |
| `013_departments.sql` | 13 | `departments` | companies, clients, branches | company/client/branch FKs | Parent company consistency must be enforced | INCLUDE |
| `014_attendance.sql` | 14 | `attendance` | companies, employees, sites | company/employee/site FKs | Shift assignment added later | REVIEW |
| `015_company_settings.sql` | 15 | `company_settings` | companies | company FK | None | INCLUDE |
| `016_ai_requests.sql` | 16 | `ai_requests` | companies, employees, users | company/employee/user FKs | AI is explicitly out of current implementation scope | REVIEW |
| `017_ai_jobs.sql` | 17 | `ai_jobs` | companies, ai_requests | company/request FKs | AI deferred | REVIEW |
| `018_ai_responses.sql` | 18 | `ai_responses` | companies, ai_requests, users | company/request/approver FKs | AI deferred | REVIEW |
| `019_audit_logs.sql` | 19 | `audit_logs` | companies, users | company/user FKs, entity/action/time indexes | Deletion FK policy needs retention review | INCLUDE |
| `020_login_history.sql` | 20 | `login_history` | companies, users | nullable company/user FKs | Conflicts with migration-only identity SQL naming | INCLUDE |
| `021_contracts.sql` | 21 | `contracts` | companies, clients, users | company/client/creator/updater FKs | No API repository/controller yet; client/company match needs rule | INCLUDE |
| `022_sites_add_contract.sql` | 22 | Alters `sites`: contract and update-audit link | sites, contracts, users | contract/user FKs | Must follow 005/008/021 | INCLUDE |
| `023_posts.sql` | 23 | `posts` | companies, contracts, sites, users | company/contract/site/user FKs | No canonical API domain yet | REVIEW |
| `024_guard_device_sessions.sql` | 24a | `guard_device_sessions` | None declared | `company_id`, string `device_id`; indexes only | Competes with required future canonical `device_sessions` and lacks device FKs | EXCLUDE |
| `024_patrol_routes.sql` | 24b | `patrol_routes` | companies, sites, posts, users | company/site/post/user FKs | Duplicate numeric prefix; patrol API/workflow deferred | REVIEW |
| `025_patrol_checkpoints.sql` | 25 | `patrol_checkpoints` | companies, patrol_routes, posts | company/route/post FKs | Patrol deferred | REVIEW |
| `026_shift_assignments.sql` | 26 | `shift_assignments` | companies, employees, contracts, sites, users | company/employee/contract/site/user FKs | Needed for future guard operations; API/workflow not yet canonical | REVIEW |
| `027_attendance_add_shift_assignment.sql` | 27 | Alters `attendance`: shift assignment/contract links | attendance, shift_assignments, contracts, users | assignment/contract/user FKs | Depends on review tables 014/026 | REVIEW |
| `028_auth_sessions.sql` | 28 | `refresh_tokens` | companies, users | company/user FKs, JTI/token hash | API repository expects this name; title differs from table | INCLUDE |
| `029_password_reset_tokens.sql` | 29 | `password_reset_tokens` | companies, users | company/user FKs | None | INCLUDE |
| `030_finance_chart_of_accounts.sql` | 30 | `finance_chart_of_accounts` | companies, users/self | company/user/self FKs | Finance out of current foundation scope | REVIEW |
| `031_finance_journals.sql` | 31 | `finance_journals`, `finance_journal_lines` | companies, users, accounts | company/user/account/journal FKs | Finance deferred | REVIEW |
| `032_payroll_periods.sql` | 32 | `payroll_periods`, `payroll_export_batches` | companies, users, payroll periods | company/user/period FKs | Finance/payroll deferred | REVIEW |
| `033_billing_invoices.sql` | 33 | `billing_invoices`, `billing_invoice_lines` | companies, clients, contracts, users | company/client/contract/user/invoice FKs | Finance/billing deferred | REVIEW |
| `034_employee_profiles.sql` | 34 | `employee_profiles` | employees, companies | employee/company FKs | API Employee repository reads/writes it | INCLUDE |
| `035_organizations.sql` | 35 | `organizations` | companies | company FK | Omitted from plan; organization model not needed to establish current API core | REVIEW |
| `036_organizational_units.sql` | 36 | `organizational_units` | companies, organizations, self | company/organization/parent FKs | Omitted; depends on 035 | REVIEW |
| `037_positions.sql` | 37 | `positions` | companies, organizations, units | company/organization/unit FKs | Omitted; depends on 035–036 | REVIEW |
| `038_authority_levels.sql` | 38 | `authority_levels` | None | Reference table; no company discriminator | Omitted; authority policy/domain unresolved | REVIEW |
| `039_employee_assignments.sql` | 39 | `employee_assignments` | companies, employees, users, organizations, units, positions, authority levels | multi-scope FKs | Omitted; depends on 035–038 and guard identity decision | REVIEW |
| `040_assignment_scopes.sql` | 40 | `assignment_scopes` | companies, employee_assignments, users | company/assignment/user FKs | Omitted; depends on 039 | REVIEW |
| `041_delegations.sql` | 41 | `delegations` | companies, employee_assignments, users | company/assignment/user FKs | Omitted; depends on 039 | REVIEW |
| `042_business_object_types.sql` | 42 | `business_object_types` | None | Global/reference types | Omitted; business-object model not a Phase 1 prerequisite | REVIEW |
| `043_object_statuses.sql` | 43 | `object_statuses` | business_object_types | type FK | Omitted; depends on 042 | REVIEW |
| `044_business_objects.sql` | 44 | `business_objects` | companies, business_object_types, object_statuses, users | company/type/status/user FKs | Omitted; generic object model not required for current API core | REVIEW |
| `045_object_relationships.sql` | 45 | `object_relationships` | companies, business_objects | company/from/to object FKs | Omitted; depends on 044 | REVIEW |
| `046_gps_locations.sql` | 46 | `gps_locations` | companies, employees, users, business_objects | company/employee/user/object FKs | Omitted; overlaps future device location history; GPS is not Phase 1 | REVIEW |
| `047_geofences.sql` | 47 | `geofences` | companies, sites | company/site FKs | Omitted; lacks required draft/review/version geometry-history model | REVIEW |
| `048_gps_events.sql` | 48 | `gps_events` | companies, gps_locations, geofences, business_objects | company/location/geofence/object FKs | Omitted; depends on 044/046/047 and future telemetry model | REVIEW |
| `049_activity_logs.sql` | 49 | `activity_logs` | companies, users, employees, business_objects | company/user/employee/object FKs | Omitted; depends on 044; duplicates/overlaps audit intent until policy resolved | REVIEW |

## D. Files 035–049 analysis

All files 035–049 use the approved `company_id` convention where company ownership applies, but all are omitted from the runner plan and `000_run_all.sql`. They are therefore **not automatic Phase 1 inputs**.

| Range | Why deferred from Phase 1 |
| --- | --- |
| 035–037 | Organization hierarchy and positions are valuable, but no current API repository uses them and they require a scoped workforce/assignment decision. |
| 038–041 | Authority, assignment scope and delegation depend on the organisation hierarchy and a final guard/employee identity model. |
| 042–045 | Generic business-object metadata/relationships are a platform abstraction, not a prerequisite for a reproducible core identity/company/schema test baseline. |
| 046 | Generic GPS location storage conflicts in responsibility with the future device telemetry history table. No GPS implementation is authorized. |
| 047 | Basic geofence table lacks the approved versioned draft/review/active/retired geometry history required for production geofencing. |
| 048 | Depends on the deferred generic GPS, geofence and business-object models; derived event design is not approved. |
| 049 | Depends on business objects and needs an explicit boundary relative to persistent `audit_logs`. |

## E. Repository-to-schema mapping

| Repository | Tables used | Expected columns (representative) | Canonical schema file | Mismatches |
| --- | --- | --- | --- | --- |
| `App\\Repositories\\CompanyRepository` | `companies` | `company_id`, name/registration/contact/status fields | 002 | API company routes need separate platform-admin policy. |
| `ClientRepository` | `clients` | `client_id`, `company_id`, name/contact/status | 003 | API lacks item/update endpoints; no table mismatch. |
| `SiteRepository` | `sites` | `site_id`, `company_id`, `client_id`, `branch_id`, `contract_id`, status | 005 + 022 | API service creates with contract ID; 022 must be included. |
| `UserRepository` | `users` | `user_id`, UUID, `company_id`, `employee_id`, credentials/status/lock fields | 008 | Employee is mandatory; account provisioning sequence must account for it. |
| `RoleRepository` | `roles` | `role_id`, `company_id`, name | 009 | None in columns. |
| `PermissionRepository` | `user_roles`, `role_permissions`, `permissions` | user/role/permission IDs, permission code/name | 010–012 | Query lacks company scoping across joins. |
| `UserRoleRepository` | `user_roles` | company/user/role/assigner fields | 011 | Unique schema constraint is user+role only; service must validate same company. |
| `AuthSessionRepository` | `refresh_tokens` | company/user, access JTI, refresh hash/expiry/revocation | 028 | Terminology file name says auth sessions but table is refresh tokens. |
| `LoginHistoryRepository` | `login_history` | company/user, IP, agent, auth method/status/failure, login/logout time | 020 | Matches canonical schema; differs from migration-only identity SQL. |
| `PasswordResetTokenRepository` | `password_reset_tokens` | company/user, hash, expiry/use, request metadata | 029 | None found. |
| `AuditRepository` | `audit_logs` | company/user, entity/action/status, IP/agent, JSON changes, occurred time | 019 | Audit retention/FK cascade policy needs review. |
| `DashboardRepository` | `clients`, `sites`, `employees`, `users`, `attendance`, `audit_logs` | `company_id`, activity/time fields | 003,005,007,008,014,019 | Attendance is REVIEW; dashboard must not claim operational readiness until final selection. |
| `EmployeeRepository` | `employees`, `people`, `employee_profiles` | employee/company/person fields plus profile data | 006,007,034 | Profile table must be included for current API behavior. |
| `Domains\\Company\\BranchRepository` | `branches`, `clients` | branch/client/company fields | 003,004 | Must validate client belongs to company. |
| `Domains\\Company\\DepartmentRepository` | `departments`, `branches`, `clients` | department/company/client/branch fields | 003,004,013 | Must validate optional branch/client scopes belong to company. |
| `Domains\\Company\\CompanySettingsRepository` | `company_settings` | company, settings JSON/fields | 015 | None found. |
| Contract repository | No API repository exists | Future `contract_id`, `company_id`, `client_id`, lifecycle fields | 021 | Root-only behavior; canonical API migration required. |
| Guard device/session repository | No API repository exists | Future device/session company keys and lifecycle/telemetry fields | No included canonical table | MISSING — FUTURE MIGRATION REQUIRED. |
| GPS/geofence repository | No API repository exists | Future telemetry/geofence version and scope fields | 046–048 are REVIEW | Current root geofence persistence is in-memory; canonical design is not yet represented. |

## F. Device tracking schema dependencies

| Chain requirement | Canonical schema table currently available | Phase 1 status |
| --- | --- | --- |
| Company | `companies` (002) | INCLUDE |
| Guard | `employees` (007), user account (008) | INCLUDE, but canonical guard identity relationship needs approval |
| Company-owned registered device | None | **MISSING — FUTURE MIGRATION REQUIRED** |
| Device assignment history | No dedicated device assignment table | **MISSING — FUTURE MIGRATION REQUIRED** |
| Tracking session | `guard_device_sessions` (024) exists but conflicts with approved target | **MISSING — FUTURE MIGRATION REQUIRED**; do not use 024 as canonical session model |
| Heartbeat history | None | **MISSING — FUTURE MIGRATION REQUIRED** |
| GPS/device location history | `gps_locations` (046) is REVIEW and lacks canonical session/device semantics | **MISSING — FUTURE MIGRATION REQUIRED** for device telemetry |
| Tracking policy | None | **MISSING — FUTURE MIGRATION REQUIRED** |
| Device presence state/history | None | **MISSING — FUTURE MIGRATION REQUIRED** |
| Persistent geofence version/geometry | Basic `geofences` (047) is REVIEW and insufficient | **MISSING — FUTURE MIGRATION REQUIRED** |
| Derived guard activity/site-compliance events | `gps_events` (048) is REVIEW/inadequate for approved source references | **MISSING — FUTURE MIGRATION REQUIRED** |
| Audit | `audit_logs` (019) | INCLUDE |

## G. Conflicts

1. Current runner plan calls all schema 001–034 “Core,” while this Phase 1 manifest defers optional AI, attendance/patrol, finance and legacy guard-session tables to avoid implementing unrelated/deferred domains as part of the minimal canonical API foundation.
2. `guard_device_sessions` (schema 024) and migration-only `device_sessions` describe overlapping domains with incompatible primary keys, company columns and device references.
3. `gps_locations` (046) overlaps with migration-only device-location history but does not establish a company-owned device/session telemetry contract.
4. `geofences` (047) uses `company_id` but lacks the approved draft/submitted/reviewed/active/retired version history and durable geometry provenance.
5. Migration-only 007/008 define required tracking/presence concepts but use rejected `tenant_id` and generic ID/FK conventions.
6. Migration documentation is historically inconsistent: the runner guide gives a different log path, and the execution checklist describes an obsolete broad migration/database approach.

## H. Risks

- Executing the existing plan unchanged would introduce deferred AI/finance/patrol/legacy session tables and still omit 035–049.
- Executing `000_run_all.sql` would bypass the PHP runner’s migration ledger and omit 035–049.
- A normal runner invocation creates database state; it must never be used as an exploratory command against a shared/development/production target.
- A `--dry-run` cannot prove SQL syntax, server version compatibility, transaction semantics, privileges or foreign-key viability.
- Environment values from `.env`/process variables override selected config and can redirect a run if not controlled.
- Existing documentation references historical architecture and cannot be treated as implementation authority over the approved decision and this manifest.

## I. Recommended Phase 1 execution order

The following is a **recommended future implementation/execution order**, not authorization to execute it.

1. Confirm test-only host/database credentials, engine/version and a protected non-production target.
2. Implement/approve the exact runner manifest mechanism so it can select this approved baseline without modifying unselected schema files.
3. Apply/validate 001–013: database, company, organisation core, identity, roles, permissions and assignments.
4. Apply/validate 015, 019–022, 028–029 and 034: company settings, audit/login history, contract/site link, session/reset security and employee profiles.
5. Decide whether attendance 014 is required for the current API dashboard baseline; include it only with an approved dashboard scope. Do not include 026/027 until shift-assignment dependency is selected.
6. Run schema, repository, API authentication and two-company isolation tests against the resulting isolated real database.
7. Produce a reconciliation report before adding any REVIEW or future tracking/geofence table.

## J. Pre-execution verification checklist

- [ ] `ARCHITECTURE_DECISION.md` status is confirmed as APPROVED / CURRENT TARGET DECISION.
- [ ] This manifest’s Phase 1 INCLUDE/REVIEW/EXCLUDE selection is explicitly approved.
- [ ] Exact test database host, port, name, user and network boundary are verified as disposable/non-production.
- [ ] Process/.env database overrides are inspected so selected testing configuration cannot be silently redirected.
- [ ] Database engine and version are recorded; SQL dialect features are validated for that engine.
- [ ] Test account privileges are limited to the dedicated test database and sufficient for the planned DDL.
- [ ] Runner execution, migration ledger path, log path, rollback limitations and failure behavior are reviewed.
- [ ] Empty database, backup/restore and teardown procedures are approved and tested safely.
- [ ] Real database schema/repository/API/two-company isolation tests are ready to run after schema application.
- [ ] No root SAF, GPS, presence, geofence, dashboard, mobile or AI feature implementation is included in the change set.

## Final status

**ARCHITECTURE DECISION:** APPROVED  
**PHASE 1 SCHEMA:** NOT READY — investigation complete; missing device-tracking tables in plan and repository terminology mismatch.  
**MIGRATION RUNNER:** NOT READY — configuration resolved, but DDL transaction safety and "fresh" test database support are missing.  
**DATABASE EXECUTION:** NOT YET APPROVED

