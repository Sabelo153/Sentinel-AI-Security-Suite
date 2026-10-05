# Sentinel Capability Registry

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define a capability registry so permissions, workflows, APIs, reports, AI tools, widgets, and screens all reference the same capability model rather than hard-coded module names.

## Capability Principle
A capability is a reusable permission and behavior unit.
Examples:
- employee.create
- employee.archive
- employee.assign_site
- employee.issue_uniform
- employee.record_training
- employee.issue_firearm
- employee.schedule_shift
- employee.view_payslip
- employee.approve_leave

## Capability Model
Each capability should define:
- capability_id
- code
- name
- description
- domain
- application
- persona
- workspace
- permission_type
- audit_required

## Capability Groups

### Employee Management
- employee.create
- employee.update
- employee.archive
- employee.assign_site
- employee.promote
- employee.issue_uniform
- employee.record_training
- employee.issue_firearm
- employee.schedule_shift
- employee.view_payslip
- employee.approve_leave

### Site Operations
- site.view
- site.update
- patrol.create
- patrol.approve
- incident.create
- incident.escalate
- visitor.checkin
- vehicle.assign

### Finance
- invoice.view
- invoice.approve
- payment.process
- gl.export
- budget.view
- debtor.manage
- creditor.manage

### AI and Automation
- ai.assistant.use
- ai.report.generate
- ai.summary.create
- ai.recommendation.view

## Capability Use Across Platform Layers
Capabilities should be referenced by:
- screens
- APIs
- workflows
- reports
- AI tools
- notifications
- widgets
- feature flags

## Approval Checklist
- Capability model approved
- Core capability groups documented
- Cross-layer usage reviewed
