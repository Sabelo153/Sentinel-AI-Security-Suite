# Sentinel Workspace Architecture

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define workspaces as the operational working modes that sit above personas and control the active navigation, dashboard, widgets, notifications, reports, and AI experience.

## Workspace Definition
A workspace is the contextual operating environment for a persona.
Examples:
- Operations Centre
- Emergency Response
- Fleet Workspace
- Control Room Workspace
- Financial Review Workspace
- Board Meeting Workspace
- Compliance Workspace

## Workspace Model

```text
Identity
  ↓
Organization Context
  ↓
Persona
  ↓
Workspace
  ↓
Navigation / Dashboard / Widgets / AI / Reports / Notifications
```

## Why Workspaces Matter
A persona may need different toolsets depending on the task.
Examples:
- An operations manager may use the Operations Centre workspace for daily operations and the Emergency Response workspace during incidents.
- An executive may enter Executive Dashboard, Financial Review, or Board Meeting workspaces.
- A technician may switch between Installation, Maintenance, and Support workspaces.

## Workspace Design Rules
- Workspaces are composed from the active persona and organization context.
- Each workspace has its own navigation profile, dashboard layout, widget composition, and AI profile.
- Workspace switching must preserve secure context and provide clear state changes.
- Workspaces should be configurable and extensible without changing core application code.

## Workspace Runtime Contract
Each workspace should define:
- workspace_id
- workspace_name
- persona_id
- landing_screen
- navigation_profile_id
- dashboard_id
- widget_set
- report_set
- notification_profile_id
- ai_profile_id
- offline_profile_id
- theme

## Approval Checklist
- Workspace concept approved
- Persona-to-workspace mapping reviewed
- Workspace runtime contract documented
- Switching behavior and context preservation reviewed
