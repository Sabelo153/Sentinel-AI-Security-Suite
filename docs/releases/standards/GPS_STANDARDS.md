# GPS Standards

Status: Draft
Last updated: 2026-08-07

## Purpose
Define one GPS telemetry and event standard used by all GPS-enabled objects and services.

## Required Telemetry Fields
- latitude
- longitude
- accuracy
- heading
- speed
- altitude
- timestamp
- provider
- battery_level
- mock_location
- signal_strength
- device_id
- confidence

## Event Naming Examples
- guard.location_updated
- vehicle.location_updated
- patrol.route_deviation_detected
- patrol.checkpoint_entered
- site.entered
- site.left
- patrol.idle_detected
- guard.sleep_suspected

## Sampling and Retention Baseline
- Live tracking cadence must be configurable by profile.
- High-risk activities may use tighter cadence than normal patrol movement.
- Raw telemetry retention and aggregated retention must be defined per tenant policy.

## GPS Quality Rules
- Reject malformed coordinates.
- Flag low-confidence or mock-location records.
- Preserve source metadata for audit and anomaly analysis.

## Integration Rules
- GPS updates must publish domain events.
- Operational intelligence consumes GPS events for anomaly scoring.
- Timeline and audit services must record significant location events.
