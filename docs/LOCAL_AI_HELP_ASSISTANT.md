# Local Help Guide assistant

Sentinel's first AI feature is intentionally limited to product guidance and report-writing education. It can explain common workflows, report structure, grammar, spelling, neutral wording and clarity. It cannot read or change Sentinel data.

## Privacy and access boundary

- The service has no database, repository, RuntimeContext, file, source-code, device, GPS or action dependency.
- Only the selected help topic and the question typed by the user are sent to the local model.
- Questions and answers are not written to Sentinel's AI request/response tables or application logs by this feature.
- Extra request fields are rejected. Obvious email addresses, long telephone/identity-number patterns and supplied credentials are rejected before the model call.
- The prompt tells the model not to request personal or confidential information and never to claim that it inspected or changed a record.
- The browser renders the answer as plain text.

Users must ask general or anonymised questions. The Help Guide screen repeats this instruction beside the question field.

## Local Ollama setup

Install Ollama on the same computer that runs the Sentinel PHP application, then download a small instruction model. The default configuration uses `qwen2.5:0.5b`, which keeps the first deployment responsive on modest hardware and can be replaced later.

Set these environment values only after the local model responds successfully:

```dotenv
AI_HELP_ENABLED=true
AI_HELP_BASE_URL=http://127.0.0.1:11434
AI_HELP_MODEL=qwen2.5:0.5b
```

Restart the PHP application after changing its environment. Sign in, open **Help guide**, choose a topic, and ask a general how-to question. If Ollama is stopped or the model is unavailable, Sentinel returns a safe service-unavailable response and the built-in help cards continue to work.

The current transport deliberately accepts only `127.0.0.1`, `localhost`, or `::1`. Do not expose Ollama directly to the internet. If the model is later moved to another computer, add an authenticated local gateway instead of weakening this restriction.

## Client manager topics

- Getting started
- Report writing
- Occurrence Book
- Incidents
- Visitors and access
- Inspections
- Dashboard navigation
- Reviewing reports and follow-ups
- Team setup
- Account security

The web Help Guide uses `dashboard.view`, so client users on every subscription plan can receive basic product help.

## Guard topics

- Starting and ending shifts
- Patrols and QR/NFC checkpoints
- Report writing
- Occurrence Book entries
- Incident reporting
- Visitors and access
- Shift handovers
- Emergency escalation
- Account security

The Android app keeps short versions of these guides on the device so they work offline. An authenticated guard can ask a general follow-up through `/api/v1/guard/help`, protected by `guard.self`. The server selects the guard audience; the app cannot switch itself to manager guidance. AI follow-up questions require connectivity and can take longer on low-powered local-model hardware.

Neither experience provides operational decisions, risk scoring, automated actions or report submission. An active emergency must follow the organisation's emergency procedure and use the appropriate emergency service or control room.
