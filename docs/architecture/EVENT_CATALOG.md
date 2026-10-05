# Sentinel Enterprise Event Catalog

Status: Draft v1.0 (constitution artifact 5 of 7)
Last updated: 2026-08-06

## Purpose
Define all domain events and event contracts so platform applications remain loosely coupled and automation-first.

## Event Naming Standard
- Use dot-separated, lowercase, past-tense event names.
- Format: `object.event_action`
- Example: `guard.checked_in`, `incident.escalated`, `payroll.approved`
- Do not mix naming styles for the same business action.

## Event Dictionary Entry Standard
Each event entry must include:
- event_name
- business_purpose
- producer
- payload_schema
- consumers
- idempotency_key
- retry_policy
- audit_requirements

Entry example:
- event_name: `incident.created`
- payload_schema:
	- incident_id
	- created_by
	- site_id
	- gps
	- occurred_at
	- priority
- consumers:
	- reporting
	- notifications
	- AI service
	- timeline
	- audit

## Required Event Envelope
Every event includes:
- event_id
- event_name
- occurred_at
- company_id
- actor_id
- source_application
- aggregate_type
- aggregate_id
- correlation_id
- payload_version
- payload

## Core Event Set
- employee.created
- employee.updated
- employee.transferred
- employee.terminated
- guard.checked_in
- guard.checked_out
- guard.location_updated
- guard.patrol_started
- guard.patrol_completed
- guard.inactivity_detected
- guard.panic_triggered
- patrol.missed_checkpoint
- incident.created
- incident.assigned
- incident.closed
- vehicle.location_updated
- inspection.completed

## Producer/Consumer Principles
- Producers publish facts only.
- Consumers apply side effects independently.
- No direct service coupling required between domains.

## Reliability Rules
- Idempotent consumers are mandatory.
- Retry and dead-letter strategy required.
- Replay support required for reporting and reprocessing.

## Approval Checklist (Artifact 5)
1. Event naming conventions approved.
2. Mandatory envelope fields approved.
3. Core event set approved by domain owners.
4. Producer and consumer ownership documented.
5. Reliability and replay strategy approved.

Approval state:
- Draft until all core domains map events.
- Approved v1.0 when architecture + integration leads sign off.
