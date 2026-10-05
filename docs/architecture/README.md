# Sentinel AI - Architecture Documentation

**Enterprise-Grade, Domain-Driven Design with Event-Driven Architecture**

---

## 📑 Quick Navigation

### 🎯 For Different Roles

**👨‍💼 Project Managers / Product Owners**
1. Read: [ARCHITECTURE.md](ARCHITECTURE.md) - Executive Summary
2. Key concept: 13 business domains + Command Centre dashboard
3. Timeline: 4-6 weeks for SRE implementation

**🏗️ Solution Architects**
1. Read: [ARCHITECTURE.md](ARCHITECTURE.md) - Full architecture
2. Study: [DOMAIN_TO_DATABASE_MAPPING.md](DOMAIN_TO_DATABASE_MAPPING.md) - Domain structure
3. Reference: [SRE_SPECIFICATION.md](SRE_SPECIFICATION.md) - Rule engine details

**👨‍💻 Backend Developers**
1. Read: [ARCHITECTURE.md](ARCHITECTURE.md) - Architecture overview
2. Study: [DOMAIN_TO_DATABASE_MAPPING.md](DOMAIN_TO_DATABASE_MAPPING.md) - Your domain's tables
3. Reference: [SRE_SPECIFICATION.md](SRE_SPECIFICATION.md) - Rules you'll use
4. Link to: `/docs/database/SCHEMA.md` - Table definitions

**🤖 AI/ML Engineers**
1. Read: [ARCHITECTURE.md](ARCHITECTURE.md) - AI Domain section
2. Study: [SRE_SPECIFICATION.md](SRE_SPECIFICATION.md) - Rule engine architecture
3. Reference: AI Domain section in [DOMAIN_TO_DATABASE_MAPPING.md](DOMAIN_TO_DATABASE_MAPPING.md)

**🔧 DevOps/Infrastructure**
1. Read: [ARCHITECTURE.md](ARCHITECTURE.md) - Scalability section
2. Consider: Event system infrastructure
3. Plan: Database replication, caching

---

## 📄 Document Overview

### [ARCHITECTURE.md](ARCHITECTURE.md)
**Complete architectural vision for Sentinel**

**Key Sections**:
- Clean Architecture layers
- Event-Driven communication
- 13 Business Domains (detailed, including Finance & ERP)
- Sentinel Rule Engine (SRE) concept
- Command Centre Dashboard design
- Integration points
- Implementation roadmap (5 phases)

**When to use**: 
- Project kickoff
- Architecture reviews
- Team onboarding
- Strategic decisions

**Read time**: 30-45 minutes

---

### [SRE_SPECIFICATION.md](SRE_SPECIFICATION.md)
**Complete specification for Sentinel Rule Engine**

**Key Sections**:
- Why SRE (speed, cost, consistency)
- Rule structure and definition
- 6 rule types with examples
- Rule evaluation process (step-by-step)
- Rule management and versioning
- Error handling and recovery
- Performance characteristics
- Security and multi-tenancy
- Implementation plan (4 phases)

**When to use**:
- Implementing the rule engine
- Understanding rule-based automation
- Designing rule templates
- Testing rule evaluation

**Read time**: 45-60 minutes

---

### [DOMAIN_TO_DATABASE_MAPPING.md](DOMAIN_TO_DATABASE_MAPPING.md)
**How DDD domains map to the evolving database baseline**

**Key Sections**:
- Domain-by-domain mapping with current schema tables
- For each domain: purpose, tables, services, events
- Complete integration points
- Cross-domain communication examples
- Event flow examples

**When to use**:
- Understanding domain data structure
- Designing domain services
- Building domain-specific features
- Writing queries and migrations
- Understanding data flow

**Read time**: 1-2 hours (read your domain first)

---

### [ESOP_ENTERPRISE_MODEL.md](ESOP_ENTERPRISE_MODEL.md)
**Capability-first enterprise operating model for Sentinel**

**Key Sections**:
- Multi-platform architecture (web, mobile, desktop over one API)
- Enterprise hierarchy and tenant boundaries
- Capability-first authorization design
- Release-track roadmap from v0.3.0 through v3.0+
- Domain organization standard for team scaling

**When to use**:
- Sprint planning and scope decisions
- Defining new modules before coding
- Aligning web/mobile/desktop experiences to the same capability model
- Reviewing long-term platform architecture

**Read time**: 20-30 minutes

---

### [PLATFORM_APPLICATIONS.md](PLATFORM_APPLICATIONS.md)
**Sentinel as a multi-application platform on one API and identity model**

**Key Sections**:
- Shared platform core services
- Application portfolio by user type
- Module-by-module release strategy
- Operations MVP application alignment

**When to use**:
- Planning app-specific releases
- Defining scope boundaries per user segment
- Aligning web, mobile, and desktop feature slices

**Read time**: 10-15 minutes

---

### [PERMISSION_MATRIX.md](PERMISSION_MATRIX.md)
**Enterprise authorization baseline for role + action + field + scope control**

**Key Sections**:
- Action taxonomy shared across entities
- Scope taxonomy and scope resolution rules
- Field sensitivity classes and masking rules
- Role baseline mappings across platform applications

**When to use**:
- Adding permissions
- Designing role policies
- Implementing middleware and field guards

**Read time**: 20-30 minutes

---

### [WORKFLOW_CATALOG.md](WORKFLOW_CATALOG.md)
**Cross-application process catalog and state transition baseline**

**Key Sections**:
- Core business processes by application
- Workflow stages, approvals, and outcomes
- Automation touchpoints and escalation rules

**When to use**:
- Designing service orchestration
- Defining approval chains
- Planning workflow automation

**Read time**: 20-30 minutes

---

### [PLATFORM_CONFORMANCE_CHECKLIST.md](PLATFORM_CONFORMANCE_CHECKLIST.md)
**Merge-gate checklist for every new module**

**When to use**:
- Before merging new modules
- During architecture and security review
- To verify shared-service reuse and policy conformance

**Read time**: 10-15 minutes

---

### [GUARD_OPERATIONS_IMPLEMENTATION_BLUEPRINT.md](GUARD_OPERATIONS_IMPLEMENTATION_BLUEPRINT.md)
**First complete module execution plan for platform validation**

**When to use**:
- Planning Operations MVP slices
- Prioritizing shared-service pull-through
- Validating platform architecture through real delivery

**Read time**: 10-15 minutes

---

### [EVENT_CATALOG.md](EVENT_CATALOG.md)
**Cross-domain event contract baseline**

**Key Sections**:
- Domain event naming conventions
- Required event envelope fields
- Event producers and consumers
- Reliability, idempotency, and replay expectations

**When to use**:
- Building event publishers/consumers
- Wiring notifications and integrations
- Designing analytics and audit automation

**Read time**: 15-25 minutes

---

### [API_CONTRACT_CATALOG.md](API_CONTRACT_CATALOG.md)
**Platform API surface and integration contract inventory**

**Key Sections**:
- Internal and external API domains
- Endpoint and payload conventions
- Versioning, compatibility, and deprecation policy
- Integration contract patterns for PROF1T coexistence

**When to use**:
- Defining new endpoints
- Reviewing integration impact
- Planning version changes

**Read time**: 20-30 minutes

---

### [UI_NAVIGATION_MAP.md](UI_NAVIGATION_MAP.md)
**Application-by-application screen map and navigation contract**

**Key Sections**:
- Navigation trees by platform application
- Role-driven menu exposure patterns
- Cross-application navigation boundaries
- Mobile and offline navigation constraints

**When to use**:
- Planning web/mobile screens
- Aligning UI to permissions/capabilities
- Preventing duplicated UX pathways

**Read time**: 15-25 minutes

---

### [SCREEN_CATALOG.md](SCREEN_CATALOG.md)
**Platform-wide screen inventory for every major application**

**When to use**:
- Defining all user-facing experiences before implementation
- Keeping web, mobile, and desktop experiences aligned

---

### [NOTIFICATION_CATALOG.md](NOTIFICATION_CATALOG.md)
**Operational, approval, compliance, and AI notification standards**

**When to use**:
- Designing alerts, escalations, and human-in-the-loop workflows

---

### [AI_CAPABILITY_CATALOG.md](AI_CAPABILITY_CATALOG.md)
**AI capabilities and governance model for the Enterprise Security Operations Platform (ESOP)**

**When to use**:
- Planning intelligent features and their approval boundaries

---

### [INTEGRATION_CATALOG.md](INTEGRATION_CATALOG.md)
**External and internal integration contracts and patterns**

**When to use**:
- Planning adapters, webhooks, feeds, and enterprise connectors

---

### [NAVIGATION_UX_BLUEPRINT.md](NAVIGATION_UX_BLUEPRINT.md)
**Cross-platform navigation, dashboard, and experience model for the enterprise platform**

**When to use**:
- Defining how users move through the system across web, tablet, and mobile
- Aligning module navigation and quick-action patterns

---

### [APPLICATION_PROFILES.md](APPLICATION_PROFILES.md)
**Persona-based application model for the Sentinel enterprise platform**

**When to use**:
- Defining the platform as a set of application experiences rather than one generic UI
- Mapping users to Guard, Operations, HR, Finance, Executive, Board, Client, Technician, SOC, and Admin experiences

---

### [SENTINEL_ESOP_ROADMAP_v1.0.md](SENTINEL_ESOP_ROADMAP_v1.0.md)
**Execution roadmap that proves the platform through one complete Guard Operations application**

**When to use**:
- Release planning for platform-to-product execution
- Sequencing shared application services and Guard Operations delivery
- Aligning supervisor/operations/executive workspace rollout

---

### [DASHBOARD_CATALOG.md](DASHBOARD_CATALOG.md)
**Dashboard inventory for each application profile**

**When to use**:
- Defining the KPIs, widgets, and alerts for every persona experience

---

### [NAVIGATION_PROFILES.md](NAVIGATION_PROFILES.md)
**Navigation structure for each application profile**

**When to use**:
- Defining menu composition, screen access, and cross-profile navigation rules

---

### [ACCESS_ARCHITECTURE.md](ACCESS_ARCHITECTURE.md)
**The four-layer access model for identity, organization context, persona, and workspace**

**When to use**:
- Defining how access is evaluated across the platform

---

### [ORGANIZATION_MODEL.md](ORGANIZATION_MODEL.md)
**How company, branch, department, site, and partner contexts are modeled**

**When to use**:
- Defining multi-company and multi-context access boundaries

---

### [ORGANIZATIONAL_HIERARCHY.md](ORGANIZATIONAL_HIERARCHY.md)
**Operational hierarchy model for company, region, branch, area, site, and operations centre**

**When to use**:
- Defining real-world security company structure and scope inheritance
- Modeling matrix oversight roles such as inspectors

---

### [ASSIGNMENT_MODEL.md](ASSIGNMENT_MODEL.md)
**Assignment-based model for multi-role, multi-scope access and runtime composition**

**When to use**:
- Defining concurrent responsibilities for one employee
- Implementing permission/scope merge and conflict resolution

---

### [AUTHORITY_MODEL.md](AUTHORITY_MODEL.md)
**Authority levels model separated from position titles and role names**

**When to use**:
- Defining approval and escalation power by scope
- Modeling board vs executive authority boundaries

---

### [POSITION_CATALOG.md](POSITION_CATALOG.md)
**Position taxonomy and position-to-assignment mapping model**

**When to use**:
- Defining operational job structures and qualification requirements
- Decoupling position identity from effective authority and permissions

---

### [APPROVAL_WORKFLOW_MODEL.md](APPROVAL_WORKFLOW_MODEL.md)
**Authority-aware approval model with delegation and acting assignment support**

**When to use**:
- Designing approval chains and escalation for operations workflows
- Enabling temporary authority transfer without permanent role mutation

---

### [PERSONA_ARCHITECTURE.md](PERSONA_ARCHITECTURE.md)
**Persona-driven access architecture for the Sentinel platform**

**When to use**:
- Defining the access and experience model beyond role-based UI composition

---

### [PERSONA_CATALOG.md](PERSONA_CATALOG.md)
**Catalog of all supported personas and their runtime experience settings**

**When to use**:
- Defining persona metadata, landing pages, dashboards, and AI/notifcation profiles

---

### [WORKSPACE_ARCHITECTURE.md](WORKSPACE_ARCHITECTURE.md)
**Workspace model for the operational context above persona**

**When to use**:
- Defining the workspace layer that composes the active experience

---

### [WORKSPACE_CATALOG.md](WORKSPACE_CATALOG.md)
**Catalog of supported workspaces and their runtime configuration**

**When to use**:
- Designing new operational modes and workspace variants

---

### [DASHBOARD_ENGINE.md](DASHBOARD_ENGINE.md)
**Composable dashboard engine for workspace-driven UI**

**When to use**:
- Building dynamic dashboards without hard-coded screens

---

### [WIDGET_CATALOG.md](WIDGET_CATALOG.md)
**Reusable dashboard widgets for persona-based dashboards**

**When to use**:
- Designing persona dashboards and widget composition rules

---

### [WIDGET_ENGINE.md](WIDGET_ENGINE.md)
**Runtime engine for registering and rendering widgets**

**When to use**:
- Building the widget system behind workspace dashboards

---

### [NAVIGATION_ENGINE.md](NAVIGATION_ENGINE.md)
**Data-driven navigation engine for persona and workspace composition**

**When to use**:
- Rendering navigation profiles dynamically from configuration

---

### [UI_COMPOSITION_ENGINE.md](UI_COMPOSITION_ENGINE.md)
**Runtime composition engine for identity, organization, persona, and workspace**

**When to use**:
- Assembling the final user experience from the platform model

---

### [AI_PERSONA_CATALOG.md](AI_PERSONA_CATALOG.md)
**AI assistant behavior and permissions by persona**

**When to use**:
- Designing persona-specific AI experiences and guardrails

---

### [NOTIFICATION_PROFILES.md](NOTIFICATION_PROFILES.md)
**Notification behavior and escalation rules by persona**

**When to use**:
- Designing alerts, escalations, and communication patterns per profile

---

### [PLATFORM_METADATA.md](PLATFORM_METADATA.md)
**Runtime metadata model for applications, personas, workspaces, navigation, widgets, AI, and notifications**

**When to use**:
- Moving the platform from hard-coded UIs to configurable metadata-driven experiences

---

### [CAPABILITY_REGISTRY.md](CAPABILITY_REGISTRY.md)
**Shared capability model for permissions, workflows, APIs, widgets, reports, and AI tools**

**When to use**:
- Defining reusable capabilities that connect all platform layers

---

### [FEATURE_FLAGS.md](FEATURE_FLAGS.md)
**Feature flag model for phased rollout and customer-specific enablement**

**When to use**:
- Controlling rollout of modules, AI, and workspaces safely

---

### [MODULE_MANIFEST.md](MODULE_MANIFEST.md)
**Contract format for implementing new platform modules as plug-ins**

**When to use**:
- Planning new modules so they integrate cleanly with the platform metadata model

---

### [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md)
**Shared visual language, component standards, and UI rules for all Sentinel products**

**When to use**:
- Building consistent screens and reusable UI components
- Establishing accessibility and branding rules

---

### [../ARCHITECTURE_GOVERNANCE.md](../ARCHITECTURE_GOVERNANCE.md)
**Governance rules for architecture review, baseline control, and implementation discipline**

**When to use**:
- Reviewing feature impact before implementation
- Enforcing the architecture baseline and ADR process

---

### [../adr/README.md](../adr/README.md)
**Architecture Decision Record index for major platform choices**

**When to use**:
- Recording new architectural decisions and preserving rationale

---

### [../database/MASTER_DATA_MODEL.md](../database/MASTER_DATA_MODEL.md)
**Long-term data ownership blueprint and Finance readiness gate**

**Key Sections**:
- Domain ownership map
- Tenant-scoped table standards
- Cross-domain integration boundaries
- Operations MVP data completion checklist
- Finance implementation gate

**When to use**:
- Reviewing schema changes
- Planning new migrations
- Validating domain ownership of new tables

**Read time**: 15-20 minutes

---

## 🎯 Architecture Principles

### 1. Clean Architecture
```
Business logic is independent of frameworks
Dependency flow is always inward
Each layer has a specific responsibility
```

### 2. Domain-Driven Design (DDD)
```
13 business domains (not technical layers)
Each domain owns its logic and data
Clear boundaries prevent chaos
Ubiquitous language (same terms everywhere)
```

### 3. Event-Driven Communication
```
NO direct calls between domains
ALL communication via events
Loose coupling enables scaling
Asynchronous processing for performance
```

### 4. Intelligent Automation (SRE)
```
Business rules evaluated first
Consistent policy enforcement
Reduced AI API calls
Every action auditable and traceable
```

---

## 🏗️ Domain Baseline

Current execution mode: domain map is stable; focus is module delivery and platform hardening for Operations MVP.

```
┌─────────────────────────────────────────────┐
│      SENTINEL AI SECURITY SUITE             │
├─────────────────────────────────────────────┤
│                                             │
│  1. Identity & Access        8. Fleet       │
│  2. Company                  9. Visitor     │
│  3. Workforce               10. Asset       │
│  4. Attendance              11. Finance&ERP │
│  5. Security Operations     12. AI Domain   │
│  6. Occurrence Book         13. Reporting   │
│  7. Patrol                                   │
│                                             │
└─────────────────────────────────────────────┘
```

### Domain Interactions

```
User Action
    ↓
Event Created
    ↓
SRE Evaluates Rules
    ↓
Multiple Domains Process Event
    ├─ Occurrence Book logs it
    ├─ Notification sends alerts
    ├─ Dashboard updates
    ├─ AI generates insights
    ├─ Audit trail records it
    └─ Analytics captured
    
Everything happens in parallel, no blocking.
```

---

## 🚀 Event System

### The Secret to "Intelligent" System

Instead of this:
```
Guard clocks in
  ↓
Update attendance
  ↓
Maybe update dashboard
  ↓
(Other things don't happen automatically)
```

We do this:
```
Guard clocks in
  ↓
Event Created: GuardClockInEvent
  ↓
┌──────────────────────────────────────┐
│ All these happen in parallel:        │
├──────────────────────────────────────┤
│ ✓ Attendance updated                 │
│ ✓ Occurrence logged                  │
│ ✓ SRE evaluates rules                │
│ ✓ Supervisor notified                │
│ ✓ Dashboard updated                  │
│ ✓ Payroll recorded                   │
│ ✓ AI learns from event               │
│ ✓ Audit trail complete               │
│ ✓ Analytics captured                 │
└──────────────────────────────────────┘

Milliseconds later, everything is done.
```

---

## 🧠 Sentinel Rule Engine (SRE)

### The Core Component

```
Every business decision goes through SRE:

Guard clocks in 45 mins late
    ↓
SRE Loads Rules
    ├─ Rule 1: If delay >= 30 mins → severity = High
    ├─ Rule 2: If pattern detected → escalate
    └─ Rule 3: If critical shift → notify immediately
    ↓
SRE Evaluates
    ├─ All conditions met? YES
    └─ Matched rules: 3
    ↓
Actions Triggered
    ├─ Create occurrence entry
    ├─ Send notification
    ├─ Create HR task
    └─ Flag for manager review
    ↓
System Feels Intelligent
```

### Why SRE Matters

| Traditional System | Sentinel with SRE |
|---|---|
| Guard clocks in → Log only | Guard clocks in → Automatic cascade |
| No pattern detection | AI learns patterns |
| Manual alerts | Intelligent alerts |
| Inconsistent policies | Exact policy enforcement |
| Not auditable | Complete audit trail |
| Slow | Lightning fast |
| Expensive (API calls) | Cost-effective |

---

## 🎨 Command Centre Dashboard

Managers see what matters in seconds.

```
╔════════════════════════════════════════╗
║    SENTINEL COMMAND CENTRE             ║
├────────────────────────────────────────┤
║                                        ║
║  🟢 Guards: 24/32  🟡 Visitors: 8    ║
║  🔴 High Risk: 3   🚗 Fleet Status   ║
║                                        ║
║  ⚠️  ALERTS (3)                        ║
║  • Guard XX - 45 mins late            ║
║  • Patrol missed checkpoint           ║
║  • Vehicle inspection overdue         ║
║                                        ║
║  🤖 AI RECOMMENDATIONS                ║
║  • Increase patrols (pattern detected)║
║  • Schedule maintenance (3 days)      ║
║  • Review guard performance           ║
║                                        ║
║  📊 TODAY'S METRICS                    ║
║  Incidents: 3 (↓20%)                 ║
║  Attendance: 92.5%                    ║
║  Patrols: 87% complete                ║
║                                        ║
╚════════════════════════════════════════╝
```

---

## 🔄 Data Flow Through Domains

### Example: Guard Clocks In

```
1. ATTENDANCE DOMAIN
   ├─ Receives: Clock-in event
   ├─ Validates: GPS within site
   ├─ Creates: attendance_record
   └─ Emits: GuardClockInEvent

2. AI DOMAIN (SRE)
   ├─ Loads: Applicable rules
   ├─ Evaluates: Is guard late? Pattern?
   ├─ Determines: Severity level
   └─ Emits: RuleEvaluatedEvent

3. MULTIPLE DOMAINS (Parallel)
   ├─ OCCURRENCE BOOK: Logs the event
   ├─ NOTIFICATION: Sends alerts
   ├─ REPORTING: Updates dashboard
   ├─ AUDIT: Records changes
   └─ ANALYTICS: Captures metrics

Result: Everything updated in real-time,
        no blocking, no delays.
```

---

## 🔗 Database Integration

The architecture aligns perfectly with database design:

```
Domains (Architecture)
    ↓
Domain Services
    ↓
Repository Pattern
    ↓
Database Tables (SCHEMA.md)
    ↓
Foreign Keys (RELATIONSHIPS.md)
    ↓
Audit Trail (soft deletes, logs)
```

See [DOMAIN_TO_DATABASE_MAPPING.md](DOMAIN_TO_DATABASE_MAPPING.md) for complete mapping.

---

## 📚 Complementary Documentation

### Architecture
- **ARCHITECTURE.md** ← You are here
- **SRE_SPECIFICATION.md** ← Rule engine deep dive
- **DOMAIN_TO_DATABASE_MAPPING.md** ← Domain-to-table mapping

### Database
- **/docs/database/SCHEMA.md** - 99 table definitions
- **/docs/database/RELATIONSHIPS.md** - Foreign keys & queries
- **/docs/database/NAMING_STANDARDS.md** - Conventions

### Getting Started
- **/docs/GETTING_STARTED.md** - Project setup
- **/docs/QUICK_START.md** - Development quickstart

---

## 🚀 Implementation Roadmap

### Phase 1: Foundation (Sprint 1-2)
- Set up project structure (domains, layers)
- Implement event system
- Build Sentinel Rule Engine (SRE)
- Create Identity & Company domains

### Phase 2: Core Operations (Sprint 3-4)
- Attendance domain
- Security Operations domain
- Occurrence Book domain
- Rule integration with events

### Phase 3: Extended Operations (Sprint 5-6)
- Patrol, Fleet, Visitor domains
- Asset domain
- Dashboard prototype

### Phase 4: Intelligence (Sprint 7-8)
- AI Domain implementation
- External LLM integration
- Analytics engine

### Phase 5: Supporting (Sprint 9-10)
- Workforce domain
- Notification domain
- Mobile app
- Performance optimization

---

## ✅ Quality Assurance

### Before Implementation

- [ ] All developers understand DDD principles
- [ ] Event system designed and reviewed
- [ ] SRE rule structure approved
- [ ] Database design validated
- [ ] Team assigned to domains
- [ ] Communication protocols established

### During Implementation

- [ ] Each domain can be tested in isolation
- [ ] Events trigger cascade of updates
- [ ] SRE rules execute consistently
- [ ] Dashboard updates in real-time
- [ ] Code follows domain boundaries
- [ ] Logging and auditing working

### Before Production

- [ ] All 12 domains tested
- [ ] Event system performance validated
- [ ] SRE rules reviewed and approved
- [ ] Multi-tenancy verified
- [ ] Audit trail complete
- [ ] Performance benchmarks met

---

## 🎓 Learning Path

### Week 1: Foundation
- Read ARCHITECTURE.md
- Understand DDD principles
- Learn about event systems
- Read your domain section in DOMAIN_TO_DATABASE_MAPPING.md

### Week 2: Deep Dive
- Read SRE_SPECIFICATION.md
- Study rule evaluation process
- Review database tables (SCHEMA.md)
- Understand domain services

### Week 3: Implementation
- Start building domain structure
- Implement event system
- Create repository patterns
- Write domain services

### Week 4+: Execution
- Build domain logic
- Integrate with SRE
- Test event cascades
- Optimize performance

---

## � Execution Directive
- Release 0.9 is the final architecture/standards phase. The architecture is frozen for Release 1.0 execution.
- Do not add new architecture documents unless a verified implementation blocker requires one and is recorded as an ADR.
- Begin implementation of the Sentinel Platform Kernel before building application UI or workflows.
- Build kernel services first, then validate them with one vertical Guard Operations slice.
- Use the architecture docs as reference; new work should be code, migrations, tests, API contracts, or runbooks that enable Release 1.0.

---

## �🔒 Security & Compliance

### Built Into Architecture

- **Multi-Tenancy**: Strict company_id isolation
- **Audit Trail**: Every action logged
- **Soft Deletes**: Data never permanently removed
- **Role-Based Access**: Via permissions system
- **Event Immutability**: Events are immutable
- **GDPR Ready**: Data retention policies enforced
- **PSIRA Compliant**: Audit trail and licensing tracking

---

## 📞 Common Questions

**Q: Why 12 domains instead of fewer?**  
A: Each domain represents a distinct business capability with clear responsibilities. 12 domains is the sweet spot—enough specificity without becoming too granular.

**Q: How do domains communicate?**  
A: Only via events. Never direct calls. This keeps domains loosely coupled and independently testable.

**Q: What's the benefit of SRE?**  
A: Business rules evaluated in milliseconds (no API delays), consistent policy enforcement, cheaper than external AI for every decision, complete auditability.

**Q: Can I test a domain in isolation?**  
A: Yes! That's the whole point. Each domain is independently testable without the full system running.

**Q: How does this scale?**  
A: Events can be processed in parallel, domains can run on separate servers, databases can be replicated. Event-driven architecture scales horizontally.

**Q: What about data consistency?**  
A: Event system ensures eventual consistency. SRE ensures business rules are applied uniformly. Database constraints ensure referential integrity.

---

## 🎯 Success Criteria

**Architecture is successful when**:

1. ✅ New features added without modifying existing domains
2. ✅ Domains testable in isolation
3. ✅ Events cascade properly
4. ✅ SRE rules execute consistently
5. ✅ Dashboard updates in real-time
6. ✅ System feels intelligent
7. ✅ Code maintainable by new developers
8. ✅ Performance meets requirements
9. ✅ Audit trail complete
10. ✅ Multi-tenancy is bulletproof

---

## 📊 Architecture Summary

| Aspect | Design |
|--------|--------|
| **Pattern** | Clean Architecture + DDD |
| **Domains** | 13 business domains |
| **Communication** | Event-driven (loosely coupled) |
| **Core Component** | Sentinel Rule Engine (SRE) |
| **Database** | Evolving baseline mapped to domain boundaries |
| **Dashboard** | Command Centre (real-time) |
| **Scalability** | Horizontal (events, microservices) |
| **Testability** | Domain isolation + integration |
| **Auditability** | Complete event + transaction log |

---

## 🚀 Status

**Architectural Vision**: ✅ Complete  
**Ready for Development**: ✅ Yes  
**Next Priority**: Implement Sentinel Rule Engine (SRE)

---

**This architecture sets Sentinel apart.**

By combining Clean Architecture, Domain-Driven Design, Event-Driven communication, and an intelligent Rule Engine, Sentinel becomes a truly **smart platform**—not just another database with forms.

The difference: When a guard clocks in, systems that *react*. Sentinel **learns, decides, and acts** intelligently across the entire organization.

---

**Architecture Documentation Status**: ✅ Complete  
**Quality**: Enterprise Grade  
**Last Updated**: Current Session

Use this documentation as the foundation for all future development.
