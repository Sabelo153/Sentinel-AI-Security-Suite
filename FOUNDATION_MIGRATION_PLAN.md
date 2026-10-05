# Sentinel Foundation Migration Plan

**Status:** Planning only — approval required before implementation  
**Date:** 2026-08-17  
**Authoritative inputs:** `ARCHITECTURE_DECISION.md`, `FOUNDATION_AUDIT.md`, `PROJECT_CONTEXT.md`  
**Change policy:** This document authorizes no code, schema, configuration, route, test, package, or data change.

## A. Executive summary

Sentinel must move from two overlapping PHP applications and two incompatible schema streams to one canonical backend, one migration-controlled schema, and one physical company boundary.

The approved targets are:

- Canonical backend: `apps/api/` (`App\\`).
- Canonical database-definition source: `database/schema/`.
- Canonical physical tenant discriminator: `company_id`.
- Canonical public API: `/api/v1`.

The root SAF application remains intact as a frozen source of domain behavior to be selectively migrated. Its device and geofence work is valuable conceptually, but must not be copied directly because its tables, repositories, authorization, and persistence model conflict with the approved architecture.

The next implementation must begin with reproducible real-database infrastructure and core identity/company integrity. No tracking, presence, GPS, dashboard, AI, or mobile feature should be implemented before those gates pass.

## B. Canonical architecture

```text
Web / mobile / integrations
          ↓
      /api/v1
          ↓
Authentication → company context → permission + resource scope
          ↓
Controllers → application services → company-scoped repositories
          ↓
Canonical MariaDB/MySQL schema and migration runner
          ↓
Audit records and domain events
```

### B1. Canonical backend audit: `apps/api/`

| Concern | Current capability | Migration assessment |
| --- | --- | --- |
| Bootstrap/container | `apps/api/bootstrap/app.php` constructs `Config`, `Container`, `Router`, PDO database, repositories, services and controllers explicitly | Suitable canonical composition root; add future modules here, not in root SAF. |
| Routing | Routes are declared centrally in bootstrap and consistently use `/api/v1` | Strong base; routes are currently limited to identity, organisation and dashboard domains. |
| Middleware | `Kernel` resolves `auth`, `tenant`, and `permission:*` middleware before handlers | Suitable security pipeline, but resource/relationship scope checks must be added in services/repositories. |
| Authentication | HMAC JWT with JTI, access/refresh flow, refresh-token persistence, account status/lock handling | Preferred over root JWT; needs production secret enforcement and a documented issuer/audience/device-auth policy. |
| Authorization | Permission middleware delegates to `PermissionService`/repository | Partially suitable. Current permission query is user-only, not explicitly scoped by `company_id`; it must be hardened before sensitive modules migrate. |
| Tenant/company resolution | `TenantContextMiddleware` derives `company_id` from verified JWT claims and exposes it to controllers | Correct direction. Branch/department header values are parsed but not proven authorized or relationship-validated. |
| Controllers | V1 controllers are transport-focused and use validator/service dependencies | Suitable pattern; canonical equivalents are absent for contracts, devices, geofences, GPS, patrols and incidents. |
| Services | Auth, company, client, site, user, employee, role, dashboard, branch, department, settings, audit | A usable starting layer. Contract and operational services must be introduced only after database/security gates. |
| Repositories | PDO prepared-statement repositories for identity and core organisation tables | Generally schema-aligned; all request-facing methods require company-scoped lookup review. |
| Models/entities | No separate ORM/entity layer | Not a blocker; service/repository boundaries are the current pattern. Avoid introducing a second persistence style during migration. |
| Validation | `Validator::require()` supports required/string/email checks only | Insufficient for operational data, but can be extended in a future approved hardening phase. |
| Error handling | Kernel maps `HttpException` to structured JSON and logs unexpected failures | Suitable v1 error convention. Ensure no sensitive internal data reaches clients. |
| Logging | `Logger` writes channel files; Kernel logs unhandled exceptions | Baseline exists; production log retention/permissions/structured correlation remain to be designed. |
| Audit | `AuditService` writes through `AuditRepository` to `audit_logs` | Stronger than root in-memory audit engine, but coverage is not complete and must be a transactional requirement for sensitive actions. |
| Configuration | API database config loads environment-specific files; auth/config is API-local | Supports distinct environments, but secrets must be externalized and the environment source made consistent with the canonical migration runner. |
| Database connection | PDO MySQL connection with exceptions and associative fetches | Suitable for canonical real-database testing and API persistence. |

### B2. Can it support root SAF functionality today?

**Only partially.** The canonical API can support the identity, company, client, site, employee, branch, department and role foundations at a basic CRUD level. It cannot today support root SAF contracts, device registration/session lifecycle, telemetry, presence, geofencing, patrols/checkpoints, incidents, or operational kernel workflows because those domains have no canonical controllers/services/repositories and their root persistence contract is incompatible with the approved schema convention.

## C. Backend migration matrix

| Feature | Current location | Canonical destination | Current status | Dependencies | Migration required | Risk |
| --- | --- | --- | --- | --- | --- | --- |
| Authentication | Root `AuthService`/JWT and API `AuthService`/JWT | API `AuthService`, `JwtService`, auth routes/middleware | API is stronger; two token systems remain | users, sessions, login history, secrets | Consolidate callers to API model; retain root only during cutover | High |
| Users | Both applications | API `UserController/Service/Repository` | Partial CRUD | companies, employees, roles | Validate company scope, user/employee relationship and route coverage | High |
| Roles | Both applications | API `Role*`, `UserRole*`, `Permission*` | Partial | companies, permissions, users | Reconcile root permission vocabulary/roles with API seeds | High |
| Permissions | Root config and API config/database | API permission service/repository and canonical seed/schema | Partial | roles, company scope | Define global-vs-company permission policy and company-scoped enforcement | High |
| Companies | Both applications | API `Company*` | Partial | auth, platform-admin policy | Resolve company management versus tenant context policy | High |
| Clients | Both applications | API `Client*` | Partial | company scope, audit | Consolidate root endpoint/service behavior | Medium |
| Contracts | Root controller/service/repository/schema | New API Contract controller/service/repository | Partial root-only | clients, companies, sites, audit | Port lifecycle and persistence after core security baseline | High |
| Branches | API domain Company | API `Domains/Company/Branch*` | Partial | companies/clients | Keep and test company/client relationship constraints | Medium |
| Sites | Both applications | API `Site*` | Partial | client, branch, contract, company | Add contract lifecycle validation and root lifecycle behavior only after contract domain | High |
| Departments | API domain Company | API `Domains/Company/Department*` | Partial | company/client/branch | Keep API pattern; confirm scopes and ownership | Medium |
| Employees | API employee stack; root treats guard as user | API `Employee*` plus explicit guard profile/role | Partial | company, person, user, roles | Define employee/guard/user identity mapping | High |
| Guard management | Root guard-operation engines and device service | New API Guard Operations domain | Stub/in-memory | employees, assignments, shifts, sites | Extract business rules only; do not port in-memory persistence assumptions | High |
| Devices | Root `GuardDevice*` | New API Device domain | Partial but schema-conflicted | company, employee/guard, audit, device auth | Create canonical company-owned device model first | Critical |
| Device sessions | Root repositories plus obsolete `guard_device_sessions` | New API DeviceSession domain | Broken/conflicted | devices, guard assignment, shift/site | Choose single session table and key convention | Critical |
| GPS | Root location recording; planned schema files 046/048 | New API Telemetry domain | Partial/broken | sessions, devices, data retention | Select one canonical location history model | Critical |
| Heartbeat | Root heartbeat recording | New API Telemetry domain | Partial/broken | sessions, devices, tracking policy | Normalize telemetry schema/ingestion policy | Critical |
| Battery telemetry | Root heartbeat fields | New API Telemetry domain | Partial | heartbeat history/policy | Include sample/state semantics, not notification feature yet | High |
| Device presence | Root `DevicePresenceService` | Future API Presence domain | Broken/unwired | durable sessions/heartbeats/policy/scheduler | Re-design against canonical repository contracts | Critical |
| Geofences | Root in-memory repository/UI; schema 047 unused | Future API Geofence domain | In-memory only | sites, permissions, audit, telemetry | Create durable versioned geometry and approval model | Critical |
| Patrols | Root engines; schema routes/checkpoints | Future API Patrol domain | Stub | sites, employees, assignments, telemetry | Establish operational persistence first | High |
| Checkpoints | Root checkpoint engine; schema | Future API Patrol domain | Stub | patrols, sites | Add only with patrol workflow | High |
| Incidents | Root engine | Future API Incident domain | Stub | sites, users, audit, attachments | Define durable incident lifecycle after foundation | High |
| Visitors | Docs/permission references only | Future API Visitor domain | Not implemented | sites, access policy | No migration; new planned module later | Medium |
| Vehicles | Docs/menu references only | Future API Fleet domain | Not implemented | companies/sites/employees | No migration; new planned module later | Medium |
| Fire/safety | Docs only | Future API Safety domain | Not implemented | sites, incidents | No migration; new planned module later | Medium |
| Audit | API `AuditService/Repository`; root `AuditEngine` | API audit service/table | Partial | every sensitive write | Make canonical audit transactional and consistently invoked | High |
| AI | Root placeholders/config | Future API AI integration boundary | Stub/disabled | trustworthy operational data, audit, access policy | Preserve concepts only; do not migrate runtime code now | Medium |
| Reporting | Root `DocumentEngine` placeholder; dashboard summary | Future API Reporting domain | Not implemented | stable audited data | No migration before operational data is durable | Medium |

## D. Database reconciliation matrix

### D1. Schema roles

`database/schema/` is the approved canonical definition source. Its numbered baseline currently governs the migration plan only through schema 034; 035–049 require explicit inclusion/reconciliation later. `database/migrations/` is a non-canonical source of historical/domain intent. Migration 007/008 must not be run as a shortcut because they use a rejected `tenant_id`/generic `id` model and reference incompatible keys.

| Table/entity | Current schema/columns/keys | Users in code | Conflict | Proposed canonical representation |
| --- | --- | --- | --- | --- |
| `guard_devices` | Only migration 007: `id` PK, `tenant_id`, `guard_id`, lifecycle/device fields; tenant/device indexes | Root `GuardDeviceRepository`, `GuardDeviceService`, `GuardDevice` model | No canonical schema table; FKs reference `companies.id`/`users.id`, while canonical keys are `*_id` | New canonical `guard_devices`: `guard_device_id` PK, `company_id`, device asset identity/lifecycle, audited assignment relationship; company/device uniqueness and company-status/guard indexes. |
| `device_sessions` | Only migration 007: `id` PK, `tenant_id`, `guard_id`, `guard_device_id`, assignment/shift/site, latest state and heartbeat/location fields | Root `GuardDeviceRepository`, `GuardDeviceService`, `DevicePresenceService` | Conflicts with `guard_device_sessions` (`session_id`, `company_id`, string `device_id`) and canonical FKs | One `device_sessions`: `device_session_id` PK, `company_id`, canonical device/guard/assignment/shift/site IDs, status/lifecycle timestamps and current derived state. |
| `device_location_history` | Only migration 007 then 008 additions: `id`, `tenant_id`, session/device refs, coordinates, accuracy, network/device status, captured/status fields; tenant/session/location index | Root `GuardDeviceRepository`; intended presence/GPS tests | Missing canonical table; duplicates purpose of generic `gps_locations`; rejected tenant/FK keys | One device-telemetry location history table, append-only, with entity-specific PK, `company_id`, session/device/guard references, capture/receipt times, accuracy/provider/network/GPS metadata and retention indexes. |
| `device_heartbeat_history` | Only migration 007 then 008 additions: `id`, `tenant_id`, session/device refs, battery/charging/network/device status and telemetry validity fields | Root `GuardDeviceRepository`, intended `DevicePresenceService` | Missing canonical table; migration SQL/type assumptions unresolved | Append-only canonical heartbeat table with `company_id`, session/device identity, battery, charging, network, GPS/permission/app state, capture/receipt time, validity and idempotency metadata. |
| `tracking_configuration` | Only migration 007/008: `id`, `tenant_id` unique, interval/presence/GPS/battery policy | Root `GuardDeviceService`; intended presence service | Missing canonical table, tenant key inconsistent | Canonical one-per-company `tracking_configuration` with `company_id` unique, policy version/effective timestamps, validated thresholds. |
| `guard_device_sessions` | Schema 024: `session_id` PK, `company_id`, string `device_id`, guard/assignment/shift and latest state columns; indexes but no FKs | `GuardDeviceSessionRepository`, `DeviceTrackingService` tests | Overlaps `device_sessions` but lacks device FK/lifecycle and differs in keys | Do not evolve as a second session model. Map/quarantine existing data during approved reconciliation, then retire it after verified cutover. |
| `geofences` | Schema 047 (not currently plan-run): `geofence_id`, `company_id`, site, code/name, circle/polygon JSON, basic ACTIVE/INACTIVE/ARCHIVED state and indexes | No database repository; root `GeofenceRepository` is in-memory | Lacks draft/review/version/approver/audit geometry history required by decision | Canonical versioned geofence root + immutable geometry/point/version records with `company_id`, `site_id`, lifecycle DRAFT/SUBMITTED/ACTIVE/RETIRED, provenance and audit linkage. |
| `gps_locations` | Schema 046: `gps_location_id`, `company_id`, employee/user/business object refs, coordinates, provider, battery, device string, capture time and indexes | No active repository | Semantically overlaps device location history; no device session FK | Keep only if a documented cross-domain location abstraction is approved. Otherwise device telemetry history is canonical for device data; avoid dual writes. |
| `gps_events` | Schema 048: `gps_event_id`, `company_id`, location/geofence/business-object refs, event metadata/indices | No active repository | Depends on `gps_locations`; no connection to device sessions or approved geofence version | Canonical derived immutable event stream after location/session/geofence design is stable; retain source sample and geofence version references. |
| `contracts` | Schema 021: `contract_id`, `contract_uuid`, `company_id`, `client_id`, lifecycle/status, user audit FKs, indexes | Root Contract repository/service/controller; no API equivalent | Canonical schema exists but API does not; client-company consistency needs enforcement | Retain canonical table; add API domain and lifecycle rules after core scope controls. |
| `audit_logs` | Schema 019: `audit_log_id`, `company_id`, optional user, entity/action/status, old/new/metadata JSON, timestamps and indexes | API `AuditRepository/AuditService`; root has in-memory AuditEngine | Root audit does not persist; schema FK cascade may conflict with long-term audit retention policy | Retain canonical table. Review deletion/FK retention and make all sensitive canonical writes audit it. |
| `login_history` | Schema 020: company/user refs, login/logout/auth method/status/failure data | API auth/login-history repository | Migration 001 names/columns differ | Retain schema 020 and API contract; reject migration-001 version. |

### D2. Reconciliation rules

1. Do not merge migration files into schema files mechanically.
2. Define each target table in a reviewed schema change with canonical `*_id` and `company_id` conventions.
3. Map data by verified foreign-key relationships and preserve source timestamps/provenance.
4. Require constraints and indexes to reflect query paths: company + active status, company + device/session + timestamp, and company + site/version/state.
5. Never create duplicate current-state and history tables without a documented responsibility and source of truth.

## E. `company_id` migration plan

### E1. Complete implementation inventory for `tenant_id`

| File | Table/column | Query or use | Service/repository/controller | Impact |
| --- | --- | --- | --- | --- |
| `database/migrations/007_guard_devices_domain.sql` | `guard_devices.tenant_id`, `device_sessions.tenant_id`, histories/configuration | DDL, FKs to `companies.id`, uniqueness/indexes | Historical tracking schema | Reject as direct deployment source; map each table to canonical `company_id` design. |
| `database/migrations/008_device_presence_telemetry.sql` | tracking table `tenant_id` indexes | DDL/indexes for presence telemetry | Historical presence schema | Re-express only after canonical telemetry tables exist. |
| `app/Repositories/GuardDeviceRepository.php` | all device/session/history tenant filters | select/insert/update keys `tenant_id`, generic `id` | Root device persistence | High: migrate only through new API repository and canonical schema. |
| `app/Services/GuardDeviceService.php` | device session/configuration tenant lookups | `tenant_id` queries and method parameters | Root device workflow | High: future API service accepts trusted `company_id` context. |
| `app/Services/DevicePresenceService.php` | session data expects `tenant_id` | passes session tenant ID and reports company ID | Root presence prototype | Critical: no direct port; redesign only after canonical telemetry/policy repository exists. |
| `app/Models/GuardDevice.php` | guard device model tenant filters | raw SQL/string checks | Root legacy model | High: do not reuse as canonical persistence abstraction. |
| `tests/Integration/GuardShiftEndToEndTest.php` | fixture fields | in-memory test data | Root test scenario | Rename/rebuild as database-backed company-isolation test later. |
| `tests/Integration/DevicePresenceTenantIsolationTest.php` | fixture fields/comments | simulated tenant assertions | Root test scenario | Preserve test intent; reimplement against canonical APIs/repositories. |
| `tests/Integration/DevicePresenceIntegrationTest.php` | comments | documentation of expected repository scope | Root test scenario | Update only with presence implementation approval. |
| `tests/Unit/Migration/DevicePresenceTelemetryMigrationTest.php` | SQL-content expectations | tenant-index strings | Migration-content test | Replace with real schema/migration execution proof only after approved change. |
| `ARCHITECTURE_DECISION.md`, `FOUNDATION_AUDIT.md`, `PROJECT_CONTEXT.md`, related docs | terminology/history | documentation | Architecture records | Do not rewrite approved/history documents; future docs use company boundary terminology. |

No `tenant_id` physical column was found in the canonical `apps/api/` implementation or `database/schema/` definitions. Its `TenantContextMiddleware` is a logical context and already holds `company_id`, which is the desired pattern.

### E2. Safe transition procedure

1. Inventory each real database table and row count before any change.
2. Confirm whether migration-only tracking tables exist and whether every `tenant_id` maps unambiguously to `companies.company_id`.
3. Build canonical tables in an isolated test/staging database using `company_id`; do not mutate old tables in place first.
4. Backfill with explicit column/key mapping and reconciliation reports (counts, orphan rows, duplicate device identifiers, invalid relationships).
5. Verify company-isolation queries and APIs against the copied data.
6. Cut over only canonical API writes after sign-off. Keep legacy tables/read paths until rollback window closes.
7. Archive/drop legacy structures only under a separately approved data-retention and rollback plan.

## F. API consolidation plan

### F1. Root endpoint map

| Existing root endpoint | Canonical endpoint | Current controller | Target controller/service/repository | Migration required |
| --- | --- | --- | --- | --- |
| `POST /api/login`, `POST /api/v1/login` | `POST /api/v1/auth/login` | Root `AuthController` | Existing API Auth controller/service/repositories | Remove duplicate client use only after token/cutover plan. |
| `POST /api/v1/logout`, `refresh`, `GET me` | `/api/v1/auth/logout`, `refresh`, `me` | Root Auth | Existing API Auth stack | Unify JWT/session semantics and response contract. |
| `GET /api/v1/experience` | No direct endpoint | ExperienceController | Future profile/workspace API if approved | Do not port root in-memory experience data as product functionality. |
| `POST /api/v1/guard-operations` | No direct endpoint | GuardOperationsController | Future Guard Operations controller/service | Port only after durable domain persistence. |
| Root geofence start/points/finish/submit/approve/reject/history/active endpoints | Future `/api/v1/sites/{siteId}/geofences...` resource API | GeofenceController | Future API Geofence controller/service/repository | Build after canonical geofence schema, authorization, audit and tests. |
| Device registration/list/show/update/lifecycle endpoints | Future `/api/v1/devices...` | DeviceController | Future API Device controller/service/repository | Build after canonical device schema/ownership policy. |
| Device session start/end/location/heartbeat/config endpoints | Future `/api/v1/device-sessions...` and telemetry subresources | DeviceController | Future API DeviceSession/Telemetry services | Build after devices, assignments and telemetry schema. |
| Root sites CRUD/lifecycle | Existing `/api/v1/sites` plus future item/lifecycle routes | SiteController | API Site controller/service/repository | Consolidate after contract rules; API currently lacks item/update/lifecycle routes. |
| Root clients CRUD | Existing `/api/v1/clients`, future item routes | ClientController | API Client controller/service/repository | API currently only list/create; complete only after authorization scope review. |
| Root contracts CRUD/sites | Future `/api/v1/contracts...` | ContractController | New API Contract domain | Introduce after canonical schema/security baseline. |
| Root company endpoints, including `/companies` | Existing `/api/v1/companies` | CompanyController | API Company controller/service/repository | Eliminate non-versioned duplicate only after platform/company admin policy. |

### F2. Duplicate and gap findings

- Root and API both expose auth, companies, clients and sites.
- Root alone exposes contracts, devices, tracking, geofences, experience and guard operations.
- API alone exposes users, employees, branches, departments, company settings and dashboard summary.
- API item-level client/site routes and site lifecycle endpoints are not yet present.
- No API route should be moved or removed during planning. Endpoint cutovers require compatibility tests, documented client inventory, telemetry and rollback.

## G. Authentication and authorization gaps

### G1. Required chain versus current API

| Required control | Current API evidence | Gap before sensitive migration |
| --- | --- | --- |
| Authenticated user | `AuthMiddleware` verifies token, session JTI and user status | Document issuer/audience/secret policy; add device authentication design before telemetry. |
| Trusted company | JWT claim → `TenantContextMiddleware` | Company creation/list/update policy must distinguish platform administration from ordinary tenant users. |
| Role/permission | `PermissionMiddleware` and permission repository | `userHasPermission(userId, permission)` does not join/filter `ur.company_id` or `p.company_id`; it must be company-scoped. |
| Resource company | Services commonly receive company ID | Not every resource has item endpoints; enforce `findByIdAndCompany` pattern for every domain. |
| Resource relationship | Branch/department services have selected ownership checks | Contract/client/site, device/guard/session, geofence/site and GPS access checks are absent. |
| Action/audit | Many API services audit | Require consistent audit events, transaction behavior and denied-access audit policy. |

### G2. Specific risk controls to design before implementation

- **IDOR:** never resolve a company-owned object by public ID alone. Scope it in SQL and revalidate parent relationships.
- **Company isolation:** permission membership and resource ownership must both include the same trusted `company_id`.
- **Device ownership:** a device is a company asset; data ingestion requires active device/session credentials and a valid assignment—not caller-submitted IDs alone.
- **Site/contract ownership:** service must prove site → contract → client → company consistency before action.
- **GPS access:** location history requires explicit operational permission and scope (own assignment/site/company); all reads are audited.
- **Geofence modification:** require company/site scope and server-derived role permission; never accept authority/role from request body as truth.
- **Administrator privilege:** system/platform administrators require an explicit separate administrative boundary; `all permissions` configuration cannot bypass company checks by accident.

## H. Real database testing plan

### H1. Target test environment

- **Engine:** MariaDB/MySQL matching the production engine/version; use the current PDO MySQL application driver.
- **Database:** dedicated, disposable `sentinel_ai_security_test` database, never development or production.
- **Configuration:** testing environment configuration exists at `database/config/environments/testing.php`, but API and migration-runner configuration discovery must be reconciled in an approved implementation step.
- **Schema loading:** canonical ordered schema/migration runner only. Tests must prove a blank database can reach the current canonical version.
- **Isolation:** database-per-run or schema-per-run with an exclusive CI database; reset/transaction strategy depends on DDL limitations.

### H2. Execution strategy

1. Provision an empty test database in local and CI environments using non-production credentials.
2. Apply the canonical schema through the one approved runner; fail on missing file/order/checksum/SQL incompatibility.
3. Seed a minimum, deterministic company/user/role/permission/client/contract/site/employee dataset.
4. For data-only tests, wrap each test in a transaction and roll it back. For tests containing DDL, use a newly created/rebuilt database because MySQL DDL can implicitly commit.
5. Use factory/fixture helpers that always require a `company_id`; create at least two companies in every isolation suite.
6. Test HTTP endpoints against the API kernel with real PDO repositories, not stub replacements.
7. Tear down with a controlled test-only database reset; never run destructive teardown against a computed/shared production target.

### H3. Test suites required

| Suite | First proof required |
| --- | --- |
| Schema/migration | Blank test database applies all canonical changes and has expected FKs/indexes/columns. |
| Repository | Company-scoped reads/writes, parent relationship constraints and transactions operate against real tables. |
| API/auth | Token/session/login/logout/refresh and error contract work end-to-end. |
| Authorization | Same user ID/role patterns cannot cross company, site, contract, device, session or GPS scopes. |
| Contract/site lifecycle | Expiry/suspension blocks future operations but preserves historical access/audit. |
| Device/telemetry | Added only after canonical device tables; proves assignment/session/ingestion/idempotency. |
| Geofence | Added after persistent design; proves version/approval/history/evaluation isolation. |

### H4. PHPUnit assessment

PHPUnit 11 is configured in `composer.json` and `phpunit.xml`, but the current bootstrap explicitly describes real database integration as future work. The full suite currently aborts on duplicate global stub class declarations. It is not a valid real-database gate. No package change is required to design the target; test configuration changes await approval.

## I. Device tracking migration plan

### I1. Preservation principle

Preserve the root tracking concepts—registration, lifecycle, sessions, heartbeat, location, battery/charging, policy thresholds, GPS quality and presence states—but do not port the root repository or `DevicePresenceService` interfaces. They are coupled to rejected table/key conventions and missing methods.

### I2. Future migration sequence

1. Define company-owned device asset and time-bounded guard assignment model.
2. Define one canonical device session record that contains authorised duty/shift/site context.
3. Define append-only heartbeat and location records with capture/receipt timestamps, quality and idempotency fields.
4. Define company tracking policy/configuration record.
5. Implement authenticated device ingestion and human access authorization.
6. Implement durable repository/API operations and real-database test suites.
7. Only then design presence state evaluation and a safe scheduled job.
8. Only after durable active geofences exist, derive site-compliance events.
9. Build guard-activity inference and dashboard views last, as derived operational intelligence.

### I3. Required conceptual separation

| Concern | Input | Output |
| --- | --- | --- |
| Device presence | valid heartbeat freshness + session/device lifecycle | ONLINE/WARNING/OFFLINE-type device communication state |
| GPS status | provider, permission, enabled state, accuracy/freshness | usable/degraded/unavailable location capability state |
| Location | verified coordinate samples | historical and current device position |
| Guard activity | multiple time-windowed signals plus assignment context | confidence-based stationary/moving/patrol/exception signals |
| Site compliance | active approved geofence + assignment/site/contract status + valid location | inside/outside/tolerance/unknown compliance evidence |

Future requirements—continuous GPS, battery/charging/full notifications, network/app/GPS state, offline/device-left-behind/guard-with-device/stationary/movement/site-abandonment detection—are derived capabilities on these reliable records. They are not authorized implementation work in this phase.

## J. Geofence migration plan

1. Retain root geofence workflow as behavior/reference only: draft mapping, point capture, validation, submit, approve/reject, version history and active selection.
2. Replace ephemeral PHP arrays with a canonical database design before any canonical route exists.
3. Model a geofence root/version and geometry history with `company_id`, `site_id`, status, version, creator/reviewer/approver, timestamps and audit links.
4. Define point/geometry format and database validation compatible with the selected engine. Preserve source mapping accuracy and capture metadata.
5. Enforce one approved active boundary per site/purpose and retain retired history for historical location evaluation.
6. Enforce company, site and permission scope in service/repository policies; requester-supplied authority fields are not valid authorization.
7. Make GPS/site-compliance consume only approved active geometry and record the geofence version used in derived events.
8. Add real-database persistence/version/tenant-isolation/API authorization tests before a UI or device integration cutover.

## K. Ordered migration phases

| Phase | Scope | Why this order | Exit gate |
| --- | --- | --- | --- |
| 0. Freeze and inventory | No new root-only features; identify active deployment/database/data | Prevents further divergence and protects unknown data | Deployment/database inventory approved |
| 1. Canonical migration control | Reproducible runner around `database/schema`, test DB creation | Every later change requires a trustworthy database baseline | Blank real DB builds successfully and repeatably |
| 2. Company/identity foundation | companies, employees/users relationship, roles, permissions, sessions, login/audit policy | Company context and identity are prerequisites for all resource safety | Auth/company/RBAC/tenant integration tests pass |
| 3. Core commercial hierarchy | clients, contracts, branches, sites, departments and lifecycle rules | Devices and operations need valid company/site/contract context | Real DB/API ownership and lifecycle tests pass |
| 4. Test-suite repair and baseline | Namespace/repair stubs, fixtures, CI test execution | No safety-critical domain should be migrated without working evidence | Full suite runs; test layers clearly labelled |
| 5. Device asset and assignment | canonical devices and guard/device assignment history | Session security depends on device ownership | Device-company/assignment tests pass |
| 6. Sessions and telemetry | device sessions, heartbeats, locations, tracking policy | Durable source data is needed before derived states | Telemetry ingestion/isolation/idempotency tests pass |
| 7. Geofences and site compliance | durable versioned boundaries and derived event sources | Needs sites and valid locations | Geofence persistence/approval/evaluation tests pass |
| 8. Presence and activity intelligence | presence scheduler, movement/stationary and exception rules | Derived states must not precede telemetry foundation | State transition and false-positive policy tests pass |
| 9. Patrols, incidents, checkpoints | operational workflows | They rely on trusted assignment/site/device context | End-to-end Guard Operations slice passes |
| 10. Dashboard/mobile | supervisor/dashboard and field client | Must consume stable API/events and security policies | API/load/security acceptance gates pass |
| 11. AI/reporting and later domains | AI, reporting, visitors, vehicles, fire/safety | These are data consumers or new modules, not foundation prerequisites | Data governance/audit/retention requirements met |

Audit should be available from Phase 2 onward and used by every later write; it is placed before operational workflows because it is a control, not a late feature.

## L. Risk register

| Risk | Severity | Mitigation/decision gate |
| --- | --- | --- |
| Data loss from unknown legacy schema/data | Critical | Read-only inventory, backups, mapping/reconciliation counts and staged cutover. |
| Cross-company data leakage | Critical | Trusted company context, company-scoped permissions/resources, real two-company adversarial tests. |
| Continuing dual API divergence | Critical | Freeze root-only feature work; publish migration map and cutover criteria before endpoint changes. |
| Device data model copy-pasted with rejected keys | Critical | New canonical tables/interfaces only; do not run migration 007/008 as-is. |
| Presence logic falsely operationalized | High | Delay until durable telemetry/policy and real state-transition tests exist. |
| Geofence records disappear or are unauthorized | High | No production use until durable company/site/version/approval persistence exists. |
| Migration SQL engine incompatibility | High | Apply from scratch to isolated real engine in CI; verify version/dialect before deployment. |
| Test suite gives false confidence | High | Repair execution and label stubs; require DB/API evidence for persistence/security claims. |
| Auth/session regression during cutover | High | Compatibility inventory, token/version cutover plan, session revocation and rollback. |
| GPS privacy/retention breach | High | Define access permission, auditing, retention/archive policy before telemetry deployment. |
| Contract expiry disrupts historical evidence | Medium | Status-based access/lifecycle changes; no deletes of operational history. |

## M. Rollback strategy

### Planning principles

- No destructive legacy operation is permitted before a tested restore path exists.
- All schema/data changes are applied first to a disposable test database, then staging, then production under change approval.
- Prefer additive canonical tables and dual-read validation over in-place destructive renames/drops.
- Record migration version, checksum, row counts, referential-integrity results and deployment/application version at every stage.

### Future cutover rollback

1. Keep legacy root application and source tables read-capable during the agreed rollback window.
2. Stop canonical writes or route traffic back only through a documented, tested compatibility switch; do not attempt unplanned bidirectional data synchronization.
3. Restore from verified pre-change backup only when data reconciliation fails or a destructive operation has occurred.
4. Preserve immutable audit/telemetry evidence and clearly mark/reconcile any records created during a failed cutover.
5. Drop/retire legacy routes/tables only after reconciliation, monitoring and business-owner sign-off.

## N. Definition of done

Foundation migration planning is complete when this document is approved. Foundation migration implementation will be complete only when all of the following are true:

- One deployed canonical API serves `/api/v1`; root SAF is not a competing write API.
- One canonical, versioned schema/migration runner can build a real empty test database repeatably.
- Every company-owned table uses `company_id` with consistent key/FK types; no operational canonical table uses `tenant_id`.
- Canonical authentication/session, permission, company scope and resource-relationship checks are covered by real database/API tests.
- Clients, contracts, sites, employees/guards and lifecycle rules are durable, company-safe and audited.
- Device asset/assignment, sessions and telemetry are durable and isolated before presence/activity logic is enabled.
- Geofences are persistent, versioned, approved, auditable and company/site-scoped before compliance decisions are enabled.
- The full test suite runs without duplicate-class failures, and stub-only tests are not represented as integration evidence.
- Cutover, rollback, privacy/retention and production-monitoring plans are approved.

## O. What must not be changed without explicit approval

- `ARCHITECTURE_DECISION.md`, `FOUNDATION_AUDIT.md` or `PROJECT_CONTEXT.md`.
- Any SQL under `database/schema/` or `database/migrations/`, including runner order or environment configuration.
- Authentication, JWT, session, password, secret or RBAC behavior.
- API routes, public endpoint prefixes, request/response contracts or application entry points.
- Deletion, rename, move or broad refactor of root SAF or `apps/api/` files.
- Database execution, data conversion, destructive reset or production/staging connection.
- Device tracking, DevicePresence, continuous GPS, geofence persistence, dashboard, mobile, AI, reporting, or new feature work.
- Dependencies/packages or test configuration.

## Approval request

The next safe step, if approved, is **Phase 1: canonical migration control and isolated real-database validation**. It must still begin with a detailed implementation plan and environment confirmation before any database command is run.

