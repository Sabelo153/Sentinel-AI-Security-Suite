# Sentinel Architecture Decision — V1 Source of Truth

**Status:** APPROVED / CURRENT TARGET DECISION  
**Date:** 2026-08-17  
**Decision scope:** Platform architecture only. This document deliberately makes no PHP, SQL, route, repository, service, test, migration, or package changes.

## Decision summary

Sentinel will be one multi-company security-operations platform. **BAC CPA is a company using Sentinel, not a special-case architecture.**

The recommended authoritative foundation is:

| Concern | Recommended source of truth |
| --- | --- |
| Canonical backend application | `apps/api/` (`App\\`), strengthened and extended deliberately |
| Canonical database-definition stream | `database/schema/` executed by one ordered migration system |
| Physical tenant discriminator | `company_id` on every company-owned record |
| Tenant terminology | “Company” is the business entity and tenant boundary; “tenant” remains a conceptual/API-security term, not a second database key |
| Canonical API prefix | `/api/v1` |
| Authentication | One central JWT access-token plus server-side session/refresh-token lifecycle |
| Authorization | Authenticated user → company → roles/permissions → resource ownership/scope → action |

The root SAF application (`app/`, `Sentinel\\`) must be treated as a **source of domain ideas and work to be selectively migrated**, not as a second live backend. It should eventually be integrated into the canonical API or deprecated after controlled parity and data validation. Nothing is to be deleted or moved until a separately approved implementation plan exists.

## 1. Application source of truth

### Current comparison

| Criterion | Root SAF (`Sentinel\\`) | Secondary API (`App\\`) |
| --- | --- | --- |
| Runtime structure | Custom app/bootstrap/routes model | Self-contained API application with explicit kernel/router/middleware |
| Identity/RBAC | JWT and route-level checks; no session revocation and uneven policy use | JWT JTI/session lifecycle, account status/lockout handling, tenant context and permission middleware |
| Existing CRUD | Clients, contracts, sites, devices | Auth, companies, clients, sites, users, employees, roles, dashboard, branch/department/settings |
| Device/geofence work | Most of it is here, but tracking contracts conflict and geofence storage is in-memory | Does not yet implement device/geofence domains |
| Database alignment | Mixed: normal CRUD expects `company_id`; tracking expects `tenant_id`/`id` keys | Closely aligned with planned `database/schema` `company_id` tables |
| API consistency | `/api`, `/api/v1`, many per-route middleware closures | Primarily `/api/v1`, central kernel and middleware declarations |

### Decision

**`apps/api/` becomes the canonical Sentinel backend application.**

It is the stronger foundation for a multi-tenant product because it already has the most coherent identity/session model, central middleware chain, and closest alignment with the runnable planned schema. The root application contains valuable workflow, device, geofence, and platform-engine work, but those components are currently not a coherent persistence/security runtime and must not remain a competing API.

### Transition posture for the root SAF application

1. Freeze new root-only features.
2. Inventory root components by reusable domain behavior, not by filename.
3. Port only approved business behavior behind canonical `apps/api/` service, authorization, repository, and test boundaries.
4. Maintain temporary read-only/compatibility access only if a deployment requires it.
5. Deprecate the root API only after endpoint parity, data migration validation, contract tests, and an approved cutover plan.

This is not a judgment that all root code is disposable; it is a decision to prevent two sources of runtime truth.

## 2. Tenant model

### Decision: `company_id` is the canonical physical identifier

Sentinel is a shared platform. A company is the tenant boundary in the product's business language and database. Every company-owned row will carry a non-null `company_id` foreign key to `companies.company_id`, unless it is explicitly global platform reference data.

Use the terms as follows:

- **Company:** customer/security provider that operates on Sentinel (for example, BAC CPA).
- **Tenant:** the logical isolation boundary represented by that company.
- **`company_id`:** the only database discriminator and foreign-key column for that boundary.
- **Never add `tenant_id` as a parallel synonym.** API code may use a `TenantContext` object, but it contains `company_id`.

### Ownership rules

| Resource | Mandatory ownership rule |
| --- | --- |
| Users | A user has a home `company_id`; any future platform operator/cross-company role requires an explicit platform-level design, not a null/implicit bypass. |
| Roles and role assignments | Company-scoped unless explicitly a platform-system role. Assignments carry scope where needed. |
| Clients | `company_id` identifies the security provider that owns the client relationship. |
| Contracts | Belong to the same `company_id` as the client; enforce the client/company match. |
| Sites | Belong to the same `company_id` as their contract and client; enforce all parent matches. |
| Employees/guards | Belong to one company; a guard is an employee and may have a user account. |
| Devices | Company-owned assets with `company_id`; assignment to a guard is historical and time-bounded. |
| Tracking sessions/history | Always carry `company_id`, even when derivable, to support isolation, indexes, retention, and audit queries. |
| Geofences/GPS events | Always carry `company_id` and enforce same-company relationships to site, device/session, and source records. |
| Audit/login history | Carry `company_id` when the event is company scoped; platform events have an explicit platform scope/type. |

### Isolation invariant

Every request obtains `company_id` from verified authentication/session claims, never from body/query/path input. Every resource read, write, update, delete, and relationship lookup scopes by that value and validates parent ownership. Database foreign keys, unique constraints beginning with `company_id` where appropriate, and database-backed tenant-isolation tests reinforce this rule.

## 3. Database source of truth

### Decision

**`database/schema/` becomes the canonical schema definition.** It already reflects the identifiers used by the strongest candidate API (`company_id`, `company_id`/`user_id`/`site_id` primary-key naming) and has a numbered, business-oriented baseline. The migration runner must become the sole approved way to apply an ordered version of that schema, but that tooling change is an implementation phase, not part of this decision.

`database/migrations/001`–`008` is not canonical as a whole. Its useful domain intent must be reconciled into future, ordered schema migrations, never copied wholesale. In particular, the device migrations' `tenant_id` and `companies.id`/`users.id`/`sites.id` assumptions are rejected.

### Target entity reconciliation

| Entity | Current schema A: `database/schema` | Current schema B: migrations/root tracking | Target design | Action required |
| --- | --- | --- | --- | --- |
| companies | `companies.company_id` | references `companies.id` in tracking SQL | `companies.company_id` is tenant boundary | Keep A; remove B key assumption during approved reconciliation |
| users | `users.user_id`, `company_id` | tracking references `users.id` as guard | User identity belongs to company; employee relationship optional/explicit | Reconcile FK naming and guard model |
| roles | company-scoped roles | different/overlapping models | company-scoped roles plus scoped assignments | Consolidate repository/service contracts |
| permissions | common permission catalog/role links | overlapping model | permission definitions plus company role grants | Establish one permission schema/seed process |
| clients | `company_id` | root CRUD uses same | company-owned customer record | Retain A; centralize API behavior |
| contracts | `company_id`, client link | root CRUD uses same | company/client-owned contractual lifecycle record | Retain A; enforce parent-company match |
| sites | `company_id`, contract link | tracking references `sites.id` | company-owned operational site under contract | Retain A; correct tracking FKs |
| employees | `employees.employee_id`, `company_id` | uses user as guard | employee is workforce record; guard is an employee role/profile | Decide/account for user-to-employee relationship |
| guard_devices | absent from runnable A | unplanned `tenant_id`, `id` model | `guard_devices.guard_device_id`, `company_id`, lifecycle and current/historical assignments | Add approved canonical schema migration |
| device_sessions | planned `guard_device_sessions` differs | `device_sessions.id`, `tenant_id` | one `device_sessions.device_session_id`, `company_id`, device/guard/site/assignment context | Replace duplicate concepts with one canonical design |
| device_location_history | absent | `tenant_id`, device session history | `device_location_history.device_location_id`, `company_id`, session/device references, source/capture metadata | Add canonical migration and retention/index policy |
| device_heartbeat_history | absent | `tenant_id` telemetry | `device_heartbeat_history.device_heartbeat_id`, `company_id`, session/device references, telemetry snapshot | Add canonical migration |
| tracking_configuration | absent | `tenant_id` one-per-tenant | `tracking_configuration.company_id` unique; policy/version fields | Add canonical migration |
| geofences | unplanned schema 047, `company_id`, no linked point model | runtime in-memory arrays | geofence + immutable/versioned geometry/points/history, `company_id`, `site_id` | Create approved persistence model later |
| gps_locations | unplanned schema 046, `company_id` | separate device location history concept | Canonical device telemetry table is location history; general `gps_locations` only if a documented cross-domain abstraction is necessary | Avoid duplicate writes until a bounded purpose is approved |
| gps_events | unplanned schema 048 | no durable implementation | immutable derived event/audit record tied to source telemetry/geofence | Add after durable telemetry and geofence |
| audit_logs | planned company-scoped table | root in-memory audit engine | immutable, company-scoped persistent audit log | Retain A; route all canonical writes through it |
| login_history | planned company/user history | root/secondary behavior overlaps | company-scoped security audit record | Retain A and canonical auth service |

### Database design rules

- Primary keys use entity-specific `*_id` names across canonical tables.
- All company-owned foreign keys use `company_id` and match `companies.company_id` type/sign.
- Parent/child consistency is enforced both in services and with foreign keys where the relationship is direct.
- Operational history is append-only or soft-retained; expiry/closure never deletes historical operational evidence.
- Schema changes are additive, ordered, reversible where safe, checksum-tracked, and verified first on an isolated real database.

## 4. Device tracking architecture

### Canonical domain chain

```text
Company
  → Employee/Guard
  → Company-owned Device
  → Device Assignment (time-bounded)
  → Tracking Session (duty/shift/site context)
  → Telemetry samples (heartbeat + location)
  → Derived device presence and GPS status
  → Derived site-compliance and guard-activity signals
  → Operational events, alerts, dashboard and reporting
```

### Separate the five concepts

| Concern | Meaning | Source | Must not be inferred solely from |
| --- | --- | --- | --- |
| Device presence | Whether a registered, assigned device is communicating within policy | Valid heartbeat timing and session state | GPS coordinates |
| GPS status | Whether location capability is available/usable | Permission, GPS enabled/provider, accuracy, freshness | Device presence |
| Device location | A time-stamped physical coordinate sample | Location telemetry | A guard's actual behaviour |
| Guard activity | Confidence-based operational behavior: movement, patrol evidence, expected duty context | Multiple inputs: assignment, session, location patterns, checkpoint/incident evidence | One location sample or device heartbeat |
| Site compliance | Relationship of a valid location/assignment to an active site/geofence/contract policy | Approved active geofence, assignment and contract/site state | Generic device online state |

This separation prevents “GPS equals guard activity.” A device can be online with poor GPS; it can be inside a site but unattended; a guard can be engaged in a legitimate stationary post; a location can be stale while the device remains connected.

### Data and lifecycle model

1. **Device registration:** company creates a device asset with immutable hardware/app identifiers, lifecycle status (`REGISTERED`, `ACTIVE`, `SUSPENDED`, `LOST`, `RETIRED`), and audit history.
2. **Device assignment:** assignment records who may carry it, the approved period, and handover/return evidence. Historical assignments are never overwritten.
3. **Tracking session:** starts only for an active company device, a valid company guard/user, and permitted active duty context. It records assignment/shift/site context and closes normally or exceptionally.
4. **Heartbeats:** append an authenticated device/session sample including battery percentage, charging state, network state/type, GPS/provider state, permission, app state, capture time and validity/sequence metadata.
5. **Locations:** append coordinates, accuracy, capture/receipt time, source, session/device/company references. Sampling policy controls cadence and batching; it does not equate to a verdict on guard conduct.
6. **Derived state:** a scheduled/evaluated service computes presence, GPS health, movement/stationary state, and geofence/site compliance from durable samples and company policy. It writes state transitions and immutable derived events idempotently.
7. **Operational intelligence:** rules/alerts consume derived states with context and confidence; human supervision remains required for conclusions such as abandonment or misconduct.

### Future capability mapping

- Battery low/critical/full notification: derive from consecutive heartbeat samples and configurable thresholds; full notification needs a state transition/debounce, not every 100% sample.
- Offline detection: derive from server receipt time, client capture time, session status, and a company tracking policy.
- Stationary/movement: derive from multiple quality-filtered locations over a time window, assignment/post type, and GPS accuracy.
- Site abandonment/device-left-behind: alert candidates, not immediate facts; require active assignment, durable approved geofence, session and evidence thresholds.

## 5. Geofence architecture

### Decision

Geofences are durable, company-scoped, site-associated, versioned operational controls. The current in-memory repository is a prototype only and must never be a production persistence source.

### Target lifecycle

```text
Site selected
  → GPS/survey mapping captures a DRAFT geometry version
  → geometry and quality validation
  → SUBMITTED_FOR_REVIEW
  → authorised manager approves or rejects
  → APPROVED version becomes ACTIVE
  → previous active version becomes RETIRED
  → history remains queryable and auditable
```

### Required persistence design

- A geofence root record identifies the company, site, lifecycle, version number, geometry type, tolerance/policy, creator, approver, timestamps, and current status.
- Geometry is stored as an immutable versioned payload or normalized ordered point set, with validation metadata (accuracy samples, mapping method, validation result). The chosen storage format must be approved with database-engine capability in mind.
- A unique company/site/version constraint prevents ambiguous histories; at most one active geofence per site/purpose is enforceable.
- Every state change, geometry revision, reviewer decision, and activation/retirement emits persistent audit records.
- Location evaluation uses only the active, approved geofence effective at the sample/event time; historical evaluation retains the geometry version used.
- Authorization verifies company, site ownership, role/permission, and scope before any draft/point/review action. Request-supplied role data is never authoritative.

## 6. Site and contract lifecycle

### Business relationship

```text
Company → Client → Contract → Site → Guard assignment → Device assignment → Geofence
```

A company owns the commercial relationship, workforce, device fleet, and operational data. A contract links a client to service delivery. A site is an operational location under an eligible contract. Guard/device assignments and geofences are time-bounded records associated with a site.

### Lifecycle policy

| Stage | Required behaviour |
| --- | --- |
| New client | Create company-scoped client, ownership/audit records, and authorized contacts. |
| New contract | Validate client belongs to company; establish dates, status, services and responsible owners. |
| New site/onboarding | Create under valid contract; add address/operational settings; do not activate live tracking until staffing/device/geofence policy is ready. |
| Active contract | Permit new site staffing, tracking sessions, geofence activation, and operational data according to permissions. |
| Expiring contract | Raise forecast/escalation events; restrict creation of future work after policy cutoff while retaining current permitted operations. |
| Renewal | Create a renewal/version or linked contract period, preserving predecessor history; revalidate site/assignment continuity. |
| Termination/expiry | Close or suspend future assignments/sessions at the effective boundary; retain all historical contracts, sites, telemetry, audit logs and reports. |
| Site suspension | Stop new sessions/assignments unless a specific emergency policy permits them; retain history and show clear operational state. |
| Site closure | End operational availability and active geofence use; never delete the site or its historical relationships. |

Operational access after contract expiry becomes **read-only history by default** for authorized company users; live operational write actions are blocked except controlled closure/audit corrections. Client-portal access is separately governed by contract terms and retention policy.

## 7. Security model

### Authorization chain

```text
Authenticated user/device
  → verified session and identity
  → trusted company context (company_id)
  → role(s) and permission(s)
  → permitted scope (company / branch / site / own assignment)
  → resource loaded with company_id
  → relationship/ownership validation
  → requested action
  → audit event
```

### Enforcement rules

1. Authentication verifies signed token, issuer/audience/time claims, session status/revocation, and account/device status.
2. `company_id` comes from trusted server-side authentication context, not a header, form value, GPS payload, or URL parameter.
3. Repositories never load a company-owned resource by ID alone in request-facing workflows; queries include company scope.
4. Services validate relationships: e.g., a device session’s device, guard, assignment and site must all belong to the same company and be valid at the sample time.
5. Permissions grant an action; scope decides which company/site/assignment instances are eligible. A broad role cannot bypass company isolation without an explicit platform-administration policy.
6. Device endpoints additionally require authenticated device/session credentials, device lifecycle validation, and assignment/session ownership—not merely a human bearer token plus a device ID.
7. All sensitive reads (GPS history, location views, contract/site changes) are permission-and-scope checked and audited.

This prevents IDOR by scoping the resource lookup, cross-company access by deriving trusted company context, device hijacking by binding device credentials/assignment/session, and unauthorized site/contract/GPS actions by separately validating permission, resource scope, and parent relationship.

## 8. Canonical API architecture

### Decision

The sole public API namespace is **`/api/v1`**. `/api/...` is not a second version and should be retired/redirected only through a future compatibility plan. API version is in the path; breaking changes create `/api/v2`, while additive compatible changes remain v1.

### Request pipeline

```text
HTTP request
  → authentication middleware
  → trusted company/tenant context
  → permission + scope policy
  → request validation
  → controller (transport only)
  → application/domain service
  → company-scoped repository/unit of work
  → persistent response + audit/event
```

- Controllers handle HTTP parsing/response only.
- Services own business/relationship/lifecycle rules.
- Repositories encapsulate company-scoped persistence queries.
- All errors use the secondary API's structured `success`, `message`, and field-level `errors` convention, formalized as the v1 contract.
- External identifiers should use UUIDs where a resource is exposed publicly; internal numeric IDs remain implementation details unless deliberately supported for compatibility.
- Device/mobile authentication must be a first-class API identity flow, not an extension of a browser-only guard user flow.

## 9. Testing architecture

| Layer | Purpose | Required proof |
| --- | --- | --- |
| Unit | Pure business/state algorithms | Deterministic state, validation, lifecycle and policy decisions; no database substitute claims. |
| Repository/database integration | Real queries against the canonical schema | Migrations apply; keys/types/indexes work; company scope cannot leak. |
| Service integration | Multiple real repositories and transactions | Parent ownership/lifecycle invariants and audit writes hold. |
| API | Full HTTP/middleware/controller/service path | Auth, validation, error contract, permissions and scope are enforced. |
| Authorization/tenant isolation | Cross-company adversarial cases | Every sensitive resource returns no data/forbidden when company, role, site or assignment differs. |
| Device tracking | Device/session/telemetry lifecycle | Valid ingestion, idempotency, lifecycle blocking, retention and derived presence calculation. |
| Geofence | Persistence/version/approval/evaluation | Geometry persists across requests; only approved active version is evaluated; history is immutable. |
| Contract lifecycle | Historical and access transitions | Expiry/renewal/suspension changes live permissions without deleting history. |

The database integration layer must run an isolated real MariaDB/MySQL schema produced by the canonical migration runner. Stub-only scenarios remain useful unit demonstrations but may not be reported as integration or persistence proof. Test classes must be namespaced/isolated so the complete suite is runnable.

## 10. Migration strategy

No migration is authorized by this decision. The following is the required future sequence.

### Phase 1 — Discover and protect

- Inventory every development, staging, and production database read-only: schema, row counts, migration history, constraints, and active application entry point.
- Snapshot/back up data and classify it as disposable development data, reusable test data, or production data.
- Freeze conflicting schema changes and create a mapping from current tables/columns to target entities.

### Phase 2 — Establish migration control

- Select/repair one migration runner around ordered canonical `database/schema` changes.
- Add schema version/checksum tracking, dry-run validation, and rollback/restore procedures.
- Build an isolated test database from zero and prove the baseline is reproducible.

### Phase 3 — Reconcile core identity and company scope

- Reconcile company/user/role/permission/client/contract/site/employee schemas to the target naming rules.
- Resolve ID/key types and parent relationships before any tracking data conversion.
- Migrate data through explicit mapping, reconciliation counts, and rollback checkpoints.

### Phase 4 — Introduce durable tracking and geofencing

- Add canonical `company_id` device, assignment, session, telemetry, tracking-policy, geofence/geometry, and derived-event tables.
- Map any existing `tenant_id` tracking data only after proving its source company relationships; quarantine unknown/ambiguous records rather than guessing.
- Preserve source timestamps, event provenance, and audit linkage.

### Phase 5 — Cut over application behavior

- Introduce canonical repositories/services/API endpoints behind tested compatibility boundaries.
- Run side-by-side read validation where feasible, then switch writes in a controlled release.
- Retire duplicate routes/runtime only after observability, rollback readiness, and data reconciliation sign-off.

### Phase 6 — Retention and operational hardening

- Define telemetry/GPS retention, archival, privacy access, alert/audit retention, indexing, performance monitoring, and recovery exercises.

## 11. Development roadmap

```text
Architecture decision
  → Database/migration source of truth
  → Identity, tenant context and authorization
  → Durable persistence + database test infrastructure
  → Existing feature stabilization
  → Device tracking and presence
  → Geofence persistence and site compliance
  → Guard activity intelligence
  → Supervisor dashboard
  → Mobile application
  → AI assistance
  → Advanced reporting
```

| Phase | Outcome | Dependency |
| --- | --- | --- |
| 0. Approve this decision | One architecture direction | Foundation audit |
| 1. Environment/schema evidence | Known active runtime/data risk | Phase 0 |
| 2. Canonical schema/migration baseline | Reproducible company-scoped database | Phase 1 |
| 3. Identity and tenant security | Trustworthy API authorization chain | Phase 2 |
| 4. Test infrastructure | Real database/API evidence | Phases 2–3 |
| 5. Stabilize existing CRUD | Clients/contracts/sites/workforce coherent in canonical API | Phases 2–4 |
| 6. Device accountability/tracking | Durable device/session/heartbeat/location flows | Phases 3–5 |
| 7. Geofence/site compliance | Approved persistent boundaries and derived events | Phase 6 |
| 8. Guard activity intelligence | Evidence-based stationary/movement/abandonment signals | Phases 6–7 |
| 9. Supervisor dashboard | Reliable operational visibility and alerts | Phase 8 |
| 10. Mobile application | Secure field client consuming canonical API | Phases 3, 6–9 |
| 11. AI | Assistive analysis over trustworthy, governed data | Phases 5–10 |
| 12. Advanced reporting | Auditable client/company reporting | Phases 5–11 |

## 12. Final decision table

| Decision | Recommendation | Reason | Implementation phase |
| --- | --- | --- | --- |
| Canonical application | `apps/api/` (`App\\`) | Best current security/middleware/schema alignment | 0–5 |
| Root SAF application | Preserve, freeze new features, selectively migrate behavior, then deprecate after parity | Valuable domain work but unsafe as parallel runtime | 1–5 |
| Canonical database schema | `database/schema/` plus one ordered runner | Aligns with canonical API and `company_id` model | 2 |
| Company vs tenant | Physical `company_id`; company is the tenant boundary | Clear business terminology; removes duplicate key systems | 2–3 |
| Canonical API | `/api/v1` | Existing accepted API-first direction and stable versioning | 3–5 |
| Authentication | Central JWT access tokens with verified server-side session/refresh lifecycle | Consistent revocation and future mobile/SSO support | 3 |
| Authorization | Permission + trusted company context + resource/relationship scope | Prevents IDOR and cross-company leakage | 3–4 |
| Device ownership | Company asset + time-bounded guard/device assignment + authenticated session | Supports accountability without assuming GPS proves a guard action | 6 |
| GPS architecture | Durable telemetry, derived state/events and policy-controlled sampling | Separates location, presence, GPS health and activity | 6–8 |
| Geofence architecture | Persistent company/site-scoped versioned and approved geometry | Supports durable historical compliance evidence | 7 |
| Contract lifecycle | Status-driven access changes; no historical deletion | Preserves operational/audit evidence after expiry | 5 |
| Testing strategy | Real-schema DB/API integration is required; stubs are unit-only | Proves persistence, isolation and authorization | 4 onward |
| Migration strategy | Discover → reproducible runner → core reconciliation → tracking/geofence → controlled cutover | Limits data-loss and compatibility risk | 1–6 |

## Approval gate

No implementation work should proceed until the following are approved:

1. `apps/api/` as the canonical backend.
2. `database/schema/` and `company_id` as the canonical persistence convention.
3. The one-runtime / `/api/v1` API direction.
4. The staged migration and testing-first stabilization sequence.
