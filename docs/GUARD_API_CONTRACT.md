# Guard API contract

All timestamps are UTC `YYYY-MM-DD HH:MM:SS`. Login uses the existing `/api/v1/auth/login` contract. Subsequent requests use a bearer token. The server derives company and employee from authenticated runtime context. Requests must never select a company, user or employee.

`GET /api/v1/guard/workspace` requires `guard.self`. It returns the authenticated user and employee, own recent shifts, active guard assignments, own attendance/patrols, and routes associated with active assigned shifts. Lists are bounded; the workspace is not a complete historical export.

`POST /api/v1/guard/commands` requires `guard.self` and accepts:

```json
{
  "operation_uuid": "a0000000-0000-4000-8000-000000000001",
  "action": "clock_in",
  "data": { "shift_assignment_id": 1, "occurred_at": "2026-09-14 08:00:00" }
}
```

| Action | Accepted data |
|---|---|
| clock_in | shift_assignment_id, occurred_at |
| clock_out | attendance_id, occurred_at |
| start_patrol | employee_assignment_id, patrol_route_id, shift_assignment_id, started_at |
| finish_patrol | patrol_session_id, occurred_at |
| checkpoint | patrol_session_id, method (`QR` or `NFC`), code, recorded_at |
| location | latitude, longitude, accuracy (optional), captured_at, battery_level (optional) |

Send the same UUID and payload on every retry. A changed payload with an existing UUID returns 409. Receipts are scoped by company and authenticated user and commit with the mutation. A successful response is `{ "data": <domain result> }`. Never discard a local command merely because a connection was attempted.

401 requires sign-in. 403/404/409/422 require review and must not be retried indefinitely. Network failures, 408, 429 and 5xx retain the command for retry with the original UUID. Backend timestamps more than five minutes in the future are rejected. Existing domain lifecycle and relationship validation also applies.

Schema 063 and reviewed `guard.self` grants are prerequisites. Guards must not receive broad management permissions just to use these endpoints. The web mutation boundary adds an audit event in the same transaction. No production schema or permission rollout was executed by the development task.
