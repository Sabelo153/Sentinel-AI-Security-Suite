# Reporting, QR/NFC patrols and offline delivery in 0.9

This extends [site assurance](SITE_ASSURANCE_0_9.md), which covers instructions,
handovers, training, evidence, service levels, maintenance and retention.

## Product references

The user selected [Report and Run](https://www.reportandrun.com/) and
[QR-Patrol](https://www.qrpatrol.com/) as feature references on 3 November 2026.
Their product descriptions informed the following independent Sentinel workflows;
no competitor code, branding, accounts or proprietary report format is imported.

| Reference area | Sentinel implementation |
|---|---|
| Photo descriptions and emphasis | Captions and non-destructive arrow, box, ellipse and text overlays. |
| Branding and layout | Reusable immutable templates with company name, optional logo, colour, footer and one/two-photo page grouping. |
| Mobile capture followed by office editing | Guard incident photos can be imported once into evidence, then assembled and annotated in the manager report builder. |
| Readable report exports | Self-contained printable HTML, browser Print / Save as PDF, and a separate original-evidence JSON investigation pack. |
| QR/NFC tours | QR camera scans and Android NFC hardware UID reading, route-scoped validation, capture timestamps, recorded method and retry-safe upload. |
| Scheduled oversight | Existing patrol windows, missed-checkpoint attention, in-app escalations, welfare plans and new scheduled client summaries. |

Sentinel does not claim Report and Run DOCX/JREP compatibility, a direct native
PDF generator, QR-Patrol beacon integration, biometric verification, automatic
man-down detection or a guaranteed real-time emergency channel. The reference
sites describe more features than this release implements.

## Deploy the matching components

After the earlier 066–068 schemas, apply **069, 070 and 071**, deploy matching
backend/admin assets, and run `composer install` from the committed lockfile.
This adds PHPMailer for TLS SMTP. The existing sodium compatibility dependency
was also updated to resolve its reported advisory. Seed permissions explicitly.

- `photo_reports.view` reads metadata, `photo_reports.manage` edits/approves
  drafts, and `photo_reports.export` grants original-photo preview/export.
- Mobile incident photo import requires `evidence.manage`; ordinary original
  upload remains under Incident evidence. Photo exports contain original image
  bytes, so grant export access only to appropriate report editors.
- `client_reports.view/manage` lets company operators configure approved
  aggregate sharing, review delivery states and authorise retries.
- `client_reports.schedule` plus an existing same-company client mapping lets
  clients choose their own times and time zone. Give clients `client_portal.read`
  and `notifications.view` to use published reports and in-app notices.

## Photo report workflow

1. Create a branded template. Templates are immutable so changing branding does
   not silently alter an earlier report's layout; create a new template instead.
2. Create a draft for a site. Import a mobile incident photo by its protected
   report-photo ID, or upload an original through Incident evidence.
3. Add the evidence ID, caption and position. Reports hold at most 20 JPEG/PNG
   items, each up to 256 KiB and 2048 pixels per side. Logos are at most 100 KB.
4. Open a photo's caption/markup editor. Drag to place arrows, boxes or ellipses;
   enter a text label before placing text. Undo or clear affects the unsaved
   overlay only. Saving changes the report revision, not original evidence.
5. Download the printable HTML and open it in a browser. Use Print / Save as PDF
   for a PDF copy. Long captions can flow onto additional pages. Draft exports
   are labelled DRAFT; approval locks report presentation and records the actor.

The report contains source evidence IDs, capture times and original SHA-256
hashes. Originals are verified when exported, and access is recorded. Approved
reports still depend on retained evidence: place appropriate incident holds if
they must remain reproducible. Evidence retention can remove originals, after
which an export fails clearly rather than inventing a replacement image.

Photo reports are reviewed and shared manually. Scheduled email below contains
an operational text summary, not automatically attached internal evidence or
unreviewed photo captions. Downloaded HTML/PDF copies and email archives need
their own access and retention controls.

## NFC setup and patrol scans

Use a phone with NFC hardware enabled. Select **Read NFC tag UID for checkpoint
setup**, tap the physical tag, and give the resulting UID to an authorised
checkpoint administrator. That operation reads an identifier only: it does not
write a tag, configure a checkpoint or create a patrol visit.

Under Patrol routes → Checkpoints, enter the tag's hardware UID in the NFC field.
New values accept 4, 7, 8 or 10 hexadecimal bytes. Colons, spaces, hyphens and case
are normalised. Duplicate active UIDs within one route are rejected. Existing
formatted hexadecimal values remain readable; symbolic legacy labels such as
`NFC-GATE` are not hardware UIDs and must be replaced from an actual tag read.

A checkpoint can have QR, NFC or both. During an active patrol choose **Read
checkpoint NFC** and tap; QR remains available on phones without NFC. The server
matches the active checkpoint within the guard's own tenant and route and records
the capture method/time. NFC UIDs and QR values are identifiers, not cryptographic
proof of physical presence; test placement and audit procedures on actual devices.

## Completing an entire patrol offline

Synchronize the guard's account, active assignments, shifts and routes before
losing connectivity. A current sign-in and downloaded duty are required; initial
login/provisioning cannot happen offline.

- Patrol start, QR/NFC scans and completion are saved in the account-scoped
  encrypted queue with stable operation UUIDs and original event times.
- A patrol started offline has a local reference and is usable immediately in
  the workspace. Dependent scans/completion keep that reference while queued.
- Upload sends the start first. Its server ID and queue acknowledgement are
  committed together in SQLite; subsequent operations resolve the same local
  reference to that acknowledged server ID. A lost response retries the same
  operation UUID, using the server's existing receipt rather than duplicating it.
- Completion appears as pending until acknowledged. Queued scans are not shown
  as independently validated checkpoint visits. Refreshed server history provides
  the final accepted records.
- Queue database version 3 adds local patrol mapping without dropping existing
  version 1/2 queued records. Only already-synchronized local metadata is bounded
  to the latest 100 entries; unsent operations are never removed by that pruning.

Saving an event schedules a persistent one-shot Android job requiring a network.
The foreground app also requests synchronization on reconnection, and the
existing periodic job remains a fallback. This uses Android JobScheduler, not
Google Play services. Android power restrictions, force-stop and network quality
can delay background execution. The manual Synchronize control remains available.
An expired session requires the same account to sign in again. Permanent server
rejections remain encrypted for supervisor review and stop later dependent
events; they are not silently discarded. The queue holds at most 500 operations.
Do not uninstall, clear storage or switch to an incompatible signing key while
records remain unsent.

## Six-hour client reports and custom local times

The default is **00:00, 06:00, 12:00 and 18:00, Africa/Johannesburg**. In Scheduled
client reports, a company operator approves automatic aggregate sharing for a
client. The client can then use My report delivery times to select 1–24 distinct
24-hour `HH:MM` times, a valid IANA time zone, and enabled/paused status. Clients
cannot approve their own access, alter another client's settings or enter an
arbitrary external recipient.

Each delivery window covers the previous scheduled boundary to its due slot.
Changing preferences retains the last generated boundary, so the next report
covers the intervening records. Reports use half-open UTC ranges, and the header
shows the selected local slot. DST nonexistent local times are skipped; repeated
clock times run once according to PHP's time-zone interpretation.

Run this server command **every minute** for each tenant using a dedicated active
operator with `client_reports.manage`:

```sh
php bin/run-client-reports.php --company=ID --operator=SERVICE_USER_ID
```

Generation creates client-scoped portal summaries and an email outbox in one
transaction. One unique schedule/UTC-slot receipt prevents duplicate generation.
The worker catches up at most 20 windows per invocation, retaining later due
windows for subsequent runs. It publishes only aggregate counts and site names;
private narratives, personal guard data and original evidence are excluded.

Offline patrols arriving after an earlier snapshot remain in server history.
Later summaries also count earlier-started patrols updated as completed during
the new delivery period, which includes delayed offline completions. This is an
update count, not a certified historical status timeline. Every summary states
its generation time and that offline data can arrive later. Earlier reports are
not silently rewritten.

## Email configuration and truthful delivery states

Configure these **on the server**, never in the APK or browser:

```dotenv
REPORT_SMTP_HOST=your-mail-server
REPORT_SMTP_PORT=587
REPORT_SMTP_ENCRYPTION=tls
REPORT_SMTP_USERNAME=your-smtp-user
REPORT_SMTP_PASSWORD=your-secret
REPORT_FROM_ADDRESS=reports@your-company.example
REPORT_FROM_NAME=Company Reports
REPORT_PORTAL_URL=https://your-server.example/admin/index.html
```

Use a verified sending domain and an authenticated provider/relay as appropriate.
Supported transport modes are STARTTLS (`tls`, normally 587) and implicit TLS
(`smtps`, normally 465), with normal certificate checks. No live provider or
credentials have been configured by adding this source. Configure domain/provider
delivery requirements and test approved staging recipients before enabling mail.

Emails go to mapped, active client accounts at their current account addresses.
Access, address and enabled/approved schedule are rechecked before sending; revoked
mappings, changed addresses and paused sharing cancel pending delivery. Portal
reports remain available when SMTP is not configured.

| State | Meaning |
|---|---|
| PENDING | Durable outbox entry awaiting a worker. |
| WAITING_CONFIG | Portal generation succeeded; SMTP has not been configured. |
| SENDING | A worker claimed the attempt before contacting SMTP. |
| ACCEPTED | SMTP accepted the message; inbox delivery/read status is unknown. |
| UNCERTAIN | An attempt failed ambiguously or a worker stopped during delivery. |
| FAILED | A definite preparation problem, such as an invalid account email. |
| CANCELLED | Recipient access, address or schedule changed before delivery. |

SMTP runs outside the database transaction. An ambiguous attempt is not retried
automatically: it might already have been delivered. Company operators can review
and queue a retry with a reason. A stable Message-ID assists deduplication but
does not guarantee exactly-once email delivery. Check provider logs before
retrying uncertain attempts. Readiness shows worker freshness and outbox state
counts. Email applies to these scheduled client summaries; earlier emergency
escalations remain in-app unless another provider workflow is added.

## Acceptance remaining before live use

Test full airplane-mode patrol start → QR/NFC scans → completion → process restart
→ reconnect, including a lost response, expired login, wrong tag and server
rejection. Confirm no duplicates and the original capture times. Exercise custom
time zones, midnight boundaries, DST, scheduler downtime/catch-up, paused clients,
revoked mappings, invalid addresses and SMTP interruption after acceptance.
Rehearse MySQL concurrency and real mail-provider delivery with approved test
recipients. Physical handset, NFC and background power-behaviour acceptance is
still required; unit tests and source builds do not replace it.
