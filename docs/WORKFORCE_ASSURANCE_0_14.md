# Sentinel 0.14: workforce assurance

Adds six online browser workspaces: Guard training administration, My lessons and
certificates, Lost and found, Incident investigations, Stock and uniforms, and My
uniform allocations. These use existing individual login, tenant/site controls,
role permissions, subscription gates and transactional audit records. The Android
0.10 APK remains compatible; these additions do not require a native rebuild.
Existing offline patrol, NFC and inspection workflows are unchanged.

## Guard training portal

Create a site course edition with lesson text, 1–20 multiple-choice questions,
2–6 distinct choices per question, a pass percentage, maximum 1–10 attempts and
certificate validity of 1–730 days. The browser provides a question builder; correct
answer numbers start at 1. Publish the edition before assigning an active employee
and a deadline. Published editions cannot be edited; create another edition for
revised content or refresher training.

Assigned employees use My lessons and certificates, acknowledge the exact published
lesson and submit answers. The server grades the quiz; learner responses never
include correct-answer fields. Stable attempt UUIDs prevent an unchanged retry from
creating another attempt/certificate. Attempt limits and completed-assignment locks
are enforced on the server. Failed attempts show the score without revealing the
answer key. After the limit, the supervisor must arrange follow-up/new training;
there is no hidden reset that erases failed attempts.

A pass issues an internal certificate code and expiry date. Learners and authorised
staff can view/print the certificate; administrators can revoke it with a reason.
Current, expired and revoked states remain visible. The business worker opens
reminders for overdue assignments and certificates expiring within 30 days.

An internal course certificate is not a statutory licence, accredited qualification
or independent verification of competence. It does not automatically add verified
employee_training records or satisfy deployment eligibility checks. Continue the
existing verified training/competency process for site requirements. Restricted
learners need the corresponding site grant as well as the assignment.

## Lost-and-found custody

Register a description, found place/time, storage location and a JPEG/PNG photo
(up to 2 MiB and 6000 × 6000). The protected photo endpoint verifies its SHA-256;
list responses include metadata without the photo bytes. Record storage moves
with a reason and current revision. Movement history is append-only.

For collection, staff verify identity and entitlement, record the claimant's name
and notes, and capture a drawn PNG signature. The server requires explicit identity
confirmation and a valid bounded image. Collection consumes the stored state, so
stale/repeated collection attempts cannot hand out the item twice. View/print the
collection receipt with signature and hash. This is a recorded attestation, not
cryptographic identity verification or a public guest link. Avoid recording full
identity-document numbers in notes.

Disposal requires the separate lost_found.dispose permission and recorded authority.
There is no automatic disposal period or automatic deletion. Photos, signatures
and custody records require the company's reviewed retention/privacy policy.

## Incident investigations

Open one case per existing incident, choose an active site-authorised investigator,
set a deadline and record the reason. Creation also places an incident preservation
hold protecting original evidence and incident report photos from the existing
retention cleanup process.

The workflow is TRIAGE → INVESTIGATING → REVIEW → CLOSED. Staff append findings,
witness accounts and root-cause notes. Link existing originals for the same incident
or upload a new original (up to 256 KiB) through the case. Evidence retains its
original filename, capture time, SHA-256 and protected access history. Original
downloads are attachments, not inline executable content.

Assign corrective actions to active investigation operators with site access.
Only each action's owner can attest completion with notes. While a case is editable,
authorised staff can hand over its investigator or an open action to another
active site operator, recording the reason, revised deadline and prior/new owner. The assigned investigator
submits the summary after recording a root cause and completing all actions.
Submission freezes notes/actions and fingerprints the incident, summary, notes and
actions. An authorised reviewer different from the investigator/submitter can
return for changes or approve the exact snapshot. Changed incident details, changed
linked evidence or failed original-file integrity checks block approval. Review
history preserves summaries, reasons, actor and snapshot fingerprints. Reopening
requires review permission and a reason.

Case closure does not change the incident's own operational status or release its
preservation hold. A retention administrator must separately review/release holds
through the existing MFA-controlled retention process. Case data is not emailed or
published to clients automatically. Restricted operators can access only granted
sites; assigning an investigator does not override their role/site permissions.

## Stock and uniforms

Create stock codes by site, size and category (UNIFORM, PPE, CONSUMABLE or OTHER),
with a reorder threshold. Stock starts at zero. Receive against a delivery note;
physical-count adjustments and loss write-offs need separate stock.adjust access.
All changes append to the movement ledger with quantity affected, available-balance
delta, resulting balance, actor, timestamp, notes and stable operation UUID.
Unchanged retries return their original receipt; altered retries are rejected.
Company-level mutation locking prevents concurrent issues overspending availability.

Issuing to an active employee immediately reserves available stock. The employee
checks the physical items and signs in My uniform allocations. Only the named
recipient can accept. Pending unaccepted issues can be cancelled once by staff,
returning the reserved quantity. Return deadlines are within one year; expired
pending allocations must be cancelled/reissued rather than accepted late.

Staff count physical returns, including partial returns. RESTOCK increases available
quantity; QUARANTINE records a non-restock return. Lost/damaged write-offs settle
outstanding quantities without increasing availability. Returns plus write-offs
cannot exceed the accepted issue. Signed issue/return ledger receipts are viewable
and printable. Quarantined goods stay outside availability: any later repaired-stock
receipt must cite the quarantine/repair reference and be reviewed by the stockkeeper.
These are stock records, not payroll deductions, purchasing or financial accounting.

Low-stock and overdue allocation conditions join the existing deduplicated business
reminders. In-app reminders resolve when their source clears; no new SMS, email or
external notification is sent.

## Rollout and permissions

Apply database/schema/076_workforce_assurance.sql after 001–075. Fresh installs
include it in 000_run_all.sql. Deploy matching services/routes/admin assets; run
the tenant-specific operational permission seed and grant intended roles explicitly.
Existing roles are not silently expanded. Newly created pilot Guard templates
include learning.self and stock.self; new Site Supervisor templates include scoped
administration/view permissions allowed by the subscription. Existing template roles
must be reviewed/updated explicitly using normal role administration.

| Workspace | Permissions | Plans |
| --- | --- | --- |
| Course administration | learning.view, learning.manage | Essential and above |
| Own training | learning.self | All known plans, assigned employee only |
| Lost and found | lost_found.view, lost_found.manage, lost_found.dispose | All known plans |
| Investigations | investigations.view, investigations.manage, investigations.review | Professional and above |
| Stock administration | stock.view, stock.manage, stock.adjust | Essential and above |
| Own uniforms | stock.self | All known plans, assigned employee only |

All new workspaces honour site_scope.restricted. Keep the existing per-tenant
business-operations worker with an active unrestricted operator and business_ops.run:

    php bin/run-business-operations.php --company=ID --operator=SERVICE_USER_ID

The existing pilot setup chooses the reminder supervisor. No additional worker is
needed. Apply schema 076 before running the updated worker. Sponsor banners remain
excluded from these operational pages; all paid tiers remain ad-free. No Google,
Play Store, external quiz or signature service is introduced.

## Verification

PHP integration tests cover server grading, answer-key privacy, attempt limits,
retry receipts, certificate revocation, media validation/integrity, single collection,
stock availability/partial returns/quarantine/write-offs, independent case review,
stale snapshots, unrelated evidence, site/tenant isolation and audit rollback.
The browser test exercises actual navigation, question authoring, lesson/quiz forms,
certificate display, drawn signatures, identity confirmation, stock forms, evidence
upload and fingerprinted review. Existing offline/operations tests remain required.

These are source/test additions, not a production deployment. Validate MySQL
concurrency, intended role/site grants, printing, physical handovers and the
company's training/investigation procedures in a supervised rollout.
