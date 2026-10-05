# ADR-0010: Audit Logging Standard

- Status: Proposed
- Date: 2026-08-06

## Decision
Standardize audit logging as a shared platform capability with immutable change records, before/after values, actor context, and correlation identifiers.

## Context
Security, compliance, and operational traceability require every significant change to be observable and reviewable.

## Alternatives Considered
- Auditing only at the database layer
- Logging only on user-facing actions
- Per-module audit implementations without a shared contract

## Consequences
- Stronger compliance posture
- Better investigations and traceability
- Requires consistent schema and retention choices

## Related Blueprint Documents
- Permission Matrix
- Event Catalog
- Entity Catalog
- Architecture Governance
