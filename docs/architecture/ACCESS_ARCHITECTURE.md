# Sentinel Access Architecture

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define the multi-layer access model for Sentinel so identity, organization context, persona, and workspace are handled as separate but related architectural concerns.

## Assignment-Aware Access Model

```text
Identity
  ↓
Assignments
  ↓
Authority
  ↓
Organization Scope
  ↓
Persona
  ↓
Workspace
  ↓
Permissions
```

## Layer Responsibilities

### 1. Identity
Defines who the user is.
- One account
- One login
- Stable user identity across all companies and contexts

### 2. Assignments
Defines which responsibilities the user currently holds.
- One user may have multiple active assignments.
- Each assignment can carry unique scope and approval authority.

### 3. Authority
Defines what level of decision power applies for each assignment.
- Approval and escalation logic should use authority level plus scope.
- Authority is not identical to job title.

### 4. Organization Scope
Defines which organization or tenant segment the user is acting within.
- Company
- Region
- Branch
- Area
- Department
- Site
- Operations Centre
- Client organization
- Partner organization

### 5. Persona
Defines how the user works in a given context.
Examples:
- Guard
- Site Operations
- Regional Operations
- Executive
- Finance Review
- Board Member
- Client
- Technician
- Administrator

### 6. Workspace
Defines the operational context or working mode within a persona.
Examples:
- Operations Centre
- Emergency Response
- Fleet Workspace
- Control Room Workspace
- Financial Review Workspace
- Board Meeting Workspace

## Design Rules
- Identity never changes with company or persona.
- Assignment determines candidate capabilities and responsibilities.
- Authority determines approval and escalation power.
- Organization scope determines data scope and tenant boundaries.
- Persona determines the user’s experience, navigation, and AI behavior.
- Workspace determines the active toolset, dashboard, widgets, reports, and notifications.
- Permissions are evaluated at the intersection of identity, assignments, authority, scope, persona, and workspace.

## Runtime Composition Model
When a user logs in, Sentinel should resolve:
- identity
- active assignments
- authority context
- current organization scope
- active persona
- active workspace
- effective permissions
- navigation profile
- dashboard composition
- widget composition
- AI profile
- notification profile

## Architectural Benefit
This allows the platform to support multiple companies, switching contexts, and different working modes without creating separate applications or duplicated permission logic.

## Approval Checklist
- Assignment-aware model approved
- Organization context rules documented
- Persona-to-workspace mapping reviewed
- Permission evaluation rules reviewed
