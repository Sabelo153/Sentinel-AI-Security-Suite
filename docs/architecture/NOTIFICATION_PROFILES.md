# Sentinel Notification Profiles

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define notification behavior by persona so each experience receives the right alerts and workflows.

## Notification Profile Model
Each notification profile defines:
- delivery channels
- priority rules
- escalation rules
- quiet hours and preferences
- alert categories
- acknowledgement expectations

## Notification Profiles by Persona

### Guard Notification Profile
- Critical incidents
- Shift reminders
- Visitor check-in alerts
- Emergency notifications
- Training reminders

### Site Operations Notification Profile
- Patrol issues
- Incident changes
- Visitor and vehicle alerts
- Maintenance issues
- Site score changes

### Regional Operations Notification Profile
- Multi-site incidents
- Risk escalation
- Fleet or device alert surges
- Client service issues

### Executive Notification Profile
- KPI changes
- Risk escalation
- Compliance exceptions
- Strategic alerts

### Finance Notification Profile
- Invoice anomalies
- Payroll approvals
- Reconciliation issues
- Budget exceptions

### Board Notification Profile
- Governance and audit notices
- Strategic risk updates
- Meeting and approval reminders

### Client Notification Profile
- Incident updates
- Service requests
- Report availability
- Invoice notices

### Technician Notification Profile
- Work-order assignments
- Device issues
- Maintenance reminders

### Admin Notification Profile
- System health problems
- Integration failures
- Audit and license notices

## Governance Rules
- Notification delivery must honor persona scope and permission boundaries.
- High severity operations alerts should escalate automatically where appropriate.
- Notification preferences should be configurable per user and persona.

## Approval Checklist
- Persona notification patterns approved
- Escalation rules reviewed
- Channel and delivery rules documented
