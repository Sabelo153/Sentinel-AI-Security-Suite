# Sentinel Organization Model

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define how organizational context is modeled so users can work across companies, branches, departments, sites, and client organizations without breaking identity or access boundaries.

## Organization Context Model

```text
Organization Root
  └── Company
        ├── Region
        │     └── Branch
        │           └── Area
        │                 └── Site
        └── Operations Centre
```

## Supported Context Types
- Company
- Region
- Branch
- Area
- Department
- Site
- Operations Centre
- Client Organization
- Partner Organization
- Internal Shared Services

## Context Rules
- Every user belongs to one or more identity contexts.
- People are placed through assignment records that bind position, authority, and scope.
- Organization context defines what data is visible and what permissions apply.
- The same user can have access to multiple companies or multiple organizational contexts.
- Context switching must be explicit, auditable, and secure.
- Matrix oversight roles (for example, inspectors) may span multiple sites without becoming line managers.

## Example Scenarios
- A consultant works across multiple security companies
- A regional operations manager moves between branches and sites
- A board member views governance data across multiple entities
- A client portal user operates inside one customer organization only

## Implementation Guidance
Organization context should be a first-class input to:
- tenant resolution
- scope filtering
- navigation and dashboard composition
- notification routing
- AI data access

## Approval Checklist
- Organization context model approved
- Multi-company and multi-context support reviewed
- Tenant and scope boundaries documented
