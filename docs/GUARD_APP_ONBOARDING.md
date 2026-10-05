# Guard app onboarding

## Local manual-test build

The debug APK may be built with a private RFC1918 HTTP server address for
same-Wi-Fi testing. Debug builds also permit screen capture so testers can
record complete onboarding and workflow evidence. The current local test APK
is bound to `http://192.168.1.187`.

These exceptions are controlled by `BuildConfig.DEBUG`. Release builds retain
`FLAG_SECURE`, reject HTTP setup addresses, and disable cleartext traffic. A
private HTTP address must never be used for public distribution or off-network
guard devices.

Sentinel Guard separates organisation creation from guard access:

- An organisation owner creates the company through Sentinel's web signup.
- Owners and administrators manage the company in the web dashboard. The app sign-in screen includes an **Open owner or administrator dashboard** shortcut.
- The owner or supervisor creates each guard account and assigns the correct company, site, role and permissions.
- The guard receives a username or email, a temporary password through the organisation's approved process, and access to the organisation's configured app.

If an owner or another account without `guard.self` permission tries to sign in to the guard workspace, the app explains which account is required and directs the user to the web dashboard. It does not show a generic `Forbidden` response.

Guards do not create unattached accounts in the app. This prevents a person from selecting an organisation, site or security permissions without authorisation.

## Production release configuration

The public release should contain the production address, so ordinary users never configure a server. Supply these Gradle properties when building the signed release:

```text
SENTINEL_SERVER_URL=https://your-sentinel-domain.example
SENTINEL_SIGNUP_URL=https://your-sentinel-domain.example/admin/index.html?signup=FREE
```

Both addresses must use HTTPS. Do not publish a production APK with an empty server address.

The Gradle release task rejects a missing signing configuration, HTTP address,
or empty signup address. Copy `apps/guard-android/release-signing.properties.example`
to a protected location outside source control, provide the values through CI
secrets or Gradle properties, and retain the keystore securely. A lost signing
key prevents ordinary app updates unless the distribution service supports a
key-recovery procedure.

## Private deployment setup

A private deployment can connect an unconfigured app by scanning a QR code containing:

```text
sentinel://connect?server=https%3A%2F%2Fyour-sentinel-domain.example
```

The app validates HTTPS, rejects credentials and paths in the server address, and stores the accepted origin in Android encrypted storage. Manual entry is available only under **Administrator setup** for testing or private deployment support.

## User-facing first run

The welcome screen provides:

- Normal username/email and password sign-in when the release is configured.
- A first-run choice between **Create organisation**, **Join as a guard**, and **Explore demo**.
- Company setup QR scanning only after the user chooses the guard path.
- A dedicated new-user page for organisation owners, guards and private-deployment administrators.
- A web-signup link for organisation owners that can be configured independently from the API address.
- Sign-in help explaining that a guard must contact their employer or supervisor.
- An offline demo that records and sends no information.

The current debug APK points to the private local test server at
`http://192.168.1.187`; it is not pointed at a production server because no
public HTTPS deployment has been approved. It can also be explored in demo
mode. Rebuild it whenever this PC's private address changes.
