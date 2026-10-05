# Sentinel Platform Metadata

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define the platform metadata model that will drive applications, personas, workspaces, navigation, widgets, AI, notifications, and capabilities at runtime.

## Metadata Architecture

```text
User
  ↓
Identity Service
  ↓
Assignment Engine
  ↓
Authority Engine
  ↓
Organization Scope
  ↓
Application Profiles
  ↓
Workspace
  ↓
Metadata Engine
  ↓
Configuration Payload
  ↓
Frontend renders itself
```

## Core Metadata Domains
- Applications
- Personas
- Workspaces
- Navigation items
- Widgets
- Workspace widgets
- Reports
- AI assistants
- Notification profiles
- Capabilities
- Feature flags
- Module manifests

## Core Platform Tables

### applications
- id
- code
- name
- description
- icon
- theme
- platform
- active

### personas
- id
- application_id
- code
- name
- description
- landing_workspace

### workspaces
- id
- persona_id
- code
- name
- description
- icon
- dashboard
- navigation

### navigation_items
- id
- workspace_id
- parent_id
- title
- route
- icon
- permission
- order

### widgets
- id
- code
- name
- type
- datasource
- permission
- refresh_interval

### workspace_widgets
- workspace_id
- widget_id
- row
- column
- width
- height

### reports
- id
- name
- permission
- workspace

### ai_assistants
- id
- name
- persona
- workspace
- prompt_profile
- tools

### notification_profiles
- id
- persona
- workspace
- channels
- priority

## Runtime Resolution Flow
1. Authenticate user
2. Load active assignments
3. Resolve authority and scope context
4. Load available personas
5. Resolve active persona
6. Load available workspaces
7. Resolve active workspace
8. Load capabilities
9. Load navigation items
10. Load widgets and dashboard layout
11. Load reports and AI assistant metadata
12. Render UI from configuration payload

## Platform Operating System Model
Sentinel should be treated as a platform shell that hosts multiple domain experiences:
- Operations
- HR
- Finance
- Executive
- Board
- Client
- AI

## Approval Checklist
- Metadata domains approved
- Runtime flow documented
- Core platform tables identified
- UI rendering from metadata reviewed
