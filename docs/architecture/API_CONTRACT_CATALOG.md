# Sentinel API Contract Catalog

Status: Draft v1.0 (constitution artifact 6 of 7)
Last updated: 2026-08-06

## Purpose
Define one API contract inventory for internal applications and external integrations, including PROF1T coexistence surfaces.

## Contract Standard
Each API contract entry must define:
- Domain and capability
- Endpoint or interface name
- Method and route
- Request schema
- Response schema
- Error contract
- Auth and permission requirements
- Version and compatibility policy

## Route Convention Standard
- Base route pattern: `/api/{resource}`
- Primary CRUD pattern:
	- `GET /api/{resource}`
	- `POST /api/{resource}`
	- `GET /api/{resource}/{id}`
	- `PATCH /api/{resource}/{id}`
	- `DELETE /api/{resource}/{id}`
- Nested resources pattern:
	- `GET /api/{resource}/{id}/{child_resource}`
	- Example: `/api/guards/{id}/patrols`, `/api/sites/{id}/guards`, `/api/incidents/{id}/attachments`
- Resource names should be lowercase plural nouns.

## API Domains
- Identity and access
- Company and tenant hierarchy
- Workforce and HR
- Operations (attendance, patrol, incidents)
- Client and contract
- Fleet and assets
- Finance and payroll handoff
- Reporting and exports
- AI services
- Integration adapters

## Versioning Policy
- Backward-compatible additive changes preferred.
- Breaking changes require new version path.
- Deprecation period and migration guidance are mandatory.

## Integration Contract Baseline
- PROF1T import contracts
- PROF1T export contracts
- Reconciliation and audit contracts
- Webhook or event-bridge contracts

## Approval Checklist (Artifact 6)
1. Domain contract inventory approved.
2. Response and error standards approved.
3. Auth and permission declarations approved.
4. Versioning policy approved.
5. Integration contract baseline approved.

Approval state:
- Draft until all active domains are listed.
- Approved v1.0 when architecture + API leads sign off.
