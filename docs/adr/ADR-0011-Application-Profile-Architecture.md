# ADR-0011: Application Profile Architecture

- Status: Proposed
- Date: 2026-08-06

## Decision
Adopt a persona-based application profile architecture in which Sentinel is a single platform with multiple application experiences, each assembled from the user’s profile, scope, and permissions.

## Context
The platform is growing beyond a generic CRUD-style application. Different user groups require radically different experiences, workflows, dashboards, AI assistants, and navigation patterns while still relying on one shared backend and data model.

## Alternatives Considered
- Single generic interface with hidden menus
- Role-only UI composition
- Separate standalone applications with duplicated services

## Consequences
- Better user experience for each persona
- Cleaner long-term platform growth and scalability
- Requires a shared application profile and navigation composition layer

## Related Blueprint Documents
- Application Profiles
- Dashboard Catalog
- Navigation Profiles
- Architecture Governance
