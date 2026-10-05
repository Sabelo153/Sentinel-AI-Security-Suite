# Sentinel Foundation Audit

**Audit date:** 2026-08-17  
**Scope:** Read-only review of the current repository, including application entry points, schemas and migrations, device tracking and geofencing, authentication and authorization, tests, and recent Git history. No application code, schemas, configuration, or tests were changed.

## A. Executive findings

The report's central warning is **confirmed**: the repository currently contains two independently bootstrapped applications using overlapping API paths and two partially incompatible data models. Neither should be removed without an explicit architecture decision.

The immediate priority is stabilization, not new features. In particular:

1. `DevicePresenceService` is not wired into the root application's container and invokes six methods absent from its declared `GuardDeviceRepository`. If it is invoked with that repository it will fail at the first such call.
2. The device-tracking tables use `tenant_id` and foreign keys to `companies.id`, while the active schema/API model uses `company_id` and `companies.company_id`. The referenced columns conflict.
3. The tracked migration plan excludes the device tracking/presence migration files and also excludes schema files 035–049. Consequently a standard migration-plan run cannot produce the database expected by the device presence and geofence code.
4. The root geofence implementation is in-memory only. The SQL geofence table is not used by its repository, so geofences are neither durable nor tenant-scoped in the implementation.
5. The complete PHPUnit suite currently aborts on duplicate global test stub declarations. The files labelled integration tests generally do not use a real database and cannot validate schema, persistence, or isolation.

## B. Application architecture

### Confirmed applications

| Application | Entry point | Namespace | Routes/API | Database configuration |
| --- | --- | --- | --- | --- |
| Root SAF application | `public/index.php` → `bootstrap/app.php` | `Sentinel\\` | `routes/web.php`, `routes/api.php`; `/api/v1/*` | `config/database.php`, loads root `.env` |
| Secondary API | `apps/api/public/index.php` → `apps/api/bootstrap/app.php` | `App\\` | declared directly in its bootstrap; also `/api/v1/*` | `apps/api/config/database.php` → `database/config/environments/<APP_ENV>.php` |

Both are genuine independently bootstrapped applications, not modules of a single runtime. They have separate containers, request/router classes, database abstractions, JWT implementations, middleware, controllers, repositories, and services. Composer maps both namespaces (`composer.json`).

### Active-reference assessment

Both are actively referenced by committed/repository code. The root app exposes device, geofence, client, contract, site, and experience APIs. The secondary API exposes authentication, company/client/site/user/employee/role/dashboard, branch, department, and company-settings APIs. Their API paths overlap materially (`/api/v1`, login/authentication, companies, clients, sites, roles, dashboard). `apps/api/public/dashboard.html` and `test.php` make the secondary API a usable standalone surface.

The currently serving production application is **UNKNOWN** from the repository alone: there is no deployment configuration, web-server virtual-host configuration, environment inventory, or live-process evidence in scope. Development usage is also **UNKNOWN**, though README/public entry points support local execution of both applications.

### Authentication and identity compatibility

The applications do **not** share authentication behavior:

- Root uses `Sentinel\\Core\\JWT` and `config/jwt.php`; access tokens are signed and checked for signature/time but there is no token-session revocation check.
- Secondary API uses `App\\Core\\Security\\JwtService` and `apps/api/config/auth.php`; it creates a JTI and `AuthMiddleware` validates an active `refresh_tokens` session.
- They use different default issuers and different configuration keys/default secrets. A token minted by one is not designed to be accepted by the other.

They may point at the same named database only if environment values align. The root defaults to port 3306 and obtains its config from `.env`; the secondary development configuration defaults to port 3307. Therefore shared database use is **UNKNOWN** and should be verified in each deployed environment without exposing credentials.

## C. Database architecture

### Schema systems

Two database-definition streams exist:

1. `database/schema/001`–`034` is the runner's only tracked execution plan (`database/migrations/migration_plan.php`). It consistently models tenants as `company_id`, uses keys such as `companies.company_id`, `users.user_id`, `sites.site_id`, and supplies the tables used by the secondary API and much of the root CRUD code.
2. `database/migrations/001`–`008` contains a separate SQL migration stream. Device migrations 007 and 008 model tracking with `tenant_id` and reference `companies.id`, `users.id`, and `sites.id`. Those columns do not match the planned schema identifiers. The same stream otherwise also defines several tables with `company_id`, showing that tenancy naming was not settled.

Additional schema files `035`–`049` define an enterprise/GPS/geofence model using `company_id`, but are absent from both `000_run_all.sql` and the migration plan. In particular, `046_gps_locations.sql`, `047_geofences.sql`, and `048_gps_events.sql` are not standard-runner inputs.

### Requested table findings

| Area | Planned schema / application expectation | Device-migration expectation | Result |
| --- | --- | --- | --- |
| Companies, users, roles, permissions, clients, contracts, sites, audit logs, login history | `company_id`; identifiers such as `company_id`, `user_id`, `site_id`; used by root CRUD and secondary API repositories | Device FKs refer to `companies.id`, `users.id`, `sites.id` | Contradictory keys for tracking links |
| Guards/employees | `employees` exists in planned schema and is used by secondary API | tracking calls a guard a `users.id` | Model relationship undecided |
| `guard_devices`, `device_sessions`, location/heartbeat history, `tracking_configuration` | Not in planned schema; root code expects `tenant_id` and device table primary key `id` | Created only by unplanned migration 007/008 | Not created by standard migration plan |
| `guard_device_sessions` | `database/schema/024_guard_device_sessions.sql`, `company_id`, `session_id` | Separate `device_sessions`, `tenant_id`, `id` | Similar domain, incompatible table/API contracts |
| GPS/geofence/events | planned only as unexecuted schema 046–048 with `company_id` | root geofence repository uses no database | SQL and runtime implementations are disconnected |

### Migration runner finding

The runner itself can read `database/migrations/migration_plan.php`; it is not proven universally broken. However, **the planned migration set is materially incomplete for current code**: it excludes migrations `007_guard_devices_domain.sql` and `008_device_presence_telemetry.sql` and schema files 035–049. A database created solely through the plan cannot support the device/presence or SQL geofence structures.

There are further execution risks in the excluded SQL, including MySQL-incompatible partial indexes (`CREATE INDEX ... WHERE ...`) in migration 007 and dialect-sensitive grouped `ADD COLUMN IF NOT EXISTS` / `MODIFY COLUMN IF EXISTS` constructs in migration 008. These require staging validation; they were not executed during this audit.

An existing successfully used database is **UNKNOWN**: the repository contains a local `.tmp-mariadb` directory and connection configuration, but no safe evidence of which schema was applied or what production uses.

## D. Authentication and authorization audit

| Concern | Classification | Evidence / finding |
| --- | --- | --- |
| JWT secret handling | **CONFIRMED** risk | `config/jwt.php` defaults to `change-me`; `apps/api/config/auth.php` defaults to `PUBLIC_EXAMPLE_CONFIGURE_LOCALLY`. Root also substitutes a deterministic development secret when empty in `app/Core/JWT.php`. Production secret enforcement is absent. |
| Password hashing | **CONFIRMED** | Both authentication services use PHP `password_hash`/`password_verify`; no plaintext comparison was found. |
| Token validation | **PARTIALLY CONFIRMED** | Both verify signature and expiry. Secondary validates issuer and session/JTI. Root does not validate issuer and has no JTI/revocation/session check (`app/Core/JWT.php`, `app/Middleware/Authenticate.php`). |
| Authentication middleware | **PARTIALLY CONFIRMED** | Present on protected routes in both apps; secondary is substantially more complete. Root's public refresh endpoint accepts any valid root token and root API has many route-by-route wrappers rather than a central policy. |
| Authorization / permissions | **PARTIALLY CONFIRMED** | Permission middleware exists. Several root device/geofence endpoints lack role/permission checks, including device registration, device updates, session end, location, heartbeat, and all geofence routes. |
| Tenant/company checks | **PARTIALLY CONFIRMED** | CRUD repositories commonly filter `company_id`; device repository commonly filters `tenant_id`. Root authentication gets company from the database user record. Geofence code performs neither tenant nor site ownership validation. |
| IDOR | **CONFIRMED** risk | `GeofenceController` and `GeofenceManagementService` accept arbitrary geofence/site IDs and caller-supplied `authority` claims, without tenant/site ownership checks. Device-session end is scoped by tenant in its service; location/heartbeat validate session/device through `GuardDeviceService`, but route authorization remains incomplete. |
| Device ownership | **PARTIALLY CONFIRMED** | `GuardDeviceRepository::deviceBelongsToGuard` is used by `GuardDeviceService` for session/location/heartbeat flows. No guarantee exists for management actions beyond tenant scope. |
| Site/contract ownership | **PARTIALLY CONFIRMED** | Root repositories commonly scope IDs to `company_id`, but geofence mapping does not verify site ownership. Secondary uses tenant context for most service calls. |
| SQL parameterization | **PARTIALLY CONFIRMED** | Reviewed repositories use prepared statements/bound parameters for data values. Dynamic column composition in `GuardDeviceRepository::update` is allow-listed. A full third-party/deployment audit was out of scope. |
| File uploads | **NOT CONFIRMED** | No upload endpoint/`move_uploaded_file` use was found in application code. Storage configuration alone is not an implementation. |
| CSRF | **PARTIALLY CONFIRMED** | APIs use bearer tokens, which normally avoid cookie-CSRF. `config/security.php` enables CSRF but no enforcement middleware/use was found. |
| XSS | **PARTIALLY CONFIRMED** | The geofence page writes API-derived values through `innerHTML` (e.g., active/history display), so a malicious persisted value could be rendered unsafely. The secondary manual tester uses `htmlspecialchars` for PHP-generated URLs. |
| Rate limiting | **CONFIRMED** absent | A `rate_limit_per_minute` configuration value exists but no enforcement middleware/use was found. |

## E. Device tracking audit

### Confirmed inconsistencies

`DevicePresenceService` is declared against `GuardDeviceRepository` but calls these methods that `app/Repositories/GuardDeviceRepository.php` does not implement:

- `findSessionByIdAndCompany(int $sessionId, int $companyId)`
- `getTrackingConfiguration(int $companyId)`
- `getLatestHeartbeat(int $sessionId, int $companyId)`
- `updateSession(int $sessionId, int $companyId, array $data)`
- `findActiveSessionsByCompany(int $companyId)`
- `getSessionStateHistory(int $sessionId, int $companyId, int $limitDays)`

The first invocation of `evaluateSessionPresence`, `evaluateAllTenantSessions`, `getCurrentPresenceState`, or `getPresenceStateHistory` will therefore produce an undefined-method error unless a different repository is supplied. The service is also not registered in `bootstrap/app.php`, so no production invocation path was found.

There is a second mismatch: `bootstrap/app.php` injects `GuardDeviceRepository` into `DeviceTrackingService`, but `DeviceTrackingService` calls `updateLocation` and `updateHeartbeat`, which only exist on `GuardDeviceSessionRepository`. Controllers currently call `GuardDeviceService` directly for location and heartbeat, making the erroneous injected `DeviceTrackingService` path apparently unused but unsafe.

`GuardDeviceSessionRepository` is a third, incompatible session implementation: it uses `guard_device_sessions`, `company_id`, and `session_id`, while `GuardDeviceRepository` uses `device_sessions`, `tenant_id`, and `id`.

### Telemetry status

- Device registration, session start/end, location writes, heartbeat writes, battery and charging are implemented in `GuardDeviceService`/`GuardDeviceRepository`, conditional on the unplanned migration tables existing.
- GPS status, location permission, app state, network type, and valid-sample fields exist only in unplanned migration 008 and are not accepted/stored by the current heartbeat controller/repository.
- Presence state logic is conceptually implemented in `DevicePresenceService`/`DevicePresenceState`, but cannot operate with the declared repository and lacks a scheduled execution path.
- `tracking_configuration` can be queried/created by `GuardDeviceService`, but it depends on the excluded tracking schema.
- No mobile application source was found, so continuous real-device collection is not implemented in this repository.

## F. Geofence audit

The root geofence workflow has API routes and an interactive page (`public/geofence-mapping.html`), and its in-memory workflow supports draft, submit, approve/reject, version incrementing, active-version selection, and audit-engine calls. It is **not production persistence**:

- `GeofenceRepository` stores records in PHP arrays, reinitialised per request. No database connection or SQL is used.
- It stores `siteId` but no `company_id`/`tenant_id`; no site ownership verification is performed.
- The SQL `geofences` table is not used by this repository and does not include the repository's points storage model.
- `GpsEngine` can evaluate a caller-provided active polygon, but there is no query linking device telemetry to persisted geofences or writing GPS events.
- API routes authenticate callers but allow authority role/scope data from the request body; add-point/finish/history/active have no authorization policy.

Therefore geofencing is **versioned and auditable only in process memory**, and is not tenant-safe, durable, or operationally integrated with tracking.

## G. Feature implementation audit

| Feature | Status | Basis |
| --- | --- | --- |
| Authentication | PARTIALLY COMPLETE | Two incompatible implementations; basic flows exist, production secret policy and unified session design do not. |
| RBAC | PARTIALLY COMPLETE | Permission checks/routes exist; coverage is uneven. |
| Companies | PARTIALLY COMPLETE | CRUD surface exists in both apps with overlapping behavior. |
| Clients | PARTIALLY COMPLETE | Root and secondary API implementations exist, but no single source of truth. |
| Contracts | PARTIALLY COMPLETE | Root CRUD/repositories exist; secondary API does not expose the same domain. |
| Sites | PARTIALLY COMPLETE | CRUD exists in both applications. |
| Guards | PARTIALLY COMPLETE | Employee/user representations exist; guard identity model is inconsistent. |
| Device registration | PARTIALLY COMPLETE | Root flow exists but depends on excluded schema and incomplete endpoint authorization. |
| Device tracking | BROKEN | Code is split across incompatible repository/table contracts. |
| Device presence | BROKEN | Service calls missing repository methods and has no bootstrap/scheduler integration. |
| Geofencing | BROKEN | UI/workflow uses ephemeral storage, not the database. |
| Occurrence Book | STUB | No connected persistence/API workflow found. |
| Incidents | STUB | Engine-level scaffolding only; no connected persistence/API workflow found. |
| Patrols | STUB | Schema and engine/scenario artifacts exist; no complete connected workflow found. |
| QR checkpoints | STUB | Checkpoint engine/scenario artifacts only. |
| Visitors | NOT IMPLEMENTED | No implementation found. |
| Vehicles | NOT IMPLEMENTED | No implementation found. |
| Fire/Safety | NOT IMPLEMENTED | No implementation found. |
| AI | STUB | Engines and schema/documents exist; no provider-connected product workflow found. |
| Audit | PARTIALLY COMPLETE | Secondary API persists audit logs; root `AuditEngine` is an in-memory/event-style component. |
| Reports/PDF | NOT IMPLEMENTED | No report/PDF generation workflow found. |
| Notifications | STUB | Engine/scaffolding only. |
| Dashboard | PARTIALLY COMPLETE | Secondary summary/dashboard surface exists; no operational tracking dashboard. |
| Mobile application | NOT IMPLEMENTED | No mobile source/application found. |

## H. Test coverage audit

`phpunit.xml` classifies suites, but its comments explicitly describe integration tests as "still mocked database." `tests/bootstrap.php` likewise says real database integration tests are future work.

- A full `php vendor/bin/phpunit --testdox` run was attempted and aborted with `Cannot declare class StubContractRepo, because the name is already in use` in `tests/Unit/ContractAccessServiceTest.php`. The global test scripts share un-namespaced stub class names.
- Several files in `tests/Unit` are executable regression scripts rather than PHPUnit test classes; they run in-memory stubs and should not be treated as database integration coverage.
- `DevicePresenceIntegrationTest` tests `DevicePresenceState` constants/logic and explicitly says service/database fixtures are deferred.
- `DevicePresenceTenantIsolationTest` describes required repository methods rather than testing the current repository implementation that lacks them.
- `DevicePresenceTelemetryMigrationTest` is content/string verification of a SQL file, not migration execution.
- No test uses an actual database, production repository, migration application, tenant isolation across real tables, authorization middleware end-to-end, device persistence, or geofence persistence.

Accordingly, current tests cannot detect the confirmed schema conflicts or prove tenant isolation.

## I. Confirmed critical problems

1. Two active, overlapping applications with different JWT/session designs and no declared ownership boundary.
2. `company_id` versus `tenant_id` and `*_id` versus `id` foreign-key conflicts in the tracking model.
3. Device/presence migrations and later GPS/geofence schema files are omitted from the runnable migration plan.
4. Device presence calls six nonexistent repository methods.
5. Root geofences are request-local in-memory data, not database data.
6. Full PHPUnit execution is currently broken and no actual-database tests exist.
7. Root device/geofence endpoint authorization and tenant/site checks are incomplete.

## J. Potential problems

- The excluded tracking migrations contain SQL syntax/dialect risks and have not been safely applied in this audit.
- Root JWT validation does not enforce issuer or revocation; default secrets can make a deployed environment unsafe if environment variables are absent.
- Geofence page API values are rendered with `innerHTML`.
- `DeviceTrackingService` is wired to the wrong repository type.
- Security configuration flags for CSRF/rate limiting appear unused.

## K. Unknowns requiring confirmation

- Which entry point is served in each development, staging, and production environment.
- Whether both apps point to the same database in every environment.
- The actual schema, migration history, and data status of every deployed database.
- Whether any production data exists in the unplanned tracking tables.
- The intended canonical relationship among company, tenant, employee, guard, user, device, and site.
- Whether an external/mobile codebase already sends device telemetry.

## L. Architecture decisions required

1. Select one API runtime as the platform's canonical application, or define a strict non-overlapping bounded-context contract between the two.
2. Adopt one tenancy term and database key strategy. For the stated platform goal, a strong default is logical tenant = a company row, with one canonical column/key naming convention across every table.
3. Select one migration source of truth and bring all required schema objects into one ordered, runnable, verified plan.
4. Select one authentication/token/session model and migrate both runtime paths to it before exposing overlapping routes.
5. Define the canonical guard model (employee vs user), company-owned-device ownership, and site/contract assignment relationships.
6. Define durable geofence storage, point geometry/versioning, audit events, and how telemetry consumes the active approved geometry.

## M. Recommended stabilization order

1. Freeze feature development and production schema changes.
2. Capture safe read-only evidence from deployed environments: served entry point, effective database target, schema inventory, and migrations history.
3. Approve the canonical application boundary, tenancy naming/key model, and migration source of truth.
4. Produce an approved migration reconciliation plan and apply it only to an isolated staging database.
5. Unify authentication, tenant context, authorization, and ownership policies.
6. Repair the repository contracts and connect durable device/session/telemetry persistence.
7. Replace in-memory geofencing with durable, tenant/site-scoped persistence and enforce approval/audit policies.
8. Repair the test suite, then add actual-database migration, tenant isolation, authorization, device tracking, and geofence persistence tests.
9. Re-test existing CRUD/auth functionality against the canonical runtime/database.
10. Resume guard tracking, then an operational dashboard, then advanced workflows/mobile/AI.

## N. Features that should not be developed yet

Do not proceed with BatteryService, an activity engine, high-frequency GPS enhancements, operational dashboarding, patrol expansion, QR verification, mobile application work, AI workflows, or new domain APIs until architecture, schema, auth/tenant enforcement, durable tracking, geofence persistence, and database-backed tests are stabilized.

## RECOMMENDED NEXT DEVELOPMENT SEQUENCE

1. **Architecture decision workshop:** approve one canonical runtime, canonical database schema/migration stream, and tenant/company identifier convention.
2. **Read-only environment verification:** compare database schemas and active entry points in development/staging/production against that decision.
3. **Stabilization design:** write the source-of-truth reconciliation/migration and compatibility plan; do not apply it yet.
4. **Foundation implementation:** enact the approved plan in a staging database, unify authentication and tenant authorization, and fix repository contracts.
5. **Evidence-based testing:** establish real database test fixtures and cover migration, tenant isolation, authorization, tracking, and geofence persistence.
6. **Guard tracking V1:** only after the preceding gates pass, implement reliable device accountability and telemetry workflows.
7. **Operational intelligence:** build supervisor dashboarding, policy-based telemetry sampling, presence/activity detection, and alerting.
8. **Expansion:** patrols, QR, incidents, visitor/vehicle/fire-safety, reporting, mobile clients, and AI assistance.

