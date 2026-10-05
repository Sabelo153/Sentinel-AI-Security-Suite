# Sentinel Persona Catalog

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Catalog the platform personas and their expected experience, permissions, and runtime behavior.

## Persona Inventory

### Guard
- Purpose: day-to-day field execution
- Landing screen: Home
- Default dashboard: Guard Dashboard
- Navigation profile: Guard Navigation
- AI profile: Guard AI Assistant
- Notification profile: Critical incidents, shift reminders, incident updates
- Offline profile: High

### Site Operations
- Purpose: day-to-day site management and oversight
- Landing screen: Site Dashboard
- Default dashboard: Site Dashboard
- Navigation profile: Site Manager Navigation
- AI profile: Site AI Assistant
- Notification profile: Patrol issues, incidents, visitors, maintenance alerts
- Offline profile: Medium

### Regional Operations
- Purpose: cross-site operational oversight
- Landing screen: Operations Dashboard
- Default dashboard: Operations Dashboard
- Navigation profile: Operations Navigation
- AI profile: Operations AI Assistant
- Notification profile: Multi-site incidents, risk, fleet, response status
- Offline profile: Low

### Executive
- Purpose: strategic operational visibility
- Landing screen: Executive Dashboard
- Default dashboard: Executive Dashboard
- Navigation profile: Executive Navigation
- AI profile: Executive AI Assistant
- Notification profile: KPI risk, client health, compliance alerts
- Offline profile: Low

### Finance Review
- Purpose: financial review and decision support
- Landing screen: Finance Dashboard
- Default dashboard: Finance Dashboard
- Navigation profile: Finance Navigation
- AI profile: Finance AI Assistant
- Notification profile: invoice, payroll, budgeting, reconciliation alerts
- Offline profile: Low

### Board Member
- Purpose: governance and board reporting
- Landing screen: Board Dashboard
- Default dashboard: Board Dashboard
- Navigation profile: Board Navigation
- AI profile: Board AI Assistant
- Notification profile: governance, risk, audit, financial summary
- Offline profile: Low

### Client Portal
- Purpose: customer-specific service visibility
- Landing screen: Client Dashboard
- Default dashboard: Client Dashboard
- Navigation profile: Client Navigation
- AI profile: Client AI Assistant
- Notification profile: incidents, service requests, reports
- Offline profile: Medium

### Technician
- Purpose: installations, maintenance, support
- Landing screen: Technician Dashboard
- Default dashboard: Technician Dashboard
- Navigation profile: Technician Navigation
- AI profile: Technician AI Assistant
- Notification profile: work orders, device issues, maintenance alerts
- Offline profile: High

### Administrator
- Purpose: platform administration and governance
- Landing screen: Admin Dashboard
- Default dashboard: Admin Dashboard
- Navigation profile: Admin Navigation
- AI profile: Admin AI Assistant
- Notification profile: audit, integration, system health, licensing
- Offline profile: Low

## Persona Metadata Fields
Each persona should define:
- code
- name
- description
- icon
- landing_screen
- dashboard_id
- theme
- navigation_profile_id
- notification_profile_id
- ai_profile_id
- offline_profile_id
- active

## Approval Checklist
- Persona inventory approved
- Persona metadata model reviewed
- Landing screen and dashboard mapping documented
- Security restrictions and scope rules confirmed
