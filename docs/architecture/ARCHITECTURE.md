# Sentinel AI - Clean Architecture & Domain-Driven Design

**Version**: 1.0  
**Status**: Architectural Vision - Ready for Implementation  
**Date**: Current Session  
**Principle**: Enterprise-Grade, Scalable, Maintainable

---

## 🎯 Core Philosophy

We are building Sentinel as an **intelligent command centre**, not a data entry system.

**Key Principle**: 
> One business event (e.g., "Guard Clocks In") triggers a cascade of intelligent automations across the entire system without direct module-to-module coupling.

This is achieved through **Event-Driven Architecture** with a **Rule Engine** at the core.

---

## 🏗️ Architectural Pattern

### Clean Architecture with DDD

```
┌────────────────────────────────────────┐
│         Presentation Layer             │  ← Command Centre Dashboard
│    (REST API, Web UI, Mobile)          │
└────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────┐
│      Application Layer                 │  ← Use Cases, Commands, Queries
│  (Domain Services, Orchestration)      │
└────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────┐
│         Domain Layer                   │  ← 13 Business Domains (DDD)
│    (Business Logic, Entities,          │     Each with Clear Boundaries
│     Aggregates, Value Objects)         │
└────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────┐
│      Infrastructure Layer              │  ← Database, Cache, External APIs
│    (Repositories, ORM, Services)       │
└────────────────────────────────────────┘
```

### Event-Driven Communication

Instead of direct coupling:

```
❌ BEFORE (Tightly Coupled)
Guard Clock In
  └── updates attendance
       └── updates occurrence book
            └── triggers notification
                 └── updates dashboard
```

```
✅ AFTER (Event-Driven)
Guard Clock In
  ↓
  Event: "GuardClockInEvent"
  ↓
  ├─→ AttendanceService (updates attendance)
  ├─→ OccurrenceBookService (logs event)
  ├─→ NotificationService (sends alerts)
  ├─→ DashboardService (updates real-time view)
  ├─→ AIService (learns from patterns)
  ├─→ PayrollService (tracks hours)
  └─→ AuditService (logs action)

All happening in parallel, independently, asynchronously.
No direct dependencies. Fully decoupled.
```

---

## 🎯 13 Business Domains

Each domain owns its business logic, data, and rules. Clear boundaries prevent chaos as the system grows.

### 1. **Identity & Access Domain**
Everything related to authentication, authorization, and session management.

**Entities**: Users, Roles, Permissions, Sessions, API Tokens  
**Responsibilities**:
- User authentication (login/logout)
- Role-based access control (RBAC)
- Permission enforcement
- API token generation and validation
- Login history and audit trail

**Key Events**:
- UserLoggedIn
- UserLoggedOut
- PermissionGranted
- PermissionRevoked

---

### 2. **Company Domain**
Multi-tenancy foundation. Every record belongs to a company.

**Entities**: Companies, Branches, Sites, Departments, Contacts, Settings  
**Responsibilities**:
- Company setup and configuration
- Branch management (regional offices)
- Site management (customer locations)
- Department organization
- Company-wide settings

**Key Events**:
- CompanyCreated
- BranchAdded
- SiteActivated
- SettingsUpdated

---

### 3. **Workforce Domain**
Employee lifecycle management.

**Entities**: Employees, Contacts, Documents, Training, Certifications, Performance  
**Responsibilities**:
- Employee onboarding/offboarding
- Skills and certifications tracking
- Training records
- Performance reviews
- Equipment assignment

**Key Events**:
- EmployeeHired
- EmployeePromoted
- TrainingCompleted
- CertificationExpiring
- EquipmentAssigned

---

### 4. **Attendance Domain**
Clock in/out, shifts, breaks, leave management.

**Entities**: Shifts, Attendance, Clock Logs, Breaks, Leave Requests  
**Responsibilities**:
- Shift scheduling
- Clock in/out tracking (GPS verified)
- Break management
- Overtime calculation
- Leave request workflow
- Attendance exceptions

**Key Events**:
- GuardClockIn
- GuardClockOut
- ShiftAssigned
- LeaveRequested
- LeaveApproved
- OvertimeDetected

---

### 5. **Security Operations Domain** ⭐
Heart of Sentinel. Incident management and security operations.

**Entities**: Incidents, Categories, Status, Images, Comments, Witnesses, Actions  
**Responsibilities**:
- Incident reporting and tracking
- Incident investigation
- Corrective actions
- Emergency responses
- Risk assessment
- Status workflow management

**Key Events**:
- IncidentReported
- IncidentInvestigating
- IncidentResolved
- HighRiskIncident
- EmergencyAlertSent

---

### 6. **Occurrence Book Domain**
Real-time event log capturing everything happening on site.

**Entities**: Occurrence Book, Events, Timeline  
**Responsibilities**:
- Record all operational events (in chronological order)
- Link events to incidents
- Create audit trail
- Support investigations
- Generate occurrence reports

**Key Events**:
- EventLogged
- OccurrenceCreated
- TimelineGenerated

---

### 7. **Patrol Domain**
Security patrol management and execution tracking.

**Entities**: Routes, Checkpoints, Patrols, Logs, Events  
**Responsibilities**:
- Patrol route creation
- Checkpoint management (with QR codes)
- Patrol execution tracking
- Checkpoint verification (GPS + QR)
- Patrol report generation

**Key Events**:
- PatrolStarted
- CheckpointCompleted
- PatrolCompleted
- CheckpointMissed
- PatrolDeviated

---

### 8. **Fleet Domain**
Vehicle management, inspections, maintenance, fuel tracking.

**Entities**: Vehicles, Types, Drivers, Inspections, Maintenance, Fuel  
**Responsibilities**:
- Vehicle registration and tracking
- Driver assignment
- Inspection scheduling and tracking
- Maintenance logging
- Fuel consumption tracking
- Cost analysis

**Key Events**:
- VehicleInspected
- InspectionFailed
- MaintenanceCompleted
- FuelLogged
- VehicleDecommissioned

---

### 9. **Visitor Domain**
Visitor access management and tracking.

**Entities**: Visitors, Visits, Badges, Documents, Blacklist  
**Responsibilities**:
- Visitor registration
- Visit scheduling
- Badge issuance
- Access control (entry/exit)
- Visitor documents
- Blacklist management

**Key Events**:
- VisitorArrived
- VisitorDeparted
- BadgeIssued
- AccessDenied
- VisitorBlacklisted

---

### 10. **Asset Domain**
Equipment, uniforms, keys, radios, maintenance.

**Entities**: Assets, Categories, Assignments, Returns, Maintenance  
**Responsibilities**:
- Asset registration
- Assignment tracking
- Return verification
- Maintenance scheduling
- Depreciation tracking
- Asset disposal

**Key Events**:
- AssetAssigned
- AssetReturned
- MaintenanceNeeded
- AssetDisposed

---

### 11. **Finance & ERP Domain**
Financial backbone for payroll, billing, accounting, procurement, and full-suite replacement readiness.

**Entities**: Chart of Accounts, Journal Entries, Journal Lines, Payroll Periods, Payroll Export Batches, Invoices, Invoice Lines, Supplier Ledgers  
**Responsibilities**:
- General ledger ownership and posting controls
- Period close governance and auditability
- Payroll period locking and export governance
- Billing lifecycle (draft, issue, payment status)
- Accounts receivable and payable foundations
- Procurement approval workflow boundaries

**Key Events**:
- JournalEntryCreated
- JournalEntryPosted
- FinancePeriodClosed
- PayrollPeriodLocked
- PayrollExportGenerated
- BillingInvoiceIssued
- PurchaseOrderApproved

---

### 12. **AI Domain** 🤖
Sentinel Rule Engine + Machine Learning + Natural Language Processing.

**Entities**: Rules, Patterns, Recommendations, Knowledge Base  
**Responsibilities**:
- Rule evaluation (business logic)
- Pattern recognition (in incidents, attendance, etc.)
- Recommendation generation
- Report writing (natural language)
- Anomaly detection
- Learning from feedback

**Key Events**:
- RuleEvaluated
- PatternDetected
- AnomalyDetected
- RecommendationGenerated
- AIFeedbackReceived

---

### 13. **Reporting Domain**
Report generation, scheduling, export.

**Entities**: Reports, Templates, Exports, Schedules, History  
**Responsibilities**:
- Report template creation
- Report generation (PDF, Excel, CSV)
- Scheduled report automation
- Email distribution
- Archive management
- Report history

**Key Events**:
- ReportGenerated
- ReportScheduled
- ReportSent
- ScheduleUpdated

---

## 🧠 Sentinel Rule Engine (SRE)

The secret sauce that makes Sentinel feel intelligent.

### What is SRE?

A rules-based automation system that evaluates business logic **before** requiring AI intervention.

### Why SRE?

1. **Faster**: Business rules evaluated instantly (milliseconds)
2. **Cheaper**: Reduces AI API calls
3. **Consistent**: Company policies enforced exactly the same way
4. **Auditable**: Every rule evaluation is logged
5. **Configurable**: Managers can define rules without code

### How SRE Works

```
Event Occurs (e.g., Guard clocks in 45 minutes late)
        ↓
SRE Evaluates Rules
        ├─ Rule 1: If clock_in > 30 mins late → severity = Medium
        ├─ Rule 2: If frequency > 3 times/week → severity = High
        ├─ Rule 3: If critical_shift → notify supervisor immediately
        └─ Rule 4: If high_severity → create occurrence book entry
        ↓
Action Determined (Notify Supervisor)
        ↓
System Executes (NotificationEvent sent)
        ↓
AI Receives Context (Optional enhancement)
        ├─ "Guard XX clocked in 45 mins late"
        ├─ "This is the 4th time this week"
        ├─ "Please write an explanation for the occurrence book"
        └─ AI generates: "Late arrival, pattern detected, discussed with manager"
        ↓
Complete Event Chain Triggered
```

### SRE Features

**Rule Types**:
- ✅ Time-based rules (if time > X then action)
- ✅ Frequency rules (if occurred N times then action)
- ✅ Threshold rules (if value > threshold then action)
- ✅ Status-based rules (if status = X then action)
- ✅ Compound rules (if A AND B then action)
- ✅ Sequential rules (if A happened then B then action)

**Rule Evaluation Context**:
- Current event data
- Historical data (past 7/30/90 days)
- Company policies
- User roles
- Shift information
- Site location
- Time of day

**Rule Actions**:
- Create occurrence book entry
- Send notification (email, SMS, push)
- Update incident status
- Trigger workflow
- Block action (e.g., prevent vehicle entry)
- Create task for manager
- Log to audit trail
- Invoke AI service

---

## 📊 Data Flow Architecture

### Attendance Example: Guard Clocks In

```
1. Mobile App
   └─ Guard scans QR code
   └─ Sends: { employee_id, site_id, gps_coordinates, timestamp }

2. Attendance Service (Receives Event)
   ├─ Validate GPS (within site boundaries)
   ├─ Create attendance record
   ├─ Calculate clock-in status (on-time, late, etc.)
   └─ Emit: GuardClockInEvent

3. SRE (Evaluates Rules)
   ├─ Is guard late? → Yes (45 mins)
   ├─ Is this a pattern? → Yes (4th time this week)
   ├─ What's the severity? → High
   ├─ Should supervisor be notified? → Yes
   ├─ Should occurrence book entry be created? → Yes
   └─ Emit: RulesEvaluatedEvent

4. Parallel Event Handlers (All triggered simultaneously)
   ├─ NotificationService
   │  └─ Send to supervisor: "Guard XX is 45 mins late (pattern detected)"
   │
   ├─ OccurrenceBookService
   │  └─ Create entry: "Guard clocked in, 45 mins late, pattern flagged"
   │
   ├─ DashboardService
   │  └─ Update real-time guard status view
   │
   ├─ PayrollService
   │  └─ Log hours (will mark as late in payroll)
   │
   ├─ AIService
   │  └─ Receive: { event_type, severity, historical_context }
   │  └─ Generate: Written explanation for occurrence book
   │
   ├─ AuditService
   │  └─ Log: Who, what, when, where, why (full audit trail)
   │
   └─ AnalyticsService
      └─ Update attendance metrics
      └─ Track patterns over time

5. Result: Immediate Updates
   ✓ Supervisor notified in real-time
   ✓ Dashboard shows guard status
   ✓ Occurrence book auto-updated
   ✓ Audit trail complete
   ✓ AI learns from event
   ✓ Payroll data recorded
   ✓ System feels "intelligent"
```

---

## 🎨 Command Centre Dashboard

The interface that gives managers the operational visibility they need.

### Dashboard Principles

1. **Real-Time**: Live data, no delays
2. **Actionable**: Managers can take action immediately
3. **Context-Rich**: Show what matters, hide the rest
4. **Exception-Based**: Highlight problems, not normal operations
5. **Responsive**: Works on desktop, tablet, mobile

### Command Centre Layout

```
╔════════════════════════════════════════════════════════════════════╗
║                    SENTINEL COMMAND CENTRE                        ║
║                                                                    ║
║  🟢 Guards On Duty: 24/32  |  🟡 Visitors: 8  |  🔴 HIGH RISK: 3 ║
├────────────────────────────────────────────────────────────────────┤
║                                                                    ║
║  ⚠️  ALERTS & EXCEPTIONS                                          ║
║  ├─ Guard XX - 45 mins late (Pattern detected) [Action]           ║
║  ├─ Patrol Team A - Checkpoint missed at 14:30 [Reassign]         ║
║  ├─ Vehicle DY-03 - Inspection overdue [Schedule]                 ║
║  └─ Visitor - Checkout 2 hours overdue [Contact]                 ║
║                                                                    ║
║  📍 SITES STATUS                                                  ║
║  ├─ Warehouse A: Normal (24 guards, 5 visitors)                  ║
║  ├─ Office B: 1 Active Incident                                  ║
║  └─ Parking C: No Active Incidents                               ║
║                                                                    ║
║  🚶 PATROLS                                                       ║
║  ├─ Route 1: 87% Complete (Last checkpoint 5 mins ago)           ║
║  ├─ Route 2: 12% Complete (On Schedule)                          ║
║  └─ Route 3: DELAYED (Last checkpoint 25 mins overdue)           ║
║                                                                    ║
║  🚗 FLEET STATUS                                                  ║
║  ├─ Vehicle A: In Use (20 km, 65% fuel)                          ║
║  ├─ Vehicle B: Maintenance (Inspection pending)                  ║
║  └─ Vehicle C: Available                                         ║
║                                                                    ║
║  🤖 AI RECOMMENDATIONS                                            ║
║  ├─ Increase patrols at Building A (incident pattern detected)   ║
║  ├─ Schedule vehicle maintenance (overdue in 3 days)             ║
║  └─ Review Guard XX performance (pattern: frequent late arrivals)║
║                                                                    ║
║  📊 TODAY'S METRICS                                               ║
║  ├─ Total Incidents: 3 (↓ 20% vs yesterday)                      ║
║  ├─ Guard Attendance: 92.5%                                       ║
║  └─ Visitor Check-Ins: 156                                        ║
║                                                                    ║
╚════════════════════════════════════════════════════════════════════╝
```

### Dashboard Components

- **Real-Time Alerts**: Exceptions that need immediate action
- **Site Status**: Overview of each site's current state
- **Patrol Tracking**: Live patrol progress with GPS
- **Fleet Status**: Vehicle location and status
- **AI Recommendations**: Intelligent suggestions for action
- **Quick Metrics**: Key performance indicators at a glance
- **Action Menu**: Buttons to take immediate action

---

## 🔄 Integration Points

### With Database

The architecture we designed integrates seamlessly:

- **Event Store**: Database captures all events as they occur
- **Domain Entities**: Map directly to database entities
- **Repositories**: Abstract database access per domain
- **Audit Trail**: Automatic via soft deletes + audit_logs

### With External Systems

- **Email Service**: Send notifications
- **SMS Gateway**: Text alerts
- **Payment Gateway**: Process subscription payments
- **Google Maps**: GPS integration for vehicle tracking
- **AI APIs**: External LLMs (GPT, Claude) for advanced NLP

### With Mobile Apps

- **Real-Time Sync**: Events push to mobile in real-time
- **Offline Support**: Mobile works offline, syncs when online
- **Biometric Integration**: Fingerprint/Face for clock-in
- **GPS Tracking**: Background location tracking

---

## 🚀 Implementation Phases

### Phase 1: Foundation (Sprint 1-2)
- [ ] Set up project structure (domains, layers)
- [ ] Implement event system (event bus, handlers)
- [ ] Create Sentinel Rule Engine (core rules evaluation)
- [ ] Build Identity & Company domains
- [ ] Create base repository pattern

### Phase 2: Core Operations (Sprint 3-4)
- [ ] Implement Attendance domain
- [ ] Implement Security Operations domain
- [ ] Implement Occurrence Book domain
- [ ] Build event handlers for clock-in cascade
- [ ] Create first dashboard prototype

### Phase 3: Extended Operations (Sprint 5-6)
- [ ] Implement Patrol domain
- [ ] Implement Fleet domain
- [ ] Implement Visitor domain
- [ ] Implement Asset domain
- [ ] Implement Finance & ERP foundation (GL, Payroll Export, Billing)
- [ ] Enhanced dashboard views

### Phase 4: Intelligence (Sprint 7-8)
- [ ] Implement AI domain
- [ ] Integrate external LLMs
- [ ] Build rule templates UI
- [ ] Create analytics engine
- [ ] Reporting domain

### Phase 5: Supporting Systems (Sprint 9-10)
- [ ] Implement Workforce domain
- [ ] Implement Notification domain
- [ ] Mobile app development
- [ ] API security hardening
- [ ] Performance optimization

---

## 📋 Naming Conventions

### Domain Package Structure
```
domains/
├── identity/          # Identity & Access domain
├── company/           # Company domain
├── workforce/         # Workforce domain
├── attendance/        # Attendance domain
├── security_ops/      # Security Operations domain
├── occurrence_book/   # Occurrence Book domain
├── patrols/           # Patrol domain
├── fleet/             # Fleet domain
├── visitors/          # Visitor domain
├── assets/            # Asset domain
├── finance/           # Finance & ERP domain
├── ai/                # AI domain
└── reporting/         # Reporting domain
```

### Domain Internal Structure
```
domains/identity/
├── entities/          # Domain entities
├── repositories/      # Data access abstraction
├── services/          # Business logic
├── events/            # Domain events
├── commands/          # Commands (state-changing operations)
├── queries/           # Queries (read operations)
├── exceptions/        # Domain exceptions
└── rules/             # Domain rules (for SRE)
```

---

## ✅ Architecture Validation

### Before Implementation, Verify:

- [ ] Each domain has clear boundaries
- [ ] No domain directly calls another domain
- [ ] All communication via events
- [ ] SRE rules are testable and versioned
- [ ] Event handlers are idempotent
- [ ] Database design aligns with domains
- [ ] API contracts defined per domain
- [ ] Error handling strategy defined
- [ ] Logging strategy defined (domain events)
- [ ] Performance requirements understood

---

## 🎯 Success Criteria

**Architecture is successful when**:

1. ✅ New features can be added without modifying existing domains
2. ✅ A domain can be tested in isolation
3. ✅ Events cascade properly (one action → multiple updates)
4. ✅ SRE rules execute consistently
5. ✅ Dashboard updates in real-time
6. ✅ System feels intelligent and responsive
7. ✅ Code is maintainable by new developers
8. ✅ Performance meets real-time requirements
9. ✅ Audit trail is complete and consistent
10. ✅ Multi-tenancy is bulletproof

---

## 📚 Key Principles

### Clean Architecture
- Business logic is independent of frameworks
- Each layer has a specific responsibility
- Dependency flow inward (domains don't depend on UI)

### Domain-Driven Design
- Domains own their business logic
- Clear boundaries prevent chaos
- Ubiquitous language (same terms everywhere)

### Event-Driven
- Loose coupling (domains don't call each other)
- Scalability (events can be processed in parallel)
- Auditability (every action is an event)

### Single Responsibility
- Each domain does one thing well
- Each service has one reason to change
- Each class has one job

---

## 🏁 Status

**Architectural Vision**: ✅ Complete  
**Ready for Implementation**: ✅ Yes  
**Next Step**: Implement Sentinel Rule Engine (SRE) as first deliverable

---

This architecture sets Sentinel apart from typical security management systems. By implementing event-driven architecture with a rule engine at the core, Sentinel becomes a truly **intelligent platform** rather than just a database with forms.

**The difference**: When a guard clocks in late, systems that react. Sentinel **learns, decides, and acts** intelligently.
