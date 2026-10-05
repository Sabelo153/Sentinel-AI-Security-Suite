# Sentinel Enterprise Navigation & UX Blueprint

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define the end-to-end user experience for the Sentinel Enterprise Platform so every application, device, and module behaves consistently.

## Navigation Principles
- One platform, many experiences: web, mobile, desktop, and tablet must share the same information architecture.
- Navigation must reflect role, scope, and capability—not just module ownership.
- Users should reach their most important workflows in three clicks or less.
- Global search, favorites, recently viewed, and quick actions must be consistent everywhere.

## Global Navigation Structure

```text
Sentinel Enterprise
├── Dashboard
├── Operations
│   ├── Clients
│   ├── Sites
│   ├── Patrols
│   ├── Incidents
│   ├── Visitors
│   └── Vehicles
├── HR
│   ├── Employees
│   ├── Leave
│   ├── Training
│   └── Compliance
├── Finance
│   ├── Billing
│   ├── Payroll
│   ├── Debtors
│   ├── Creditors
│   └── General Ledger
├── Fleet
├── Assets
├── AI Assistant
├── Reports
└── Administration
```

## Dashboard Layout Model
Every primary dashboard should contain:
- Overview cards for the user’s most relevant KPIs
- Exception alerts and action queues
- Recent activity stream
- Shortcut actions
- Context switching controls
- Search and global command bar

## Navigation by Device

### Web/Desktop
- Left navigation rail for primary modules
- Contextual top bar for company/branch/site switching
- Center workspace for task execution
- Right-side or drawer panel for detail and activity

### Tablet
- Collapsible navigation panel
- Large cards and touch-friendly forms
- Split view for list and detail where appropriate

### Mobile
- Bottom navigation for top tasks
- Quick actions for time-sensitive operations
- Offline state indicators
- Swipe actions for common workflows

## Cross-Module Links
Common cross-module navigation rules:
- Employee profile links from HR, operations, and compliance modules
- Site link from patrol, incident, visitor, and client screens
- Incident link from patrol, vehicle, and control room workflows
- Contract and client links from service delivery and billing views
- AI assistant available from dashboards, records, and workflow screens

## Search Experience
Global search must support:
- People
- Clients
- Sites
- Incidents
- Assets
- Vehicles
- Documents
- Tasks
- Reports
- Knowledge articles

Search results should include:
- Match type
- Status or context
- Quick action button
- Permission-aware visibility

## Quick Actions
Default quick actions by role:
- Guard: clock in/out, incident capture, patrol start/stop, visitor check-in
- Supervisor: incident review, task assignment, patrol exception review
- HR: employee profile, leave review, training expiry review
- Finance: invoice review, payroll handoff, reconciliation queue
- Admin: user management, tenant settings, audit review

## Notification and AI Entry Points
- Notification center is global and accessible from the top bar
- AI assistant entry points appear in dashboards, summaries, and workflow screens
- AI actions must be permission-scoped and auditable

## Personalization Layer
The experience should support:
- Favorites
- Recently viewed records
- Saved filters and views
- Role-based home dashboard personalization
- Multi-company and multi-branch switching

## Multi-Tenant Context Switching
Every experience must support:
- Company switching
- Branch switching
- Department context
- Site context
- Shift context

Context switching should be visible, fast, and consistent across modules.

## UX Standards
- Consistent page structure across modules
- Standard page header with context, actions, and breadcrumbs
- Shared table, form, modal, and alert patterns
- Default empty states and loading states for every screen type
- Accessibility support for keyboard, screen readers, and contrast

## Approval Checklist
- Main navigation structure agreed
- Dashboard layout model approved
- Mobile/tablet/web navigation patterns reviewed
- Search and quick action model approved
- Cross-module and context-switching rules documented
