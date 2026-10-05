# Sentinel Enterprise Workflow Catalog

Status: Draft v1.0 (constitution artifact 4 of 7)
Last updated: 2026-08-06

## Purpose
Define the core business workflows across Sentinel applications so orchestration, approvals, and automation remain consistent.

## Workflow Standard
Each workflow must define:
- Trigger
- Stages
- Required approvals
- State transitions
- SLA or timeout rules
- Escalation path
- Completion outputs

## Core Workflows

### Hire Employee
1. Candidate approved
2. HR approval
3. Employee record created
4. Compliance profile initialized
5. Uniform and equipment issuance
6. Site and shift assignment
7. Training and medical verification
8. Deployment ready

### Client Onboarding
1. Lead converted to client
2. Quotation created
3. Commercial approval
4. Contract finalized
5. Billing profile initialized
6. Site setup and risk baseline
7. Service activation

### Incident Management
1. Incident detected or reported
2. Triage and severity classification
3. Supervisor assignment
4. Containment actions
5. Investigation initiated if required
6. Closure approval
7. Lessons and report publication

### Payroll Handoff
1. Attendance period closed
2. Exceptions reviewed
3. Supervisor approval
4. Payroll validation checks
5. Export batch creation
6. Reconciliation and audit lock

### Procurement Cycle
1. Requisition submitted
2. Approval routing
3. RFQ or supplier selection
4. Purchase order issued
5. Goods received
6. Invoice matching
7. Payment approval

## Approval Checklist (Artifact 4)
1. Operations workflows approved.
2. HR workflows approved.
3. Finance and payroll workflows approved.
4. Procurement and asset workflows approved.
5. SLA and escalation models approved.
6. Workflow-to-event mapping approved.

Approval state:
- Draft until all core workflows are mapped and reviewed.
- Approved v1.0 when architecture + operations leads sign off.
