# Sentinel Master Data Model

Status: Approved baseline for Operations MVP planning (required before further module expansion)
Version: 1.0
Last updated: 2026-08-05

## Purpose
Define the enterprise data blueprint so all current and future modules fit one stable model as Sentinel scales toward full PROF1T replacement.

## Hard Governance Rules
- No new domain expansion while Operations MVP is in progress.
- Every table must map to one primary bounded context owner.
- Cross-domain reads and writes must use domain contracts or events, not direct repository coupling.
- All business tables must be tenant-scoped by company_id unless explicitly global reference data.
- Role permissions must be combined with scope filters (company, branch, department, site, assignment).
- Finance implementation remains deferred until Operations and HR readiness gates pass.

## Canonical Platform Hierarchy

```text
Platform
├── Company
│   ├── Region
│   │   ├── Branch
│   │   │   └── Area
│   │   │       └── Site
│   ├── Department
│   ├── Cost Centre
│   ├── Business Unit
│   └── Operations Centre
├── People
│   ├── Employee
│   ├── Client Contact
│   ├── Visitor
│   ├── Contractor
│   └── Supplier Contact
├── Operations
│   ├── Site
│   ├── Shift
│   ├── Roster
│   ├── Patrol
│   ├── Incident
│   ├── Task
│   ├── Vehicle
│   ├── Equipment
│   ├── Asset
│   └── Control Room
├── HR
├── Payroll
├── Finance
├── Procurement
├── Inventory
├── Billing
├── CRM
├── Reporting
└── AI
```

## Bounded Context Ownership

### 1) Identity and Access
Purpose: Authentication, authorization, and security controls.

Core entities:
- users
- roles
- permissions
- user_roles
- role_permissions
- auth_sessions
- password_reset_tokens
- login_history

### 2) Company Structure
Purpose: Enterprise hierarchy and tenancy boundaries.

Core entities:
- organizations (planned)
- organizational_units (planned)
- companies
- company_settings
- regions (planned)
- branches
- areas (planned)
- departments
- business_units (planned)
- cost_centres (planned)
- positions (planned)
- authority_levels (planned)
- employee_assignments (planned)
- assignment_scopes (planned)
- approval_levels (planned)
- delegations (planned)
- acting_assignments (planned)

### 3) People and Workforce
Purpose: Master people records and operational employee lifecycle.

Core entities:
- people
- employees
- employee_profiles
- employee_contacts (planned)
- employee_documents (planned)
- employee_qualifications (planned)
- employee_licenses (planned)
- employee_training (planned)
- employee_certifications (planned)
- employee_medicals (planned)
- employee_employment_history (planned)
- employee_promotions (planned)
- employee_disciplinary (planned)
- employee_performance_reviews (planned)
- employee_leave_requests (planned)

### 4) CRM and Client Delivery
Purpose: Client contracts and service-delivery structure.

Core entities:
- clients
- client_contacts (planned)
- client_risk_profiles (planned)
- contracts
- contract_sla_terms (planned)
- sites
- site_contacts (planned)

### 5) Operations Core
Purpose: Day-to-day security operations execution.

Core entities:
- shift_templates (planned)
- shifts
- rosters (planned)
- roster_assignments (planned)
- attendance
- attendance_events (planned)
- patrol_routes
- patrol_checkpoints
- patrol_runs (planned)
- occurrence_entries (planned)
- incidents (planned)
- incident_actions (planned)
- incident_attachments (planned)
- tasks (planned)
- control_room_alerts (planned)

### 6) Visitor and Access Operations
Purpose: Visitor lifecycle and gate controls.

Core entities:
- visitors (planned)
- visitor_registrations (planned)
- visitor_vehicles (planned)
- visitor_hosts (planned)
- watchlists (planned)

### 7) Fleet and Vehicle Operations
Purpose: Vehicle readiness and compliance.

Core entities:
- vehicles (planned)
- vehicle_assignments (planned)
- vehicle_inspections (planned)
- vehicle_fuel_logs (planned)
- vehicle_service_records (planned)
- vehicle_damage_reports (planned)

### 8) Equipment and Asset Operations
Purpose: Controlled issue/return and traceability.

Core entities:
- equipment_items (planned)
- equipment_issue_logs (planned)
- equipment_returns (planned)
- assets (planned)
- asset_maintenance (planned)
- key_registers (planned)

### 9) Reporting and AI
Purpose: Operational reporting, analytics, and AI augmentation.

Core entities:
- reports (planned)
- report_templates (planned)
- report_exports (planned)
- ai_requests
- ai_jobs
- ai_responses
- ai_alerts (planned)

### 10) HR, Payroll, Finance, Procurement, Inventory, Billing (Deferred Build)
Purpose: Enterprise replacement path for PROF1T modules after operations maturity.

Core entities (current and planned):
- payroll_periods
- payroll_export_batches
- finance_chart_of_accounts
- finance_journals
- finance_journal_lines
- billing_invoices
- billing_invoice_lines
- suppliers (planned)
- purchase_requisitions (planned)
- purchase_orders (planned)
- inventory_items (planned)
- stock_movements (planned)

## Relationship Spine (Non-Negotiable)

1. company -> branch -> department
2. company -> people -> employees
3. company -> clients -> contracts -> sites
4. site -> shifts/rosters -> attendance
5. site -> patrol_routes -> patrol_checkpoints -> patrol_runs
6. site -> incidents -> incident_actions/attachments
7. employee -> equipment assignments -> issue/return logs
8. operations evidence -> billing evidence -> finance postings (deferred execution)

## Scope Model (Authorization + Data Filtering)

Required scope chain:
1. company
2. region
3. branch
4. area
5. site
6. shift
7. employee

Scope rules:
- Permission grants answer what a user can do.
- Scope grants answer which records a user can access.
- APIs must enforce both permission and scope.

## Table Design Standards
- Primary key: unsigned bigint surrogate key.
- External ID strategy: UUID at API boundary where required.
- Tenant key: company_id mandatory on business tables.
- Scope keys: branch_id, department_id, site_id, assignment keys where relevant.
- Audit fields: created_at, updated_at mandatory.
- Soft-delete/status: use workflow statuses and deleted_at where lifecycle recovery is required.
- Unique constraints: composite keys must include tenant dimension.
- JSON use: only for bounded flexible metadata, not core relational identity.

## Operations MVP Data Completion Gate
The following must be designed and mapped in this model before each module build starts:

1. Employee master record extensions (compliance, training, documents, history)
2. Site operational profile (SOP, emergency, patrol, risk, mapping)
3. Shift templates and roster structures
4. Attendance event model (GPS/QR/NFC/offline/override)
5. Patrol execution model (checkpoints, deviations, alerts)
6. Occurrence and incident evidence model
7. Equipment and vehicle assignment lifecycle
8. Control-room live state and alert model

## Finance Readiness Gate
Finance implementation may begin only when:
1. Operations MVP modules are implemented and stable.
2. HR module baseline is implemented.
3. Auth, RBAC, and tenant isolation matrices are green.
4. This master data model is version-approved for affected modules.
