# Sentinel Organizational Hierarchy

Status: Draft v1.0
Last updated: 2026-08-07

## Purpose
Model the real operational hierarchy of a security company as organizational units, not only permissions.

## Canonical Hierarchy

```text
Company
  -> Region
    -> Branch
      -> Area
        -> Site
  -> Operations Centre
```

## Unit Types
- Company
- Region
- Branch
- Area
- Site
- Operations Centre

## Workforce Placement
People are placed into organizational units through assignments.
Line management and oversight functions are separate concerns.

## Matrix Roles
Some roles are not direct line managers.
Example:
- Inspector can inspect across sites under operations authority.
- Inspector reporting line may vary by company design.

## Hierarchy Rules
- Every operational record links to company and at least one organization unit.
- Scope evaluation must support subtree access (for example, area includes its sites).
- Cross-unit access must be explicit through assignment scope.
- Context switching must be logged and auditable.

## Required Data
- organizations
- organizational_units
- organizational_unit_types (planned)
- organizational_unit_relationships (planned for complex matrix structures)

## Approval Checklist
- Unit taxonomy approved.
- Parent-child constraints documented.
- Matrix role handling rules approved.
- Scope inheritance behavior approved.
