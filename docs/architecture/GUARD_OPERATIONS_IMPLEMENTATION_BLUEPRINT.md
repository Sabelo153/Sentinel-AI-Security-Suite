# Guard Operations Implementation Blueprint

Status: Draft v1.0
Last updated: 2026-08-07

## Objective
Deliver one complete, production-quality Guard Operations module as the architecture proof point for Platform v1.

## Scope
- Shift management
- Clock in and clock out
- Equipment inspection
- Patrol execution
- QR and NFC checkpoints
- Incident reporting
- Photo evidence and voice notes
- AI incident summary assistant
- Offline capture and sync
- Supervisor review and approval
- Operational analytics

## Done Means Done
A slice is complete only when all of the following are true:
- API, data model, permissions, and UI flows are complete
- Events are published and consumed
- Timeline and audit records are generated
- Task workflow is operational where assignments or approvals exist
- Document attachments are stored through shared document contracts
- Reporting dataset is exposed and queryable
- Tenant isolation, auth matrix, and RBAC tests are green
- Mobile and offline behavior works for required field workflows

## Delivery Strategy
Implement Guard Operations as vertical slices, not horizontal scaffolding.

Release and slice order:
1. Release 1.0 platform kernel services (UBO, task, timeline, workflow, approval, document, search, reporting, notification, audit, GPS, event/rules, operational intelligence)
2. Release 1.1 guard workflow MVP (shift assignment/clock in-out, patrol checkpoints, incident capture, equipment inspection, AI report assistant)
3. Release 1.2 supervisor workspace (live monitoring, approvals, patrol compliance, incident queue)
4. Release 1.3 control room workspace (dispatch, alarm/panic monitoring, live operations map)
5. Release 1.4 operations management workspace (company operations KPIs, scheduling visibility, AI operational insight)

## Shared Services Pull-Through Rule
Only implement or expand shared services when a Guard Operations slice requires them in production scope.

Expected shared services from this module:
- Task engine for assignments and approvals
- Timeline engine for entity events
- Document engine for evidence and attachments
- State machine engine for workflow transitions
- Search indexing for universal lookup
- Reporting datasets for operational analytics

## Initial Entity Set
- GuardShift
- ShiftAssignment
- AttendanceRecord
- PatrolRoute
- PatrolCheckpoint
- PatrolRun
- Incident
- IncidentAttachment
- EquipmentInspection
- SupervisorDecision

## Event Baseline
- shift.assigned
- attendance.clocked_in
- attendance.clocked_out
- patrol.started
- patrol.checkpoint_completed
- patrol.missed_checkpoint
- incident.created
- incident.media_attached
- incident.submitted
- incident.approved
- incident.rejected

## Success Criteria
- Supervisors can run daily guard operations without paper fallback.
- Operations leadership can review risk and performance from one dashboard.
- Platform v1 proves reusable through real module execution rather than additional engine design.

## Workspace Model for Application Proof
- Guard workspace: shift, patrol, incident, equipment, and quick action flows
- Supervisor workspace: live field operations, approvals, and intervention queues
- Operations manager workspace: multi-site performance and workforce governance
- Executive and board views consume reporting outputs from the same Guard Operations datasets

## Mobile Model
One mobile application package is used across personas, with runtime composition controlled by persona and workspace context.
