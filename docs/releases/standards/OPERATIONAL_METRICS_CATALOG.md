# Operational Metrics Catalog

Status: Draft
Last updated: 2026-08-07

## Purpose
Define operational metrics and their event/data sources for shared analytics and intelligence.

## Metric Entry Template
- Metric code:
- Description:
- Business object:
- Source events:
- Aggregation method:
- Time window:
- Consumer workspaces:

## Initial Metric Baseline
- patrol.started.count
- patrol.completed.count
- patrol.missed_checkpoint.count
- guard.inactivity.alert_count
- incident.created.count
- incident.closed.count
- incident.mean_resolution_minutes
- guard.clock_in.on_time_rate
- guard.clock_out.missing_count
- vehicle.route_deviation.count
- vehicle.idle.minutes
- site.coverage.gap_minutes

## Data Quality Rules
- Metrics must be reproducible from source events.
- Late-arriving events must support recomputation.
- Aggregation jobs must publish processing metadata.

## Consumption Rules
- Reporting service uses curated aggregates.
- Operational intelligence uses near-real-time streams and historical trends.
- Executive dashboards use approved KPI views derived from metrics catalog.
