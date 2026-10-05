# ADR-0012: Persona-Driven Access Architecture

- Status: Proposed
- Date: 2026-08-06

## Decision
Adopt a persona-driven access architecture where the active application persona determines navigation, dashboard, widgets, AI experience, and notification behavior, while roles remain one input to security evaluation.

## Context
Sentinel must support multiple very different user experiences across guard, operations, HR, finance, executive, board, client, technician, SOC, and admin contexts while preserving a shared backend and data model.

## Alternatives Considered
- Role-only UI composition
- Single generic interface with hidden menus
- Separate standalone applications per persona with duplicated services

## Consequences
- Much better UX alignment for each user type
- Cleaner scalability as new personas and workflows are added
- Requires a shared persona composition layer and strong permission enforcement

## Related Blueprint Documents
- Persona Architecture
- Persona Catalog
- Application Profiles
- Dashboard Catalog
- Navigation Profiles
- Widget Catalog
- AI Persona Catalog
- Notification Profiles
