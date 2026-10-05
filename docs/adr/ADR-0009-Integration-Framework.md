# ADR-0009: Integration Framework

- Status: Proposed
- Date: 2026-08-06

## Decision
Implement a shared integration engine for connectors to PROF1T, accounting systems, Microsoft 365, payroll services, and other external platforms.

## Context
Sentinel must support both migration and ongoing interconnection with enterprise systems without embedding fragile logic into business modules.

## Alternatives Considered
- Point-to-point integrations in each module
- Manual data import only
- Heavy custom middleware with no shared contract model

## Consequences
- Better maintainability and observability
- Easier migration and reconciliation
- Requires common connector contracts and retry handling

## Related Blueprint Documents
- Integration Catalog
- API Contract Catalog
- Event Catalog
- PRD
