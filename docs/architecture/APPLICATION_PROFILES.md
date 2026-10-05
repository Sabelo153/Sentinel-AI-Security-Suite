# Sentinel Application Profiles

Status: Draft v1.0
Last updated: 2026-08-07

## Purpose
Define Sentinel as a single enterprise platform composed of multiple persona-specific applications rather than one generic interface with hidden menus.

## Core Principle
The backend, data model, APIs, permissions, and integrations remain shared.
The user experience is assembled from the user’s assigned application profiles.

## Application Profile Model

```text
User
  ↓
Company / Branch / Department / Site
  ↓
Role(s)
  ↓
Application Profile(s)
  ↓
Navigation Profile
  ↓
Dashboard
  ↓
Widgets / Actions / AI / Reports / Notifications
```

## Application Profiles

### Guard App
- Purpose: field execution for security officers
- Primary users: guards, field officers
- Core screens: Home, Start Shift, Patrols, Incidents, Visitors, Messages, Emergency, My Schedule, My Leave, My Payslip, Training, AI Assistant
- Permissions: self, assigned shift, assigned site, incident capture, visitor check-in
- Offline capability: high
- AI features: incident summary, task assistance, compliance reminders

### Site Manager App
- Purpose: operational oversight for a site or cluster of sites
- Primary users: site supervisors, site managers
- Core screens: Dashboard, Employees, Visitors, Vehicles, Patrols, Incidents, Equipment, Keys, Maintenance, Site Documents, Site AI, Reports
- Permissions: site-level operations, approvals, staffing oversight
- Offline capability: medium
- AI features: risk summary, anomaly detection, site health insights

### Operations App
- Purpose: centralized command and operational coordination
- Primary users: operations managers, control room leads
- Core screens: Operations Dashboard, All Sites, All Guards, Shifts, Vehicles, Patrol Monitoring, Control Room, Incidents, Response Teams, Clients, Scheduling, Risk, Reports, Analytics, AI Operations Centre
- Permissions: multi-site, incident coordination, dispatch oversight
- Offline capability: low/medium
- AI features: incident triage, response recommendations, risk scoring

### HR App
- Purpose: workforce lifecycle and compliance management
- Primary users: HR managers, HR officers
- Core screens: Employees, Recruitment, Contracts, Training, Disciplinary, Leave, Payroll, Performance, Documents, PSIRA, Medical, Firearm, AI HR Assistant
- Permissions: employee lifecycle, sensitive records, approvals
- Offline capability: low
- AI features: compliance health, training recommendations, resignation risk

### Finance App
- Purpose: financial control and revenue operations
- Primary users: finance managers, finance officers
- Core screens: Dashboard, Invoices, Debtors, Creditors, Payroll, GL, Budgets, Assets, Bank, Purchasing, Reports
- Permissions: financial records, approvals, exports, reconciliation
- Offline capability: low
- AI features: anomaly detection, forecasting, financial summaries

### Executive App
- Purpose: strategic oversight and performance monitoring
- Primary users: general managers, regional heads
- Core screens: Executive Dashboard, Revenue, Profit, Contracts, Operations, Risk, Clients, Employee Health, Growth, Compliance, AI Executive Advisor
- Permissions: company-level summaries, reporting, decision support
- Offline capability: low
- AI features: summary briefs, KPI insights, risk analysis

### Board App
- Purpose: governance and board reporting
- Primary users: board members, directors
- Core screens: Board Portal, Board Pack, Monthly Reports, Financial Statements, Risk Register, Audit Findings, AI Strategic Summary, Meeting Minutes, Voting, Approvals
- Permissions: read-only or approval-focused governance views
- Offline capability: low
- AI features: strategic summary, risk digest, meeting briefing

### Client Portal
- Purpose: service visibility for customers
- Primary users: client admins, client contacts
- Core screens: My Sites, Incidents, Visitors, Guard Attendance, Invoices, Reports, Maintenance, Service Requests, AI Chat
- Permissions: client-scoped data only
- Offline capability: medium
- AI features: service summaries, incident help, report Q&A

### Technician App
- Purpose: installations, maintenance, and support operations
- Primary users: technicians, installers, field support
- Core screens: Installations, Maintenance, Inventory, Checklists, Devices, Work Orders, AI Troubleshooting
- Permissions: assignment-based and service-area scoped
- Offline capability: high
- AI features: troubleshooting, work-order guidance

### SOC Command Center
- Purpose: live monitoring and incident response
- Primary users: SOC operators, control room operators
- Core screens: National Map, Active Guards, Incidents, Vehicles, LPR, CCTV, AI Alerts, Weather, Panic Events, Response Teams
- Permissions: real-time operational monitoring and response
- Offline capability: low/medium
- AI features: anomaly detection, alert summarization, response intelligence

### Admin Console
- Purpose: platform administration and governance
- Primary users: administrators, platform owners
- Core screens: Companies, Users, Roles, Permissions, Integrations, Audit Logs, API, Feature Flags, Licensing, Settings
- Permissions: platform governance and security administration
- Offline capability: low
- AI features: admin guidance, audit patterns, anomaly review

## Application Profile Rules
- Every user may have one or more application profiles.
- Application profiles are assigned based on identity, role, scope, and business context.
- Navigation, widgets, reports, AI assistants, and notification rules are composed from the active profile.
- A user can move between profiles without changing the underlying permissions model.

## Implementation Guidance
- Each application profile should declare:
  - Purpose
  - Users
  - Navigation
  - Widgets
  - Permissions
  - AI abilities
  - Reports
  - Notifications
  - Offline capability
  - Landing page
  - KPIs

## Approval Checklist
- Application profile inventory approved
- Profile-to-permission mapping reviewed
- Landing page and navigation patterns documented
- Cross-profile switching rules approved
