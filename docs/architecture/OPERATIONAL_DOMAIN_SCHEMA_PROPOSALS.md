# Operational Domain Schema Proposals

**Status:** APPROVED - canonical SQL implemented in `056_incidents.sql` through `062_notifications.sql`
**Tenant key:** `company_id`

These proposals define the minimum relationships for the schema-first domains. They do not authorize production migrations or application implementation.

## Approved Implementation Notes

The approved schema uses `company_id` as the only tenant discriminator and the existing `employee_id`, `employee_assignment_id`, `shift_assignment_id`, `site_id`, `user_id`, and `patrol_session_id` identities. No `guard_id` identity is introduced. Incident, access-event, occurrence-entry revision, and notification-delivery history is append-oriented. Operational parents use `RESTRICT` where removal would erase evidence; optional actor and subject references use `SET NULL` to preserve history.

## Incidents

### `incidents`

- `incident_id` primary key
- `incident_uuid` unique
- `company_id` required
- `site_id` required
- `employee_id` nullable
- `shift_assignment_id` nullable
- `patrol_session_id` nullable
- `incident_number` unique per company
- `incident_type` and `incident_category`
- `severity`: `LOW`, `MEDIUM`, `HIGH`, `CRITICAL`
- `status`: `OPEN`, `INVESTIGATING`, `RESOLVED`, `CLOSED`, `CANCELLED`
- `occurred_at`, `reported_at`
- `description`, `actions_taken`, `resolution`
- `created_by_user_id`, `updated_by_user_id`
- `created_at`, `updated_at`, `deleted_at`

Rules:

- Site, employee, shift, and patrol session must belong to the same company.
- Shift and patrol session relationships are optional but must be validated when supplied.
- Incident history is retained; closing is a state transition, not deletion.
- Evidence belongs in a separate `incident_attachments` table with company and incident foreign keys.

## Occurrence Book

### `occurrence_entries`

- `occurrence_entry_id` primary key
- `occurrence_entry_uuid` unique
- `company_id` required
- `site_id` required
- `employee_id` nullable
- `shift_assignment_id` nullable
- `entry_reference` unique per company
- `entry_type`
- `occurred_at`
- `entry_text`
- `handover_required` boolean
- `handover_reference` nullable
- `created_by_user_id`
- `created_at`, `updated_at`

### `occurrence_entry_revisions`

- immutable revision ID
- company and occurrence-entry foreign keys
- revision number
- previous text/details
- revision reason
- revised by user
- revised at

Rules:

- Entries are append-only evidence.
- Corrections create revisions; arbitrary update and delete are prohibited.
- Site/date/shift indexes support handover and daily-book queries.

## Visitors

### `visitors`

- `visitor_id` primary key
- `visitor_uuid` unique
- `company_id` required
- name fields
- ID/passport reference stored with a documented protection policy
- contact details
- status and timestamps

### `visitor_visits`

- `visitor_visit_id` primary key
- `company_id` required
- `visitor_id` required
- `site_id` required
- `host_employee_id` nullable
- authorization user and status
- purpose
- planned arrival/departure
- actual arrival/departure
- visit status: `PRE_REGISTERED`, `AUTHORIZED`, `CHECKED_IN`, `CHECKED_OUT`, `CANCELLED`, `DENIED`
- timestamps and audit user fields

Rules:

- Visitor identity and visit history are separate.
- A visit cannot check in at a cross-company site.
- Current visitors query only includes checked-in, non-closed visits.
- Sensitive ID data must not appear in broad list projections.

## Vehicles and Access Control

### `vehicles`

- `vehicle_id` primary key
- `vehicle_uuid` unique
- `company_id` required
- registration number unique per company
- make/model/color optional
- owner type/reference only when a canonical owner relationship exists
- status and timestamps

### `access_events`

- `access_event_id` primary key
- `company_id` required
- `vehicle_id` nullable
- `visitor_visit_id` nullable
- `employee_id` nullable
- `site_id` required
- `gate_post_id` nullable until a canonical gate/post model exists
- authorization status
- entry/exit timestamps
- denied reason
- created by user and audit timestamps

Rules:

- At least one subject reference is required: vehicle, visitor visit, or employee.
- Site and all subject references must belong to the same company.
- Access history is append-only; corrections use reversal/adjustment events.
- Do not use `posts` as gates until the domain decision explicitly establishes that relationship.

## Inspections

### `inspections`

- `inspection_id` primary key
- `inspection_uuid` unique
- `company_id` required
- `site_id` required
- `inspector_employee_id` or inspector user reference
- inspection type
- scheduled/started/completed timestamps
- status: `DRAFT`, `IN_PROGRESS`, `COMPLETED`, `REVIEWED`, `CANCELLED`
- findings and outcome summary
- corrective action summary and follow-up date
- created/updated users and timestamps

### `inspection_items`

- item ID and inspection foreign key
- checklist code/label
- result: `PASS`, `FAIL`, `NA`, `OBSERVATION`
- finding, corrective action, due date, resolved timestamp
- evidence reference where needed

Rules:

- Inspector, site, and all evidence are company-scoped.
- Completion requires item results according to the inspection template.
- Supervisor review is a state transition with an audit record.

## Notifications and Alerts

### `alerts`

- `alert_id` primary key
- `company_id` required
- event type and severity
- related entity type/id
- message and structured payload
- status: `OPEN`, `ACKNOWLEDGED`, `RESOLVED`, `DISMISSED`
- assigned/acknowledged/resolved users and timestamps

### `notifications`

- `notification_id` primary key
- `company_id` required
- recipient user ID
- alert reference nullable
- event type, severity, message
- read state and read timestamp
- created and sent timestamps

### `notification_queue`

- queue ID and company ID
- notification reference
- channel: `IN_APP`, `EMAIL`, `SMS`, `WHATSAPP`
- delivery status and retry counters
- next attempt, sent, and failure timestamps

Rules:

- Internal notifications are implemented first.
- Every alert and notification is company-scoped.
- Queue payloads must be redacted and must not contain credentials or secrets.
- Deduplication keys prevent repeated alerts for one state transition.
- Escalation rules belong in a separate policy table or rule-engine integration, not embedded in notification rows.
