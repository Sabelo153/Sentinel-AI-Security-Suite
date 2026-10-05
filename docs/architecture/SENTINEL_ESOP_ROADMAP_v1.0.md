# Sentinel ESOP Roadmap v1.0

Status: Draft v1.0
Last updated: 2026-08-07

ESOP = Enterprise Security Operations Platform.

## Mission
Transform BAC CPA into a technology-enabled physical security operations company by digitizing operations and applying AI to improve decision quality, accountability, service performance, and operating cost efficiency.

Core principle:
Digitize everything -> automate everything -> analyze everything -> improve everything.

AI augments guards, supervisors, and managers. It does not replace them.

## Direction
Shift primary focus from generic platform expansion to one complete production application that proves the platform.

Narrow scope to physical security operations execution. Defer non-operational enterprise expansion until core operations outcomes are proven in production.

## Runtime Core Model

```text
Identity
	-> Assignment
		-> Authority
			-> Business Objects
				-> Platform Services
					-> Capabilities
						-> Applications
```

Everything else (workspaces, dashboards, AI, navigation, notifications) is composed from this model.

## Platform Layers

Layer 1 - Sentinel Platform Kernel (build once)
- Identity, assignment, authority, UBO, platform registry, metadata engine, experience runtime
- Shared core services: task, timeline, workflow, approval, document, search, reporting, GPS, notification, AI, event bus, rules engine, state machine, audit

Layer 2 - Business Objects (build once)
- Object types are modeled once and inherit UBO behaviors by default.

Layer 3 - Applications (vertical slices)
- Applications compose objects and services into persona/workspace experiences.
- Applications should not duplicate infrastructure or core object logic.

Capability layer rule:
- Applications are composed from capabilities, not from direct module-specific infrastructure.
- Capabilities orchestrate business objects and platform services in reusable contracts.

## Platform Pillars
1. Digital operations
- Attendance, patrols, incidents, visitors, inspections, occurrence book, handovers, checklists, evidence, and documents become structured digital workflows.

2. GPS and workforce tracking
- Live guard and supervisor visibility, route adherence, response travel, site time verification, and service-proof telemetry.

3. AI operational intelligence
- Exception-first intelligence for guard behavior, supervisor activity, vehicle behavior, site risk, and operational business patterns.

4. Automation
- Event/rule-driven actions for alerts, escalation, incident/task creation, and resolution tracking.

5. Executive intelligence
- Live KPI and risk views for operational and financial decision support.

6. Client experience
- Client-facing visibility into attendance, patrol proof, incident reporting, and service analytics.

## Phase Model

### Phase 1 (Complete)
- Architecture baseline
- Runtime foundation
- Experience Engine
- Platform and capability registry
- Event bus
- Rules engine
- Metadata model
- Persona and workspace model

### Phase 2 (Current)
- Build Guard Operations as the first complete production application
- Validate platform reuse through real end-to-end operations workflows

### Phase 3
- HR
- Fleet
- Control Room
- Visitor
- Finance
- Client experience expansion

### Phase 4
- AI automation
- Advanced analytics
- Executive intelligence

## Product Release Model
Planning and delivery are release-driven. Sprints are internal execution units inside each release, not the primary product planning layer.

## Release 0.9 - Platform Foundation
Objective:
Define the language of the platform before kernel implementation.

Deliverables:
1. Universal Object Dictionary
- Define each business object once (for example: guard, employee, client, site, checkpoint, patrol, incident, vehicle, visitor, asset, equipment, inspection, task, shift, document).
- For each object define: description, owner, relationships, workflow, events, permissions, AI capabilities, GPS capabilities, reports, and APIs.

2. Event Dictionary
- Enforce one event naming convention using dot-separated, past-tense domain events.
- Example canonical events: guard.checked_in, guard.checked_out, guard.location_updated, guard.patrol_started, guard.patrol_completed, guard.inactivity_detected, guard.panic_triggered, incident.created, incident.assigned, incident.closed, vehicle.location_updated, patrol.missed_checkpoint, inspection.completed.

3. API Standards
- Enforce consistent CRUD and nested resource patterns.
- Example patterns: GET /guards, GET /guards/{id}, POST /guards, PATCH /guards/{id}, DELETE /guards/{id}, GET /guards/{id}/patrols, GET /sites/{id}/guards, GET /incidents/{id}/attachments.

4. Capability Catalog
- Define reusable platform capabilities as the composition contract for applications.
- Map each capability to permissions, workflows, events, APIs, AI, notifications, and reporting outputs.

5. AI Prompt Library
- Centralize and version prompts outside application code.
- Example prompt keys: incident_summary_v1, patrol_analysis_v1, uniform_compliance_v1, executive_summary_v1, client_report_v1.

6. GPS Standards
- Standardize telemetry fields: location, accuracy, speed, heading, altitude, battery, timestamp, device_id, confidence.
- Standardize geofence events: entered_site, left_site, entered_checkpoint, route_deviation, idle_detected, sleep_suspected.

7. Digital Twin Standards
- Standardize twin contract fields: identity, current_status, assignment, current_location, health, last_activity, timeline, related_objects, AI_insights, risk_score, documents, history.

8. Database Naming Standards
- Enforce one naming convention for tables, columns, indexes, and constraints.

9. Permission Naming Standards
- Enforce one permission namespace and action pattern across capabilities.

10. Notification Standards
- Define channel, priority, retry, escalation, and acknowledgement contracts.

11. Reporting Standards
- Define dataset, metric, dimension, filter, export, and schedule contracts.

12. KPI Dictionary
- Define KPI names, formulas, owners, and refresh cadence.

13. Operational Metrics Catalog
- Define operational metric definitions and event/data sources for shared analytics.

## Shared Platform Services Required by Applications
These services are implemented as application-enabling services tied to Guard Operations delivery.

GPS is a first-class platform service, not a feature-level module utility.

Foundational services:
- Identity service
- GPS service
- Operational intelligence engine
- AI service
- Notification service
- Reporting service
- Document service
- Workflow/state service
- Task service
- Timeline service
- Audit service
- Media service
- Search service

1. Task Engine
- Assigned to, assigned by, priority, status, due date
- Escalation, related entity, checklist, evidence, completion

2. Timeline Engine
- Unified event history per entity across modules
- Employee, site, vehicle, incident, contract, and asset lifecycle timelines

3. Document Engine
- Entity type, entity ID, category, version
- Retention policy, permissions, tags

4. State Machine Engine
- Metadata-driven state transitions
- Approval, escalation, and audit-aware workflow states

5. Reporting Engine
- Dataset registration by module
- Shared charting, export, and BI connectors

6. Search Engine
- Universal platform lookup across major entities
- Permission-filtered results and relevance ranking

7. Operational Intelligence Engine
- Inputs: GPS telemetry, patrol completion, incidents, shift schedules, attendance, vehicle data, equipment status
- Outputs: sleeping guard alerts, patrol anomalies, route deviations, missed checkpoints, high-risk site warnings, supervisor exceptions, executive KPIs

## Universal Object Model (Enterprise Baseline)
All core objects should support one common behavior model so features are built once and reused everywhere.

Core objects include:
- Employee
- Site
- Vehicle
- Client
- Asset
- Incident
- Visitor
- Equipment
- Task
- Document
- Contract

Baseline behaviors:
- Object ID and object type
- Status and workflow state
- Owner and assignments
- Authority and approval context
- Location and GPS context
- Audit trail
- Timeline
- Attachments
- Comments and mentions
- Tags
- Tasks
- Notifications
- AI summaries
- Search indexing
- Permissions
- Activity history
- Relationships
- Reporting participation

New modules should primarily configure business objects and orchestrate platform services rather than implement bespoke infrastructure.

## AI Accountability Principle
AI assists human decision-making and workflow speed, but final accountability remains with assigned human authorities.

## First Complete Application: Guard Operations

### Guard Workspace
- Current shift and assigned site
- Tasks and AI briefing
- Notifications
- Clock in and quick actions

### Functional Scope
- Shift management (clock in/out, notes, GPS, attendance)
- Patrol execution (start patrol, QR/NFC/GPS checkpoints, missed checkpoint handling)
- Incident management (photo/video/voice evidence, witness capture, AI summary)
- Equipment inspection (radio, baton, handcuffs, torch, firearm, bodycam, vehicle)
- AI assistance (report drafting, grammar improvement, risk scoring, action recommendations, supervisor summary)

Phase 1 AI capabilities:
- AI patrol monitoring (missed patrols, shortcuts, delays, fake patrol patterns)
- AI sleeping guard detection (GPS inactivity + patrol expectation + supporting motion/camera signals)
- AI uniform compliance checks
- AI incident report drafting from voice and field notes
- AI client report generation (weekly/monthly/SLA/KPI)
- AI performance scoring (attendance, patrol completion, GPS compliance, incident quality, response time)
- AI route optimization (patrol, supervisor, and response routes)
- AI control room assistant prioritization

### Supervisor Workspace
- Sites, current guards, missed patrols, incidents, tasks, approvals, live map, AI alerts

### Operations Manager Workspace
- Company operations overview, attendance, patrol compliance, client issues, vehicle status, revenue, AI summary

### General Manager Workspace
- KPI and contract health, revenue and profit, client satisfaction, staffing and compliance

### CEO Workspace
- Executive dashboard, financial health, operational risk, growth, contracts, incidents, strategic AI summary

### Board Workspace
- Company performance, risk heatmap, financial trends, compliance, expansion readiness, board reports

## Mobile Delivery Principle
Deliver one mobile app package where the runtime composes each user experience by persona and workspace.

## Product Release Plan

### Capability Maturity Model
Use a staged model to avoid overbuilding AI features in early releases.

Level 1: Digitized
Level 2: Automated
Level 3: AI assisted
Level 4: Predictive
Level 5: Autonomous recommendations

Example progression:
- Patrols: L1 QR patrols -> L2 reminders -> L3 AI summaries -> L4 missed patrol prediction -> L5 patrol change recommendations
- Incidents: L1 digital reports -> L2 auto notifications -> L3 AI summaries -> L4 pattern detection -> L5 risk prediction

### Release 1.0 - Platform Kernel
Objective:
Digitize-ready enterprise kernel with reusable cross-application services.

Core platform:
- Identity engine
- Assignment engine
- Authority engine
- Organization engine
- Experience runtime
- Platform registry
- Metadata engine
- Feature flags
- Module registry

Universal Business Object:
- Object framework
- State machine engine
- Workflow engine
- Approval engine
- Timeline engine
- Task engine
- Document engine
- Notification engine
- Search engine
- Reporting engine
- Audit engine

Intelligence:
- GPS engine
- Operational intelligence engine
- Rules engine
- Event bus
- AI service
- Digital Twin engine foundations

Success criteria:
- No business application exists yet.
- Shared platform services are operational and tested.
- Kernel contracts are reusable by all future applications.

### Release 1.1 - Guard Operations
Modules:
- Guard management
- Patrol management
- QR/NFC patrol verification
- GPS tracking
- Shift management
- Incident reporting
- Emergency panic
- Voice-to-report
- Uniform verification (AI)
- Guard inactivity detection
- Patrol compliance

AI features:
- AI incident reports
- Patrol anomaly detection
- Sleep/inactivity alerts
- Patrol route suggestions
- AI shift summary

Success criteria:
- A guard can complete an entire shift using only Sentinel mobile.

### Release 1.2 - Supervisor Workspace
Features:
- Live guard map
- Exception dashboard
- Missed patrols
- Inspection scheduling
- Performance monitoring
- Digital site inspections
- Remote guard verification
- Assignment management

AI:
- AI supervisor assistant
- Suggested inspections
- Risk prioritization
- Guard performance insights

### Release 1.3 - Control Room
Features:
- Live incident queue
- GPS dispatch
- Vehicle tracking
- Alarm queue
- Radio log
- AI prioritization
- Response coordination
- Emergency command view

AI:
- AI control room assistant
- Incident prioritization
- Dispatch recommendations
- Response time optimization

### Release 1.4 - Operations Management
Features:
- Multi-site dashboard
- Area performance
- Contract performance
- Vehicle fleet
- Fuel monitoring
- Staff utilization
- SLA monitoring
- Operations planning

AI:
- Operational analytics
- Resource optimization
- Contract profitability
- Staffing recommendations

### Release 1.5 - Executive Workspace
Features:
- CEO dashboard
- General manager dashboard
- Board dashboard
- Company KPIs
- Profitability
- Client retention
- Compliance
- Growth metrics

AI:
- Executive briefings
- Monthly summaries
- Strategic risk analysis
- Business forecasting

### Release 1.6 - Client Portal
Features:
- Live guard status
- Patrol history
- Incident reports
- Visitor logs
- Attendance
- SLA reports
- AI summaries
- Live site dashboard

### Release 2.0 - Operational Intelligence
Objective:
Shift from digitization to prediction and optimization.

AI outcomes:
- Crime prediction
- Patrol optimization
- Vehicle optimization
- Workforce planning
- Predictive maintenance
- Contract risk scoring
- Performance forecasting

Digital twin expansion:
- Live digital twins for people, places, and assets
- State, health, location, assignment, and incident linkage

## Platform Terminology Standard
- Platform Kernel: core runtime and shared services
- Business Objects: core entities such as guards, sites, incidents, patrols
- Platform Services: workflow, GPS, AI, notifications, documents, search, reporting, and related shared engines
- Applications: Guard Operations, Supervisor, Control Room, Operations Management, Executive, Client Portal
- Workspaces: user experience contexts within an application
- Personas: current mode of work (for example, guard, supervisor, operations manager, CEO)
- Assignments: where and in what capacity a user is currently authorized to operate
- Authority: which decisions a user may make within assignment scope

## Exit Criteria for v1.0 Proof
- Guard Operations can run daily operations without paper fallback
- Supervisor and operations workspaces are complete and role-correct
- Shared services are reused, not re-implemented per feature
- Core matrices remain green (auth, RBAC, tenant isolation)
- Reporting datasets and search index are active for Guard Operations entities
