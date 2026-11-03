# Sentinel 0.10: client workflows and assigned-site workspaces

This web/API release extends the 0.9 patrol, reporting and site-assurance release.
It adds six operational workflows. The existing Android 0.9 APK remains compatible;
these additions run in the authenticated browser workspace. Native offline patrol
capture, QR/NFC and scheduled portal/email reports retain their existing behaviour.

## Inspection forms

Use **Custom inspection forms**, enter the site ID and load its forms. A manager
with `inspection_forms.manage` can publish a form using a stable code, a title and
up to 30 typed fields. Supported types are pass/fail, yes/no, text and number.
Each field has separate required-answer, required-photo and failed-check-follow-up
settings. Failure follow-ups apply to pass/fail and yes/no fields.

Use expected revision 0 for a new code, or the latest revision when replacing an
existing form. Definitions and completed submissions retain their original
versions. New submissions must use the current definition. A failed answer can
create a supervisor task that requires a written resolution.

Required photos accept original JPEG/PNG files up to 128 KiB and 2048 pixels per
side; combined encoded photos cannot exceed 700,000 characters. The server stores
and verifies their SHA-256 hashes. Photo retrieval requires both the workflow
permission and access to the submission's site, and is audited. Submission history
includes protected photo controls and failed-check tasks.

Submissions use a stable operation UUID. Retrying an unchanged accepted submission
returns its receipt without creating another task or photo. Reusing the UUID with
changed answers is rejected. The browser preserves the submitted payload for a
retry while that page remains open. These custom forms require a connection;
they do not use the Android patrol queue.

## Client requests and complaints

Company staff use **Client requests and complaints**; mapped client accounts use
**My requests and complaints**. Requests are attached to a site and its client.
Clients can submit requests and complaints, reply, reopen a resolved issue, and
explicitly confirm resolution. A company response can start work or mark an issue
resolved; only a mapped client can confirm and close it.

The default first-response deadline is 24 hours. A public company response records
the first-response time; an internal note does not clear the overdue flag.
Resolution and client confirmation have separate timestamps. Concurrent actions
require the latest request revision. Client history excludes internal notes and
internal actor IDs. Removing the client mapping removes subsequent access.

This release records portal conversations. It does not send separate complaint,
quotation or acknowledgement emails. The previously implemented scheduled report
SMTP worker continues to handle report delivery.

## Leave and availability

**My leave and availability** derives the employee from the signed-in account.
Employees request leave/unavailability and can cancel their own pending or
approved requests. Company managers use **Leave and availability** to review
requests, approve, reject or cancel them with notes and a current revision.

Approval is blocked while the employee has overlapping planned, assigned,
confirmed or in-progress duty. Arrange relief or cancel that duty first. Approved
absence blocks overlapping active shift creation, shift updates and relief
replacement. Adjacent periods are allowed. Requests and roster changes use the
same company transaction lock on MySQL to prevent concurrent approval/assignment
from bypassing the check. Dates use UTC; this is operational availability rather
than a leave-balance or payroll entitlement calculation.

## Site-level permissions

Use **Site access assignments** to grant a user specific sites and restrict a
dedicated supervisor/contractor role. The administrator needs `site_access.manage`;
the role also needs the normal permissions for each desired workflow.

`site_scope.restricted` is a restriction marker. If any active role grants it,
the whole account is restricted, even when another role grants broad permissions.
With no site grants, the account has no site access. Grant revocation is checked
on the next request, including requests using an already issued login token.

Restricted accounts can use only these explicitly scoped permissions:

- `site_workspace.view`
- `inspection_forms.view`, `inspection_forms.manage`
- `client_requests.view`, `client_requests.manage`, `client_requests.client`
- `report_reviews.view`, `report_reviews.client`
- `availability.self`, `users.password_change`

**Assigned site workspace** lists accessible sites, their latest instructions,
emergency contacts, open incident references and open faults. Full company-wide
directories, reports, exports, quotation administration and existing guard/mobile
workflows are blocked for restricted accounts. Use dedicated supervisor/contractor
accounts; keep existing Android guard accounts on their existing guard roles.

The role restriction screen refuses to restrict a role belonging to the acting
administrator. New organisation owners never receive the marker automatically.
The marker is seeded separately from endpoint permissions and must not be included
in a bulk "grant every permission" operation.

## Explicit report acknowledgements and questions

**Read and acknowledge reports** shows reports for currently mapped clients and
accessible sites. A client must explicitly choose **I have read report**. The
record identifies the client user, time and exact content fingerprint. Download,
portal publication and SMTP acceptance are not read acknowledgements.

Editing a published report invalidates its previous acknowledgement for the
current content; its historical acknowledgement remains available. A stale page
cannot acknowledge revised content. **Client report acknowledgements** lets
authorised staff review acknowledgements by site. Clients with request permission
can raise a question linked to that specific report; it follows the request
conversation and resolution workflow.

## Quotations, contracts and mobilisation

**Quotations and mobilisation** supports monthly service quotations with 1–30
lines, quantities, integer minor-unit prices, tax basis points, terms and a
validity date. Supported two-decimal currencies are ZAR, USD, EUR, GBP, NAD and
BWP. For example, 10000 minor units is ZAR 100.00; 1500 basis points is 15% tax.
Totals are computed on the server with integer arithmetic and rounding.

Managers can edit current drafts, then share an immutable offer to mapped client
accounts. **My service quotations** allows a mapped client to accept or decline
the exact shared content before expiry. The decision records the authenticated
actor and notes. It is a portal acceptance record, without a certified-signature
provider integration. Revised terms require a new offer.

An accepted offer can create one draft contract and one onboarding site. Select
an active branch belonging to the quoted client, a unique contract number, dates,
guard requirement and site address. Site subscription limits apply. The contract's
monthly value is the quotation subtotal excluding tax; its metadata preserves the
accepted offer, currency and tax. An unchanged conversion retry returns the same
IDs; changed setup details after conversion are rejected.

Seven mobilisation tasks cover acceptance, site instructions, staffing/training,
QR/NFC commissioning, equipment/radios, client reporting and emergency recovery.
Each completion needs an evidence reference and current task revision. Only a
complete checklist permits atomic activation of both the contract and site.
This creates the records and readiness checklist; staff still configure checkpoint
tags, portal mappings, schedules and deployment in their respective modules.

## Installation and permissions

Back up the database and apply `database/schema/072_client_workflows.sql` after
schemas 001–071. Fresh installs include it through `000_run_all.sql`. Run the
existing tenant-specific `database/seed/operational_permissions.sql` with the
intended `@sentinel_company_id`. It inserts permissions without granting roles.
Deploy the PHP services/routes and the updated admin assets together.

Grant the applicable permissions explicitly:

| Workflow | Staff permissions | Self/client permission |
| --- | --- | --- |
| Site access | `site_access.view`, `site_access.manage` | `site_workspace.view` |
| Inspections | `inspection_forms.view`, `inspection_forms.manage` | — |
| Requests | `client_requests.view`, `client_requests.manage` | `client_requests.client` |
| Availability | `availability.view`, `availability.manage` | `availability.self` |
| Report reviews | `report_reviews.view` | `report_reviews.client` |
| Quotations | `quotations.view`, `quotations.manage` | `quotations.client` |

Staff workflow modules require Essential or above. Site access and self/client
permissions are available on known plans, subject to RBAC and client/site mapping.
Restricted accounts remain subject to their plan. New signup seeds the marker
without granting it to the owner. Existing accounts need the tenant permission
seed and explicit role grants.

Support diagnostics now check the workflow tables and provide a recovery guide.
This migration adds tables only; it does not rewrite existing patrol/report data.

## Verification

The integration suite covers whole-account site restrictions, existing-token
revocation, cross-tenant/site access, immutable form versions, required photos,
idempotent inspection submissions, private notes, client confirmation, exact report
acknowledgements, leave/roster/relief conflicts, quotation arithmetic, portal
acceptance, branch matching, duplicate conversion and mobilisation activation.
Audit failure rolls back new inspection submissions and client requests.

Run PHP checks with `php vendor/phpunit/phpunit/phpunit --no-coverage`.
The browser smoke test is `node tests/Browser/client-workflows.cjs`, with Playwright
installed and Microsoft Edge available. It uses mocked API responses to verify UI
controls and request payloads; backend integration tests exercise the actual
services using SQLite. A deployed MySQL/SMTP/physical-phone acceptance run remains
separate from these development checks. No APK rebuild is needed for this release.
