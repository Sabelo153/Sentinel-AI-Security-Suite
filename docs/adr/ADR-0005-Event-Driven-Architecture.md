# ADR-0005: Event-Driven Architecture

- Status: Proposed
- Date: 2026-08-06

## Decision
Use an event-driven integration model for cross-domain communication so modules remain loosely coupled and automation-friendly.

## Context
Sentinel must support workflow automation, notifications, analytics, and integrations without creating direct module-to-module coupling.

## Alternatives Considered
- Direct synchronous service calls everywhere
- Shared database triggers as the primary integration method
- Message bus only without domain events

## Consequences
- Better scalability and decoupling
- Easier automation and analytics
- Requires reliable event contracts and idempotent consumers

## Related Blueprint Documents
- Event Catalog
- Workflow Catalog
- Integration Catalog
- Architecture Baseline v1.0
