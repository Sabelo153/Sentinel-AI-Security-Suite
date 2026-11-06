# Sentinel 0.7 field operations and managed Android

This release is source for staging and field acceptance. It does not install a
server, provision a phone, configure a notification provider or certify emergency
delivery. Do not describe device-reported policy status as independent attestation.

## Deployment order

1. Back up the database and configuration and rehearse restoration. Apply only
   new canonical schemas `066_field_operations_release.sql` and
   `067_managed_android.sql` to an existing compatible installation, using the
   migration identity. The full bootstrap runner is for empty databases.
2. Deploy backend and admin assets together. The login service now requires
   `account_mfa` and `security_sessions`. Missing security tables fail closed.
   Older JWTs have no registry entry and must sign in again.
3. Configure an independently generated `ENCRYPTION_KEY` (at least 32 characters
   of secure random material) for MFA and keep it in secure backup. Then review
   and seed the updated `database/seed/operational_permissions.sql` for
   each company. Explicitly assign roles; the seed never grants them. New company
   owners receive plan-appropriate permissions through signup metadata.
4. Company operators enroll authenticator MFA in Account security, save the
   single-use recovery codes securely offline, confirm a code, then sign out and
   sign in again with a fresh code. `support.manage` and
   `managed_android.manage` require a session authenticated with MFA.
5. Build a signed Android release for the approved HTTPS server. No private LAN
   server URL or signing secret is committed. Test the debug build on disposable
   devices first; a debug APK is not a managed production distribution.
6. Configure a server scheduler to run the escalation worker every minute under
   a dedicated, active same-company operator with `escalations.manage`:

   `php bin/run-escalations.php --company=COMPANY_ID --operator=SERVICE_USER_ID`

   Watch exit status and execution age. A failed worker rolls back that tenant's
   notifications/events. Rules support in-app delivery only. External email,
   SMS and WhatsApp transports require a separate provider integration and are
   not represented as delivered. Do not use this as the only emergency channel.

## Android app allowlisting and notification dropdown

Supported enforcement is for fully managed, company-owned Android devices with
Sentinel as **device owner**, not ordinary installs or a personal work profile.
Use an authorised Android Enterprise provisioning process for production. On a
factory-reset test phone, an IT operator can use the documented Android development
device-owner setup (`adb shell dpm set-device-owner
com.sentinel.guard/.SentinelDeviceAdmin`). Do not run this against an employee's
personal or already-managed phone. This release does not factory-reset devices.

1. Install the signed Sentinel application and all approved apps before disabling
   app installation. Android 9+ is required for Sentinel to launch arbitrary
   approved apps in multi-app kiosk mode; the base app still supports Android 8.
2. In Managed Android phones, create enrollment. Store the unique **offline
   recovery code** in a restricted company vault before enabling restrictions.
   Enrollment expires after 30 minutes and is consumed once; codes are shown once.
3. Sign in to the phone with its company guard account, choose Enrol company-managed
   phone, and enter its enrollment code. Enrollment binds a random installation
   ID to the company. A separately encrypted installation credential allows
   policy polling and release even when the guard is signed out.
4. Save a policy using exact Android package names. Sentinel remains allowed.
   The controller configures Android lock task packages, a managed home launcher,
   optional installation restriction and status-bar restriction. It does not
   uninstall blocked apps or erase their data. System Settings/installers are
   rejected as kiosk apps. Review approved apps carefully: their own browsers,
   file pickers or exported activities may expose additional workflows.
5. Compare the saved revision with **reported revision/state** and last contact.
   NOT_DEVICE_OWNER means enforcement was not available. PARTIAL can mean an app
   is missing, kiosk is not active, or a restriction failed. Saving a policy is
   not confirmation of enforcement. OEM behaviour needs physical-device testing.
6. Use the phone's approved-app buttons to launch permitted apps. Kiosk policies
   do not change the emergency call mechanisms provided by Android/OEM software;
   explicitly test those and company radio/phone procedures on every device model.
7. Release a policy in the PC console to remove restrictions on next contact.
   Background polling is opportunistic (Android JobScheduler), not instantaneous.
   The last cached policy remains available offline and is applied when Sentinel
   resumes; company guard sign-out does not remove management.
8. For offline recovery, choose Device management / supervisor recovery and enter
   the per-device recovery code. Five failed attempts impose a 15-minute local
   delay. Recovery releases kiosk/status-bar/install/window restrictions and
   persists across restart for that revision. Only a newer server revision may
   re-enable restrictions. Recovery is reported to the server on subsequent
   contact and recorded as a **device-reported** event. Device-owner enrollment
   remains; removal/decommissioning belongs to the company's IT process.

Keep a separate IT recovery/provisioning procedure if the device is damaged,
the app cannot start or encrypted storage is lost. No remote wipe, silent screen
control or hidden management of personal phones is included. A stolen installation
credential must be treated as a credential compromise; do not simply copy an old
installation record to a replacement phone. Create a new enrollment for it.

For an expired or interrupted enrollment, Replace enrollment regenerates the
codes and revokes the previous installation credential. It requires an enrollment
that never became active, or a disabled policy whose release was acknowledged.
It cannot silently abandon a phone with active restrictions.

## Guard reporting, photos and queue recovery

Guards choose an assigned shift and submit an incident or occurrence/handover
with factual text and an optional camera thumbnail. The server derives the guard
and site from authenticated identity and shift ownership. JPEGs are limited to
200 KB and 4096 pixels. Photos are for operational reports, not full-resolution
forensic evidence. Manager incident/occurrence pages expose protected photo views.

Reports and photos use the existing encrypted queue and stable operation UUIDs.
An interrupted/repeated upload commits one mutation/receipt. Queue schema version
2 upgrades existing queues without deleting payloads. A blocked event preserves
later events. The phone shows action, UUID and HTTP status without exposing report
contents; retry requires a reference/reason, retains the original UUID/content and
records a bounded encrypted local recovery history. It does not claim that a
supervisor remotely approved a repair. Invalid records can remain blocked until
the underlying account/site problem is corrected; no destructive discard or
arbitrary payload editing is provided. Fully offline dependent patrol creation
remains limited by the server-issued patrol-session ID.

Guards can upload a diagnostic snapshot containing app version, installation ID,
queue counts and validated/unverified connectivity. It excludes report text,
photos, credentials and location. Support sees the server receipt time. A PC
cannot retrieve device-only records while that phone is offline. Do not clear
app data or reinstall as a queue repair procedure.

## Accounts, MFA and temporary support

TOTP uses 30-second SHA-1 codes with a one-step clock window. Replays are rejected,
failures are counted with a temporary lock, and recovery codes are single-use.
MFA secrets are encrypted with a key derived for that purpose from the separate
server `ENCRYPTION_KEY`; keep it in secure backup. JWT signing-key rotation does
not re-encrypt MFA secrets. Losing or replacing the encryption key makes existing
MFA secrets unreadable without the previous key. Plan encryption-key rotation or
re-enrollment deliberately.

Individual login sessions can be revoked from Account security, or company-wide
by authorised MFA-authenticated support. Changing a password revokes that user's
registered sessions. Disabling an account blocks company access across devices.
Already-running requests and records cached on an offline phone are not erased.

Temporary support grants last 1–8 hours and require an active same-company support
account with MFA. Assign only `support.delegated_view` and self-service account
security access to that role; the dedicated diagnostic endpoint checks the grant
and MFA-authenticated session on every request. Grants do not remove broader
permissions separately assigned to an account. Never grant ordinary `support.view`
or company management permissions to a time-limited diagnostic account.

## Business workflows

- **Compliance:** track a requirement, reference, employee and due date. Record a
  human evidence review. Renewing the date/reference clears verification so new
  evidence is reviewed. This is not an automatic PSiRA verification integration.
- **Equipment:** maintain asset codes; record issue/return condition and actor.
  One open handover per asset is enforced by serialized asset access inside the
  transaction. No weapons/firearm licensing workflow is implied.
- **Escalations:** create primary/backup recipients and a delay. A tenant worker
  deduplicates attention events and due compliance reminders. Acknowledgement
  stops backup escalation. Review/acknowledge old events after resolving them.
  Worker limits currently cover 20 active rules, 500 findings and 100 due
  compliance items per evaluation; larger deployments need measured batching.
- **Client portal:** map an account to specific client IDs and assign
  `client_portal.read`. Publish only reviewed client-appropriate report text.
  The portal never exposes raw internal incidents, guard location or all sites.
  Remove a mapping to revoke access. Existing other role permissions remain
  authoritative; use a dedicated client role.
  Managers can generate a draft from site-scoped patrol counts and current open
  incident counts, then review/edit and publish it. Generating a draft never
  publishes it; current statuses must not be described as historical snapshots.
- **Payroll export:** approve completed attendance, then export a bounded date
  range. A record fingerprint invalidates approval after relevant attendance
  changes. CSV protects formula-leading values. Worked hours are elapsed hours;
  breaks, overtime, wages and statutory treatment belong to payroll software.

## Acceptance and operational limits

Verify two-company isolation, viewer/manager differences, revoked/expired sessions,
MFA recovery and lockout, grant expiry, duplicate report delivery, malformed photos,
encrypted queue migration/reboot, client mapping revocation, double asset issue,
stale payroll approval and worker idempotency/backup escalation. Rehearse audit
failure rollback, backup restore and recovery from database outages.

On physical Android devices verify provisioning, notification/quick-settings
dropdown, home/recents/back, approved camera and radio apps, calls, accessibility,
restart, screen lock, battery/network loss, signed-out policy polling, remote
release, offline recovery, and a missing approved app. Do not enable kiosk on a
live site until recovery and emergency procedures have passed on that exact model.

Review retention and deletion for report photos, diagnostic snapshots, session
records, client reports and MFA recovery material. No automatic retention purge
is installed; do not prune mobile retry receipts while old commands may return.
