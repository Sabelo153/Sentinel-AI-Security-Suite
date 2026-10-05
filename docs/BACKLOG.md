# Sentinel Historical Development Backlog

> Items referring to the removed empty `apps/api` scaffold describe an abandoned consolidation direction. Current work targets the root `Sentinel\\` runtime. See [`EMPTY_FILE_AUDIT.md`](EMPTY_FILE_AUDIT.md).

## Backlog Structure
Work is managed as:
- Epic
- Feature
- Story
- Task

## Current Code Anchors
The first backlog is tied to the code that already exists:

- Root framework bootstrap: `public/index.php`, `bootstrap/app.php`, `app/Core/*`, `routes/*`
- CLI and tooling: `sentinel`, `database/migrations/run_migrations.php`, `database/seed/run_seeds.php`
- Existing API surface: `apps/api/bootstrap/app.php`, `apps/api/app/Controllers/V1/*`, `apps/api/app/Services/*`, `apps/api/app/Repositories/*`
- Current schema foundation: `database/schema/002_companies.sql` through `database/schema/027_attendance_add_shift_assignment.sql`

## Architecture Control
- Capability catalog source of truth: `config/capabilities.php`
- Permission catalog source of truth: `config/permissions.php`
- Enterprise architecture baseline: `docs/architecture/ESOP_ENTERPRISE_MODEL.md`
- Rule: new features must be defined as capabilities and mapped to permissions before UI/API expansion
- Rule: no new domain expansion while Operations MVP is in progress
- Rule: Platform Foundation v1 is frozen (ADR-0015); no new platform engine without approved exception ADR
- Rule: no application owns core business logic; applications compose shared services and business objects
- Rule: no Finance API/service implementation until Operations MVP and HR Platform gates are complete
- Required artifact before Finance coding: `docs/database/MASTER_DATA_MODEL.md`
- Required artifact before major module implementation: `docs/database/ENTITY_CATALOG.md`
- Required baseline before major module expansion: seven approved enterprise constitution artifacts (entity, relationships, permissions, workflows, events, API contracts, UI navigation)
- Required merge gate for new modules: `docs/architecture/PLATFORM_CONFORMANCE_CHECKLIST.md`

## Milestone 1 - Foundation

### Epic 1 - Foundation

#### Feature: SAF framework runtime
- Story: As a developer, I need a stable request lifecycle so every feature is built on one predictable framework.
- Status: In progress
- Current anchors:
- `public/index.php`
- `bootstrap/app.php`
- `app/Core/Application.php`
- `app/Core/Router.php`
- `app/Core/Container.php`
- Tasks:
- Verify GET, POST, PUT, DELETE route support in root router
- Add middleware pipeline classes for CORS, auth, and rate limiting
- Add request ID generation and propagation into logs
- Add standardized error response envelope with machine-readable codes

#### Feature: Configuration and logging
- Story: As a developer, I need consistent environment-driven config and structured logs so deployments stay predictable.
- Status: In progress
- Current anchors:
- `.env.example`
- `config/*.php`
- `app/Core/Logger.php`
- Tasks:
- Finalize version key propagation into runtime config
- Standardize log context fields: request_id, user_id, company_id, module
- Add health-ready storage and queue status reporting hooks

#### Feature: Migration and seed system
- Story: As a developer, I need repeatable migrations and seeds so environments can be reproduced safely.
- Status: In progress
- Current anchors:
- `database/migrations/run_migrations.php`
- `database/seed/run_seeds.php`
- `database/migrations/migration_plan.php`
- Tasks:
- Run real migration flow against local database after credential confirmation
- Document verified seed credentials for local testing
- Add rollback strategy beyond scaffold status

### Epic 2 - Identity & Multi-Tenant Platform

#### Feature: Company structure
- Story: As an administrator, I need companies, branches, sites, and departments so operations can be organized correctly.
- Status: Partially implemented
- Current anchors:
- `database/schema/002_companies.sql`
- `database/schema/003_clients.sql`
- `database/schema/004_branches.sql`
- `database/schema/005_sites.sql`
- `database/schema/006_departments.sql`
- `apps/api/app/Controllers/V1/CompanyController.php`
- `apps/api/app/Controllers/V1/ClientController.php`
- `apps/api/app/Controllers/V1/SiteController.php`
- Tasks:
- Confirm schema completeness for UUID, audit, and soft-delete strategy
- Verify create/list flows against real seeded data
- Align public API identifiers with UUID strategy in SPS
- Add branch and department endpoints
- Verify tenant isolation on every company-scoped record
- Add branch and department update/delete flows with tenant isolation tests
- Add tenant context ownership checks for branch_id and department_id request headers

#### Feature: Enterprise capability model
- Story: As a platform architect, I need one capability model so web, mobile, and desktop clients share the same business rules.
- Status: In progress
- Current anchors:
- `config/capabilities.php`
- `config/permissions.php`
- `docs/architecture/ESOP_ENTERPRISE_MODEL.md`
- Tasks:
- Add capability-to-permission validation in CI checks
- Add API endpoint for current user capabilities and menus
- Generate client navigation from capability and permission grants
- Add platform-specific UX contracts for web, mobile, and desktop

#### Feature: People and workforce identity
- Story: As an administrator, I need employees and users so guards and supervisors can exist in the system.
- Status: Partially implemented
- Current anchors:
- `database/schema/006_people.sql`
- `database/schema/007_employees.sql`
- `database/schema/008_users.sql`
- `apps/api/app/Services/UserService.php`
- `apps/api/app/Repositories/UserRepository.php`
- Tasks:
- Add employee endpoints at API level
- Verify employee-to-user creation workflow
- Add status transitions and validation rules
- Add employee profile fields for PSIRA and compliance data

#### Feature: Compliance
- Story: As an administrator, I need compliance records so expiry monitoring can warn before credentials lapse.
- Status: Not started
- Current anchors:
- `database/schema/007_employees.sql`
- `docs/architecture/DOMAIN_TO_DATABASE_MAPPING.md`
- Tasks:
- Define a compliance model for PSIRA, firearm, driver's licence, first aid, and training
- Add certificate upload and expiry tracking
- Surface upcoming expiries in the dashboard

#### Feature: Roles and permissions
- Story: As an administrator, I need RBAC so users have only the access they require.
- Status: Partially implemented
- Current anchors:
- `database/schema/009_roles.sql`
- `database/schema/010_permissions.sql`
- `database/schema/011_user_roles.sql`
- `database/schema/012_role_permissions.sql`
- `apps/api/app/Middleware/PermissionMiddleware.php`
- Tasks:
- Verify permission naming against SPS and SEH conventions
- Add role-permission administration endpoints
- Add protected route integration tests

### Epic 3 - Authentication

#### Feature: Login and JWT
- Story: As a user, I want to log in securely so I can access the platform.
- Status: Framework login exists; repository-backed verification needs completion at root and live credential validation in API
- Current anchors:
- `app/Controllers/AuthController.php`
- `apps/api/app/Controllers/V1/AuthController.php`
- `apps/api/app/Services/AuthService.php`
- `apps/api/app/Core/Security/JwtService.php`
- Tasks:
- Complete repository-backed authentication as the default production path
- Track failed login attempts and account lockout
- Standardize auth error codes like `AUTH_001`
- Confirm session history persistence strategy

#### Feature: Logout and session history
- Story: As a user, I want sessions to be traceable and terminable.
- Status: Not started
- Current anchors:
- `database/migrations/001_identity_and_access.sql`
- `database/schema/020_login_history.sql`
- Tasks:
- Add logout endpoint
- Add session persistence table usage
- Add login history records on success and failure

#### Feature: Password recovery and account lockout
- Story: As a user, I need password recovery and account protection so access stays secure.
- Status: Not started
- Tasks:
- Add refresh token handling
- Add password reset flow
- Add password change flow
- Add failed-login lockout policy

## Next MVP Slice

### Epic 4 - Operations MVP (Current Product Focus)

Execution model reference:
- `docs/architecture/SENTINEL_ESOP_ROADMAP_v1.0.md`
- Releases execute in this order: 0.9 platform foundation, 1.0 platform kernel, 1.1 guard operations, 1.2 supervisor workspace, 1.3 control room, 1.4 operations management, 1.5 executive workspace, 1.6 client portal, 2.0 operational intelligence.

#### Feature: Platform foundation standards
- Story: As a platform team, we need a single platform language so all later releases use consistent object, event, API, AI, GPS, and digital twin contracts.
- Status: Planned (Release 0.9)
- Current anchors:
- `docs/architecture/SENTINEL_ESOP_ROADMAP_v1.0.md`
- `docs/ARCHITECTURE_GOVERNANCE.md`
- Tasks:
- Finalize Universal Object Dictionary
- Finalize Event Dictionary naming standards
- Finalize Capability Catalog and capability-to-permission mappings
- Finalize API standards and nested resource conventions
- Finalize database naming standards
- Finalize permission naming standards
- Finalize AI prompt library standards and versioning model
- Finalize GPS telemetry and geofence event standards
- Finalize notification standards (channels, priority, escalation)
- Finalize reporting standards (datasets, metrics, exports)
- Finalize KPI dictionary definitions and ownership
- Finalize operational metrics catalog and source mapping
- Finalize Digital Twin contract standards

#### Feature: Architecture freeze gate
- Story: As delivery leadership, we need a hard freeze gate after Release 0.9 so engineering focus shifts to code and shipping releases.
- Status: Planned (Release 0.9)
- Current anchors:
- `PROJECT_ROADMAP.md`
- `docs/ARCHITECTURE_GOVERNANCE.md`
- Tasks:
- Run Release 0.9 standards sign-off with architecture, engineering, and product leads
- Publish approved standards package and baseline version tag
- Enforce no-new-foundation rule for Version 1.0 execution unless blocker plus ADR exception
- Shift active planning cadence from architecture expansion to release execution readiness

#### Feature: Guard Operations complete module proof
- Story: As a platform owner, I need one complete Guard Operations module to validate Platform v1 before adding new infrastructure.
- Status: Planned (priority)
- Current anchors:
- `docs/adr/ADR-0015-Platform-Foundation-Freeze-v1.0.md`
- `docs/architecture/GUARD_OPERATIONS_IMPLEMENTATION_BLUEPRINT.md`
- `docs/architecture/PLATFORM_CONFORMANCE_CHECKLIST.md`
- Tasks:
- Deliver Guard Operations as vertical production slices (not scaffolding)
- Implement shared application services through those slices (task, timeline, document, state machine, reporting, search)
- Enforce module conformance checklist before merge
- Keep auth, RBAC, and tenant isolation matrices green per slice

#### Feature: Sentinel platform kernel for Guard Operations
- Story: As a product team, we need common services so Guard Operations and all later modules can reuse the same execution patterns.
- Status: Planned (Release 1.0)
- Current anchors:
- `docs/architecture/SENTINEL_ESOP_ROADMAP_v1.0.md`
- `docs/architecture/GUARD_OPERATIONS_IMPLEMENTATION_BLUEPRINT.md`
- Tasks:
- Implement GPS service foundations (live location, history, route checkpoints, geofence support)
- Define Task engine contract (assignment, priority, due date, escalation, evidence)
- Define Task data model baseline (`tasks`, `task_assignments`, `task_history`, `task_comments`, `task_attachments`, `task_templates`)
- Define Timeline engine contract (entity-agnostic event stream)
- Define Workflow engine contract for object lifecycle orchestration
- Define Approval engine contract for authority-based decisions and delegation support
- Define Document engine contract (entity type/id, category, version, retention, permissions, tags)
- Define State machine engine contract (metadata-driven transitions and approvals)
- Define Notification service contract for in-app/push/SMS/email channels
- Define Reporting engine dataset registration and export contracts
- Define Search engine indexing and permission-filtered query contracts
- Define Operational Intelligence engine contract (anomaly inputs, scoring, alerts, and workspace outputs)

#### Feature: Universal object model baseline
- Story: As a platform owner, I need one object behavior model so every module automatically gains core enterprise capabilities without duplicate implementations.
- Status: Planned (Release 1.0)
- Current anchors:
- `docs/architecture/SENTINEL_ESOP_ROADMAP_v1.0.md`
- `docs/architecture/PLATFORM_CONFORMANCE_CHECKLIST.md`
- Tasks:
- Define cross-object behavior contract for object identity/type, status/workflow, owner/assignments, authority context, GPS context, audit, timeline, attachments, comments, mentions, tags, tasks, notifications, AI summaries, search, permissions, activity history, relationships, and reporting participation
- Map the baseline to initial objects (Employee, Site, Vehicle, Client, Asset, Incident, Visitor, Equipment, Task, Document, Contract)
- Add conformance checks so new entities cannot bypass baseline object capabilities

#### Feature: Supervisor workspace delivery
- Story: As a supervisor, I need live operational visibility and approvals so I can manage guards and incidents in real time.
- Status: Planned (Release 1.2)
- Tasks:
- Deliver live guard monitoring and current site coverage
- Deliver patrol compliance and missed checkpoint queue
- Deliver incident triage queue and approval workflows
- Deliver supervisor task board with escalations

#### Feature: Operations workspace delivery
- Story: As an operations manager, I need cross-site operational intelligence so I can optimize service quality and risk.
- Status: Planned (Release 1.4)
- Tasks:
- Deliver company operations KPI dashboard
- Deliver workforce scheduling and attendance governance views
- Deliver cross-site patrol compliance analytics
- Deliver AI operational summary and recommended actions

#### Feature: Control room workspace delivery
- Story: As a control room operator, I need real-time operational orchestration so incidents and emergencies are prioritized and dispatched quickly.
- Status: Planned (Release 1.3)
- Tasks:
- Deliver live operations map for guards and vehicles
- Deliver incident queue triage and dispatch workflow
- Deliver alarm and panic monitoring views
- Deliver AI prioritization for alerts and active incidents

#### Feature: Executive workspace delivery
- Story: As executive leadership, we need strategic, financial, and risk intelligence experiences rather than operational workflow screens.
- Status: Planned (Release 1.5)
- Tasks:
- Deliver executive KPI dashboards (company performance, revenue, profitability, contract health)
- Deliver risk and SLA compliance views with trend lines
- Deliver workforce utilization and client satisfaction insights
- Deliver board-ready reporting outputs and AI strategic summaries

#### Feature: Client portal delivery
- Story: As a client, I need transparent service visibility so I can verify delivered security outcomes without manual follow-up.
- Status: Planned (Release 1.6)
- Tasks:
- Deliver live guard attendance and patrol verification views
- Deliver incident history with attachments and AI summaries
- Deliver monthly KPI and SLA reporting views
- Deliver client-facing service analytics dashboards

#### Delivery Order Rule
- Release sequencing is authoritative: 0.9 foundation -> 1.0 kernel -> 1.1 guard operations -> 1.2 supervisor -> 1.3 control room -> 1.4 operations -> 1.5 executive -> 1.6 client portal -> 2.0 operational intelligence.
- Feature slices must be implemented vertically through UBO plus shared platform services, not as isolated module infrastructure.

#### Feature: Attendance
- Story: As a guard, I want to clock in and out digitally so attendance is captured without paper.
- Current anchors:
- `database/schema/014_attendance.sql`
- `database/schema/026_shift_assignments.sql`
- `database/schema/027_attendance_add_shift_assignment.sql`
- Tasks:
- Build attendance repository and service
- Add `POST /api/v1/attendance/clock-in`
- Add `POST /api/v1/attendance/clock-out`
- Add state validation and audit logging
- Feed attendance counts into dashboard summary

#### Feature: Employee Management
- Story: As an operations company, I need complete employee records so all guard workflows are grounded in verified identities and compliance.
- Status: Scoped baseline implemented; expansion gated by entity catalog completion
- Tasks:
- Complete employee profile endpoints and lifecycle states
- Add document upload metadata model and retention rules
- Add employee compliance sub-entities (qualifications, licenses, certifications, medicals)
- Add employment history, promotion history, and disciplinary records
- Add role-ready assignments for guards, supervisors, and managers
- Add role-ready assignments for guards, supervisors, and managers

#### Feature: PSIRA and compliance verification
- Story: As compliance staff, I need expiry and credential controls so non-compliant officers are flagged before deployment.
- Status: Not started
- Tasks:
- Add PSIRA, firearm competency, driver license, and medical tracking
- Add compliance expiry alerts to dashboard
- Add compliance filters to assignment workflows

#### Feature: Patrol and occurrence operations
- Story: As supervisors, we need patrol and occurrence workflows that replace paper processes end to end.
- Status: Not started
- Tasks:
- Add patrol flow support for QR, NFC, and GPS checkpoints
- Add missed patrol alerting and escalation workflow
- Extend occurrence workflow with incident types, media evidence, and AI summary contracts

#### Feature: Visitor, fleet, and asset operations
- Story: As field and control-room teams, we need on-site movement and equipment traceability.
- Status: Not started
- Tasks:
- Add visitor flows for pre-registration, vehicle visitors, contractors, and deliveries
- Add fleet inspections, fuel, mileage, trips, maintenance, and renewals
- Add asset registers for firearms, radios, keys, uniforms, PPE, torches, consumables

#### Feature: Operations dashboard and AI report assistant
- Story: As executives and operations managers, we need actionable operational visibility and AI-ready reporting.
- Status: Not started
- Tasks:
- Add operations KPI widgets and risk heat signals
- Add AI-assisted report generation for incidents and patrol summaries
- Add PDF report outputs across operations modules

#### Feature: Occurrence book
- Story: As a supervisor, I want to record site events digitally so paper books can be replaced.
- Status: Not started
- Tasks:
- Create occurrence schema and migration
- Add repository, service, and controller
- Add create/list endpoints
- Add recent activity integration
- Prepare PDF export contract

### Epic 5 - Platform Stabilization Gates

#### Feature: Master database model approval
- Story: As architecture owners, we need one master model so every table has a stable domain home for the next decade.
- Status: Approved baseline (v1.0)
- Current anchors:
- `docs/database/MASTER_DATA_MODEL.md`
- Tasks:
- Validate domain ownership for all current tables
- Add naming, key, and tenant-scoping standards
- Enforce model review before schema additions
- Require module-level entity map signoff before creating new schema files

#### Feature: Enterprise constitution approval
- Story: As architecture owners, we need seven baseline artifacts so all platform applications scale from one constitutional model.
- Status: In progress
- Current anchors:
- `docs/database/ENTITY_CATALOG.md`
- `docs/database/ENTERPRISE_RELATIONSHIP_MODEL.md`
- `docs/architecture/PERMISSION_MATRIX.md`
- `docs/architecture/WORKFLOW_CATALOG.md`
- `docs/architecture/EVENT_CATALOG.md`
- `docs/architecture/API_CONTRACT_CATALOG.md`
- `docs/architecture/UI_NAVIGATION_MAP.md`
- Tasks:
- Approve enterprise entity catalog per application area
- Approve relationship model and canonical ERD boundaries
- Approve action, field, and scope permission matrix and role mappings
- Approve workflow catalog for operations, HR, finance, payroll, procurement, fleet, assets, CRM, and client portal
- Approve cross-domain event catalog for automation, reporting, audit, and integrations
- Approve API contract catalog for internal and external integration surfaces
- Approve UI navigation map across all platform applications
- Require signoff before expanding Employee, Site, Shift, Patrol, Incident, Vehicle, Equipment, Finance, and Cyber modules

#### Feature: Regression hardening
- Story: As engineering leads, we need repeatable release gates so rapid module delivery does not break core platform guarantees.
- Status: In progress
- Tasks:
- Keep auth matrix at 27/27
- Keep RBAC matrix at 11/11
- Keep tenant isolation matrix at 9/9
- Add module-level regression matrices as operations modules are introduced

### Epic 6 - ERP Migration and Integration (Deferred Build)

#### Feature: PROF1T integration baseline
- Story: As an implementation partner, I need Sentinel to integrate with PROF1T so customers can adopt Sentinel without immediate ERP replacement.
- Status: Not started
- Current anchors:
- `docs/MIGRATION_STRATEGY_PROF1T.md`
- `PROJECT_ROADMAP.md`
- Tasks:
- Define import contracts for companies, employees, clients, and sites
- Define export contracts for attendance, approved hours, payroll-ready data, and billing-ready data
- Define reconciliation report format between Sentinel and PROF1T
- Add integration audit events and failure handling standards

#### Feature: Payroll handoff pipeline
- Story: As a payroll officer, I need approved operational hours exported reliably to PROF1T payroll inputs.
- Status: Not started
- Tasks:
- Define payroll period locking and approval states
- Build export job for payroll-ready timesheet outputs
- Add validation rules for overtime, public holiday, and shift differentials
- Add checksum and reconciliation controls per export batch

#### Feature: Financial replacement track
- Story: As a product owner, I need module-based replacement sequencing so payroll, billing, and accounting can move from PROF1T to Sentinel safely.
- Status: Not started
- Tasks:
- Define minimum viable billing model tied to contracts and verified hours
- Define debtor and creditor domain boundaries
- Define general ledger event model for operational postings
- Add phased cutover criteria and rollback plans for each module

## Out of Scope for Version 1.0
- Messenger
- Sentinel OS
- Billing
- Visitor management
- Vehicle inspections
- Fire drills
- Advanced analytics
- AI chat
