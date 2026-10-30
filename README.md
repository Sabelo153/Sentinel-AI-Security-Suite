# Sentinel AI Security Suite

A security operations development project by **Sabelo Andile Biyela**, based in Durban, South Africa.

**[Live project website](https://sabelo153.github.io/Sentinel-AI-Security-Suite/)** — interactive sample tour hosted on GitHub Pages. Login and operational records require separate server hosting.

## Project status
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
