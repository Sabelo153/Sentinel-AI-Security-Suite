# Sentinel AI Security Suite — Historical Project Context

> The earlier dual-application design below is retained for provenance. The empty `apps/api` scaffold has been retired. The canonical runtime is the root `Sentinel\\` application. See [`docs/EMPTY_FILE_AUDIT.md`](docs/EMPTY_FILE_AUDIT.md) for the current repository disposition.

> **Purpose of this file**: Provide future AI coding sessions with a fast, accurate understanding of the Sentinel AI Security Suite codebase so they can work effectively without re-discovering the system from scratch.

---

## 1. Project Purpose

Sentinel AI Security Suite is a **digital security management platform for a private security company**. The intended product covers:

- Digital Occurrence Book / Incident Management
- Visitor Management
- Guard / Shift Management
- Patrol Verification (QR checkpoints + GPS)
- Vehicle Inspections
- Fire/Safety Reports
- AI-assisted reporting, risk assessment, and a security assistant
- Supervisor dashboard, client reporting, audit trail, PDF reports
- Mobile access (Android)
- (Future) Cybersecurity / security-operations capabilities

**Current reality**: The platform is in **early-to-mid development**. Infrastructure, architecture documentation, identity/auth, RBAC, and the Guard Operations proof-point module are the most advanced. Most feature modules are stubs or in-memory implementations.

---

## 2. Technology Stack

| Layer | Technology | Notes |
|-------|-----------|-------|
| Language | **PHP 8.2+** | `composer.json` requires `^8.2` |
| Backend Framework | **Custom "SAF" (Sentinel Application Framework)** | NOT Laravel. Hand-rolled container, router, config, PDO. See `app/Core/` |
| Secondary API | Custom micro-framework in `apps/api/` | Separate namespace `App\`, separate container/router |
| JWT | `lcobucci/jwt` v5.5 | |
| Logging | `monolog/monolog` v3.7 | Wrapped in `Sentinel\Core\Logger` |
| Env | `vlucas/phpdotenv` v5.6 | |
| Crypto | `paragonie/sodium_compat_ext_sodium` | |
| Database | **MySQL/MariaDB 8.0+** | PDO |
| Testing | `phpunit/phpunit` ^11.1 | |
| Frontend | **PHP + Bootstrap** static HTML pages | `public/geofence-mapping.html`, `public/sites.html`, `apps/api/public/dashboard.html` |
| Mobile | **Android (Kotlin)** — *planned only, no code* | |
| AI | Python services — *planned only* | `config/ai.php` has `enabled = false` |
| Infrastructure | Apache/Nginx, Docker — *planned only* | `infrastructure/` |
| OS/Stack | Windows dev (XAMPP), MySQL port varies (3306 or 3307) | |

---

## 3. Architecture

### Dual Application Structure (IMPORTANT)

There are **two separate PHP applications** in this repo, with different namespaces and stacks:

1. **Root SAF application** (`app/`, namespace `Sentinel\`)
   - Entry: `public/index.php` → `bootstrap/app.php`
   - Owns the Platform Kernel, Experience Engine, Guard Operations, Geofence, and Device Tracking code
   - Uses custom `Sentinel\Core\*` framework
   - Auto-wires via `Sentinel\Core\Container` using reflection or manual `instance()` registrations

2. **Legacy/Secondary API** (`apps/api/`, namespace `App\`)
   - Entry: `apps/api/public/index.php` → `apps/api/bootstrap/app.php`
   - Uses its own `App\Core\*` framework (Kernel, Router, Container, PDO)
   - Has a `Controllers/V1`, `Domains/`, `Services/`, `Repositories/` layered structure
   - Provides CRUD for auth, users, employees, clients, sites, branches, departments, dashboard
   - Uses middleware: `auth`, `tenant`, `permission:xxx`

**These two apps are NOT integrated.** They have separate routing, separate containers, and (potentially) different database assumptions. This is significant architectural debt.

### Root SAF Boot Flow

`bootstrap/app.php`:
1. Loads composer autoload + helpers
2. Loads `.env` via dotenv
3. `Config::fromPath('config')`
4. Creates `Container`, `Router`
5. Registers singletons (Logger, Database, ExceptionHandler, ~45 engine/services)
6. Loads `routes/web.php` + `routes/api.php`
7. Captures request, dispatches, renders response

### Container

`Sentinel\Core\Container` supports:
- `instance($id, $value)` — register an existing object
- `singleton($id, $factory)` — register a factory (cached)
- `make($id)` — auto-resolve via reflection (constructor injection)
- Falls back to reflection when a class isn't explicitly registered

---

## 4. Directory Structure

```
├── app/                    # Root SAF framework + domain code (namespace Sentinel\)
│   ├── Controllers/        # Auth, Client, Company, Contract, Device, Experience, Geofence, GuardOperations, Home, Site
│   ├── Core/               # Application, Config, Container, Database, ExceptionHandler, HttpException, JWT, Logger, Middleware, Request, Response, Router, RuntimeContext, Validator, DevicePresenceState
│   ├── Events/             # DevicePresenceEvents (stub)
│   ├── Helpers/            # env.php, uuid.php
│   ├── Middleware/         # Authenticate, Authorize
│   ├── Models/             # Role, User, Permission, GuardDevice, etc.
│   ├── Repositories/       # Client, Company, Contract, Experience, Geofence, GuardDevice, GuardDeviceSession, Permission, PlatformRegistry, Role, Site, User
│   ├── Rules/              # (empty)
│   ├── Services/           # ~50 engines/services (see below)
│   └── Validators/         # (empty)
├── apps/api/               # Secondary API (namespace App\)
│   ├── app/Controllers/V1/
│   ├── app/Domains/        # Company, Finance, Integration
│   ├── app/Core/           # Kernel, Router, Container, Security/JwtService
│   ├── app/Middleware/     # AuthMiddleware, PermissionMiddleware, TenantContextMiddleware
│   ├── app/Repositories/   # 14 repos
│   ├── app/Services/       # 12 services
│   ├── bootstrap/          # app.php
│   ├── config/             # app, auth, database, logging
│   └── public/             # index.php, dashboard.html, test.php
├── database/
│   ├── schema/             # 49 SQL schema files (create tables)
│   ├── migrations/         # 8 "migration" SQL files + runner
│   ├── seeds/              # seed SQL/php
│   ├── config/environments/ # development.php, production.php, staging.php, testing.php (DB creds!)
│   ├── tools/              # validate_schema.php
│   └── docs/               # data dictionary, DB standards
├── config/                 # Root SAF config: ai, app, auth, cache, capabilities, database, events, jwt, logging, mail, permissions, security, storage
├── public/                 # index.php, health.php, sites.html, geofence-mapping.html
├── routes/                 # api.php, console.php, web.php
├── tests/                  # Unit, Integration, API, Feature, Performance, Security (11+ test files)
├── docs/                   # Extensive documentation, ADRs, architecture
├── services/               # (mostly empty — planned microservices)
├── shared/                 # shared utilities/constants (empty)
├── infrastructure/         # deployment configs (docker/cloud)
├── testing/, tools/, scripts/, assets/, archive/  # misc
```

---

## 5. Database

### Schema Management — TWO conflicting systems (CRITICAL)

There are **two parallel and inconsistent database definition systems**:

1. **`database/schema/*.sql`** (49 files) — A "canonical" but **incomplete** set of CREATE TABLE statements, run via `000_run_all.sql`. Uses a **`company_id` + `table_id`** naming convention. **This is the set referenced by `migration_plan.php`.**

2. **`database/migrations/*.sql`** (8 files) — Newer, **domain-oriented** files. These use a **`tenant_id` + `id`** naming convention. E.g. `001_identity_and_access.sql`, `007_guard_devices_domain.sql`. These define the tables the actual PHP repositories query (e.g. `guard_devices`, `device_sessions`, `device_location_history`, `device_heartbeat_history`, `tracking_configuration`).

### Critical mismatch example

- The **migrations** define `guard_devices (id, tenant_id, ...)`, `device_sessions (id, tenant_id, ...)`.
- The **schema** files define `guard_device_sessions (session_id, company_id, ...)` — a different table.
- `GuardDeviceRepository` queries `guard_devices`, `device_sessions`, `device_location_history`, `device_heartbeat_history` (the migration naming).
- `GuardDeviceRepository` also contains obsolete code referencing the OLD `guard_device_sessions` table (a leftover).

### The migration runner is BROKEN

- `database/migrations/run_migrations.php` calls `loadEnvironmentConfig()` which looks for `config/environments/{env}.php`.
- The actual DB config files live in **`database/config/environments/`**.
- There is NO `config/environments/` directory. **Migrations cannot run as-is.**

### Key tables (per repository usage + schema)

- **Identity**: `companies`, `users`, `roles`, `permissions`, `user_roles`, `role_permissions`, `login_history`, `auth_sessions`, `password_reset_tokens`
- **Organizational**: `clients`, `branches`, `sites`, `departments`, `employees`, `employee_profiles`
- **Guard/Device**: `guard_devices`, `device_sessions`, `device_location_history`, `device_heartbeat_history`, `tracking_configuration`
- **Location**: `gps_locations`, `geofences`, `gps_events`
- **Attendance/Shift**: `attendance`, `shift_assignments`
- **Patrol**: `patrol_routes`, `patrol_checkpoints`
- **AI**: `ai_requests`, `ai_jobs`, `ai_responses`
- **Audit**: `audit_logs`, `activity_logs`
- **Finance (planned)**: `finance_chart_of_accounts`, `finance_journals`, `payroll_periods`, `billing_invoices`
- **Workflow/UBO**: `business_object_types`, `object_statuses`, `business_objects`, `object_relationships`, `workflows`
- **Contract/Assignment**: `contracts`, `assignment_scopes`, `employee_assignments`, `authority_levels`, `delegations`

**DB naming mismatch is pervasive**: The repositories and migrations use `tenant_id`/`id` while the schema files use `company_id`/`xxx_id`. This must be reconciled carefully before the DB is expected to work.

---

## 6. Authentication & Authorization

### Root SAF (`Sentinel\`)

- **Login**: `AuthController::login` → `AuthService::login`
  - Validates email/password via `Validator`
  - `UserRepository::findByEmail` → checks `status === 'ACTIVE'`, `locked_until`, `password_verify`
  - On failure: increments failed attempts, records login history
  - On success: issues JWT with claims `sub` (user_id), `company_id`, `email`, `username`
- **JWT**: `Sentinel\Core\JWT` — HS256, issuer, TTL, refresh
- **Auth middleware**: `Authenticate` — validates Bearer token, loads user, sets `request.user`
- **Authorization**: `Authorize` middleware — checks a single permission code via `PermissionRepository::hasPermission`
- **Permissions**: configured in `config/permissions.php` (permissions + 10 predefined roles)
- **Important**: `AuthController` is NOT explicitly registered in container — it's auto-resolved by reflection. Same for `AuthService`, `UserRepository`, `JWT`. This works but is fragile.

### Secondary API (`App\`)

- Has its own `JwtService`, `AuthService`, `AuthMiddleware`, `PermissionMiddleware`, `TenantContextMiddleware`
- Routes support middleware chains: `['auth', 'tenant', 'permission:xxx']`
- Appears to be a more mature, but separate, CRUD API

### Notable security concern

- `config/permissions.php` roles include "System Administrator" with `all_permissions => true` — check how this is enforced
- `LoginHistoryRepository`/`UserRepository` and `AuthService` reference columns (`login_history_id`, `company_id`, `login_at`, `logout_at`, `auth_method`, `login_status`) that **match schema 020** — good, this set is consistent. But the **migration** `001_identity_and_access.sql` defines `login_history` with DIFFERENT columns (`login_id`, `login_timestamp`, `login_method`, `login_success`). So the migration and the repository disagree about `login_history`.

---

## 7. API Overview

### Root SAF routes (`routes/api.php`)

**Auth:**
- `POST /api/login`, `POST /api/v1/login` — login
- `POST /api/v1/logout` — logout (auth)
- `POST /api/v1/refresh` — refresh token
- `GET /api/v1/me` — current user (auth)

**Experience:**
- `GET /api/v1/experience` — resolve experience (auth)

**Guard Operations:**
- `POST /api/v1/guard-operations` — execute guard ops flow (auth)

**Geofences:**
- `POST /api/v1/geofences/start` — start mapping (auth)
- `POST /api/v1/geofences/{geofenceId}/points|finish|submit|approve|reject` (auth)
- `GET /api/v1/sites/{siteId}/geofences/history|active` (auth)

**Devices (guard devices + tracking):**
- `POST /api/v1/devices/register` (auth)
- `GET /api/v1/devices`, `GET /api/v1/devices/active` (auth + `device.view`)
- `GET|PUT /api/v1/devices/{deviceId}` (auth)
- `POST /api/v1/devices/{deviceId}/activate|suspend|mark-lost|retire` (auth + `device.manage`)
- `GET /api/v1/devices/config/tracking` (auth)
- `POST /api/v1/devices/session/start` (auth)
- `POST /api/v1/devices/session/{sessionId}/end|location|heartbeat` (auth)

**Sites:**
- `GET|POST /api/v1/sites` (auth + `site.view`/`site.manage`)
- `GET|PUT /api/v1/sites/{siteId}` (auth + `site.edit`)
- `POST /api/v1/sites/{siteId}/activate|suspend|close|reactivate` (auth + `site.manage`)

**Clients:**
- `GET|POST /api/v1/clients` (auth + `client.view`/`client.manage`)
- `GET|PUT /api/v1/clients/{clientId}` (auth)

**Contracts:**
- `GET|POST /api/v1/contracts` (auth + `contract.view`/`contract.manage`)
- `GET|PUT /api/v1/contracts/{contractId}` (auth)
- `GET /api/v1/contracts/{contractId}/sites` (auth)

**Companies:**
- `GET /companies` and `GET /api/v1/companies` (auth + `company.manage`) — DUPLICATE route (one without `/api/v1` prefix)

### Secondary API routes (`apps/api/bootstrap/app.php`)

`/api/v1` system/health endpoints; `/api/v1/auth/*`; CRUD for companies, clients, sites, users, employees, roles, dashboard, branches, departments, company-settings. Uses `['auth','tenant','permission:xxx']` middleware chains.

---

## 8. Feature Status Summary

| Feature | Status | Evidence |
|---------|--------|----------|
| **Identity & RBAC** | **PARTIALLY COMPLETE** | Auth + JWT + permission checks work for a subset. Sprint 2 RBAC tagged v0.2.1. |
| **Company/Client/Site/Branch/Department CRUD** | **PARTIALLY COMPLETE** | In secondary API (`apps/api`). Root SAF has Site/Client/Contract controllers. |
| **Guard Device Registration/Tracking** | **MOST COMPLETE** | `GuardDeviceService`, `DeviceController`, `GuardDeviceRepository` (migration naming). But DB schema mismatch threatens runtime. |
| **Device Presence Detection** | **PARTIALLY COMPLETE / BROKEN** | `DevicePresenceService` references repository methods (`findSessionByIdAndCompany`, `getTrackingConfiguration`, `getLatestHeartbeat`, `updateSession`, `findActiveSessionsByCompany`, `getSessionStateHistory`) that **DO NOT EXIST** on `GuardDeviceRepository`. Service not wired into container. |
| **Geofence Mapping** | **PARTIALLY COMPLETE (IN-MEMORY)** | `GeofenceRepository` is an **in-memory array** — data does NOT persist. No DB table. Workflow logic exists (start/addPoint/finish/approve/reject). |
| **Guard Operations (Platform Kernel)** | **PARTIALLY COMPLETE (IN-MEMORY)** | `PlatformKernelEngine` orchestrates assignment→authority→scope→delegation→UBO→shift→geofence→gps→checkpoint→patrol→incident→event→rules→exception→task→timeline→audit. But all sub-engines are **in-memory/stubs** (no DB persistence). `PlatformKernelEngine` has **duplicate unreachable code** (lines 266-292 never run). |
| **Incidents** | **STUB** | `IncidentEngine::submit` returns in-memory data, no DB, no photo/attachment support. |
| **Patrol** | **STUB** | `PatrolEngine`, `CheckpointEngine` are in-memory. |
| **Visitor Management** | **NOT IMPLEMENTED** | Only permission codes (`visitor.*`) and UI menus exist. No tables/services. |
| **Vehicle Management** | **NOT IMPLEMENTED** | Only referenced in menus. |
| **Fire/Safety** | **NOT IMPLEMENTED** | Only in docs. |
| **AI** | **NOT IMPLEMENTED (disabled)** | `config/ai.php` `enabled=false`; `AiEngine`/`ExperienceRepository` are stubs returning `[]`. No real AI logic. |
| **Audit System** | **PARTIALLY COMPLETE** | `AuditEngine`, `audit_logs` table exist. Used in some flows, not everywhere. |
| **Notifications** | **STUB** | `NotificationEngine` depends on empty `ExperienceRepository`. |
| **Reporting / PDF** | **NOT IMPLEMENTED** | `DocumentEngine` is minimal/stub. |
| **Frontend** | **MINIMAL** | Static HTML: geofence-mapping page, sites page, API dashboard. No real app UI. |
| **Mobile App** | **NOT IMPLEMENTED** | No Android code present despite README claiming it. |

---

## 9. Key Services and Their State

- **PlatformKernelEngine** — orchestrator; runs the guard-operations flow; **in-memory**, has dead duplicate code.
- **GuardOperationsService** — wraps kernel; returns summary.
- **GuardDeviceService** — register/get/list/activate/suspend/lost/retire devices + tracking sessions. Uses migration-named tables. **Relatively complete.**
- **GuardDeviceSessionService** / **DeviceTrackingService** — very thin; reference repo methods but constrained.
- **DevicePresenceService** — **broken** (calls non-existent repo methods).
- **GeofenceManagementService** + **GeofenceController** — workflow logic solid, but **in-memory repo** (no persistence).
- **AiEngine / ExperienceRepository / experience subsystem** (`ExperienceEngine`, `NavigationEngine`, `DashboardEngine`, `WidgetEngine`, `ThemeEngine`, `NotificationEngine`, `ShortcutEngine`, `ModuleEngine`) — all depend on `ExperienceRepository` which **returns empty arrays**.
- **IdentityEngine/OrganizationEngine/AssignmentEngine/AuthorityEngine/ScopeEngine/DelegationEngine/UniversalBusinessObjectEngine/ShiftEngine/ExceptionEngine/PatrolEngine/CheckpointEngine/IncidentEngine/GpsEngine/GeofenceEngine/EventBus/RulesEngine/TaskEngine/TimelineEngine/AuditEngine** — all used by kernel; all **in-memory/stub implementations** (no DB persistence) in the root SAF.

---

## 10. Configuration Files (Root SAF `config/`)

| File | Purpose |
|------|---------|
| `app.php` | app name, env, debug, timezone |
| `auth.php` / `jwt.php` | JWT secret, issuer, TTL |
| `database.php` | DB connection (uses env) |
| `permissions.php` | Permission codes + role definitions |
| `capabilities.php` | Capability groupings + menus + platform clients |
| `security.php` | rate limit, https, csrf, xss, upload size |
| `ai.php` | AI enabled flag + provider |
| `logging.php`, `cache.php`, `mail.php`, `storage.php`, `events.php` | misc |

---

## 11. Known Bugs / Issues

1. **CRITICAL — Two incompatible DB schema systems** (`schema/*.sql` vs `migrations/*.sql`). Repos query tables that schema files don't create and vice versa (`tenant_id` vs `company_id`; `guard_devices` vs `guard_device_sessions`).
2. **CRITICAL — Migration runner broken**: `run_migrations.php` looks for `config/environments/` but configs are in `database/config/environments/`.
3. **CRITICAL — `DevicePresenceService` references non-existent repository methods** → will fatal when used. Not wired into container.
4. **CRITICAL — `GeofenceRepository` is in-memory** → geofence data does not persist between requests.
5. **HIGH — `PlatformKernelEngine::run()` has duplicate unreachable code** (dead block at end).
6. **HIGH — All engine/stub implementations are in-memory**, no DB persistence, so the core operational flow does not actually store data.
7. **HIGH — Dual unintegrated applications** (root `Sentinel\` vs `apps/api` `App\`) with separate auth, routing, and DB assumptions. Duplicate controllers/services.
8. **MEDIUM — Duplicate route definitions** (`/api/login` + `/api/v1/login`; `/companies` + `/api/v1/companies`).
9. **MEDIUM — Hardcoded credentials** in `database/config/environments/*.php` (development: `sentinel_user`/`PUBLIC_EXAMPLE_CONFIGURE_LOCALLY`).
10. **MEDIUM — JWT default secret `'change-me'`** if env not set; no enforcement of strong secret.
11. **MEDIUM — `login_history` schema mismatch** between migration 001 and schema 020/repos.
12. **MEDIUM — `GuardDeviceRepository` contains obsolete code for `guard_device_sessions`** (old table name).
13. **MEDIUM — `SqlRunner` rollback for ALTER TABLE** is brittle (parses SQL with regex).
14. **LOW — Many empty placeholder dirs/files** (`app/Rules/`, `app/Validators/`, `services/`, `shared/`, `apps/api/app/Domains/Finance/`, `Integration/`, etc.)
15. **LOW — `Application.php` subclass is empty**; `ExperienceRepository` methods return `[]`.

---

## 12. Security Concerns

- **Default/weak JWT secret** if `.env` not configured.
- **Hardcoded DB credentials** in environment config files (committed?).
- **Weak/broken authorization coverage**: Many root-SAF routes only require `Authenticate` (any active user), not a specific permission (e.g., device session endpoints, geofence endpoints, site show). The actual `Authorize` permission is only applied to a subset.
- **No rate limiting / CSRF / XSS protection actually enforced** despite `config/security.php` flags — those are config values only; need to verify middleware/enforcement.
- **In-memory geofence repo** means authorization checks can be bypassed on restart (no server-side state).
- **Tenant isolation relies on `company_id`/`tenant_id` columns** — but repository queries must consistently pass the tenant; some queries (e.g. `updateLastSeen`, `isActive`) do NOT scope by tenant.
- **Mass assignment risk**: `GuardDeviceRepository::update` whitelists fields (good) but some update methods don't scope by tenant (e.g., `updateLastSeen`).
- **No HTTPS enforcement** in dev (ENFORCE_HTTPS=false).
- **`config/permissions.php` "System Administrator" has all permissions** — ensure only truly elevated users get this role.

---

## 13. Current Development Priorities / Direction

Per **ADR-0015 (Platform Foundation Freeze v1.0)**:
- **Platform is frozen at v1.0** — no new platform engines by default.
- **Focus is ONE complete production module: Guard Operations** — the proof point.
- Guard Operations scope: shift management, clock in/out, equipment inspection, patrol execution, QR/NFC checkpoints, incident reporting, photo/voice evidence, AI incident summary, offline capture/sync, supervisor review/approval, operational analytics.

Shared services should be implemented only when a Guard Operations slice needs them in production scope.

**Deliverable order** (from Guard Operations blueprint):
1. Release 1.0 — platform kernel services
2. Release 1.1 — guard workflow MVP
3. Release 1.2 — supervisor workspace
4. Release 1.3 — control room workspace
5. Release 1.4 — operations management workspace

---

## 14. Changes an AI Agent MUST NOT Make Without Approval

- **Do NOT modify the database schema** (either `schema/*.sql` or `migrations/*.sql`) without explicit approval — there is no single source of truth and changes could break either system.
- **Do NOT delete, rename, or restructure** the two overlapping application trees (`app/` vs `apps/api/`) without approval.
- **Do NOT run migrations** without first fixing the `config/environments` path issue and confirming which schema system is authoritative.
- **Do NOT change authentication/session/JWT behavior** without approval (security-sensitive).
- **Do NOT touch `database/config/environments/*.php`** credentials or commit real secrets.
- **Do NOT add new platform engines** — ADR-0015 freezes them.
- **Do NOT change RBAC permission codes / role definitions** in `config/permissions.php` or `PermissionRepository` without approval.
- **Do NOT remove "stub" or "in-memory" services** that appear unused — they are the intended architecture scaffolding and may be wired into future modules.

---

## 15. Recommended Development Order (for future sessions)

1. **Resolve the DB schema conflict** — pick one authoritative schema system; align `schema/*.sql` and `migrations/*.sql`; fix the migration runner path.
2. **Fix critical safety items**: JWT secret enforcement, tenant scoping in repos, authorization coverage on root-SAF routes.
3. **Wire up `DevicePresenceService`** to real repository methods, or remove/mark clearly as not-yet-wired.
4. **Persist `GeofenceRepository`** to a DB table.
5. **Complete the Guard Operations module** as the end-to-end proof point (per ADR-0015 & blueprint), replacing in-memory engines with DB-backed implementations.
6. **Unify or deprecate the two applications** (`app/` root SAF vs `apps/api/`).
7. **Add real tests** for the in-memory engines and device tracking; the current tests are mostly unit tests of enums/state.
8. **Build the actual UI** and integrate with the verified APIs.

---

*Generated during investigation. No secrets or credentials are stored in this file beyond placeholders already present in the repo's committed configs.*
