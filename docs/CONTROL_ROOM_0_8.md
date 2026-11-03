# Sentinel 0.8 control-room operations

## Rollout

This extends the 0.7 field release. Apply `068_control_room.sql` after schemas
066 and 067, deploy the backend and admin assets together, and build Android
0.8.0 (version code 16). See [the 0.7 rollout](FIELD_RELEASE_0_7.md) and
[private APK distribution](APK_DISTRIBUTION.md). Android still works without
Play Store, a Google account, Firebase or Google Play services.

Seed the updated operational permissions for each company, then explicitly
assign roles. New permissions are not automatically granted to existing roles:

| Area | Permissions | Plan |
|---|---|---|
| Dispatch | `dispatch.view`, `dispatch.manage` | Professional and Enterprise |
| Lone-worker plans | `lone_worker.view`, `lone_worker.manage` | Professional and Enterprise |
| Coverage and relief | `rostering.view`, `rostering.manage` | Essential and above |
| Gate register | `gate_register.view`, `gate_register.manage` | All plans |
| Invoice drafts and rates | `billing.view`, `billing.manage` | Essential and above |
| Phone health | `device_health.view` | All plans |
| Readiness and backup evidence | `readiness.view`, `readiness.manage` | All plans |

Guards use `guard.self` to read their own response assignments, accept/arrive,
submit live check-ins, report phone health and queue gate captures for their own
assigned shifts. They do not need manager permissions. Manager web pages are
under Company workspace. Refresh snapshots for current status; no push or
automatic alarm delivery is implied.

Schedule both workers every minute using a dedicated active same-company user:

```sh
php bin/run-control-room.php --company=ID --operator=SERVICE_USER_ID
php bin/run-escalations.php --company=ID --operator=SERVICE_USER_ID
```

Run control-room evaluation before escalation. The former requires
`readiness.manage`; the latter requires `escalations.manage`. Add an active
escalation rule with primary and backup users; each recipient needs
`notifications.view`. Control-room operators need readiness viewing and
acknowledgement permissions. Existing escalation notifications remain in-app
only. Acknowledging a readiness signal stops it appearing as a new source for
escalations; already-created escalation events must be acknowledged separately.
Monitor workers' process exits through the deployment scheduler as well as the
readiness screen. A database outage may prevent a failure receipt from being
saved. Missing first receipts show UNKNOWN, then receipts older than five
minutes show STALE. Keep independent radio/telephone emergency procedures.

## Operational workflows

- **Dispatch:** select an open incident, active responder and arrival deadline
  within 24 hours. Only one active response is permitted per incident. Acceptance
  precedes arrival; closure requires a resolution. Each transition checks the
  displayed revision. Guards can act only on their own response. An overdue
  arrival generates a deduplicated readiness signal. Dispatch closure does not
  automatically close the incident. Times record server acknowledgement, not
  independently verified physical arrival or GPS tracking.
- **Lone worker:** create a plan for the guard account assigned to a current or
  upcoming shift. Share its one-time randomly generated duress code privately;
  only a password hash is stored. `OK` records a normal live check-in. The duress
  code returns the same confirmation while creating a restricted control-room
  signal immediately. The worker detects missed deadlines, including missed
  check-ins still outstanding when a shift ends. An operator ends the plan
  after reviewing welfare. There is no background handset alarm, fall detection,
  local reminder, SMS delivery or covert physical-device certification in this
  release. Check-ins require a live connection; failures are never represented
  as received. Test company response procedures before relying on this feature.
- **Rostering:** existing Shift assignments schedule guards. Add a required
  window for an active post and headcount; coverage computes the minimum distinct
  scheduled employees throughout every segment, so partial coverage does not
  hide gaps. This is planned coverage, not attendance. Relief checks the displayed
  original employee, active replacement and overlap, retains a reason/history,
  and rejects shifts with attendance or an open lone-worker plan. Use the
  attendance workflow for handovers after a shift has started.
- **Gate register:** record visitor/delivery contact, optional registration,
  purpose, direction, authorisation and original event time. This movement ledger
  complements existing visitors, vehicles and pre-registered visits; it does not
  automatically merge identities or grant entry. Manager capture is online.
  Android gate capture uses the existing account-scoped encrypted retry queue,
  original UUID and timestamp. Its site comes from the guard's assigned shift.
  The event must fall inside that shift and be no more than 30 days old when
  first received. PENDING is the safe default. Offline capture cannot query
  current blocklists or validate authorisation. Capture and receipt times remain
  separate. Protect the phone and follow your retention/privacy procedure.
- **Contracts and billing:** attach a site to its contract using existing site
  administration. Add immutable effective-dated hourly rates as integer currency
  minor units (for example ZAR 12500 is R125.00). Invoice drafts include only
  completed attendance with a current approval fingerprint, inside contract
  dates. Each line snapshots elapsed seconds, rate and attendance fingerprint;
  round the line to the nearest minor unit. Missing rates, mixed currencies and
  overlapping site/date drafts are rejected. Later attendance changes are flagged
  when reviewing a draft. Drafts are not tax invoices and are not sent to clients.
  Review breaks, overtime, taxes, credits and contract terms in the accounting
  system. Void a draft with a recorded reason before generating a corrected
  replacement; the original remains in history. There is no finalisation,
  payment or credit-note integration in this release.
- **Device health:** Android uploads battery percentage and version code after
  a successful workspace refresh. Failure never blocks duty sync. Contact older
  than 15 minutes, battery at or below 20%, and versions below
  `MINIMUM_GUARD_VERSION_CODE` (default 16) are flagged. Existing managed-policy
  contact and revision/state are shown separately. No report means unknown
  battery/version; signed-out/offline phones may retain stale observations.
  Policy reports remain device observations, not independent attestation.
- **Readiness:** review unacknowledged response/check-in/duress signals, worker
  receipts, in-app channel scope and backup evidence. Backup entries record an
  operator's reference and backup/restore times; Sentinel does not execute or
  independently verify backups. UNKNOWN must never appear as successful backup.

## Acceptance before live use

Rehearse on staging with separate tenant, guard, viewer and manager accounts.
Exercise dispatch races and duplicate submissions; a missed check-in and duress
code; a coverage gap and conflicting relief; offline gate replay after lost
response; attendance corrections after invoice creation; low/stale device health;
worker failure and recovery; and restoration of a real backup. Check MySQL
concurrency with multiple operator processes and existing shift editors. Apply
edge request limits to check-in and capture endpoints. Test phone power/network
loss, kiosk recovery and radio/phone fallback on every supported handset model.
No production migration, physical provisioning or live emergency response has
been performed by adding this source.
