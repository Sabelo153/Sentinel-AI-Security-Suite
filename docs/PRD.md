# Sentinel Enterprise Platform PRD

Status: Draft v1.0
Last updated: 2026-08-06

## 1. Product Vision
Build Sentinel as an Enterprise Security Operations Platform that can replace PROF1T over time while expanding into a multi-domain enterprise platform for operations, workforce, client delivery, fleet, assets, finance, and AI-driven decision support.

## 2. Business Goals
- Replace fragmented security operations tooling with one shared platform.
- Create a scalable foundation for future modules and years of expansion.
- Preserve security, compliance, auditability, and tenant isolation at enterprise scale.
- Support web, mobile, desktop, and API-first delivery with one data model.

## 3. Personas
- Security Operations Manager
- Site Supervisor
- Control Room Operator
- HR Manager
- Finance Officer
- Fleet Manager
- Client Service Manager
- Platform Administrator
- Executive Stakeholder

## 4. Functional Requirements
- Multi-tenant company structure with branch and department hierarchy
- Employee lifecycle and compliance management
- Client, contract, and site operations management
- Shift, roster, patrol, incident, and task workflows
- Visitor, vehicle, equipment, and asset management
- Reporting, audit, and AI-assisted insights
- API-first integration for internal systems and external platforms

## 5. Non-Functional Requirements
- Security: role-based and field-based access control, tenant isolation, audit logs
- Performance: sub-second operational reads for core screens where possible
- Offline support: field workflows must work offline and sync safely
- Scalability: support growing tenant size and module expansion
- Reliability: event-driven processing with retries and replay support
- Compliance: data retention, archival, export, and traceability controls

## 6. Module Definitions
- Core Platform: identity, tenant, settings, audit, permissions
- Operations: attendance, patrols, incident response, tasks, control room
- Workforce: employees, training, compliance, leave, performance
- Client Delivery: clients, contracts, sites, visitors, service tracking
- Fleet and Assets: vehicles, equipment, maintenance, assignments
- Intelligence: AI assistant, reports, anomaly detection, forecasting
- Finance and Payroll: deferred until operations and HR readiness gates pass

## 7. Success Criteria
- Core operational workflows are usable for field and control-room users
- Tenant isolation and permission design are verified end to end
- New modules can be added without redesigning the core platform
- AI capabilities are explainable and audit-friendly

## 8. Release Roadmap
- Phase 0: Blueprint and governance approval
- Phase 1: Company, identity, RBAC, and core platform baseline
- Phase 2: Employee, client, site, and operations foundation
- Phase 3: Patrols, incidents, reporting, mobile support
- Phase 4: Fleet, assets, AI assistance, and integrations
- Phase 5: Finance, payroll, procurement, and full PROF1T replacement path

## 9. Technical Constraints
- One backend and one identity system across all applications
- Shared entity catalog and permission model mandatory
- No new modules may bypass the blueprint artifacts
- New schema changes must preserve tenant and audit expectations
