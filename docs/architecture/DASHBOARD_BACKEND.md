# Dashboard Backend

## Endpoint

`GET /api/v1/dashboard/summary` requires authentication, tenant context, and `dashboard.view`. It returns fixed aggregate metrics and a maximum of 20 safe recent audit activity records.

## Tenant and Safety Rules

Each aggregate query derives `company_id` from `RuntimeContext` and includes an explicit company predicate. The endpoint accepts no request-selected company, date, site, sort, or limit filters. Recent activity projects only audit ID, actor ID, entity/action identifiers, status, and timestamp; audit payload JSON is never returned.

## Metrics

The summary uses only canonical implemented sources: employees, employee assignments and positions, guard deployments, sites, shift assignments, attendance, patrol sessions and checkpoint events, contracts, posts, geofences, and audit logs. It exposes counts for active workforce/deployments/sites/contracts/posts/geofences, current-day attendance and shift state, patrol state, and checkpoint events.

## Performance

The repository uses fixed `COUNT(*)` aggregates and one bounded ordered activity query. It does not load source records into PHP, perform N+1 lookups, scan GPS history, or expose unbounded lists. Optional raw location and site-detail dashboard views are deferred until a product workflow requires them.