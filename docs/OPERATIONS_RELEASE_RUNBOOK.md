# Operational release runbook

This document describes rollout work. No production deployment, database migration, role grant, notification transmission, or backup restore was performed by the implementation task.

## Release gates

1. Run PHP lint and `php vendor/phpunit/phpunit/phpunit --no-coverage`. Coverage needs a compatible coverage driver; disabling coverage does not disable assertions.
2. Run `php testing/operational-performance.php`. This is an isolated SQLite query benchmark, not a MySQL concurrency or capacity test.
3. Rehearse the canonical schema runner on an isolated MySQL/MariaDB instance. Include `063_mobile_operations.sql` before enabling guard commands. Never run the canonical bootstrap runner against an existing production database without reviewing every statement and using an approved migration procedure.
4. Review `database/seed/operational_permissions.sql` in staging. Set `@sentinel_company_id` to the intended company. Resolve its reported legacy name conflicts deliberately; the script neither renames existing permissions nor grants roles. Grant only the reviewed permissions through tenant-safe RBAC administration. Regenerate this inventory after adding routes.
5. Give guards only `guard.self` and any separately reviewed self-service permissions. Do not give them company-wide management permissions as a workaround for mobile access.
6. Build and lint `apps/guard-android`. Complete the real-device checklist below. A debug APK is for testing, not public distribution.
7. Rehearse deployment rollback and backup restoration. Obtain a maintenance window and operator authorization for production actions.

On 5 October 2026, the Android 0.6.4 debug build passed `assembleDebug`, `testDebugUnitTest` (8 tests, 0 failures) and `lintDebug` with `minSdk 26`, `compileSdk 37` and `targetSdk 37`. Lint reported 0 errors and 7 warnings, primarily AndroidX Kotlin-style suggestions plus a Gradle-version advisory. The APK is under `apps/guard-android/app/build/outputs/apk/debug/app-debug.apk`. QR scanning prefers Google Code Scanner and falls back to a bundled offline model using the camera capture flow. The generated APK is approximately 26.8 MB. Runtime QR acceptance remains outstanding: installation to the connected Android 10 device was rejected by the device, so no feature was exercised on this build. Android 8/API 26 and Android 17/API 37 acceptance also remain outstanding. Release signing and physical-device acceptance remain release gates.

Before producing a release APK, set `SENTINEL_SERVER_URL` and `SENTINEL_SIGNUP_URL` to the approved public HTTPS deployment. Ordinary users are never asked for these values. See `docs/GUARD_APP_ONBOARDING.md` for the owner, guard, setup-QR and offline-demo flows.

## Web deployment

- Serve only `public/` as the document root through HTTPS. Keep `.env`, source, vendor, logs, backups, and test data outside the public directory.
- Configure PHP 8.2+ with PDO MySQL, JSON, OpenSSL, mbstring and the Composer-required extensions. The optional local help assistant uses cURL.
- Set the production environment and turn off debug responses. Generate deployment secrets outside source control and keep secret values out of reports and logs.
- Use a least-privilege application database account. Use a separate migration identity. The runtime account should not create or drop databases or tables.
- Configure HTTPS termination, request-size limits, edge rate limits for login and guard commands, and explicit origins if a separate frontend origin is introduced.
- Test authentication → tenant resolution → authorization → handler ordering. Test two companies and two users within the same company. Verify guard ownership and notification recipient isolation.
- Protected API mutations now commit together with a required route audit event. An unavailable audit store causes rollback and a safe failure; monitor that dependency as critical.
- Deploy web source and migration-compatible backend before releasing the mobile client. Do not enable a client that depends on missing schema 063.

## Backup and restore

The operator must choose retention, recovery point objective (RPO), and recovery time objective (RTO) before release. No numerical recovery promise is established by this code.

- Take encrypted database backups using the installed database vendor's supported tools. Keep a copy on independent storage with restricted access and alert on backup failure or excessive age.
- Back up schema version information and required encrypted application configuration separately. Keep backup decryption keys in the organization’s secret store, not beside the backup.
- Test restoration into a new isolated database. Verify table counts, constraints, representative tenant-scoped reads, audit history, and retry receipts before changing traffic.
- Preserve `mobile_operations` receipts for at least the maximum supported offline/retry lifetime. Deleting receipts while old commands can still arrive can reintroduce duplicate mutations. No automatic receipt pruning is installed.
- Define a reviewed lifecycle for visitor personal data, GPS telemetry, audit records, and device queues. Retention must be consistent with operational and legal obligations determined by the operator.

## Monitoring and incident response

Track HTTP availability and latency, login failures, 401/403 rates, 409 retry conflicts, 422 validation errors, 503 database/audit/provider failures, database connections, slow queries, disk use, backup age, and mobile receipt growth. Alert thresholds require staging load measurements.

Use `GET /healthz` for a dependency-free liveness probe and `GET /readyz` for a database readiness probe. Neither endpoint returns configuration, credential, tenant, or exception details. Alert on consecutive readiness failures rather than a single brief database reconnect.

Use request IDs to correlate application and route-audit events. Never log passwords, tokens, raw mobile payloads, precise locations, or help-assistant questions and answers. Investigate a sustained audit outage before restoring mutation traffic.

Supervisor attention is evaluated on request. It is not an installed background alerting service. The default review thresholds are 15 minutes for a missing check-in when no explicit window is configured and 30 minutes for stale location telemetry. Operators must approve these policies. Location gaps can reflect device or connectivity problems. Overdue checkpoints use the first outstanding configured checkpoint interval. Schema `065_patrol_schedule_windows.sql` provides explicit expected patrol-start windows; a patrol is shown as missed only when its window ends without matching start evidence. Apply that schema before enabling Patrol schedule.

For a suspected cross-company exposure: restrict affected traffic, preserve evidence and request IDs, revoke affected sessions using established account controls, verify isolation with disposable accounts, and follow the organization's incident procedure. Do not delete evidence to make a check pass.

## External services

- Notifications currently deliver in-app. EMAIL/SMS/WHATSAPP requests return 503; provider transport, credentials, delivery retry workers and delivery receipts remain deployment/integration work.
- The local Help Guide assistant is disabled by default. Configure `AI_HELP_ENABLED`, `AI_HELP_BASE_URL`, and `AI_HELP_MODEL` after installing Ollama and the chosen model on the Sentinel host. The URL is restricted to a loopback address.
- The web guide serves client managers through `dashboard.view`; the Android guide provides offline guard instructions and optional follow-up questions through `guard.self`. Each protected route assigns its audience on the server. The assistant receives only a supported help topic and the deliberately entered question. It has no repository, record, database, code, location, device or action access. The service does not persist questions or answers. Obvious contact details and credentials are rejected before a model call. See `docs/LOCAL_AI_HELP_ASSISTANT.md`.

## Signup and subscription tiers

Apply schema `064_company_subscriptions.sql` before enabling public signup. Signup creates the company, owner person/employee/user, owner role, plan permissions, default client, branch and site, and subscription in one transaction. Existing companies without a subscription row are temporarily grandfathered to preserve compatibility; create and review a subscription row for every existing company before launch.

- Free: up to 10 active users and 3 active sites, with unlimited retained Occurrence Book entries, incidents, visitors, vehicles/access events, inspections, alerts and in-app notifications. Core people/user/role administration is included so a small team can actually adopt the product.
- Essential: up to 30 active users and 10 active sites, adding workforce administration, attendance, shifts, contracts and operational reports.
- Professional: up to 100 active users and 50 active sites, adding patrols, devices, GPS/geofences and supervisor workflows.
- Enterprise: all protected modules, integration controls and rollout support.

The free plan intentionally does not meter safety record creation or delete old records. Seat and site limits create the upgrade boundary without interrupting operational recordkeeping. Archived users and closed/inactive sites do not consume capacity.

South Africa is the default billing market. The public pricing and signup UI also supports the United States, United Kingdom, European Union, Australia and an international USD option. The selected billing market is stored on the company record. These are launch prices rather than automatic exchange-rate conversions; checkout must confirm the final currency, tax treatment and order total.

Paid-plan signup creates a 14-day `TRIALING` subscription. No payment processor, automatic renewal, invoicing, cancellation worker, grace period or plan-change API is connected. Do not advertise paid checkout until those commercial rules and a provider are configured. Authorization checks the subscription as well as RBAC, so a hidden menu is never the security boundary.

## Android acceptance checklist

Test on managed physical devices starting with Android 8/API 26 and including the latest supported Android release (API 37 for this build):

- Valid/invalid login, HTTPS certificate validation, 401 expiry, disabled account and wrong-company cases.
- Two different users on one device. Sign out during synchronization. Pending encrypted records remain associated with the original account and server.
- Disconnect before sending, during upload, and after server commit but before response. Reconnect and verify one server mutation for each stable operation UUID.
- Clock-in/out for own shifts, rejected peer shifts, completed/cancelled shifts, and invalid timestamps.
- Start/complete patrols; valid, unknown and wrong-route QR/NFC codes; duplicate/ambiguous checkpoint registry values.
- QR scanning prefers Google Code Scanner. If Google Play services or its scanner module is unavailable, the app captures a camera preview and reads the QR locally with the bundled model. Test this fallback on devices without Google services. NFC uses the lowercase hexadecimal tag UID registered as `nfc_tag`; UID matching is not cryptographic anti-cloning proof.
- Fresh foreground GPS, denied permission, GPS disabled, poor signal and mock location. Continuous/background GPS is not implemented.
- Queue capacity (500), blocked 403/404/409/422 events, process death, reboot, encryption-key failure, and device loss. A blocked event stops later events; a secure supervisor repair/export workflow is still needed.
- Android's background job scheduling is opportunistic, not a real-time delivery guarantee. Users can synchronize explicitly.
- New patrols created offline must synchronize before attaching scans to the server-issued session. Fully offline dependent patrol creation is not implemented.
- Release signing, managed distribution, privacy notice, accessibility, battery use and long-duration field testing remain release gates.
