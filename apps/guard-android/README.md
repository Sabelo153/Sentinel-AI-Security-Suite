# Sentinel Guard Android

Native Kotlin client for the tenant-scoped guard API. Requires Android 8.0+ (API 26+) and an HTTPS Sentinel server. The app compiles/targets Android API 37. Build with JDK 17+ (tested toolchain uses JDK 21), Android SDK 37, Android Gradle Plugin 9.2.1 and Gradle 9.4.1.

From this directory, run Gradle `assembleDebug testDebugUnitTest lintDebug`. `local.properties` supplies the local SDK path and must be adjusted on another machine. No release signing identity is embedded.

The app uses Android Keystore AES-GCM to encrypt sessions, cached workspace data and queued command payloads. Queues are partitioned by server and user. Successful synchronization removes acknowledged records; transient failures preserve them, and non-retryable errors block the queue for review. App backup is disabled. A lost encryption key cannot be bypassed to recover encrypted records.

Implemented controls cover login, own shifts/attendance, patrol start/completion, QR capture (Google Code Scanner with an offline bundled-model camera fallback), NFC tag UID capture, foreground GPS and retry-safe synchronization. Background synchronization uses Android JobScheduler. Read the deployment runbook for hardware tests and known limits before field use.

The app uses [Google Code Scanner](https://developers.google.com/ml-kit/vision/barcode-scanning/code-scanner) when available. If Google Play services or its scanner module is unavailable, it opens the phone's camera and reads the captured QR locally using the bundled [ML Kit Barcode Scanning](https://developers.google.com/ml-kit/vision/barcode-scanning/android) model. The fallback does not request camera permission in Sentinel or upload the captured image. The build versions follow the [AGP 9.2 compatibility requirements](https://developer.android.com/build/releases/agp-9-2-0-release-notes). No location or security data was sent to an external service during development.
