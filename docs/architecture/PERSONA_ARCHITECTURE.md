# Sentinel Persona Architecture

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define Sentinel as a persona-driven access architecture where the user experience is shaped by the active application persona rather than by role alone.

## Core Principle
The platform answers: "Who is this user right now?" rather than only "What role do they have?"

## Architectural Model

```text
User
  ↓
Company / Branch / Department / Site
  ↓
Employment Position(s)
  ↓
Application Persona(s)
  ↓
Context
  ↓
Navigation
  ↓
Dashboard
  ↓
Widgets
  ↓
Permissions
  ↓
Field Security
```

## Persona Definition
A persona is the way a user interacts with the platform in a specific context.
Examples:
- Guard
- Site Operations
- Regional Operations
- Executive
- Finance Review
- Board Member
- Client Portal
- Technician
- Administrator

## Persona Switching
Users may switch personas based on business context without logging out.
Examples:
- Site Manager moves from Site Operations to Regional Operations
- CEO moves from Executive to Board Member or Finance Review
- Operations Manager temporarily opens the SOC Command Center persona

## Persona Design Rules
- Personas compose the navigation, dashboard, widgets, reports, notifications, and AI experience.
- Roles remain one input to access evaluation, but they are not the primary UI driver.
- Each persona must be bound to the user’s scope and permissions.
- Persona switching must preserve security boundaries and audit trail context.

## Persona Runtime Contract
At login or persona switch, the platform returns a persona configuration containing:
- persona_id
- persona_name
- landing_screen
- dashboard_id
- navigation_profile
- widget_set
- report_set
- notification_profile
- ai_profile
- offline_profile
- theme

## Implementation Guidance
Platform behavior should be driven by persona configuration rather than by hard-coded menus and screens.
This enables:
- flexible UX composition
- simpler onboarding of new personas
- better support for multi-company and multi-context usage
- future AI-specific experience layers

## Approval Checklist
- Persona model approved
- Persona switching and context rules documented
- Permission and scope integration reviewed
- UI composition model approved
