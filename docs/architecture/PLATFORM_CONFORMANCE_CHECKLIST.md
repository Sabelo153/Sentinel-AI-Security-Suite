# Platform Conformance Checklist

Status: Draft v1.0
Last updated: 2026-08-07

## Purpose
Ensure every new module conforms to Sentinel platform standards and reuses shared services consistently.

## Usage Rule
This checklist must be completed and approved before a module is merged beyond feature-branch scope.

## Required Yes/No Controls
1. Module registers in Module Manifest and platform metadata registry.
2. Capabilities are defined in the capability catalog and mapped to permissions.
3. Permission scopes are defined (tenant, company, site, team, self as applicable).
4. Domain events are published for business-significant state changes.
5. Relevant shared events are consumed (notifications, analytics, timeline, integrations).
6. Timeline integration is implemented for key entity actions.
7. Task integration is implemented for assignable and approvable work items.
8. Document integration is implemented for attachments and evidence.
9. State transitions are metadata-driven (state machine/workflow), not hard-coded.
10. Audit logging exists for create, update, state transition, approval, and delete operations.
11. Reporting datasets are exposed through platform reporting contracts.
12. AI touchpoints are defined where meaningful and permission-bounded.
13. Persona and workspace composition is defined and validated.
14. API contracts are documented and versioned.
15. Tenant isolation tests exist for all company-scoped access paths.
16. Offline and sync behavior is defined when mobile execution is in scope.
17. Universal object model behaviors are implemented (audit, timeline, attachments, comments, mentions, tags, tasks, notifications, AI summary, search, permissions, activity history, relationships, reporting).
18. GPS/location behavior is integrated where operationally relevant (tracking, route validation, geofence checks, response timing).
19. AI actions remain advisory with explicit human authority approval points for accountable decisions.
20. Application layer does not duplicate core object logic; business rules execute through shared services and object contracts.
21. Every application feature maps to one or more registered capabilities (no orphan feature logic).
22. Every feature passes the business value gate (cost, service, revenue, or visibility/accountability impact).

## Required Artifacts
- Module manifest
- Capability and permission mappings
- Workflow/state definitions
- Event contract entries
- API contract entries
- Navigation and workspace definitions
- Reporting dataset contract

## Review Sign-Off
- Product owner sign-off
- Domain lead sign-off
- Architecture lead sign-off
- Security/compliance sign-off (for regulated workflows)

## Merge Gate Outcome
- PASS: all controls are yes with artifacts linked.
- CONDITIONAL PASS: approved exceptions with remediation dates.
- FAIL: merge blocked until required controls are satisfied.
