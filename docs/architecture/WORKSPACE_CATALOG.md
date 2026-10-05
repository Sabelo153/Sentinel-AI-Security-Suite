# Sentinel Workspace Catalog

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Catalog the workspaces that can be activated under each persona and organization context.

## Workspace Inventory

### Operations Centre Workspace
- Persona: Regional Operations / Site Operations
- Purpose: day-to-day monitoring and control
- Primary screens: patrol overview, incidents, shifts, vehicles, site status
- Widgets: incident heatmap, active guards, patrol coverage, risk indicators
- AI: operations assistant
- Notifications: incidents, late guards, patrol issues

### Emergency Response Workspace
- Persona: Regional Operations / Site Operations / SOC
- Purpose: crisis response and incident coordination
- Primary screens: incident command, timeline, response teams, weather, live alerts
- Widgets: active incidents, response teams, alert feed, map view
- AI: emergency response assistant
- Notifications: critical alerts, escalations, geofence events

### Fleet Workspace
- Persona: Regional Operations / Technician / Finance Review
- Purpose: fleet readiness and vehicle operations
- Primary screens: vehicle inventory, inspections, maintenance, fuel, trips
- Widgets: fleet health, overdue inspections, fuel anomalies, vehicle assignments
- AI: fleet intelligence
- Notifications: maintenance due, inspection failures, incidents

### Control Room Workspace
- Persona: Operations / SOC
- Purpose: live monitoring and dispatch
- Primary screens: map, CCTV, alarms, guard location, incidents
- Widgets: live map, incident feed, camera health, active alerts
- AI: threat and event summarization
- Notifications: high severity alerts, panics, cyber alerts

### Financial Review Workspace
- Persona: Executive / Finance Review / Board Member
- Purpose: financial oversight and decision support
- Primary screens: revenue, cashflow, invoices, budgets, payroll, debtor/creditor views
- Widgets: profitability, budget variance, cashflow, approvals
- AI: finance insight assistant
- Notifications: approvals, anomalies, financial exceptions

### Board Meeting Workspace
- Persona: Board Member / Executive
- Purpose: governance and board reporting
- Primary screens: board pack, financial statements, risk register, meeting minutes
- Widgets: board summary, governance exceptions, risk summary, meeting agenda
- AI: board pack assistant
- Notifications: governance alerts, approvals, meeting reminders

### Compliance Workspace
- Persona: HR / Executive / Operations
- Purpose: compliance oversight and audit readiness
- Primary screens: certifications, training, medical, licenses, incidents, audit findings
- Widgets: expiry alerts, compliance score, overdue items, exceptions
- AI: compliance assistant
- Notifications: expiry reminders, audit issues, violations

## Workspace Metadata
Each workspace should define:
- workspace_id
- workspace_name
- persona_id
- landing_screen
- navigation_profile_id
- dashboard_id
- widget_set
- report_set
- ai_profile_id
- notification_profile_id
- theme
- offline_profile_id

## Approval Checklist
- Workspace inventory approved
- Persona-to-workspace mapping reviewed
- Dashboard and widget requirements documented
