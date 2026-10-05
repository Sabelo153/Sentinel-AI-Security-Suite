# Windows local test host

This profile runs Sentinel on the current Windows PC using the installed
Apache 2.4 and MariaDB Windows services. It is suitable for same-network
manual testing. It is not an internet deployment.

## Local service layout

- Apache serves only `C:/Development/Sentinel-AI-Security-Suite/public`.
- SentinelMariaDB listens locally on port 3307 and is never exposed through the router.
- The local address is `http://192.168.1.187/` while this PC retains that DHCP
  address.
- Clean application routes are handled by `public/.htaccess`.
- The `Apache2.4` and `SentinelMariaDB` services start automatically with Windows.
- The old `MariaDB` service is disabled and its data retained for rollback.
- Administrator credentials are stored in the restricted local file
  `C:\ProgramData\SentinelDatabase\private\admin-client.ini`.
- `Repair-SentinelSchema.ps1` and `Test-MariaDbAccess.ps1` use this file when
  the replacement service exists. The old provisioning script refuses to run
  against this setup so it cannot overwrite the new connection.
- Windows Firewall permits inbound TCP port 80 on private networks through the
  `Sentinel Web (Private HTTP)` rule.

## Manual test entry points

- On this PC: `http://127.0.0.1/`
- From another device on the same private Wi-Fi: `http://192.168.1.187/`
- Admin sign-in: `http://192.168.1.187/admin/index.html`
- Debug APK: `apps/guard-android/app/build/outputs/apk/debug/app-debug.apk`
- Local PC Support Console: `http://127.0.0.1/support` (this PC only)

The private IP can change after a router or PC restart. Reserve
`192.168.1.187` for this PC in the router before a longer pilot. If it changes,
update `APP_URL`, the two `SENTINEL_*_URL` entries in the Android
`gradle.properties`, and rebuild the debug APK.

## Internet release requirements

Do not expose the HTTP-only local address to customers or place a database port on
the internet. A public release still requires:

1. a domain or subdomain whose DNS can be changed;
2. trusted HTTPS in front of Apache;
3. router or tunnel configuration for ports 80/443 only;
4. production environment secrets and `APP_DEBUG=false`;
5. a tested database backup and restore;
6. a release APK built with the final HTTPS site and signup URLs.

The public domain is intentionally absent from this profile. It must never be
invented or replaced with the private `192.168.1.187` address in the Android
release build.

## Local support console

When a local test problem occurs, open `http://127.0.0.1/support` **on this
PC**. It checks the application, database connection, writable runtime storage,
accounts/access, staffing, shifts, patrols, guard mobile sync, devices,
incidents, Occurrence Book, visitor/vehicle access, inspections, alerts,
notifications, reports, audit, subscriptions and the optional AI guide. Every
check is read-only and no credentials, database settings, customer data,
patrols, reports, GPS data or logs are displayed. It is unavailable from the
private network and disabled by default in production. Set
`SUPPORT_DIAGNOSTICS_ENABLED=true` only for an authorised non-production
support session.

## Patrol workflow repair

Run `Repair-SentinelSchema.ps1` as an administrator after updating an existing
local installation. It applies the operational schemas through `065`, including
patrol schedule windows, and seeds the global authority levels required when an
administrator assigns a guard to a position. The Sentinel runtime database user
does not receive schema-change privileges.
