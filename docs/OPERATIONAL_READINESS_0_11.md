# Sentinel 0.11: offline inspections and operational readiness

This release adds the six workflows following the 0.10 client-workflow release.
The new dashboards and offline inspection workspace run in a browser. Existing
Android 0.9 patrol, QR/NFC, device-owner and private APK installation workflows
remain compatible. No Play Store, Google account, Play services, cloud translation
service or third-party map API is required by these additions.

## Offline inspections

From **Custom inspection forms**, open **passphrase-protected offline inspections**
in the same browser tab. While connected and signed in:

1. Wait for **Offline page ready**. The service worker caches only this small
   offline application's static files; API responses and login pages are not cached.
2. Create or unlock the current account's vault using a passphrase of at least
   12 characters. Download each site's current forms before leaving coverage.
3. Keep the tab/browser profile available. Cached forms, including reviewed
   translated labels, can be reopened after an offline reload.
4. Complete fields and attach required photos, then choose **Save inspection
   offline**. A saved operation UUID, complete payload and photos remain queued
   until a server receipt is acknowledged.

The vault uses IndexedDB, a passphrase-derived non-extractable AES-256-GCM key,
PBKDF2-SHA256 with 310,000 iterations, random salt/IV, and company/user identity as
authenticated encryption data. The key and passphrase are not persisted. Web Locks
serialize encrypted updates across tabs to prevent lost queue entries. The vault
locks on page exit, login change and 15 minutes without activity. Signing out of
the main workspace removes its cached offline identity.

Reconnection, foreground visibility, a visible-page timer and **Sync now** attempt
uploads using the current session. The server-verified company and user must
match the vault. This does not store a refresh token or upload after the browser
is closed. Expired sessions require signing in again with the original account.
An interrupted acknowledgement retries the unchanged operation UUID; the server
returns the same receipt without duplicate follow-ups. Only a valid positive
receipt removes pending data and photos. The latest 100 small receipts remain.

Permission loss, missing records, stale form definitions and validation conflicts
remain as **NEEDS_REVIEW**. They are not silently rewritten or discarded. Review
the current form/permissions before retrying the unchanged submission or explicitly
discarding the local record. New capture against an updated form is a new operation.
Accepted historical retries still resolve to their existing receipt.

Limits: 200 cached forms, 50 pending inspections and a 25 MiB vault per account.
Existing photo limits remain 128 KiB per original JPEG/PNG, 2048 pixels per side and
700,000 combined base64 characters. Capture times shown in the local queue are
device times; server submission time is the time of receipt.

Use HTTPS (localhost is supported for development), WebCrypto, IndexedDB, Web Locks
and service-worker support. Persistent browser storage is requested; the page
shows when storage remains best-effort. Clearing site data, browser-profile loss
or browser eviction can delete unsynced records. A forgotten vault passphrase
cannot recover unsynced data. Device screen locks and managed browser policies
remain important for shared phones. This vault is separate from the native
Android patrol queue, which continues to work as before.

## Numbered keys and access cards

**Keys and access cards** registers a site's uniquely numbered items. An issue
reserves an available item for an active employee with a login, return deadline
and notes. **My key custody** lets the actual logged-in recipient draw a signature
and accept it. An inactive employee cannot accept a new issue.

The recipient signs a return request. The item stays unavailable until a different
logged-in operator signs confirmation of physical receipt. Revisions and the
company transaction lock prevent duplicate issues, stale returns and reissue
while custody is active. Pending issues can be cancelled. Overdue items are
flagged from their recorded deadlines.

Loss marks the item unavailable and creates a linked HIGH-severity access-control
incident. Record card deactivation, lock changes and other containment in the
loss notes as appropriate. Sentinel does not operate door/access hardware.
Managers can record recovery or retire an available/lost item; history remains.
Signed receipts verify stored PNG hashes before protected, audited viewing.

Signatures are authenticated-user handover attestations, not certified electronic
signatures or independent proof of physical possession. Key/card actions require
a live connection. A recipient can still view and return their own custody when
their site assignment changes; this does not grant them general site access.

## Shift replacement suggestions

**Shift replacement suggestions** examines eligible upcoming relief shifts and
active guard-position assignments. It excludes candidates with:

- Approved overlapping leave/unavailability or another shift.
- Missing, unverified, revoked or expiring site-required training.
- Insufficient scheduled rest before or after the shift.
- Scheduled hours beyond the employee's configured UTC-week limit, including both
  weeks when a shift crosses Sunday midnight.
- A known base-to-site straight-line distance beyond the configured limit.

The page displays candidates, excluded reasons and whether distance is known.
Ranking considers known distance and scheduled weekly workload. Coordinates are
optional; missing distance is shown as unknown, without inventing travel times.

Company planning defaults are 2,880 scheduled minutes per week, 660 minutes rest
and 200 km maximum when distance is known. Managers can set employee-specific
limits and an optional base location. These are configurable planning settings,
not a calculation of legal working-time entitlement or road travel time.

**Approve** recomputes eligibility under the company transaction lock, then uses
the existing relief workflow and history. It requires the expected original
employee. A newly approved absence or changed roster can invalidate an earlier
suggestion. No replacement is assigned automatically. Ordinary manual relief
remains a separate manager workflow.

## Emergency procedures, roll calls and drills

**Emergency procedures and drills** publishes immutable site procedure revisions
with ordered response steps, emergency contacts and an assembly point. Starting
an event requires an explicit **DRILL** or **EMERGENCY** selection and an explicit
list of unique participant labels. Include visitors and distinguish duplicate
names. The list is a staff-supplied snapshot, not an automatic presence census.

Each event pins its procedure revision. Later publications do not change active
steps. Operators record step completion and each participant as UNKNOWN, SAFE,
ASSISTANCE or MISSING, with notes and actor/time. Revisions reject stale updates.

An emergency cannot be marked closed until all listed participants are safe and
every pinned step is recorded. A drill can end as **CLOSED_WITH_FINDINGS**, keeping
its unresolved roll call and debrief. These records do not call emergency services,
dispatch responders, unlock gates or verify that evacuation physically occurred.
Use the site's phone/radio and response arrangements.

## Reviewed language content

**My workflow language** selects English (`en`), isiZulu (`zu`) or Afrikaans (`af`).
Selected content is applied to inspection form titles/labels, assigned guard site
instructions, emergency procedures and personal notification inbox text. The
offline page caches the form's displayed language when it downloads the form.
Main administrative controls remain primarily English.

**Reviewed workflow translations** loads the original record and target language.
Reviewers translate all required text while preserving form field keys and step
numbers. They can load the current target revision and existing translation.
Recipient-specific notification source reviews require the separate management
permission and are audited; ordinary users can read only their own notifications.

Translations carry the original-content fingerprint, a revision and reviewer/time.
Missing or stale translations fall back to the original English text. They never
silently carry forward after source text changes. Form validations, safety flags
and required-photo settings remain those of the original form. A fluent company
reviewer must check meaning; this release does not automatically machine-translate
safety instructions or include a ready-made multilingual content library.

## Company branding and subscription administration

**Company branding and subscription** records a tenant display name, primary colour,
PNG/JPEG logo and support contact. Signed-in workspace branding changes by tenant;
it resets on logout and ignores responses belonging to an old session. The public
login and marketing site keep the Sentinel brand. Logos are limited to 128 KiB and
1024 pixels per side; arbitrary HTML/SVG and CSS values are not accepted.

The page shows subscription status, plan limits, user/site usage and remaining
capacity. Setup evidence covers accounts, sites, rosters, devices, report delivery,
backup restoration and supervised acceptance. Completed checks can be reopened
for fresh evidence, with audit history.

Plan-change requests are recorded and can be cancelled. They do not activate a
paid plan, change limits or charge a card. Account administration and any billing
provider integration handle commercial approval outside this workflow. The
administration page remains available to permitted accounts when a subscription
needs attention; permissions are still required.

## Rollout

Apply `database/schema/073_operational_readiness.sql` after schemas 001–072 and deploy
the matching PHP/routes and all admin assets. Fresh installs use `000_run_all.sql`.
Run the tenant-specific operational permission seed with the intended
`@sentinel_company_id`, then grant roles explicitly. Refresh the browser assets and
prepare the offline page while connected. No new server worker or APK build is
required for this release.

| Workflow | Permissions |
| --- | --- |
| Keys/cards | `key_register.view`, `key_register.manage`, `key_register.self` |
| Replacement suggestions | `replacement_suggestions.view`, `replacement_suggestions.manage` |
| Emergency procedures | `emergency_procedures.view`, `emergency_procedures.manage` |
| Languages | `workflow_languages.self`, `workflow_languages.view`, `workflow_languages.manage` |
| Workspace branding | `company_branding.read` |
| Company administration | `company_workspace.view`, `company_workspace.manage` |
| Offline inspections | Existing `inspection_forms.view` and applicable site grants |

Keys and replacement staff modules require Essential or above. Self custody,
emergency, language and company administration permissions are available on known
plans, subject to subscription/RBAC checks. Existing tenants without subscription
records retain the existing grandfathering behaviour.

The site restriction marker continues to win over broad roles. Restricted accounts
can use scoped key/emergency workflows, self language settings, scoped translated
reads and branding reads. They cannot use company administration, company-wide
replacement suggestions or translation management. Existing guard accounts can
use their assigned translated instructions; grant the new self permissions for
the browser's custody and language pages as needed.

## Development verification

PHP integration tests exercise actual services using SQLite: custody transitions,
signature integrity, loss incidents, tenant/site restrictions, replacement
revalidation and cross-week limits, emergency snapshots/roll calls, translation
fallback and notification ownership, branding validation, plan-request isolation,
setup evidence and audit rollback.

Playwright tests use Microsoft Edge and fixture APIs:

```
node tests/Browser/operational-readiness.cjs
node tests/Browser/offline-inspections.cjs
node tests/Browser/client-workflows.cjs
```

The offline test serves the real offline page locally, installs its service worker,
goes offline, reloads, captures a required-photo inspection, reloads again, simulates
an interrupted receipt, and reconnects without duplicate acceptance. It also checks
encrypted storage, wrong passphrases, concurrent saves, account isolation and login
changes. PHP checks run with `php vendor/phpunit/phpunit/phpunit --no-coverage`.
Production MySQL concurrency, actual field devices, browser storage policy and
site-specific emergency procedures still need a deployment acceptance run.
