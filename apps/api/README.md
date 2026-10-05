# Archived secondary API prototype

The empty PHP scaffold previously stored here was removed during the empty-file audit. It was never a working second application and its duplicate `App\` namespace made the repository architecture ambiguous.

The canonical Sentinel application is now unambiguous:

- public entry point: [`../../public/index.php`](../../public/index.php)
- bootstrap: [`../../bootstrap/app.php`](../../bootstrap/app.php)
- API routes: [`../../routes/api.php`](../../routes/api.php)
- runtime namespace: `Sentinel\`
- guard API contract: [`../../docs/GUARD_API_CONTRACT.md`](../../docs/GUARD_API_CONTRACT.md)

The remaining `public/dashboard.html` file is a historical visual prototype. It is not an application entry point and should not be deployed.

See [`../../docs/EMPTY_FILE_AUDIT.md`](../../docs/EMPTY_FILE_AUDIT.md) for the cleanup record.
