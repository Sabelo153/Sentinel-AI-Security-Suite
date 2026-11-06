# Sentinel 0.13: business operations and first-party sponsors

This source release adds open-shift offers, approved visitor QR passes, fleet
records, contract renewals, encrypted tenant snapshots and pilot setup. Matching
web/API services are required. Android 0.10 (versionCode 18) adds home-screen
first-party banners. Existing native offline patrol/NFC capture is retained.

## Open-shift offers

Managers broadcast an eligible upcoming shift with a response deadline. Candidates
come from existing replacement checks: active guard position, qualifications,
approved absence, recorded hours/rest and optional distance preferences. Guards
see their own offers and express acceptance. Acceptance does not assign the shift.
A manager confirms one accepted candidate; eligibility and the original assigned
employee are checked again atomically, and normal relief history is retained.
Expired offers close in the business worker. Notifications are in-app records.

## Client-approved visitors

Staff or a currently mapped client pre-register a visitor, purpose, authorised site
and arrival/departure window. Only the mapped client can approve. Approval shows a
random pass once, with downloadable QR SVG. The server stores its hash. Gate staff
scan using a supported browser camera or paste the pass, check the visitor's
identity, then confirm. One check-in consumes the pass and creates an approved
gate entry. Checkout creates its exit. Wrong site, cancelled/expired/used passes
and former client mappings are rejected. These are online authenticated gate
workflows; the pass does not open hardware doors or replace identity checks.
Overdue checkout conditions produce supervisor reminders. The locally bundled QR
generator and its MIT notice are in public/admin/vendor; no CDN is needed.

## Fleet

Register site vehicles, odometer and next service by distance/date. Allocate an
active driver and non-overlapping vehicle/driver window (up to 48 hours). Assigned
drivers see their own trips. Start requires five explicit yes/no checks: brakes,
tyres, lights, seatbelts and fluids. Any failed check grounds the vehicle. Record
workshop evidence and a future service threshold to return it to service. Mileage
cannot decrease. Finish records the final odometer; cancellation does not change
it. The browser accepts litres and normal currency amounts; the API stores
thousandths of a litre and currency minor units. Service-due
vehicles cannot be newly allocated or started. These records do not provide live
vehicle telemetry, licence validation or an accounting integration.

## Contract renewals

Managers propose effective dates after the existing term, an explicit currency,
monthly minor-unit price and terms. Currently mapped clients accept or decline
the exact offer fingerprint. Managers schedule accepted offers; the worker applies
them when the effective date arrives. Until then the existing price stays in
place. Original-contract edits or proposal corruption block application. Before
and after terms remain in history. This is recorded approval, without automatic
billing, electronic-signature certification or email delivery.

## Encrypted tenant backups

Configure an independent secret (at least 32 characters) and a private directory:

    BACKUP_ENCRYPTION_KEY=<independently generated protected secret>
    BACKUP_DIRECTORY=<absolute path outside the web document root>
    BACKUP_MAX_BYTES=2147483648

PHP requires sodium, PDO MySQL for the live database, and PDO SQLite for restore
verification. Install matching schemas separately. Administrators with verified
MFA can enable 1–168-hour schedules, retain 2–30 verified snapshots or queue a
manual run. The CLI backup worker performs the actual export outside an HTTP
mutation transaction. It streams a repeatable-read tenant snapshot into bounded,
authenticated XChaCha20-Poly1305 encrypted frames, finalises the file and records
its SHA-256. Frames support binary/text/null/numeric values; rows above 8 MiB of
encoded content or archives above the configured limit fail visibly. Storage
paths and keys are not exposed in the browser.

Each snapshot automatically decrypts into a private disposable SQLite database.
Every row is compared, tenant identity and authenticated final count are checked,
and plaintext test files are removed. Only a passed test advances the schedule
and permits pruning of older verified snapshots. Failed jobs/verification and
stalled or overdue schedules appear in reminders. Failed jobs back off for one
hour; an operator can request a reviewed retry. A stale RUNNING job is not silently
overwritten: investigate the stopped process before repairing its state.

**The automated test verifies tenant data round-trip, not a full MySQL recovery.**
The archive contains rows from base tables with company_id. It excludes database
DDL, empty-table definitions, tables without company_id, uploaded files on disk,
server configuration and encryption/signing keys. Keep conventional database and
file backups as well. Copy encrypted archives to protected off-site storage using
the operator's existing backup system; off-site transfer is not automated here.
Keep the backup key recoverable separately. A lost key makes archives unreadable.

Before a pilot, restore the production database schema/global reference data and
uploads into an isolated compatible server using the organisation's recovery
procedure, then verify application access, tenant records and evidence files.
Record the drill evidence under the existing company setup BACKUP check. The
pilot wizard requires that evidence plus a recent passed data test; it does not
restore into production or claim a host disaster recovery test occurred.

## Pilot setup and reminders

The wizard shows schema checks, backup configuration, latest restore receipt,
recorded recovery evidence, role templates, a reminder supervisor and fresh
worker receipts. Create Guard, Client and Site Supervisor roles using an
MFA-verified role administrator with roles.create and roles.assign_permissions.
Templates grant only active seeded permissions available on the current plan.
The supervisor template requires the site_scope.restricted marker. Assign users,
sites and client mappings explicitly; the wizard does not grant broad access to
existing users. Demo examples live only in pilot_demo_records and never populate
operational incidents, shifts, contracts, visitors or vehicles.

Select an active unrestricted supervisor with pilot_setup.view. The worker
records service, visitor, renewal and backup reminders, deduplicates persistent
conditions and resolves cleared ones. Recipient access is rechecked before each
in-app notification. These reminders do not send SMS, email or emergency calls.

## Own sponsor banners: Free only

Deployment-owned sponsor text/link settings:

    SPONSOR_BANNERS_ENABLED=true
    SPONSOR_TITLE="Your sponsor name"
    SPONSOR_BODY="Your approved sponsor message"
    SPONSOR_CTA="Learn more"
    SPONSOR_URL="https://your-approved-sponsor.example/"

The default is a Sentinel plan promotion, ready to replace with your own sponsor.
Only the authenticated server subscription with exact plan_code FREE enables a
banner. Essential, Professional and Enterprise stay ad-free, including trials,
cancelled/expired subscriptions. Missing/unknown subscription or failed checks
hide banners. Browser/client flags cannot enable them. Deployment can disable
banners globally. Creatives are plain text plus a rooted local or HTTPS link;
no supplied HTML, ad scripts, tracking pixels, ad SDKs, Google or Play Store
service is introduced.

Web banners appear only on Dashboard, Help, Company workspace and Release notices.
Native banners appear only on the home screen and use a separate short-timeout
worker. They refresh entitlement every 15 seconds while visible, hide on unknown
network state/navigation/logout/background, and have no offline cache. Native
link taps recheck current entitlement. Emergency, voice, gate, capture, patrol
and encrypted offline inspection screens do not display ads.

## Installation and permissions

Apply database/schema/075_business_operations.sql after 001–074. Fresh installs
include it through 000_run_all.sql. Run database/seed/operational_permissions.sql
for the intended tenant, then grant roles explicitly. Definitions do not assign
users or memberships. Company-wide backup/pilot/renewal administration remains
blocked for account-level restricted site supervisors.

| Workflow | Permissions |
| --- | --- |
| Offers | shift_offers.view, shift_offers.manage, shift_offers.self |
| Visitors | visitor_invites.view, visitor_invites.manage, visitor_invites.client, visitor_invites.gate |
| Fleet | fleet.view, fleet.manage, fleet.self |
| Renewals | renewals.view, renewals.manage, renewals.client |
| Recovery | backups.view, backups.manage, worker-only backups.run |
| Pilot/reminders | pilot_setup.view, pilot_setup.manage, worker-only business_ops.run |

Staff offers/fleet/renewals require Essential or above. Visitor, recovery, pilot
and self/client additions are available on known plans subject to RBAC and
site/client assignments. Existing subscription permissions remain in effect.

Schedule both commands each minute with active unrestricted tenant service users
and their corresponding explicit worker permission:

    php bin/run-business-operations.php --company=ID --operator=SERVICE_USER_ID
    php bin/run-business-operations.php --company=ID --operator=SERVICE_USER_ID --backups

The second evaluates the configured backup interval; it does not necessarily
create an archive each minute. Keep existing control-room, report, escalation and
operations-hub workers. No real worker or external notification was run against
customer data during development.

## Verification and native package

Backend integration tests cover offer eligibility rechecks/manager award,
one-use visitor passes and mapping changes, vehicle overlap/ownership/failed
checks/mileage, renewal hashes/history/stale bases, real encrypted data round-trip,
corrupt files, retention, private paths, isolated demo records and deduplicated
reminders. Sponsor tests cover every paid plan and status plus unsafe links.
Browser tests cover the new controls, generated QR SVG and Free/paid/operational
screen banner isolation, alongside existing offline/workflow regressions.

Android assembleDebug, 21 JVM tests and lintDebug passed. Lint reports 20 warnings
(resource/translation cleanup), with no errors. The local 0.10.0 debug APK is a
signed test build, not a production release. APK SHA-256:

    e33bf23f7c09ef338939b2883bfeb2cfd51b116bd45037f3183a521bec65742b

Debug certificate SHA-256:

    c12dc442b3e923e336f2a818ea1da26dfda256496c54681f6f7240af28848324

Distribute a production APK with the company's protected release key using
[private APK installation](APK_DISTRIBUTION.md). Physical Android/AOSP phone,
camera/NFC, kiosk and MySQL concurrency/deployment checks remain rollout gates.
