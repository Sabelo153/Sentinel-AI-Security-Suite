# UI integration review — 6 November 2026

This review checks the combined Sentinel browser workspace through service delivery
0.15. It fixes integration and usability problems without requiring a new Android
APK or database migration beyond the existing required schema 077.

## Fixes

- Navigation groups cover control room, daily records, workforce/patrols, site
  resources, clients/service, organisation and administration. Search matches page
  names, identifiers and groups. Existing permission-filtered navigation is retained.
- A shared site selector shows only authorised site names/IDs for staff, clients,
  assigned guards and restricted supervisors. Restricted scope applies before the
  500-site limit. Large directories retain an authorised-ID fallback; every record
  operation still checks access on the server. Site-directory replies use no-store.
- Closed mobile navigation is inert, opens with search focus, keeps keyboard focus
  within the drawer and returns focus when closed. Forms, signatures, long content,
  structured records and tables stay within narrow screens.
- Nested records no longer render as [object Object]. Routine views show readable
  content; revision/hash/implementation metadata is under audit details. Risk
  controls, post duties, continuity steps, quote terms and handover snapshots have
  domain-specific presentation instead of raw JSON dumps.
- Quotation/fuel/renewal forms accept normal currency amounts, tax percentages and
  litres, converting exactly to existing integer API units. API contracts and server
  financial arithmetic stay unchanged. One-off service quotes are correctly labelled
  in older quotation views and do not offer monthly-contract conversion. Legacy
  activation controls follow client mobilisation approval state.
- Named-person handover acceptance, assigned-inspector submissions, investigator
  submission, action-owner completion and independent review controls follow the
  authenticated identity. Server checks remain authoritative.
- Quizzes require an explicit answer to every question instead of silently selecting
  the first answer. Future transport starts and premature service completion are
  not offered as ready actions.
- Obsolete reads cancel on navigation. Replies from an old session cannot sign out
  or overwrite a newly signed-in account, including late 401 replies. Branding is
  requested only when the role can read it.
- The default JSON request limit is 4 MiB, so the advertised 2 MiB base64 photo can
  reach its image validator. The image/file limits themselves are unchanged. Set
  MAX_JSON_BODY_BYTES and the web-server/reverse-proxy body limits consistently.
- Changed browser assets have updated cache versions in the admin entry page.

## Verification

The PHP suite covers authenticated identity discovery, tenant/client/duty/restricted
site choices, scope-before-limit behaviour in large companies and near-2-MiB photo
uploads through the actual application dispatcher, alongside the existing domain,
security and audit tests.

Eight focused browser checks cover the existing workflows plus mobile shell/session
race/error recovery. The ninth, integrated-ui.cjs, starts a loopback PHP server with
a disposable SQLite fixture, registered signed sessions and real API routing,
authentication, tenant, permission and business services. It does not mock API replies.
It checks 53 distinct views across administrator, client, guard, restricted supervisor
and incoming-supervisor sessions, including connected workflows:

- Client quote acceptance → staff shift-coverage scheduling, with consistent legacy
  quotation labels and activation/conversion controls.
- Client visitor QR approval → staff identity-confirmed gate check-in.
- Lesson acknowledgement → server-graded quiz → current internal certificate.
- Drawn uniform acceptance → issued allocation in the staff stock ledger.
- Named incoming supervisor → accepted frozen control-room handover.
- Client mobilisation approval → existing contract/site activation.
- Investigator submission → different reviewer closure.
- Unauthorised-site denial, bounded large photo upload, mobile layout/drawer and
  server-driven Free-only banners across all four subscription plans.

Run the PHP suite:

    php vendor/phpunit/phpunit/phpunit --no-coverage

Run the browser checks with Playwright and Microsoft Edge installed:

    node tests/Browser/service-delivery.cjs
    node tests/Browser/workforce-assurance.cjs
    node tests/Browser/business-operations.cjs
    node tests/Browser/operations-hub.cjs
    node tests/Browser/offline-inspections.cjs
    node tests/Browser/operational-readiness.cjs
    node tests/Browser/client-workflows.cjs
    node tests/Browser/workspace-shell.cjs
    node tests/Browser/integrated-ui.cjs

Set SENTINEL_TEST_PHP to the PHP executable if it is not in the adjacent Windows
test runtime. The real-API fixture uses a generated sentinel-ui-audit temporary
directory and only synthetic records; it is not a deployment router and sits
outside the public document root. Successful runs remove the disposable database
and session files. The default artifact directory is ../../outputs/ui-audit beside
the checkout; SENTINEL_UI_OUTPUT can choose another output location. Artifacts
include report.json, desktop/mobile screenshots and the local API test log.

## Rollout limits

These checks are not a production deployment or a full MySQL concurrency test.
Apply the existing migrations/permissions and validate staging configuration,
HTTPS, proxy body limits, backup keys/storage, SMTP and physical Android/AOSP,
camera/NFC, device-owner and recovery workflows before rollout. No live client
email, webhook, emergency call or external provider operation ran during this audit.
The Android source/package is unchanged by this browser/API review.
