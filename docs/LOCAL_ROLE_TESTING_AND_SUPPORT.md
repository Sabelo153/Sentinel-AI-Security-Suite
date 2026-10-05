# Local role testing and support

Company: Sentinel Role Testing (Enterprise trial; subscription expiry still applies).

Accounts: admin.roles@sentinel.local, supervisor.roles@sentinel.local,
operator.roles@sentinel.local, viewer.roles@sentinel.local, guard.roles@sentinel.local.
Passwords are supplied separately in the chat, not in support exports.

- Company Admin: all company permissions, not host/database administration.
- Supervisor: read/review plus staffing, attendance and patrol management.
- Operator: daily operational records; no user or role administration.
- Viewer: read-only permissions.
- Guard: guard.self only; use the Android APK. Assign shifts and patrols before field tests.

These are dedicated local test accounts. Existing accounts and companies were preserved.
The existing demo guard still has its practice shift and route.

Open http://127.0.0.1/support on the host PC. The server rejects remote clients.
Run system checks, search for a feature or symptom, follow its suggested checks,
and record manual test outcomes. Download the safe JSON report for support.
Reports include infrastructure statuses, table names and manual outcomes only.
Manual outcomes are held in browser memory; export before closing the tab.
Account checks use a temporary token and clear the password; no token is exported.

Automatic coverage: application response, database connectivity, temporary storage,
PHP execution, and required table access across operational domains.
Configuration detection does not prove AI/model availability or external delivery.
Manual coverage: all current operational areas, account restrictions, phone hardware,
offline reconciliation, UI/session behaviour, provider delivery and backup restoration.
No automatic schema changes, data repair, service restarts or security overrides occur.
Continuous background geofence entry/exit alerts remain unimplemented.
No console can certify every possible failure; correlate faults with Request IDs and
restricted server logs, and reproduce in a test company before modifying live data.
