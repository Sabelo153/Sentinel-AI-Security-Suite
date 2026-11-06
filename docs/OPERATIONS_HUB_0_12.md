# Sentinel 0.12: communication, client reviews and operational alerts

This web/API release adds the six workflows following operational readiness 0.11.
The existing Android 0.9 patrol app and private APK installation remain compatible.
The new pages require a connection; native patrol capture and the encrypted browser
inspection vault retain their existing offline behaviour.

## Site/team voice channels

Use **Voice channel administration** to create a site channel and explicitly add
its members, including administrators who need to listen. Membership, the normal
`radio.self` permission and any account-level site restriction are all enforced.
Removing a member immediately stops subsequent feed/audio access. New members
cannot read recordings made before they joined, including messages from the same
timestamp second.

In **Site/team voice channels**, select a channel and hold **Hold to talk**. The
browser requests microphone permission and records a short burst. Releasing the
button sends it to the channel. Browser capture stops after 20 seconds. Server
floor leases last 30 seconds and limit concurrent recording turns. The encoded
audio limit is 512 KiB; supported containers are WebM, Ogg and MP4, selected from
the browser's supported recording formats.

**Hold for priority voice call** records an urgent request immediately and can
preempt a normal floor lease. It cannot preempt another active priority lease.
Authorised members receive an in-app priority notice; open channel pages show the
request and let a different member acknowledge it. Acknowledgement records receipt
by that member, not physical arrival or external emergency dispatch.

Choose **Enable listening** to request automatic playback of fresh bursts. If the
browser blocks autoplay, use the individual play control. Channel feeds poll while
the page is visible. Microphone tracks stop after capture, failed floor requests,
navigation and sign-out. Changing channel, muting or leaving stops automatic
playback. Delayed audio downloads cannot start after logout or a channel change.

Voice is sent after release, rather than streamed while the button is held. It is
a foreground browser communication tool, not always-on native two-way radio.
Browser suspension, permission denial, poor coverage and autoplay restrictions
can delay or prevent contact. Keep the site's phone/radio response arrangements.
Failed uploads remain in page memory for an unchanged receipt retry; they are not
part of the durable offline inspection/patrol queue. A never-received burst whose
lease expired needs a new recording turn.

History is chosen at channel creation:

- **Off:** audio becomes inaccessible after five minutes.
- **On:** audio becomes inaccessible after 30 days.

The worker removes expired audio bytes; receipt metadata remains. Access checks
and expiry apply even before cleanup runs. Playback verifies stored audio hashes.
Recordings are not transcribed or sent to a cloud speech provider. Deployment needs
HTTPS, a supported browser, microphone access and an appropriate company recording
policy. The tests use a simulated microphone, not physical guard phones.

## Control-room map

**Control-room map** loads a selected authorised site. Its geographic coordinate
view shows the site and guards with positions captured during current assigned
duty. It supports marker details, drag/pan, zoom and fitting all positions. Active
incidents mark the site's pin; the incident's own location is not independently
recorded. Today’s patrol table counts distinct **VISITED** checkpoints, excluding
SKIPPED and FAILED events.

Positions older than five minutes are **STALE**. Missing or invalid coordinates are
**UNKNOWN**; timestamps more than two minutes ahead of server time are invalid and
not plotted. Accuracy and mock-location flags remain visible. Readings before the
current shift are not reused as current site duty. The page refreshes every 15
seconds while visible and reports refresh failures without claiming old data is
live.

This is a coordinate map without road/traffic tiles. A marker offers an explicit
external OpenStreetMap link for road context. No Google service, map API key or
external tile request is required to display the Sentinel view. A recorded GPS
point is not independent proof of presence or safety. Offline patrol uploads may
change progress later.

## Monthly client service reviews

In **Monthly client service reviews**, generate a draft for a current or previous
calendar month. The snapshot includes existing daily service-level observations,
recurring incident-type counts, generation time and a partial-month indicator.
Write a client-appropriate summary and review it before publication. Drafts can be
regenerated using their current revision; published snapshots are locked.

Add agreed actions with an active owner and due date. Staff record completion
notes; a mapped client can explicitly confirm completion. Actions remain separate
from the immutable monthly snapshot. **My monthly service reviews** gives currently
mapped clients published reviews for the selected site, actions and feedback.
Each client user can submit one 1–5 rating and comments tied to the published
content fingerprint. Drafts and other clients' reviews remain inaccessible.
Reassigning a site's client stops access to its old client review through this
workspace. No automatic email or contractual performance certification is created.

## Integration centre

**Integration centre** creates site-scoped machine credentials with a maximum
90-day lifetime. Choose only the required scopes. API-key management requires
`integrations.manage` and a currently MFA-verified login. Keys are shown once and
stored only as SHA-256 hashes. Revocation, expiry, issuer deactivation, loss of
management permission and a site/client reassignment stop subsequent requests.
The ingress service establishes an isolated verified tenant context from the key,
never from caller-supplied company/site IDs.

| Scope | Endpoint | Behaviour |
| --- | --- | --- |
| `alarms.write` | `POST /api/v1/connect/alarms` | Creates an external-alarm incident for operator review. |
| `access.write` | `POST /api/v1/connect/access` | Records ENTRY/EXIT in the gate register with authorisation fixed to PENDING. |
| `exports.read` | `GET /api/v1/connect/attendance` | Returns a bounded site/date attendance export with approval states. |

Use `X-Sentinel-Key: YOUR_MACHINE_KEY`. The three connect routes authenticate that
credential instead of a person’s login JWT. Unsupported fields, including supplied
site/company IDs, are rejected. Write requests require a stable operation UUID.
Identical retries return the existing receipt; changed payloads with that UUID
return a conflict. Writes to unavailable sites are blocked. Receipts and business
records roll back together if auditing fails.

Example alarm payload:

```json
{
  "operation_uuid": "11111111-1111-4111-8111-111111111111",
  "title": "Gate alarm",
  "description": "External alarm received; operator review required.",
  "severity": "HIGH"
}
```

Example access payload:

```json
{
  "operation_uuid": "22222222-2222-4222-8222-222222222222",
  "visitor_name": "Delivery contact",
  "purpose": "Parcel delivery",
  "direction": "ENTRY"
}
```

Attendance query parameters are `date_from` and `date_to` in YYYY-MM-DD format,
covering at most 32 inclusive days and 5,000 rows. Each row identifies its employee
and recorded clock times, with `approval_state` CURRENT, STALE or UNAPPROVED.
`approved_worked_seconds` is present only for a current approval and valid completed
duration. Apply pay rates, breaks, overtime and statutory rules in the receiving
payroll system. This is not a payroll calculation or automatic pay authorisation.

### Signed webhooks

Create a site endpoint before publishing events that it should receive. Webhooks
currently cover alarm/access receipts, monthly review publication and newly opened
operational alerts. Payloads contain event type, site, timestamp and record IDs,
not private incident narratives, audio, diagnostics, reports or payroll values.
No historical backfill is performed when creating an endpoint.

The signing secret is shown once and encrypted at rest using the existing
ENCRYPTION_KEY with a separate webhook key derivation. URLs must use HTTPS, public
DNS, port 443, and no credentials, query or fragment. Delivery rejects private or
reserved IPv4 resolutions, pins an allowed resolved IP, verifies TLS, disables
redirects and proxies, and bounds connection/request time. IPv6-only receivers
are not supported by this first transport. Site/client reassignment cancels old
endpoint deliveries rather than sending a new client's data to an old receiver.

Headers:

```
Content-Type: application/json
X-Sentinel-Event-ID: STABLE_EVENT_UUID
X-Sentinel-Timestamp: UNIX_SECONDS
X-Sentinel-Signature: sha256=HEX_HMAC
```

The HMAC is SHA-256 over `timestamp + "." + exact_request_body`, using the signing
secret. Verify the signature with a constant-time comparison and reject timestamps
outside the receiver's chosen replay window, for example five minutes. Persist
event UUID receipts before applying side effects, and deduplicate retries using
the UUID. The body and event UUID stay unchanged across attempts; the signature's
timestamp is refreshed.

Delivery states distinguish PENDING, SENDING, RETRY, ACCEPTED, FAILED, UNCERTAIN and
CANCELLED. HTTP 2xx means accepted by the receiver, not independently processed.
Retriable responses use bounded backoff with five attempts. Uncertain transport
outcomes and interrupted workers need a reviewed retry with a reason. Subscribers
must handle duplicate delivery; exactly-once external processing is not promised.
Disabled endpoints stop subsequent attempts. A stale worker cannot overwrite a
newer delivery attempt's result.

Keys can be rotated by creating a new key, changing the adapter and revoking the
old key. Rotate webhook signing secrets by creating a new endpoint configuration
and disabling the old one. Receiver-specific alarm, access-control, accounting and
payroll adapters must still be configured and tested. The API does not open doors
or silently claim compatibility with a particular hardware vendor.

## Customer support and release notices

**Customer support tickets** provides staff assignment, replies and resolution.
**My support tickets** provides the creator's conversation, reopening and explicit
resolution confirmation. A self-service creator must have a client mapping,
current/upcoming duty or an explicit grant for the selected site. Company staff
with management permission can create site tickets directly. Tenant/site checks
apply on every request, including after a client/site reassignment.

Only the ticket owner can explicitly share sanitised diagnostics. These snapshots
use the existing diagnostics service and exclude credentials, raw queued payloads
and guard coordinates. The owner can revoke consent; the stored snapshot is then
removed and cannot be viewed through the ticket. Protected views verify the
snapshot hash and record access. Creating a ticket does not grant an external
vendor access to a company account or send anyone an email.

The workspace links to existing company onboarding checks and evidence. **Release
notices** publishes company notices for ALL, STAFF or CLIENT audiences. Users
explicitly acknowledge the exact content hash. These are portal notices, without
automatic email or phone notifications. Support tickets are not an emergency
dispatch channel.

## Operational alerts

Configure a rule in **Operational alerts** with two different active recipients
who have alert and site access. Defaults are 30 minutes for pending inspections,
24 hours for closed drill findings and 15 minutes before backup escalation.

The worker evaluates:

- Browser-reported inspection queues that are old or need review.
- Keys/cards whose active custody deadline has passed.
- Drills closed with findings that still need follow-up.

The offline inspection page sends per-site queue counts when it reconnects and
after sync. It does not send inspection answers/photos as diagnostics. These are
last-reported snapshots; the server cannot inspect an unreachable browser's live
vault. Old contact is labelled unknown. A new zero-pending report resolves that
source. Acknowledgement stops escalation but does not falsely resolve the source.

Alerts deduplicate per source and episode. Persistent conditions do not generate
duplicate primary notices on every tick. Unacknowledged alerts notify the backup
after the configured delay; recipient permissions and site grants are checked
again at delivery. When sources clear, alerts resolve. A later recurrence opens
a new episode. Key return/cancellation clears overdue custody; **Resolve findings
for drill** records reviewed follow-up while preserving the historical roll call.

The page shows the latest operations-worker receipt. It is UNKNOWN before a run,
FAILED after a failed run, or STALE after five minutes without a successful receipt.
The existing readiness dashboard includes this worker too. Notifications are
in-app records, without automatic SMS, external radio or emergency-service calls.

## Installation, permissions and workers

Apply `database/schema/074_operations_hub.sql` after schemas 001–073. Deploy the
matching services, routes and admin assets. Run the tenant-specific operational
permission seed with the intended `@sentinel_company_id`, then grant roles
explicitly. The seed adds permission definitions without automatically granting
roles. Fresh installs include the schema through `000_run_all.sql`.

| Workflow | Permissions |
| --- | --- |
| Voice | `radio.self`, `radio.manage` |
| Map | `control_map.view` |
| Reviews | `service_reviews.view`, `service_reviews.manage`, `service_reviews.client` |
| Integrations | `integrations.view`, `integrations.manage`, worker-only `integrations.deliver` |
| Support | `customer_support.view`, `customer_support.manage`, `customer_support.self` |
| Notices | `release_notices.view`, `release_notices.manage` |
| Alerts | `operational_alerts.view`, `operational_alerts.manage` |

Voice/map require Professional or above; staff monthly reviews require Essential
or above; integrations require Enterprise. Self/client support, notices and alerts
are available on known plans, subject to RBAC and applicable site/client mapping.
Existing grandfathered tenants retain the existing subscription behaviour.

Account-level site restrictions continue to win over broad roles. They permit
explicitly scoped radio, map, review, support and alert workflows, but block
integration administration and company-wide notice publication. Administrators
must still add radio members. Existing Android roles do not automatically gain
the new permissions.

Schedule the worker once a minute using an active, unrestricted tenant service
operator with `operational_alerts.manage`:

```
php bin/run-operations-hub.php --company=ID --operator=SERVICE_USER_ID
```

To enable external webhook attempts, additionally grant `integrations.deliver`
explicitly and configure the company's encryption key, PHP cURL and receiver:

```
php bin/run-operations-hub.php --company=ID --operator=SERVICE_USER_ID --deliver-webhooks
```

The worker evaluates alerts, removes expired audio and optionally processes at
most 20 webhook attempts per run. Keep the existing control-room, escalation and
client-report workers. No new Android APK build is required. Production database
backups and matching backend/admin deployment remain part of rollout.

## Verification

PHP integration tests exercise the services with SQLite and a fake webhook
transport. Coverage includes floor preemption, radio membership/expiry and
same-second join boundaries; current-duty map privacy, stale/future locations and
VISITED-only progress; client review publication and feedback; key hashing,
scope/issuer revocation and attendance approval labels; stable signed webhook
retries and client reassignment; diagnostic consent; alert deduplication,
escalation and recurrence; explicit notice reads; and audit rollback.

Playwright uses Edge with a simulated microphone to test actual browser recording
and upload controls, channel options, map details/zoom, review feedback, API-key
forms, explicit consent, release reads, site rules and stopping the microphone on
logout. The offline test also checks reported queue counts before/after receipt.

```
php vendor/phpunit/phpunit/phpunit --no-coverage
node tests/Browser/operations-hub.cjs
node tests/Browser/offline-inspections.cjs
node tests/Browser/operational-readiness.cjs
node tests/Browser/client-workflows.cjs
```

Browser/API fixtures and a simulated transport do not replace a deployment test
with MySQL concurrency, real receiver certificates, physical phone microphones,
browser background restrictions and the company's actual response procedures.
No live client webhook, email or emergency call was sent during development checks.
