# Sentinel Architecture Consistency Review

**Review date:** 2026-08-17  
**Scope:** Document comparison only. No implementation, schema, configuration, test, or route changes were made.

## Purpose and document roles

The three records form a valid current → target → transition chain:

| Document | Intended role | Review result |
| --- | --- | --- |
| `PROJECT_CONTEXT.md` | Current repository state and known risks | Mostly current-state evidence; some conclusions are now superseded by later decisions. |
| `ARCHITECTURE_DECISION.md` | Target architecture and approved direction | Clear target, but its own status line still says `Proposed for approval`. |
| `FOUNDATION_MIGRATION_PLAN.md` | Controlled path from current to target | Correctly stages implementation and prohibits premature feature work. |

## A. Consistent decisions

| Topic | Consistent position across the records |
| --- | --- |
| Canonical backend | `PROJECT_CONTEXT.md` identifies `apps/api/` as the more mature separate CRUD API; the decision explicitly selects it; the plan makes it the migration destination. |
| Root SAF | The context identifies valuable root device/geofence/operational work; the decision says it is preserved/frozen and selectively migrated; the plan explicitly retains it during transition. No document authorizes deletion. |
| Company boundary | Context identifies the `company_id`/`tenant_id` conflict. The decision selects `company_id` as the sole physical key. The plan inventories `tenant_id` and stages conversion rather than changing it immediately. |
| Schema source | Context identifies two conflicting schema systems. Decision selects `database/schema/`. Plan treats `database/migrations/` as historical/domain input, not a deployment shortcut. |
| API prefix | Context identifies duplicate legacy routes. Decision selects `/api/v1`. Plan maps legacy root routes to future canonical `/api/v1` resources and defers route changes. |
| Authentication | Context describes two JWT implementations and calls API authentication more mature. Decision selects the API's centralized JWT/session lifecycle. Plan migrates callers only through a controlled compatibility/cutover process. |
| Authorization | Context identifies uneven root authorization. Decision requires trusted company context plus permission/resource scope. Plan identifies current API scope gaps before sensitive domain migration. |
| Core hierarchy | All three support Company → Client → Contract → Site → Guard/Employee → Device/Session as the operational direction. |
| Devices/GPS | Context says current root tracking is schema-conflicted. Decision requires durable device/session/telemetry before derived presence. Plan orders devices, sessions, and telemetry before presence/activity/dashboard. |
| Device presence/activity | Decision and plan explicitly separate device presence, GPS status, location, guard activity, and site compliance. Neither equates GPS with duty performance. |
| Geofences | Context identifies in-memory geofences. Decision requires persistent, company/site-scoped, versioned approval records. Plan schedules geofence work only after site and telemetry foundations. |
| Audit | Context identifies persistent API audit logs versus root in-memory audit. Decision makes persistent audit canonical; plan treats audit as a control from the identity foundation forward. |
| Testing | Context documents stub-heavy/broken tests. Decision and plan require an isolated real database and database/API integration evidence; stubs remain unit-only. |
| Data preservation | Decision requires append-only/soft-retained history. Plan uses inventory, backup, additive cutover, reconciliation and rollback rather than destructive conversion. |

## B. Contradictions found

### 1. Architecture decision status is internally stale

- `ARCHITECTURE_DECISION.md` begins with: **“Status: Proposed for approval.”**
- The user-approved instruction and `FOUNDATION_MIGRATION_PLAN.md` both treat its targets as approved; for example, the plan says **“The approved targets are”** and names `apps/api/`, `database/schema/`, `company_id`, and `/api/v1`.

**Conflict:** the governing decision document itself still labels the decision proposed while downstream planning treats it as approved.

**Impact:** an implementer could reasonably pause rather than begin Phase 1, or treat the source-of-truth choices as reversible without a new decision.

**Clarification:** record the actual approval status through the project’s change-control process before implementation. This review does not modify that document.

### 2. Migration-runner diagnosis differs between current-state records

- `PROJECT_CONTEXT.md`, section “The migration runner is BROKEN,” states it looks for `config/environments/{env}.php` while config is under `database/config/environments/`.
- `FOUNDATION_AUDIT.md`, section “Migration runner finding,” says the runner **“can read `database/migrations/migration_plan.php`; it is not proven universally broken,”** but the planned set is incomplete.
- `FOUNDATION_MIGRATION_PLAN.md` consequently says API and migration-runner configuration discovery **“must be reconciled”** in Phase 1.

**Conflict:** the context calls the runner categorically broken; the later audit says its path resolution is not proven broken, while identifying a more material completeness/dialect risk.

**Impact:** Phase 1 could spend effort fixing an assumed path bug instead of first performing a real, isolated dry-run validation.

**Clarification:** Phase 1 should begin with a non-production runner execution/dry-run against the test environment and record the exact observed configuration path, plan resolution, ordered files, and SQL compatibility. Treat no diagnosis as final until that evidence exists.

### 3. Scope of the canonical schema baseline is phrased inconsistently

- `PROJECT_CONTEXT.md` describes `database/schema/*.sql` as **“49 files”** and calls it a canonical but incomplete set run by `000_run_all.sql`.
- `FOUNDATION_MIGRATION_PLAN.md` states the migration plan currently governs through schema 034 and that 035–049 require explicit inclusion/reconciliation.
- `ARCHITECTURE_DECISION.md` chooses `database/schema/` as the canonical source but does not explicitly choose whether 035–049 are part of the immediate baseline or future controlled additions.

**Conflict:** not a disagreement on the directory, but an unresolved scope boundary within the chosen directory.

**Impact:** a Phase 1 implementation could inadvertently load schema files 035–049 merely because they reside in the canonical folder, creating GPS/geofence/business-object tables before their target model has been approved.

**Clarification:** approve a precise Phase 1 baseline manifest (not just a directory): which schema files are applied initially, their order, and the explicit status of 035–049.

## C. Ambiguous decisions

| Topic | Ambiguity | Recommended clarification |
| --- | --- | --- |
| Approval record | The user has approved the direction, but the ADR-style document still says proposed. | Establish the decision’s approved status and approver/date in the project governance record. |
| Platform administration | API company routes require tenant middleware, while a platform administrator may need to create/list companies outside an existing tenant. | Define a separate platform-administration principal/context and its API boundary; do not let ordinary company roles bypass company scope. |
| Permission ownership | Current schema has `permissions.company_id`; the decision allows company-scoped roles but does not finally choose global permission catalog versus per-company permissions. | Decide whether permission definitions are platform-global with company role grants, or truly company-local; align seeds, middleware, and queries accordingly. |
| Guard identity | Documents say a guard is an employee and may have a user account, but current tracking references a user as guard. | Define canonical guard ID/reference and user-to-employee cardinality before device table work. |
| Device assignment | Decision requires historical, time-bounded assignment, but no exact target table/relationship has been approved. | Approve the device-assignment data model before session/telemetry implementation. |
| Location model | Schema 046 `gps_locations` and migration-only `device_location_history` overlap. | Select one canonical device-location record and explicitly decide whether generic GPS locations will exist later for non-device domains. |
| Geofence geometry | The desired lifecycle is clear but geometry storage is not selected (normalized points vs versioned JSON/spatial type). | Decide format after confirming production database-engine/version support and query needs. |
| Legacy API retirement | `/api/v1` is canonical, but legacy route client inventory and compatibility period are unknown. | Inventory consumers, define deprecation headers/dates and rollback switch before changing routes. |
| Data retention/privacy | The plan recognizes GPS retention/privacy, but no retention duration, access policy or archival location is decided. | Approve a security/privacy retention policy before collecting continuous location data. |

## D. Missing decisions

1. Production/staging environment inventory: the live entry point, database/schema version, migration history, client consumers and whether production tracking data exists remain unknown.
2. Canonical migration-runner specification: manifest, checksums, locking, execution model, SQL dialect/version, rollback support and ownership are not yet approved.
3. Device authentication model: device credentials, token issuance/rotation/revocation, enrollment, and session binding are required before telemetry ingestion.
4. Company/platform admin boundary: cross-company support and company provisioning need explicit policy.
5. Audit retention/deletion policy: the current `audit_logs` foreign-key cascade must be evaluated against the decision to retain historical evidence.
6. Contract enforcement rules: specific write/read behavior at expiry, renewal and closure needs final API and data policy detail.
7. Operational scheduler/runtime: presence and derived-event processing eventually need an approved job runner, locking/idempotency and monitoring design; it is correctly out of scope now.

## E. Risks

| Risk | Assessment | Control required before implementation |
| --- | --- | --- |
| Implementing against an unapproved decision | High | Resolve the stale `Proposed` status. |
| Applying too broad a schema baseline | Critical | Approve an exact Phase 1 manifest; do not execute all files by directory membership. |
| Destroying/reinterpreting legacy data | Critical | Read-only inventory, backups, mapping/reconciliation counts and staged additive cutover. |
| Cross-company authorization weakness | Critical | Company-scope permissions/resources and two-company real DB/API tests before sensitive module migration. |
| Premature GPS/geofence implementation | Critical | Maintain ordered gates: database → identity/company → core hierarchy → device/session/telemetry → persistent geofence → derived states. |
| Treating device/GPS signals as proof of human behavior | High | Preserve the documented five-concern separation and human-review/threshold policy. |
| False test confidence | High | Repair full suite and require real database/API tests; stubs cannot prove persistence or isolation. |
| Dual backend writes during cutover | High | No uncontrolled bidirectional synchronization; use approved compatibility, reconciliation and rollback controls. |

## F. Recommended clarifications

Before Phase 1 implementation approval, record these decisions explicitly:

1. Mark `ARCHITECTURE_DECISION.md` as approved through the authorized governance process, including date/owner.
2. Define the Phase 1 canonical schema manifest: exact ordered files, excluded files, database engine/version and migration-runner behavior.
3. Authorize a read-only/non-production validation of the migration runner to resolve the configuration-path and SQL-compatibility evidence gap.
4. Define platform administration separately from normal company administration.
5. Decide global-versus-company-local permission definitions and revise future company-scoping tests accordingly.
6. Approve the user/employee/guard identity and company-device assignment model before device schema design.
7. Approve the GPS location canonical model, telemetry retention/privacy policy and geofence geometry representation before those phases begin.
8. Require a deployment/client/database inventory before legacy route or application cutover work.

## Explicit safety verification

The migration plan does **not** require any of the following:

- Deleting the root SAF application prematurely.
- Destroying existing data or applying in-place destructive schema conversion.
- Implementing continuous GPS before canonical device/session/telemetry storage and real-database tests exist.
- Implementing geofencing before persistent, versioned, company/site-scoped storage exists.
- Treating device presence as guard activity.
- Treating GPS location as proof that a guard is performing duties.
- Treating stub-only tests as sufficient evidence of database, authorization or tenant-isolation correctness.

## ARCHITECTURE STATUS: REQUIRES CLARIFICATION

The architecture is directionally consistent and the migration plan is safe in intent. Implementation should wait for the approval-status correction and the Phase 1 schema/migration-runner baseline clarifications above. Once they are recorded, the first authorized implementation remains database foundation plus real integration-test environment—not GPS, presence, geofencing, dashboard, mobile or AI work.

