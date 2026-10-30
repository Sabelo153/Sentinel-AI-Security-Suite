# PC emergency support workspace

Open Company workspace → Emergency support on the HTTPS Sentinel deployment.
Use an individual company account with `support.view`. Recovery additionally
requires `support.manage`; no implicit administrator bypass is introduced.
These permissions remain usable when a subscription expires, but RBAC and the
active-account/company checks still apply. New company owner roles receive the
permissions through signup route metadata. For existing companies, review and
run the updated `database/seed/operational_permissions.sql` for the intended
company, then explicitly grant permissions to the appropriate role using the
existing role administration. The seed does not grant roles. No new schema is
required: canonical devices, sessions, sites, mobile operations and audit tables
are used. Missing tables produce unavailable sections, never a healthy result.

## Operator procedure

1. During a physical emergency, use the site's radio/phone escalation and cover
   procedures immediately. This console is not a monitored dispatch channel.
2. Refresh the snapshot. Review server states, stale device/session contact and
   affected site names. Contact is last-known evidence, not live tracking.
   The 30-minute stale marker is a support triage default, not an SLA or proof
   of physical inactivity. Unknown/future timestamps are explicitly identified.
3. Review server sync receipt summaries. These do not reveal a phone's local
   queue or rejected requests. Ask the guard to retain the error/request ID,
   charge the phone, check connectivity and explicitly synchronize.
4. Preserve encrypted queues. Do not clear storage, uninstall the app, change
   encryption keys or delete mobile retry receipts as a repair procedure.
5. For a lost/compromised phone, identify the correct user in the Users module,
   confirm the user ID and provide a recovery reason. Disable affects that
   user's company access on subsequent requests across all devices. The
   operator cannot disable their own account. Containment and audit commit
   together; an audit failure rolls back containment. Already-running requests
   are not cancelled. This action is not a device wipe or individual JWT revoke.
6. Arrange replacement access through the company administrator. Do not simply
   re-enable a compromised account: existing bearer tokens could become usable
   again before expiry. Follow the account/password recovery procedure and
   verify old credentials/tokens have expired or been invalidated before reuse.
7. Use the existing Occurrence Book/Incidents pages for radio/phone fallback
   records. Identify the reporting source, actual event time, controller entry
   and outstanding follow-up; do not impersonate a mobile submission.
8. Download a sanitised bundle for technical support. It contains component
   counts/states and limitations, excluding identifiers, free text, precise GPS,
   credentials, payloads and raw errors. The export itself is audited. Refresh
   after recovery and review the support action timeline.

## Deployment and acceptance

- Deploy backend and updated admin assets together; invalidate cached admin JS.
- Use HTTPS, individual accounts, login rate limits and restricted support role
  grants. MFA enforcement and time-limited vendor delegation are not implemented
  by this change. Do not share company administrator credentials with vendors.
- Existing localhost-only `/support` checks remain available to the server
  operator when explicitly enabled. Remote company support exposes only health
  states, not global schema details or customer records from another company.
- Check viewer versus manager permissions, unauthenticated access, two-company
  isolation, missing tables, database/audit outage and self-disable rejection.
- Rehearse the loss of a site phone and a server outage using disposable accounts
  and test phones before using this workflow operationally.
- Server monitoring, automatic dispatch, external notification delivery, mobile
  diagnostic uploads, queue repair/export and remote screen control remain
  separate integration work. This release deliberately makes no claim that
  unsynced phone data is remotely accessible.
