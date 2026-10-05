# ADR-0015: Platform Foundation Freeze v1.0

- Status: Accepted
- Date: 2026-08-07

## Decision
Freeze the platform foundation at v1.0 and stop adding new platform engines by default.

No new platform engine may be introduced unless a production business module cannot be implemented with the current platform stack.

Current frozen foundation:
- Runtime: Experience Engine, Organization Engine, Assignment Engine, Persona Engine, Workspace Engine
- Composition: Module Engine, Navigation Engine, Dashboard Engine, Widget Engine
- Platform: Event Bus, Rules Engine, Platform Registry, Capability Registry

## Context
Sentinel has reached a strong architectural baseline and enough shared runtime to support full business module delivery. The largest risk now is architecture creep: continuing to build platform infrastructure faster than business capability and delaying operational value.

The required proof point is one complete production-quality module that validates the platform end to end. Guard Operations is selected as the first module because it exercises identity, personas, workspace composition, eventing, rules, notifications, AI, documents, tasks, timeline, and reporting.

## Alternatives Considered
- Continue building additional engines before completing any business module
- Build many modules partially in parallel
- Freeze only code structure without governance controls

## Consequences
- Product teams must prioritize complete business outcomes over horizontal infrastructure expansion.
- Shared services are implemented only when demanded by a real Guard Operations slice.
- Platform integrity is improved through a formal exception process and conformance checks.
- Delivery risk drops because the architecture is validated through a full operational module.

## Exception Process for New Engines
A new platform engine is allowed only when all of the following are true:
1. A Guard Operations use case is blocked in production scope.
2. The gap cannot be solved by extending an existing engine.
3. The proposal includes module impact, events, permissions, and migration plan.
4. The change is recorded in a new ADR and approved through architecture governance.

## Implementation Policy
- Build one complete module first: Guard Operations.
- Implement shared services only when required by that module.
- Apply the Platform Conformance Checklist before merging new modules.

## Related Blueprint Documents
- Architecture Governance
- Platform Conformance Checklist
- Module Manifest
- Workflow Catalog
- Event Catalog
- Permission Matrix
- API Contract Catalog
- UI Navigation Map
