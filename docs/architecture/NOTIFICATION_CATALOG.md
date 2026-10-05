# Sentinel Notification Catalog

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Standardize how the platform communicates operational, approval, compliance, and AI-driven events to the right users at the right time.

## Notification Principles
- Notifications are event-driven and auditable.
- Every notification must have a severity, channel, owner, and acknowledgement rule.
- Critical operational alerts must support escalation and tracking.
- Sensitive notifications must respect field-level and scope-level access controls.

## Notification Types

| Type | Trigger | Primary Channels | Typical Recipients |
|---|---|---|---|
| Operational Alert | Incident, patrol miss, late attendance, equipment issue | In-app, push, email, SMS | Supervisor, control room, site lead |
| Approval Request | Leave, promotion, invoice, procurement, incident closure | In-app, email, push | Approver, manager |
| Compliance Reminder | License, certificate, training, medical expiry | Email, push, in-app | Employee, HR, supervisor |
| Escalation Notice | SLA breach, repeated exception, high severity event | In-app, SMS, email | Operations lead, admin |
| AI Recommendation | Risk model output, anomaly detection, insight summary | In-app, email | Assigned user |
| System Event | Sync failure, integration issue, device offline | In-app, email | Admin, integration owner |
| Audit Notice | Sensitive change or break-glass action | In-app, email | Security admin |
| Client Communication | Service issue or report delivery | Email, portal notification | Client contact |

## Standard Notification Envelope
- notification_id
- event_id
- recipient_user_id
- tenant_id
- template_key
- severity
- channel
- status
- created_at
- acknowledged_at
- expires_at

## Routing Rules
- Operational alerts route to nearest responsible team by site and shift.
- Approval requests route by role hierarchy and delegated authority.
- Compliance notices route to both the individual and the owning department.
- High-risk notifications must be retried and logged for audit review.

## Delivery Rules
- Critical notifications must be delivered within 60 seconds where infrastructure allows.
- Non-critical reminders should be batched and sent according to user preferences.
- Offline devices must queue notifications and sync when connectivity returns.

## Approval Checklist
- Notification templates approved by domain owners
- Channel preferences captured in user profile
- Acknowledgement and escalation behaviors defined
- Notification retention and privacy requirements approved
