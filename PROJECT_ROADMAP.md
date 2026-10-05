# Sentinel AI Security Suite - Development Roadmap

> Version 0.1.0, Codename Foundation

---

## Project Overview

| Aspect | Details |
|--------|---------|
| Project Name | Sentinel AI Security Suite |
| Current Version | 0.3.0 Company Core (In Progress) |
| Delivery Model | Milestones + product releases |
| Development Duration | 12-16 weeks |
| Primary Markets | Security operations, facility management |
| Target Users | Security teams, company management |
| Technology Stack | PHP, MariaDB, Python (AI), Android/Kotlin |
| Architecture | SAF + layered architecture + DDD |

---

## Status

| Area | Status |
|------|--------|
| Architecture | Complete |
| Planning | Complete |
| Database Design | In Progress |
| Framework | In Progress |
| Backend | In Progress |
| Frontend | Not Started |
| Android | Not Started |
| AI | Not Started |

### Execution Directive (2026-08-05)
- Freeze architecture expansion and stop adding new domains.
- Prioritize platform stabilization and complete Operations MVP before Finance module build-out.
- Finance remains design-contract only until Operations and HR baselines are stable.

### Enterprise Platform Directive (2026-08-06)
- Design Sentinel as one enterprise platform with many applications, not isolated modules.
- Enforce one shared data model, authentication model, permission engine, audit system, AI engine, notification engine, workflow engine, reporting engine, and API platform.
- Require enterprise constitution artifact approval before major module expansion.
- ESOP naming standard: Enterprise Security Operations Platform.

### Platform Foundation Freeze Directive (2026-08-07)
- Freeze Platform v1 foundation (ADR-0015) and stop adding new engines by default.
- Add new platform engines only when a production Guard Operations slice proves a concrete delivery blocker.
- Prove architecture through one complete module, not broad partial scaffolding.
- Lock ESOP Architecture Baseline v1.0 and avoid creating new architecture documents during execution unless an ADR-approved blocker exists.
- Focus product scope on physical security operations and remove cybersecurity expansion from near-term roadmap scope.
- Implement vertically from the five-concept core: Identity -> Assignment -> Authority -> Business Objects -> Platform Services.

---

## Milestones

1. v0.2.0 - Authentication (Complete)
2. v0.2.1 - RBAC (Complete)
3. v0.3.0 - Company Core (In Progress)
4. v0.4.0 - Employee Core
5. v0.4.1 - Compliance (PSIRA, licenses, training)
6. v0.5.0 - Clients, Contracts, and Sites
7. v0.6.0 - Assets and Fleet
8. v0.7.0 - Attendance and Clock-In
9. v0.8.0 - Patrols and Occurrence Book
10. v0.9.0 - Incidents and Reporting
11. v1.0.0 - Commercial Release
12. v1.1+ - AI Operations
13. v2.0+ - Sentinel MDM
14. v3.0+ - Sentinel Secure OS

### Next Major Milestone
**Operations MVP** (stabilization release gate)

Architecture validation objective:
1. Deliver one complete Guard Operations module as Platform v1 proof.
2. Implement UBO-based shared operations services through Guard Operations delivery with GPS and event/rule automation as first-class capabilities.
3. Enforce capability-first composition and module merge gates using the Platform Conformance Checklist.

Execution architecture layers:
1. Sentinel Platform Kernel (build once)
2. Business Objects (build once)
3. Applications/Workspaces (vertical composition)

Build and stabilize in this order:
1. Release 0.9 Platform Foundation
2. Release 1.0 Platform Kernel
3. Release 1.1 Guard Operations
4. Release 1.2 Supervisor Workspace
5. Release 1.3 Control Room
6. Release 1.4 Operations Management
7. Release 1.5 Executive Workspace
8. Release 1.6 Client Portal
9. Release 2.0 Operational Intelligence

---

## Master Phases

### Phase A - Foundation
- SAF framework
- Database
- Authentication
- Companies
- Users

### Phase B - Identity & Multi-Tenant Platform
- Authentication
- JWT access and refresh tokens
- Password reset and password change
- Account lockout and login history
- RBAC and permission-driven access
- Company, branch, site, and department hierarchy
- Employee master records
- Client and site management
- Dashboard driven by live platform data
- Compliance tracking for PSIRA, licenses, and certifications

### Phase C - Operations Platform (Current Priority)
- Employees and profile lifecycle
- Compliance (PSIRA, firearm competency, licenses, medical, contracts)
- Clock in/out and attendance controls
- Shift and roster management
- Occurrence book and evidence workflows
- Patrol operations (QR, NFC, GPS)
- Visitor operations (people, vehicle, contractor, delivery)
- Fleet operations (inspections, fuel, mileage, maintenance)
- Asset operations (firearms, radios, uniforms, PPE, consumables)
- Control room operations (incident queue, dispatch, panic monitoring, live map)
- Operations dashboard and AI-ready reporting

### Phase D - HR Platform
- Leave and training
- Recruitment and onboarding workflows
- Performance and disciplinary workflows
- Contract lifecycle and renewals
- Attendance exception governance
- Payroll-hours readiness outputs

### Phase E - Finance Platform (Deferred Build)
- Payroll engine
- Billing and receivables
- Payables and procurement
- General ledger and accounting controls
- PROF1T selective replacement modules

### Phase F - Intelligence and Mobile
- AI report assistant and recommendations
- Android guard application
- Offline mode and sync
- Push notifications

### Phase G - Commercial Scale
- SaaS subscriptions
- White-label branding
- Multi-tenant hosting
- API marketplace
- Client portal and premium service analytics

---

## Product Releases

1. Release 0.9 Platform Foundation (object dictionary, capability catalog, event dictionary, API standards, database/permission naming standards, AI prompt standards, GPS standards, notification/reporting standards, KPI dictionary, operational metrics catalog)
2. Release 1.0 Platform Kernel (Identity, Assignment, Authority, Organization, UBO, Registry, Metadata Runtime, Task, Timeline, Workflow, Approval, Document, Notification, Search, Reporting, Audit, GPS, Operational Intelligence, Event Bus, Rules, State Machine, Digital Twin foundations)
3. Release 1.1 Guard Operations (mobile-first operations execution)
4. Release 1.2 Supervisor Workspace (exception-driven oversight)
5. Release 1.3 Control Room (dispatch and command experience)
6. Release 1.4 Operations Management (multi-site performance and planning)
7. Release 1.5 Executive Workspace (KPI and strategic intelligence)
8. Release 1.6 Client Portal (service transparency and SLA visibility)
9. Release 2.0 Operational Intelligence (predictive optimization and digital twin expansion)

Capability maturity model used across releases:
- Level 1: Digitized
- Level 2: Automated
- Level 3: AI assisted
- Level 4: Predictive
- Level 5: Autonomous recommendations

### Architecture Freeze Milestone (post Release 0.9)
- Trigger: Release 0.9 standards approved by architecture, engineering, and product leads.
- Result: core architecture is frozen for Version 1.0 execution.
- Rule: no new foundational concepts or architecture documents unless implementation exposes a verified blocker.
- Rule: new artifacts must support implementation (code, tests, migrations, runbooks, API contracts), not introduce parallel abstractions.

Reference: `docs/architecture/SENTINEL_ESOP_ROADMAP_v1.0.md`

### Release 1.0 Implementation Directive
- Release 0.9 is the architecture/standards phase. The next step is Release 1.0: build the Sentinel Platform Kernel.
- Do not create new architecture documents unless a real implementation blocker exists and is recorded as an ADR.
- Focus first on kernel contracts: Identity, Assignment, Authority, UBO, GPS, Event Bus, Rules, Audit, Task, Timeline, Workflow, Document, Search, Notification, Reporting, Operational Intelligence.
- Guard Operations, Supervisor, Control Room, and other applications must consume the kernel; they are not the starting point for Release 1.0.
- Every new artifact should be implementation-focused: migrations, services, repositories, tests, API contracts, or runbooks that enable the kernel.

### Stabilization Gates
- No new Finance endpoints until Operations MVP is complete and stable.
- Master database model must be approved before any Finance service implementation.
- Entity catalog must be approved for a module before building major new endpoints/tables for that module.
- The seven constitution artifacts must exist and be approved for the affected application area before major feature expansion:
- Enterprise Entity Catalog
- Enterprise Relationship Model (ERD)
- Permission Matrix (role + action + field + scope)
- Workflow Catalog
- Event Catalog
- API Contract Catalog
- UI Navigation Map
- Auth, RBAC, and tenant isolation matrices must remain green at each milestone freeze.

---

## Migration Strategy (PROF1T)

Sentinel follows a phased replacement model to reduce customer migration risk.

### Stage 1 - Parallel Integration
- Sentinel and PROF1T run side by side
- Sentinel manages operations and workforce workflows
- PROF1T remains financial source of truth
- Data exchange through controlled import/export contracts

### Stage 2 - Dual Operation
- Sentinel becomes operational source of truth
- PROF1T continues payroll and accounting execution
- Payroll and financial handoff files become standardized outputs from Sentinel

### Stage 3 - Selective Replacement
- Sentinel introduces payroll, billing, and financial modules progressively
- Customers can migrate module-by-module without full cutover

### Stage 4 - Full Replacement
- Sentinel provides complete ERP coverage for private security operations
- Historical migration tooling and reporting continuity are mandatory

Reference: `docs/MIGRATION_STRATEGY_PROF1T.md`

---

## Milestone 1 - Foundation

### Objective
Build the Sentinel Application Framework and the identity platform that every later module depends on.

### Deliverables
- SAF framework
- Database migration system
- Seeder system
- Authentication
- JWT security
- Refresh token support
- Password reset and password change
- Account lockout and login history
- Company management
- Branch, site, and department hierarchy
- User management
- Roles and permissions
- Audit logging
- Initial dashboard

### Operating Model
- Work is managed as Epic -> Feature -> Story -> Task.
- Every feature follows Requirements -> Design -> Database -> Backend -> API -> Frontend -> Android -> Testing -> Documentation -> Merge.
- Every step should produce code that can be committed.

### Immediate Engineering Sequence
1. SAF bootstrap and entrypoints
2. Router and middleware pipeline
3. Dependency injection container
4. Configuration and environment loading
5. PDO database manager
6. Migration runner
7. Foundation schema through users
8. Authentication and RBAC
9. Company, client, site, department, and dashboard product slice
