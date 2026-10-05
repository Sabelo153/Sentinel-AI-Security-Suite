# ADR-0013: Workspace-Driven Experience Architecture

- Status: Proposed
- Date: 2026-08-06

## Decision
Adopt a workspace-driven experience architecture in which the active workspace composes the navigation, dashboard, widgets, AI, reports, and notifications for the current persona and organization context.

## Context
Sentinel needs to support many different operational modes within the same persona, such as operations, emergency response, fleet, financial review, and board governance. Hard-coded dashboards and menus will become too rigid.

## Alternatives Considered
- Hard-coded dashboard screens per persona
- Role-based UI composition only
- Separate standalone apps per workflow

## Consequences
- More flexible and scalable platform experience
- Easier addition of new workspaces and tools
- Requires a runtime composition engine and strong permission enforcement

## Related Blueprint Documents
- Access Architecture
- Organization Model
- Workspace Architecture
- Workspace Catalog
- Dashboard Engine
- Widget Engine
- Navigation Engine
- UI Composition Engine
