# Install and update without Play Store or Google services

Sentinel Guard is a standalone Android APK. Installation and operation do not
require a Google account, Play Store, Google Play services, Firebase, Google
Code Scanner, ML Kit or Google-hosted push messaging. QR decoding is the embedded
Apache-2.0 ZXing Core library; GPS uses Android LocationManager and management
uses Android DevicePolicyManager. Its Maven namespace `com.google.zxing` is a
historical package name, not a Play services runtime dependency.

The build still uses the Android SDK and Android Gradle plugin. That is distinct
from requiring Google accounts or services on guards' phones. Standard Android,
manufacturer and company installation controls still apply.

## Distribution choices

1. **Private company website:** build/sign the APK, publish it with its metadata
   on the approved HTTPS Sentinel server, then visit `/downloads`. The page
   offers the APK only when real release metadata exists. It does not advertise
   a missing or debug build as a production release.
2. **Offline file transfer:** IT can copy the same signed APK using USB, a trusted
   local file channel or a company-controlled local network. Open it using the
   approved file manager and follow Android's per-source installation prompt.
3. **ADB for authorised IT/test devices:** use `adb install -r sentinel-guard.apk`.
   Use approved USB debugging procedures. Disable development access after
   provisioning according to company policy.
4. **Company device-management distribution:** the organisation can deploy the
   signed APK through its own compatible management tool. Sentinel does not
   require Managed Google Play or Android Management API enrollment to use its
   device-owner controls; device-owner provisioning is a separate IT operation.

## Build and publish a company release

Use a protected company signing keystore and the existing release signing
configuration. Set the HTTPS `SENTINEL_SERVER_URL` and `SENTINEL_SIGNUP_URL`, then
run `assembleRelease`. Do not commit keys/passwords or publish a debug APK as the
production app. Keep the signing key in a secure, recoverable backup; Android
updates must retain the original signing identity.

On the Windows packaging machine:

```powershell
./scripts/package-guard-release.ps1 -ApkPath <signed-release.apk> -BuildToolsDirectory <Android-build-tools>
```

The script verifies the APK signature, rejects the standard Android Debug
certificate, reads the actual package/version, and produces:

- `public/downloads/sentinel-guard.apk`
- `public/downloads/guard-release.json`, including SHA-256 and the signing
  certificate fingerprint.

These generated distribution files are ignored by Git. Publish them together
through the company HTTPS server. For the first install, verify the trusted
company source and certificate fingerprint separately; a checksum alone is not
publisher authentication. Existing installations verify the signing identity
when accepting an update.

## Updates and managed-device restrictions

Increase versionCode for each update and retain the same application ID/signing
key. Install over the existing app to preserve encrypted queues and local keys.
Do not uninstall, clear storage or change signing identity while records remain
on the phone. A release signed with a different key cannot update the installed
package and must not be presented as a routine update.

For kiosk phones, install Sentinel and approved camera/radio apps before blocking
installation. IT must release installation restrictions for a supervised manual
update, verify successful synchronization, then restore the reviewed policy.
The native app's Company APK installation / updates button opens the company
download page; kiosk policy may deliberately block browsers, so IT can use USB
or its approved management channel instead. No silent updater is claimed.

## Google-free field acceptance

On at least one physical Android/AOSP device without a Google account or Play
services, verify fresh installation, company setup QR, login/MFA, checkpoint QR,
NFC, GPS, offline report/photo capture and replay-safe synchronization. The phone
needs a compatible camera application for camera capture; allowlist its exact
package on kiosk devices. No extra scanner app or downloaded recognition model
is required. Confirm direct updates preserve a pending encrypted queue.

See [field rollout guide](FIELD_RELEASE_0_7.md) for device-owner provisioning,
offline recovery, server schema/permissions, account security and remaining
physical-device release gates.
