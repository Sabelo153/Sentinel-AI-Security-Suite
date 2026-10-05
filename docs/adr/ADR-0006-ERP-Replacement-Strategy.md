# ADR-0006: ERP Replacement Strategy

- Status: Proposed
- Date: 2026-08-06

## Decision
Treat ERP replacement as a staged expansion after the operations platform and shared services are mature, rather than a parallel build from day one.

## Context
Sentinel should evolve toward replacing PROF1T modules over time, but finance and ERP features should not be attempted prematurely.

## Alternatives Considered
- Build finance and ERP immediately
- Replace PROF1T in a single big-bang migration
- Use a purely integration-only strategy forever

## Consequences
- Lower risk and better sequencing
- Clear path to ERP replacement over time
- Requires careful migration and reconciliation planning

## Related Blueprint Documents
- PRD
- Master Data Model
- Entity Catalog
- Architecture Baseline v1.0
