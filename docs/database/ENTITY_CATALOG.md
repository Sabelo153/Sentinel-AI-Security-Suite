# Sentinel Enterprise Entity Catalog

Status: Draft v1.1 (constitution artifact 1 of 7)
Last updated: 2026-08-06

## Purpose
Define every platform business object before major feature expansion so Sentinel scales as a unified enterprise platform and eventual full PROF1T replacement.

## Usage Rule
- Before any major module implementation, the affected entity set must be defined here.
- Every schema change must reference entity ownership in this catalog.
- API design must follow lifecycle, permissions, scope, and audit rules in this catalog.

## Master Entity Backbone

```text
Platform
├── Company
├── Organizational Unit
├── Branch
├── Area
├── Department
├── Employee
├── Authority Level
├── Employee Assignment
├── Delegation
├── Acting Assignment
├── Approval Level
├── Client
├── Client Site
├── Contract
├── Position
├── Shift
├── Patrol Route
├── Patrol Checkpoint
├── Visitor
├── Vehicle
├── Equipment
├── Asset
├── Incident
├── Investigation
├── Task
├── Work Order
├── Document
├── Training
├── Qualification
├── Payroll
├── Leave
├── Billing
├── Accounting
├── Procurement
├── Inventory
├── AI
└── Reporting
```

## Global Lifecycle Standard
All business entities should support:
1. Create
2. Read
3. Update
4. Archive (preferred over destructive delete)
5. Restore (where operationally required)
6. Delete (restricted, policy-driven)

## Permission Standard
Each entity should support action-level permissions:
1. View
2. Create
3. Edit
4. Delete
5. Approve
6. Export
7. Print
8. AI Access

Each permission must be combined with scope:
1. Own
2. Department
3. Branch
4. Company
5. Global

## Scope Resolution Chain
Company -> Region -> Branch -> Area -> Department -> Site -> Shift -> Employee

## Entity Definitions

### Company
- Purpose: Tenant root and legal operating entity.
- Key fields: company_name, registration_number, vat_number, status.
- Child entities: branches, departments, business units, settings.
- Audit: all setting and status changes mandatory.
- Reporting: compliance, operational KPI rollups.
- Integrations: government/regulatory systems, accounting suites.

### Branch
- Purpose: Regional operating structure.
- Child entities: departments, sites, branch staff assignments.
- Scope rule: branch managers limited to assigned branch records.

### Department
- Purpose: Functional grouping for people, assets, and workflow controls.
- Child entities: department assignments, shift templates, approvals.

### Employee
- Purpose: Master workforce identity and employment record.
- Child entities:
  - Employee Profile
  - Employment History
  - Promotion History
  - Transfers
  - Site Assignments
  - Department Assignments
  - Shift Assignments
  - Leave
  - Medical
  - Training
  - Qualifications
  - PSIRA
  - Firearm
  - Driver Licenses
  - Uniforms
  - Equipment Issued
  - Warnings
  - Investigations
  - Performance Reviews
  - Payroll
  - Documents
  - Photos
  - Emergency Contacts
  - Bank Details
  - Next of Kin
  - Audit History
- Permissions: field-level partitions required (personal, bank, salary, documents, medical, compliance).
- Offline: essential read and update for guard and supervisor apps.
- AI: resignation risk, training recommendation, compliance expiry detection.

### Client
- Purpose: Customer organization receiving security services.
- Child entities:
  - Contacts
  - Contracts
  - Invoices
  - Branches
  - Sites
  - Risk Assessments
  - Keys
  - Alarm Codes
  - SOPs
  - Emergency Contacts
  - Documents
  - Equipment Installed
  - Service History
  - Visits
  - Inspections
  - Assets
  - Vehicles Allowed
  - Visitors Allowed
  - Payments
  - Communication History
- Scope: client users restricted to their contract/site data.

### Client Site
- Purpose: Operational execution context.
- Child entities:
  - Maps
  - Buildings
  - Floors
  - Zones
  - Checkpoints
  - Patrol Routes
  - Incident History
  - Risk Register
  - Threat Register
  - Emergency Procedures
  - Key Holders
  - Contacts
  - Fire Equipment
  - Vehicles
  - Parking
  - Assets
  - Visitors
  - Contract Staff
  - Photos
  - CCTV Cameras
  - IoT Sensors
  - AI Cameras
  - Drone Coverage
  - Uniform Requirements
  - Required Qualifications
  - Site Instructions
  - Post Orders
  - SOPs
  - Attendance
  - Weather Alerts
  - Power Status
  - Generator
  - UPS
  - Network Devices
- AI: anomaly and risk pattern detection by zone and route.

### Contract
- Purpose: Commercial service agreement, rates, and SLA terms.
- Child entities: contract terms, SLA obligations, billing rules, amendments.

### Position
- Purpose: Job design and grade controls.
- Child entities: required qualifications, compensation bands, assignment rules.

### Shift
- Purpose: Time-bound operational staffing unit.
- Child entities: templates, roster assignments, attendance links, overtime flags.

### Patrol Route
- Purpose: Planned patrol path and compliance expectation.
- Child entities: checkpoints, route rules, expected durations, geofence tolerances.

### Patrol Checkpoint
- Purpose: Verifiable checkpoint event in patrol execution.
- Child entities: checkpoint scans, lateness/deviation events, exception records.

### Visitor
- Purpose: On-site person access control and movement tracking.
- Child entities: pre-registrations, host approvals, identity scans, vehicle links.

### Vehicle
- Purpose: Fleet and operational transport management.
- Child entities:
  - Driver
  - Fuel
  - Mileage
  - Maintenance
  - Insurance
  - Accidents
  - GPS
  - Trips
  - Service History
  - Tyres
  - Licenses
  - Permits
  - Photos
  - Documents
- AI: service prediction, route efficiency, anomaly detection.

### Equipment
- Purpose: Controlled issue and accountability of operational equipment.
- Child entities: serial numbers, issue/return logs, condition checks, maintenance records.

### Asset
- Purpose: Long-lived owned resources with lifecycle and valuation context.
- Child entities: assignments, maintenance, depreciation, insurance, disposals.

### Incident
- Purpose: Formal record of operational events and disruptions.
- Child entities: classifications, severity, attachments, corrective actions, escalation logs.

### Investigation
- Purpose: Structured follow-up process for incidents, discipline, and root cause.
- Child entities: findings, interviews, evidence, outcomes, recommendations.

### Task
- Purpose: Action tracking unit for operations and compliance execution.
- Child entities: assignees, due dates, status transitions, completion evidence.

### Work Order
- Purpose: Structured operational or maintenance execution request.
- Child entities: approvals, materials, labor entries, closure signoff.

### Document
- Purpose: Shared document governance across all business entities.
- Child entities: versions, categories, retention policy, access controls.

### Training
- Purpose: Capability development and compliance readiness tracking.
- Child entities: schedules, attendance, outcomes, expiry/renewal reminders.

### Qualification
- Purpose: Formal competency and role eligibility criteria.
- Child entities: issuing authority, issue/expiry dates, verification status.

### Payroll
- Purpose: Time-to-payroll transformation and compliance output.
- Child entities: periods, adjustments, deductions, export batches.

### Leave
- Purpose: Planned and unplanned absence lifecycle.
- Child entities: requests, approvals, balances, calendar conflicts.

### Billing
- Purpose: Revenue realization from service delivery evidence.
- Child entities: invoices, lines, statements, receipts, age analysis.

### Accounting
- Purpose: Financial control, compliance, and reporting backbone.
- Child entities:
  - Chart of Accounts
  - General Ledger
  - Journals
  - Receipts
  - Payments
  - Debtors
  - Creditors
  - Bank
  - Cashbook
  - Budgets
  - Cost Centres
  - VAT/Tax
  - Financial Statements

### Procurement
- Purpose: Controlled purchasing workflow.
- Child entities: requisitions, RFQs, purchase orders, approvals, goods received.

### Inventory
- Purpose: Stock and consumable control.
- Child entities: warehouses, stock lots, issues, returns, transfers, minimum levels.

### AI
- Purpose: Shared intelligence services across domains.
- Child entities:
  - Predictions
  - Scheduling Optimizer
  - Incident Classification
  - Image Recognition
  - Facial Recognition
  - License Plate Recognition
  - Guard Behaviour Signals
  - Attendance Insights
  - Risk Scores
  - Fraud Detection
  - Maintenance Prediction
  - Demand Forecasting
  - Financial Forecasting
  - Payroll Validation
  - Invoice Validation
  - Chat Assistant
  - Report Generation
  - Knowledge Base
  - Recommendations

### Reporting
- Purpose: Operational, HR, financial, and compliance decision outputs.
- Child entities: templates, schedules, exports, subscriptions, KPI snapshots.

## Audit Requirements (All Entities)
- Create/update/archive/delete event logging.
- Before/after value capture for critical fields.
- Actor identity, scope context, timestamp, source channel.
- Immutable audit records for compliance-sensitive domains.

## Reporting Requirements (All Entities)
- Operational summary views.
- Compliance exception views.
- Trend and forecast-compatible metrics.
- Exportable outputs (CSV, PDF, API).

## Mobile and Offline Requirements
- Define per-entity offline read/write capability.
- Conflict resolution policy for delayed sync.
- Device trust and audit linkage for offline edits.

## Integration Requirements
- PROF1T coexistence and phased replacement interfaces.
- Payroll/accounting exports with reconciliation controls.
- Government/regulatory feeds where required (tax, compliance, licensing).
- IoT/CCTV/device integrations for site and control-room domains.

## Implementation Gate
Before coding a major module:
1. Entity definition completed in this catalog.
2. Child entity map and lifecycle approved.
3. Permission matrix (actions + field-level + scope-level) defined.
4. Audit and reporting obligations defined.
5. Mobile/offline and integration behavior defined.

## Approval Checklist (Artifact 1)
For each platform application area (Operations, HR, Finance, Payroll, Procurement, Fleet, Assets, AI, Cybersecurity, CRM, Client Portal, Mobile, Reporting, Administration, API Platform):
1. Master entities listed and named consistently.
2. Child entity hierarchy completed.
3. Lifecycle transitions documented.
4. Permission-sensitive fields identified.
5. Scope chain implications identified.
6. Integration and event touchpoints identified.

Approval state:
- Draft until all application areas satisfy checklist.
- Approved v1.0 when signed off by architecture + engineering leads.