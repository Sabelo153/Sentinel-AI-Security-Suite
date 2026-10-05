# Sentinel Enterprise Permission Matrix

Status: Draft v1.0 (constitution artifact 3 of 7)
Last updated: 2026-08-06

## Purpose
Define enterprise-grade authorization using role + action + field + scope so all Sentinel applications enforce one security model.

## Permission Formula
Permission decision = role grant + action grant + field grant + scope grant + policy constraints.

## Permission Naming Standard
- Format: `object.action`
- Object token: lowercase singular noun (for example, `incident`, `patrol`, `guard`, `vehicle`).
- Action token: one of the standard action taxonomy items or approved extended actions.
- Examples: `incident.create`, `incident.read`, `incident.update`, `incident.delete`, `incident.approve`, `incident.export`.
- The same object/action meaning must never be represented with multiple permission names.

## Action Taxonomy
Standard actions per entity:
1. Create
2. Read
3. Update
4. Delete
5. Archive
6. Restore
7. Approve
8. Reject
9. Export
10. Import
11. Assign
12. Revoke

## Scope Taxonomy
1. Self
2. Team
3. Department
4. Branch
5. Company
6. Regional
7. Global

## Field Sensitivity Classes
1. Public operational fields
2. Internal restricted fields
3. Confidential personal fields
4. Financial confidential fields
5. Medical and compliance-sensitive fields

## Role Baseline
- Platform Super Admin: global, all actions, controlled break-glass access.
- Company Admin: company-wide governance and administration.
- Branch Manager: branch-limited operational and approval access.
- Department Supervisor: department operational access and limited approvals.
- HR Officer: employee lifecycle and HR-sensitive access.
- Finance Officer: billing, accounting, and financial exports.
- Control Room Operator: live operational and incident actions.
- Guard User: self and assigned-shift operations only.
- Client User: contracted site visibility only.

## Entity Permission Pattern (Example: Employee)
Actions:
- Create, Read, Update, Archive, Approve, Export
Field-sensitive actions:
- View Salary
- View Medical
- View Banking
- Edit PSIRA
- Edit Firearm
- View Disciplinary
- Approve Leave
- Approve Promotion
- Approve Termination
- Reset Password
- Upload Documents
- Delete Documents
Scopes:
- Self, Team, Department, Branch, Company, Regional, Global

## Enforcement Rules
- Default deny when any dimension is missing.
- Field-level restrictions override action-level grants.
- Scope is always evaluated against tenant chain.
- Every allow/deny decision must be auditable.

## Approval Checklist (Artifact 3)
1. Role inventory approved.
2. Action taxonomy approved.
3. Scope taxonomy approved.
4. Field sensitivity classes approved.
5. Entity-level permission templates approved.
6. Audit and exception handling approved.

Approval state:
- Draft until all core entities have matrix entries.
- Approved v1.0 when architecture + security leads sign off.
