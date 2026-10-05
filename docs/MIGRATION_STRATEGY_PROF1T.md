# PROF1T Migration Strategy

## Purpose
Define a low-risk path for Sentinel to replace PROF1T through phased adoption.

## Strategic Position
Sentinel is planned as an ERP platform for the private security industry, not only an operations tool.

## Core Principle
Do not force immediate ERP replacement. Deliver value while customers continue running existing financial systems.

## Phase 1 - Integrate with PROF1T
Sentinel and PROF1T run in parallel.

### PROF1T remains source of truth for:
- Payroll processing
- Debtors
- Creditors
- General ledger
- Financial reporting

### Sentinel provides:
- Employee lifecycle and assignments
- Attendance and approved hours
- Patrols, incidents, visitors, and operations evidence
- Asset and equipment traceability
- Operational dashboards and AI-assisted insights

### Integration outcomes:
- Import core master data from PROF1T
- Export payroll-ready and accounting-ready datasets from Sentinel

## Phase 2 - Dual Operation
Sentinel becomes the operations source of truth while PROF1T remains the financial engine.

### Example flow:
Attendance -> timesheets -> approved hours -> payroll export -> PROF1T payroll

### Success criteria:
- Operational workflows executed in Sentinel
- Financial posting still completed in PROF1T
- Reconciliation reports prove consistency across systems

## Phase 3 - Selective Replacement
Sentinel introduces financial modules incrementally.

### Target module families:
- Payroll
- Billing and contract revenue
- Debtors and creditors workflows
- Job costing and profitability

### Success criteria:
- Customer can choose Sentinel for specific modules while retaining PROF1T for others
- Side-by-side financial outputs are validated against historical baselines

## Phase 4 - Full ERP Replacement
Sentinel provides complete financial and operational coverage.

### Required capability baseline:
- Accounting foundation (GL, journals, tax, reconciliation)
- Payroll engine with compliance rules
- Procurement and inventory
- Contracts, billing, collections
- Migration tooling for historical data and reporting continuity

### Success criteria:
- Full customer migration can occur without parallel PROF1T dependency
- Auditability and regulatory reporting are preserved end to end

## Module Prioritization Model
Priority should be driven by replacement target scope:
- Payroll-first replacement
- Accounting-first replacement
- Billing and job-costing-first replacement
- Complete suite replacement

This decision changes data model depth, integration sequencing, and compliance requirements.

## Architecture Requirements
- Capability-first authorization model across all clients
- Tenant-aware data model on all business records
- Stable integration boundary with import/export contracts
- Immutable audit logs for operational and financial events
- Versioned APIs for third-party connectors

## Delivery Governance
Before moving to each migration phase:
- Define interface contracts
- Run reconciliation tests
- Run tenant isolation and authorization regressions
- Freeze with version tags and release notes