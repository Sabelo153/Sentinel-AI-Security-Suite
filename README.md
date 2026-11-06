# Sentinel AI Security Suite

A security operations development project by **Sabelo Andile Biyela**, based in Durban, South Africa.

**[Live project website](https://sabelo153.github.io/Sentinel-AI-Security-Suite/)** — interactive sample tour hosted on GitHub Pages. Login and operational records require separate server hosting.

## Project status
The latest addition is [Sentinel 0.15 service delivery](docs/SERVICE_DELIVERY_0_15.md): alarm response, guard transport, effective post orders, continuity plans, equipment inspections, control-room handovers, extra-service quotations/fulfilment, client mobilisation approval, subcontractor site/document controls, risk assessments and site analytics.

The preceding addition is [Sentinel 0.14 workforce assurance](docs/WORKFORCE_ASSURANCE_0_14.md): assigned lessons and quizzes with expiring internal certificates, photographed lost-and-found custody and signed collection, staged incident investigations with independent closure review, and stock/uniform ledgers with signed acceptance and controlled returns.

The preceding addition is [Sentinel 0.13 business operations](docs/BUSINESS_OPERATIONS_0_13.md): manager-confirmed open shifts, client-approved visitor QR passes, fleet records, effective-dated renewals, encrypted tenant backups with restore verification, pilot setup and your own sponsor banners on Free only. Paid plans stay ad-free. Android 0.10 adds home-screen sponsor support without Play Store dependencies.

The preceding web/API addition is [Sentinel 0.12 operations hub](docs/OPERATIONS_HUB_0_12.md): short-burst site voice channels, a geographic control-room map, monthly client service reviews, scoped API keys/signed webhooks, consent-based support tools and operational alerts.

The preceding [Sentinel 0.11 operational readiness](docs/OPERATIONAL_READINESS_0_11.md): encrypted offline inspections, signed key/card custody, qualified replacement suggestions, emergency roll calls and drills, reviewed language content, and tenant branding/subscription administration.

The preceding [Sentinel 0.10 client workflows](docs/CLIENT_WORKFLOWS_0_10.md): custom inspection forms with required photos and failed-check actions, client complaints and confirmation, leave-aware rostering, assigned-site access, explicit report acknowledgements, and quotation-to-contract/site mobilisation.

The preceding addition is [Sentinel 0.9 site assurance](docs/SITE_ASSURANCE_0_9.md): versioned instructions, handover checklists, training gaps, original evidence, service-level reports, fault tickets and controlled retention with investigation holds.
It also adds [photo reporting, NFC setup, complete offline patrol capture and scheduled client portal/email summaries](docs/REPORTING_AND_OFFLINE_0_9.md), including six-hour defaults and client-selected delivery times.

The preceding [Sentinel 0.8 control-room release](docs/CONTROL_ROOM_0_8.md) adds dispatch, lone-worker check-ins, roster coverage and relief, offline gate capture, invoice drafts, phone health and readiness monitoring. Follow both releases' migration and acceptance steps before rollout.

This repository is a source snapshot of work in development. Features, architecture plans and verification notes are documented in the codebase; they are not a claim of a completed commercial product or a production security certification. The AI name describes the project's direction, not a measured detection-accuracy claim.

## What is in the source
- PHP application framework, API routes, authentication and role-based access components.
- MySQL/MariaDB schema and migrations for security operations.
- Web interfaces and services for incidents, occurrence records, attendance, patrols and device management.
- A Kotlin Android guard client.
- Unit/integration tests and architecture documentation.

## Technologies
PHP 8.2+, MySQL/MariaDB, HTML, CSS, JavaScript, Kotlin/Android and Composer.

## Field operations 0.7 source
The next release adds mobile incident/occurrence reporting with photos, safe
unchanged-command queue retries, uploaded diagnostics, individual login-session
revocation, authenticator MFA with single-use recovery codes, temporary diagnostic
access, compliance requirements, equipment handovers, approved client reports,
attendance approvals/exports and a scheduled in-app escalation worker.

Android device-owner controls provide approved-app kiosk policies, optional
notification/status-bar restrictions and offline supervisor recovery on supported
company-managed phones. Ordinary app installation does not grant these controls.
See [field release and rollout guide](docs/FIELD_RELEASE_0_7.md) for schemas,
permissions, provisioning, recovery, integration limits and physical-device
acceptance. These source additions are not a production deployment.

The Android app can be installed from a company-hosted APK or USB without Play
Store, a Google account or Play services. See [private APK installation and updates](docs/APK_DISTRIBUTION.md).

## Start locally
1. Install PHP 8.2+, Composer and a compatible MySQL/MariaDB server.
2. Run `composer install`.
3. Copy `.env.example` to `.env`, then configure your local database and generate your own application/JWT/encryption keys.
4. Review migrations and seed profiles before running `php sentinel migrate` against an empty development database.
5. Run `php sentinel serve 127.0.0.1 8080` and `php sentinel serve:api 127.0.0.1 8091`.

See [Setup guide](SETUP_GUIDE.md), [framework documentation](docs/ORIGINAL_PROJECT_README.md), and [Android client](apps/guard-android/README.md) for further details. Development seed data and verification scripts are for isolated test environments only.

## Publishing notes
Local environment files, databases, uploads, logs, backups, local seed files, generated builds, installed dependencies and original Git history are excluded from this public snapshot. No live security records are included. This source upload did not run the application's test suite or verify readiness for deployment.

The PHP API and database require server hosting. GitHub Pages cannot run them.

## License
The project's existing [MIT license](LICENSE) is retained.
