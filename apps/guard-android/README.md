# Sentinel Guard Android

Version 0.7 adds encrypted offline incident/occurrence reports with optional JPEG
camera thumbnails, unchanged-command queue retries, diagnostic snapshots, MFA
login and per-session logout. Device-owner-only kiosk/app allowlisting and
status-bar restrictions include a per-device offline recovery code. See
[`FIELD_RELEASE_0_7.md`](../../docs/FIELD_RELEASE_0_7.md) before provisioning or field use.

Native Kotlin client for the tenant-scoped guard API. Requires Android 8.0+ (API 26+) and an HTTPS Sentinel server. The app compiles/targets Android API 37. Build with JDK 17+ (tested toolchain uses JDK 21), Android SDK 37, Android Gradle Plugin 9.2.1 and Gradle 9.4.1.

From this directory, run Gradle `assembleDebug testDebugUnitTest lintDebug`. `local.properties` supplies the local SDK path and must be adjusted on another machine. No release signing identity is embedded.

The app uses Android Keystore AES-GCM to encrypt sessions, cached workspace data and queued command payloads. Queues are partitioned by server and user. Successful synchronization removes acknowledged records; transient failures preserve them, and non-retryable errors block the queue for review. App backup is disabled. A lost encryption key cannot be bypassed to recover encrypted records.

Implemented controls cover login, own shifts/attendance, patrol start/completion, QR capture (embedded ZXing decoder with Android camera capture), NFC tag UID capture, foreground GPS and retry-safe synchronization. Background synchronization uses Android JobScheduler. Read the deployment runbook for hardware tests and known limits before field use.

QR decoding uses the standalone Apache-2.0 ZXing Core 3.5.4 library bundled in the APK. It requires no Google account, Google Play services, scanner app, model download, Firebase or Play Store. The historical Maven package name `com.google.zxing` does not introduce a Play services dependency. Camera capture uses the installed camera app. See [direct APK distribution](../../docs/APK_DISTRIBUTION.md) for company hosting, USB installation and updates.
