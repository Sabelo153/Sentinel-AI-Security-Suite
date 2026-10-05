# Sentinel Architecture Governance

Status: Draft v1.0
Last updated: 2026-08-07

## Purpose
Define the governance process that keeps Sentinel aligned with the architecture baseline as the platform grows.

## Governance Rules
1. No major feature may be implemented without referencing the relevant blueprint documents.
2. Any architecture change must be reviewed and recorded in an ADR.
3. Platform Foundation v1.0 is frozen by default; no new platform engine is allowed without an approved ADR exception.
4. Business modules must consume shared services, not re-implement platform logic.
5. Changes that affect security, tenant isolation, audit, integrations, or UX require explicit review.
6. Every new module must pass the Platform Conformance Checklist before merge.
7. No application may own core business object logic; core behavior lives in business objects and platform services.
8. Data is entered once, then reused everywhere through events, services, and object contracts.

## Architecture Baseline Release
- Release name: Architecture Baseline v1.0
- Status: Draft for review
- Scope: product, data, workflows, APIs, UX, platform services, AI, integrations

## Required Review Inputs for Every Feature
Before implementation, each feature must identify:
- Relevant PRD section
- Relevant entity or domain catalog entries
- Relevant workflow(s)
- Required permission and scope rules
- Related API contract(s)
- UX and screen impact
- Required ADR if architecture changes are involved

## Change Approval Process
1. Proposal submitted with business and technical rationale.
2. Impact review against architecture baseline and related blueprints.
3. ADR created for significant architecture decisions.
4. Approval by architecture lead and affected domain owners.
5. Implementation tracked against release milestones.

## Platform Freeze Policy
- Platform Foundation Freeze v1.0 is recorded in ADR-0015.
- New engines are exception-based and must prove a real business-module delivery blocker.
- Priority is one complete production module (Guard Operations) before additional platform expansion.

## Architecture Documentation Freeze Policy
- Architecture Baseline v1.0 is the active execution baseline and is locked.
- No new architecture document should be created unless there is a validated delivery blocker.
- Preferred action is to update existing baseline artifacts instead of adding new standalone documents.

## Architecture Freeze Milestone
- Milestone owner: architecture lead with engineering and product sign-off.
- Milestone trigger: Release 0.9 standards package approved.
- Milestone effect: architecture is declared feature-complete for Version 1.0 execution.
- Post-freeze rule: implementation artifacts are prioritized over new conceptual documentation.
- Exception rule: foundational additions require proof of implementation blocker and ADR approval.

## Feature Acceptance Rule
Every new feature proposal must answer yes to all of the following before implementation:
1. Can this be implemented using the existing platform kernel and shared services?
2. Does it reuse the Universal Business Object model instead of special-case logic?
3. Does it strengthen the architecture through reuse rather than bypassing core contracts?

## Business Value Gate
Every feature must answer yes to at least one of the following:
1. Does it reduce operating costs?
2. Does it improve service delivery?
3. Does it create or protect recurring revenue?
4. Does it improve operational visibility or accountability?

## Terminology Standard
Use these terms consistently across architecture, code, APIs, and delivery artifacts:
- Platform Kernel
- Business Objects
- Platform Services
- Applications
- Workspaces
- Personas
- Assignments
- Authority

## Module Conformance Gate
Before merging a new module, reviewers must confirm:
- Platform Conformance Checklist is completed
- Required artifacts are linked (capability, permission, workflow, event, API, navigation)
- Tenant isolation and RBAC test matrices are green
- Audit, timeline, task, and reporting integration obligations are met when applicable

## Required Platform Service Delivery Order
1. Identity and authentication
2. Authorization and tenant isolation
3. Audit and observability
4. Notification service
5. File and document service
6. AI service abstraction
7. Workflow engine
8. Reporting engine
9. Integration engine
10. Offline sync engine

## Development Discipline
- Build through complete business slices; add shared services only when required by real module delivery.
- Ensure every core business object supports the universal object capabilities baseline.
- Keep business logic centralized in objects and shared services; applications compose experiences only.
- Deliver operations MVP before ERP expansion.
- Keep mobile, web, and desktop aligned to the same API contracts.
- Use ADRs to preserve the historical rationale for design decisions.
