# ADR-0004: AI Provider Abstraction

- Status: Proposed
- Date: 2026-08-06

## Decision
Introduce an AI service abstraction layer so Sentinel can switch providers and control prompts, permissions, and audit logging centrally.

## Context
AI capabilities must be reusable across reporting, summaries, assistants, and recommendations while staying auditable and policy-aware.

## Alternatives Considered
- Direct integration into each module
- Single hard-coded provider model
- Full in-house model deployment from day one

## Consequences
- Better flexibility and governance
- Easier future provider changes
- Requires common prompt and output contracts

## Related Blueprint Documents
- AI Capability Catalog
- PRD
- Event Catalog
- Permission Matrix
