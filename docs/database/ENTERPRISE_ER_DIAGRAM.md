# Sentinel Enterprise ER Diagram

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Provide the canonical enterprise relationship model for Sentinel as an ESOP platform so entity ownership, tenant boundaries, and lifecycle relationships stay consistent during expansion.

## ERD Design Principles
- Every business table is tenant-scoped by company_id unless explicitly global reference data.
- Parent/child relationships must be explicit and auditable.
- Cross-domain relationships must use domain contracts or events rather than hidden coupling.
- Soft delete and status history are preferred for operational and compliance-heavy entities.

## Core Relationship Spine

```text
Company
  └── Branch
        └── Department
              └── Employee
                    ├── Shift / Roster / Attendance
                    ├── Leave / Training / Compliance
                    ├── Equipment / Asset Assignment
                    └── Incident / Investigation

Company
  └── Client
        └── Contract
              └── Site
                    ├── Patrol Route / Checkpoint
                    ├── Visitor / Vehicle / Access Control
                    ├── Incident / Task / Work Order
                    └── Document / Risk Register

Company
  └── Payroll / Billing / Finance
        └── Journal / Ledger / Reconciliation

Company
  └── AI Services
        └── Recommendations / Report Outputs / Audit Trails
```

## Entity Relationship Groups

### Organization and People
- company 1:N branches
- company 1:N departments
- company 1:N employees
- branch 1:N departments
- department 1:N employee assignments
- employee 1:N documents, qualifications, training, leave, incidents

### Client and Delivery
- client 1:N contracts
- contract 1:N sites
- site 1:N patrol routes, checkpoints, visitors, vehicles, incidents
- site 1:N assets and equipment

### Operations Execution
- site 1:N shifts
- shift 1:N roster assignments
- shift 1:N attendance records
- patrol_route 1:N patrol_checkpoints
- incident 1:N investigations, tasks, attachments

### Financial and Administrative Backbone
- company 1:N cost_centres
- company 1:N invoices / payments / journals
- employee 1:N payroll outputs
- client 1:N billing evidence and invoices

### AI and Reporting
- company 1:N ai_jobs
- ai_job 1:N ai_outputs
- report_template 1:N report_exports
- report_export 1:N audit references

## Relationship Rules
- All operational entities must maintain a company_id foreign key.
- Branch, department, and site keys propagate where relevant.
- Employee and client assignments should be explicit and auditable.
- Attachments and documents should point to a parent business entity with type and owner metadata.
- Audit records should never be deleted and should preserve before/after values.

## ERD Approval Checklist
1. Core entity spine reviewed and approved
2. Tenant-scope propagation rules accepted
3. Critical cross-domain relationships documented
4. Lifecycle and archive behavior mapped
5. Integration and reporting touchpoints reviewed
