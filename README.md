# Sentinel AI Security Suite

A security operations development project by **Sabelo Andile Biyela**, based in Durban, South Africa.

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
