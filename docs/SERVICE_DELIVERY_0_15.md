# Sentinel 0.15: service delivery and site operations

This release adds eleven connected workflows through eighteen online browser
workspaces. It uses existing incidents, dispatch, quotations, mobilisation tasks,
maintenance, custody, attendance approval, permissions and tenant/site controls.
Android 0.10 and existing offline QR/NFC patrol/inspection capture remain compatible.
No new native package or Google/Play Store dependency is required.

## Alarm response tracking

Record receipt against an open incident with a unique source reference and a UTC
receipt time within the last 30 days. Assign an active site-authorised responder
and a response deadline. This creates the normal dispatch job, preserving existing
control-room overdue-response monitoring. My alarm responses lets only that
responder accept and record arrival. Staff close a confirmed or false alarm only
after arrival; false alarms require a cause. Cancellation records a reason and
cancels an outstanding dispatch. Alarm closure does not close the incident.

The view shows dispatch states, receipt-to-arrival seconds, outcome/cause and
history. An absent arrival remains unknown. This records staff actions; it is not
an alarm hardware receiver, independently verified arrival or emergency-service
call. Existing scoped alarm API intake can create the incident; an operator then
records the panel reference/receipt and dispatches through this workflow.

## Guard transport

Plan a site route with vehicle registration, capacity, driver/operator, departure,
pickup grace and notes. Add active employees with pickup locations/times during
the 12 hours following route departure. Capacity and duplicate passenger checks
apply. Assigned guards see their own pickup entries, without other passenger data.

Start after departure time. Operators confirm boarding, alighting or a missed
pickup; missed status cannot be recorded before the pickup grace expires. Inactive
employees cannot board a new trip. All expected/boarded passengers must be accounted
for before finishing. Planned runs can be cancelled. Missing expected pickups on
an active run create deduplicated in-app supervisor reminders. Vehicle/driver names
are operator records, not automatic fleet allocation, GPS tracking or licensing
verification; coordinate vehicle availability using the existing fleet workspace.

## Effective post orders

Publish an immutable site edition containing duties, contacts, effective date and
review date. Later editions cannot precede the most recently published effective
date. My post orders requires current/upcoming assigned duty or an explicit site
grant, as well as any account-level restriction. Future editions are hidden from
learners until effective. Acknowledgement records the exact latest effective
content hash; previous editions and acknowledgements remain in history. These
add effective dates to the existing instruction capabilities without rewriting
historical instruction acknowledgements.

## Business continuity

Publish site scenarios for POWER, NETWORK, STAFFING or OTHER, each with steps and
a fallback contact. Activate a scenario from the current effective edition with
a reason, then record step completion. Closure requires all steps and a debrief;
cancellation records the reason. Activations keep the edition and steps used.
Assigned guards can read/acknowledge effective continuity plans. Review reminders
join the existing business worker. This coordinates human recovery; it does not
switch power/network infrastructure, provide offline access to these new pages,
or replace existing offline patrol queues and emergency procedures.

## Equipment inspections

Schedule an existing equipment asset at a site with checklist, frequency (1–365
days), assigned inspector and next due time. Only the named inspector submits
explicit yes/no answers. Stable UUIDs protect unchanged retries. Failed checks
create or reuse an open maintenance defect; a pass is blocked until the defect
has been VERIFIED through the existing maintenance workflow. Results preserve
actor, time, checks, notes and linked defect. Schedule reassignment/pause/frequency
changes require the current revision and a reason; checklist text stays immutable.
Overdue active schedules generate in-app reminders. No physical hardware test or
automatic lockout of the equipment is implied.

## Control-room shift handover

Generate a site snapshot of open/investigating incidents, in-progress patrols,
expired patrol windows without a matching completed session, outstanding key/card
custody and unreturned equipment handovers. Patrol window matching respects its
assigned shift when present. Each category is limited to 500 records; an excessive
backlog blocks generation instead of silently omitting records.

Choose a different active site-authorised incoming supervisor and add notes.
Only that person accepts the exact stored snapshot/hash and current revision.
Acceptance records receipt rather than resolving any underlying source. The page
also shows the current picture so the incoming supervisor can compare later
changes. Historical missed windows remain visible until the schedule is reviewed;
this is not a guarantee that an unrecorded patrol did not physically occur.

## Extra guards and event service requests

Clients mapped to the current site client, or authorised staff, request future
coverage lasting up to seven days and specify guard count. Staff issue a one-off
quotation using the existing quotation engine: unit price is per guard for the
whole requested period, not a monthly or hourly rate. Currency minor units and
explicit tax basis points remain visible. Mapped clients accept/decline the exact
shared offer in either quotation or service-request views; decisions synchronize
the request. Changed client/site mappings stop old-client access. One-off quotes
cannot be converted into monthly contracts through the existing conversion route.

Staff link distinct existing shift IDs after acceptance. Every interval in the
requested period must have enough distinct active guards at the correct site.
Completion after the period rechecks coverage and records a staff attestation;
the client confirms fulfilment separately. Cancellation records the reason and
withdraws any still-shared quote. Accepted agreements are not silently rewritten.
This does not create shifts, verify physical attendance, dispatch guards, raise an
invoice or automatically send email. Review agreed cancellation and billing terms.

## Client onboarding and mobilisation

Use existing accepted quotations to create the contract/site and seven mobilisation
tasks. Complete those evidence-backed tasks, then prepare a readiness packet with
emergency contacts and a site-survey reference. The packet fingerprints the exact
contract, site, tasks, contacts and survey reference. A mapped client accepts or
declines it. Later changes require a freshly prepared packet and new approval.

Activation uses the existing site/contract activation operation. When a client
packet exists, that older endpoint also enforces current approval; it cannot bypass
the new review. This release retains legacy mobilisation behaviour for existing
onboardings without a packet. The new readiness workspace requires a packet before
activation. Contacts and survey are operator-supplied records, not independent site
validation or a public client signup flow.

## Subcontractor management

Register the company, registration/contact details, licence and insurance references
and expiry dates. Approve sites only through the earlier of both document expiries.
Every company update increments a revision; site approvals must be reviewed against
the new revision. Site approvals can be revoked immediately. Suspension, expiry,
missing approvals or removal of the restricted-role marker deny subsequent access.
Document references are reviewed evidence, not a licensing-authority lookup.

Use separate active individual accounts with site_scope.restricted and explicit
user_site_grants. Linking a company does not create broad roles, client mappings
or site grants. Existing issued API credentials from a newly linked account are
revoked. Existing own-key and own-fleet views/actions now also check current site
access. Disabling a contractor account deactivates it rather than removing its
link and accidentally restoring normal employee access. Only an account already
linked to that company can be disabled here. Global subcontractor administration
and operational analytics are blocked for restricted accounts.

The access policy fails closed if its security tables are unavailable. **Apply
schema 077 before deploying the updated authorization code.** A failed migration
must be corrected before operational access can resume. Password-change access
retains its existing route rules; expired documents do not grant other access.

## Site risk assessments

Publish one or more hazards with likelihood and impact (1–5), controls and an active
site-authorised control owner. Score = likelihood × impact: HIGH at 15–25, MEDIUM
at 8–14, LOW at 1–7. Editions have effective/review dates and immutable history.
Currently mapped clients acknowledge the exact latest effective edition. Site/client
reassignment stops the previous client's access. The matrix is a planning aid;
competent staff must review site-specific risks, controls and legal requirements.
Acknowledgement is not independent certification that controls work.

## Operational analytics

Compare sites over at most 31 inclusive UTC days. Metrics show recorded patrol
sessions/completions, known dispatch-to-arrival times, recurring incident categories
and approved working/overtime estimates. Missing arrivals are UNKNOWN, not zero.
Patrol completion describes recorded sessions, not all required/unrecorded patrols.

Attendance uses only current approval fingerprints. Missing, invalid, stale,
unapproved or overlapping records are excluded and counted. Full UTC weeks around
the requested dates are loaded so a partial-week view does not reset the threshold.
Estimated overtime is allocated chronologically once each employee exceeds the
chosen weekly threshold (default 2,880 minutes), then attributed to the relevant
site and clipped to the selected days. It excludes breaks, pay multipliers, local
legal rules and payroll authorisation. At most 10,000 attendance records and fewer
than 500 sites are supported per query; larger ranges fail visibly. This global
report is not available to site-restricted accounts.

## Installation, permissions and workers

Apply database/schema/077_service_delivery.sql after 001–076, then deploy matching
services/routes/admin assets. Fresh installs include it through 000_run_all.sql.
Run the tenant-specific operational permission seed and grant roles explicitly.
Existing roles/templates are not silently expanded. Newly created pilot Guard,
Client and Site Supervisor templates include the applicable new permissions allowed
by their plan, with individual site/client assignments still required.

| Domain | Permissions | Plans |
| --- | --- | --- |
| Alarms | alarm_response.view/manage/self | Staff Professional+, own response all known plans |
| Transport | guard_transport.view/manage/self | Staff Essential+, own pickup all known plans |
| Orders/continuity | post_orders.view/manage/self, continuity.view/manage/self | All known plans |
| Equipment/handover | equipment_checks.view/manage, control_handover.view/manage | Essential+ |
| Extra service/mobilisation | extra_services.view/manage/client, client_mobilisation.view/manage/client | Staff Essential+, client all known plans |
| Risk | risk_assessments.view/manage/client | Staff Essential+, client all known plans |
| Partners/analytics | subcontractors.view/manage, operations_analytics.view | Professional+; unrestricted accounts |

Keep the existing per-tenant control-room worker for dispatch deadlines and the
business-operations worker for pickup, inspection, plan-review and subcontractor
expiry reminders. Choose the reminder supervisor in existing Pilot setup.

    php bin/run-control-room.php --company=ID --operator=SERVICE_USER_ID
    php bin/run-business-operations.php --company=ID --operator=SERVICE_USER_ID

The control-room worker requires readiness.manage; the business worker requires
business_ops.run. Use active unrestricted tenant service operators. Reminders are in-app records, with no
new SMS, email, calls, live hardware connection or production worker execution.
The new operational pages do not display sponsor banners. Paid tiers remain ad-free.

## Verification and rollout limits

PHP integration tests exercise all eleven flows, including response ownership,
vehicle capacity/drop-off checks, exact plan acknowledgements, continuity steps,
defect retry/repair gates, incoming handover ownership, one-off quote/coverage,
mobilisation activation gates, current approval analytics, partner expiry/revocation,
legacy self-service scope and audit rollback. Browser tests exercise all eighteen
new workspaces and the existing offline/voice/client/training regressions.

These are tested source additions on the draft review branch. Production MySQL
concurrency, real accounts/grants, printing, hardware and physical handovers still
need a supervised deployment acceptance check. No release-signing credentials or
live client records are included, and no production deployment was performed.
