# Database Naming Standards

Status: Draft
Last updated: 2026-08-07

## Purpose
Provide one database naming standard to prevent schema drift across teams.

## Table Naming
- Use lowercase snake_case.
- Use plural nouns for tables.
- Avoid ambiguous abbreviations.

Examples:
- guards
- patrol_routes
- incident_attachments

## Column Naming
- Use lowercase snake_case.
- Primary key column: id.
- Foreign keys end with _id.
- Timestamp fields: created_at, updated_at, deleted_at.

## Index and Constraint Naming
- Primary key: pk_{table}
- Foreign key: fk_{table}_{column}_{ref_table}
- Unique index: uq_{table}_{column_list}
- Non-unique index: ix_{table}_{column_list}

## Enum/Status Naming
- Status columns should use {entity}_status where needed.
- State-machine values must align with workflow catalogs.

## Tenant and Scope Keys
- company_id required on business tables unless globally shared reference table.
- Additional scope keys must be explicit (for example, branch_id, area_id, site_id).

## Migration Rules
- Migrations must be reversible.
- Destructive schema changes require explicit rollback strategy and data migration notes.
