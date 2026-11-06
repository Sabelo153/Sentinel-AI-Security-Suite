# Sentinel 0.9 site assurance

This extends [0.8 control-room operations](CONTROL_ROOM_0_8.md). The source adds
site instructions, shift handovers, competency tracking, investigation evidence,
service-level reporting, maintenance and controlled retention. It does not deploy
a server, certify evidence, verify physical guard activity or perform cleanup on
an existing company's data.

## Deployment and access

Back up and rehearse restoration on staging. Apply `069_site_assurance.sql`,
`070_photo_reports.sql` and `071_client_report_delivery.sql` after schemas 066–068,
then deploy matching PHP/admin assets, Composer dependencies and Android 0.9.0
(version code 17). See [reporting and offline delivery](REPORTING_AND_OFFLINE_0_9.md)
for NFC setup, full offline patrols, printable photo reports and scheduled portal/email
summaries. Seed the updated operational permissions for each company and explicitly
assign roles. Existing roles receive no new automatic grants. For fresh signups,
the plan policy filters the owner's permissions using the registered API metadata.
Update the admin assets together, including `site-assurance.js`.

| Area | Permissions | Plan |
|---|---|---|
| Instructions | `site_instructions.view`, `site_instructions.manage` | All |
| Handovers | `handovers.view`, `handovers.manage` | All |
| Training | `training.view`, `training.manage` | Essential and above |
| Evidence | `evidence.view`, `evidence.manage`, `evidence.export` | Professional and above |
| Service levels | `service_levels.view`, `service_levels.manage` | Essential and above |
| Client service levels | `service_levels.client_read` plus a client mapping | All |
| Maintenance | `maintenance.view`, `maintenance.manage` | Essential and above |
| Retention | `retention.view`, `retention.hold`, `retention.manage` | All |

Retention management and hold release require a session authenticated with MFA,
as well as the permission. Creating a hold is separately authorised, so an
investigator can preserve records without being allowed to delete them. Keep
evidence export distinct from metadata access. Ordinary client portal access
does not automatically expose the new service-level report: explicitly grant
`service_levels.client_read` and map the account to its client.

Guards use `guard.self` to read only site instructions for their assigned current
or upcoming duties, acknowledge current revisions, send their own handovers,
accept handovers addressed to their receiving shift and read their own training
validity. They cannot read company training, evidence or retention dashboards.

## Site instructions and handovers

Publishing an instruction creates an immutable revision containing procedures
and emergency contacts. The caller supplies the displayed previous revision;
stale updates fail. A guard's acknowledgement is tied to one exact revision and
user. Publishing a newer revision requires another acknowledgement. This records
receipt, not comprehension or training certification. Verify contact details
and physically rehearse procedures separately.

Android caches the latest fetched duty snapshot through its account-scoped
encrypted store. On network failure it labels the copy OFFLINE and shows its
fetch time. Cached information may be outdated and does not permit acknowledgement
or handover acceptance. There is no claim that an offline phone knows a new
revision exists. Sign-in and revoked access still require server contact.

Handovers link successive shifts for different guards at the same site, within
24 hours of the outgoing shift end. Record keys, equipment checks, open incident
IDs and outstanding tasks. The receiving guard accepts using their own account,
with discrepancy/check notes, from one hour before their shift through its end.
Records identify the author and accepting account; a manager can record an
outgoing handover, but cannot impersonate the receiving guard's acceptance.
Physically count keys and test equipment; the checklist does not transfer or
verify hardware automatically. After an uncertain network result, refresh history
before submitting again. These handovers require a live connection and do not
join the encrypted offline movement/report queue.

## Training and maintenance

Define competencies and site requirements, then record immutable employee
qualification dates and references. Manual verification makes a record eligible
to satisfy a requirement. Renewals are new records; revocation preserves history.
The gap dashboard checks assigned duties in the next 30 days and requires a
verified, unrevoked qualification valid through the entire shift, including an
overnight end date. It flags gaps; it does not cancel shifts, check an external
accreditation service or replace a competency assessment. Large rosters exceeding
2,000 shift/requirement combinations fail with an explicit limit message.

Fault tickets cover radios, gates, cameras, vehicles and other assets, with an
optional same-company equipment reference and an active assignee. Transitions
are ASSIGNED → IN_REPAIR → AWAITING_VERIFICATION → VERIFIED, or cancellation with
notes. Each transition checks a revision and appends actor/time/notes history.
Verification requires an operator other than the assignee and the person who
recorded the repair. No contractor, hardware-control or purchasing integration
is configured by this feature.

## Evidence and investigation packs

Upload an original file of at most 256 KiB with capture time and description.
The server stores its exact bytes as base64, byte count, SHA-256, receiving time
and actor. Originals have no editing endpoint. A correction is another original
linked to the earlier evidence in the same incident. The application checks
bytes/count/hash before download or pack export and records access. A failed
integrity check prevents the download and preserves a failure receipt. Downloads
use an attachment, generic binary MIME type and `nosniff`; files are not executed
or displayed inline.

An investigation JSON pack includes up to 50 originals, incident details and
access receipts. Base64-decode each included file and verify its SHA-256. Existing
mobile report photos remain separate protected records; import an original into
the evidence workflow when appropriate and include any separately exported photos
in the investigation process. File hashes and database receipts are not independent
timestamps, digital signatures or protection against an administrator rewriting
both data and hashes. Protect database/storage access, use encrypted backups and
rehearse restoration. Apply legal or contractual evidence handling requirements
through the company's own approved procedure.

## Service-level dashboard

Define effective-dated daily site targets for response minutes, patrol count and
elapsed guard minutes. New effective dates retain earlier target rows. Reports
cover up to 31 days and use each day's effective target. They show completed
attendance overlap with that UTC day, currently completed patrols by start date,
server-received dispatch arrival intervals, late arrivals against the target,
and assignments overdue against their dispatch deadline without an arrival.
Invalid attendance longer than 48 hours is excluded. Today is partial; missing
targets or response samples remain unknown, not successful or zero-duration.

These are operational observations, not independent physical presence/arrival
verification or billing calculations. Corrections to underlying records can
change reports. Mapped clients with the explicit client-read permission see only
aggregate reports for their own sites; no employee identities, incident narratives
or evidence are included. Managers can also continue sharing reviewed summaries
through the existing approved-report portal.

## Retention and holds

Nothing is deleted automatically. Configure an explicit policy (7–3,650 days),
generate a preview, review its category/action/cutoff/identifiers, then supply
the one-time preview code and exact phrase `DELETE EXPIRED DATA` in the separate
execution form. Previews are bound to the tenant and operator, expire in 15
minutes and contain at most 500 records. Eligibility, holds and policy are
rechecked in the execution transaction. A change requires another preview;
the code cannot be reused. A failed audit write rolls back cleanup and preview
consumption. Tenant mutation locking serializes HTTP changes with these checks;
measure contention and rehearse concurrency on MySQL before rollout.

| Category | Action and scope |
|---|---|
| `gate_records` | Delete old gate movement ledger records by original event time. |
| `report_photos` | Delete old mobile report photo rows; textual reports remain. |
| `location_history` | Delete old `gps_locations` records. |
| `location_events` | Delete old `gps_events` rows, which may contain location payloads. |
| `incident_evidence` | Remove original file bytes and descriptive fields; retain hash, identifiers, purge time and access receipts. |
| `visitor_profiles` | Redact names, contacts and external reference, archive the profile and retain IDs and visit/access relationships. Profiles with active planned/authorised/checked-in visits are skipped. This is profile redaction, not erasure of all visitor-related data. |

A hold can protect one record or an entire category. An incident hold additionally
protects all existing and later evidence originals and mobile incident photos
linked to that incident. Release requires MFA, permission and a reason. Holds
are not permission to rewrite/delete records and do not claim to preserve
unrelated telemetry. Configure location history and event policies separately.

This feature does not erase exports, downloaded files, mobile/offline caches,
backups, text in incidents/visits, access events or every other source of personal
data. Do not present it as a complete privacy-compliance or subject-erasure system.
Review the company's obligations and retention periods before enabling cleanup.
Test only disposable records until backup restoration, investigation holds,
audit failure rollback and concurrency have been accepted.

## Staging acceptance

Exercise two tenants and separate manager, viewer, investigator, client and guard
accounts. Test concurrent instruction revisions, outdated acknowledgements,
handover discrepancies/reassignments, overnight qualification expiry, independent
repair verification, original/corrupted file exports, client mapping, held cleanup,
stale/expired previews, policy changes and audit failure. Check Android offline
labels, account switching, lost responses, device-owner recovery and emergency
phone/radio procedures on actual supported handsets. Continue using the 0.8
control-room and escalation workers; this release adds no automatic deletion job.
