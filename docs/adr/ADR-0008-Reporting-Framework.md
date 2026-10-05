# ADR-0008: Reporting Framework

- Status: Proposed
- Date: 2026-08-06

## Decision
Use a shared reporting engine for dashboards, scheduled reports, exports, and PDF/Excel generation with tenant-aware access control.

## Context
Reporting is required across operations, HR, finance, and executive use cases and should not be duplicated in each module.

## Alternatives Considered
- Module-specific report implementations
- Embedded reporting only in the UI layer
- Third-party reporting only without shared governance

## Consequences
- Standardized report generation and export patterns
- Easier future analytics and AI integration
- Requires a common data access and scheduling model

## Related Blueprint Documents
- PRD
- Event Catalog
- API Contract Catalog
- Navigation & UX Blueprint
