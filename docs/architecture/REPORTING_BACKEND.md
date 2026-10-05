# Reporting Backend

## Scope

The reporting backend reads only canonical, currently implemented domains: attendance, shift assignments, guard deployments, patrol sessions, patrol checkpoint events, GPS locations, geofences, contracts, posts, and audit activity. Incidents, occurrence-book, visitors, access control, inspections, and notifications are intentionally excluded.

## Security

Every query derives `company_id` from `RuntimeContext` and includes an explicit company predicate. Report filters never accept `company_id`. Employee, site, route, and user filters are verified against the current company before a query runs. Report queries use fixed SQL definitions, fixed selected columns, prepared values, and no caller-selected SQL identifiers or sort order.

## Filters and Size

Filters are report-specific and can include `date_from`, `date_to`, employee/site/route/user references, status, checkpoint event type, or audit action/entity where those fields apply. Dates use `YYYY-MM-DD`, ranges must be ordered, status values use report-specific allowlists, and records are limited to 500 by default and 1,000 at most. This bounds JSON and CSV generation without changing the pre-existing global list-response contract.

## CSV

`/api/v1/reports/{report}/csv` uses native `fputcsv()` with deterministic report-specific column order, UTF-8 CSV content type, safe report-name filenames, and header rows even when no records match. Values beginning with `=`, `+`, `-`, or `@` receive a leading apostrophe to prevent spreadsheet formula interpretation.