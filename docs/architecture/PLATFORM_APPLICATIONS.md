# Sentinel Platform Applications

Status: Baseline
Last updated: 2026-08-07

## Purpose
Define Sentinel as a platform of focused applications sharing one backend, one identity system, and one tenant-aware data model.

Near-term scope is physical security operations excellence. Non-core enterprise domains remain deferred until operations proof targets are achieved.

## Shared Platform Core
All applications share:
- Authentication and JWT session model
- RBAC permissions and capability bundles
- Company, branch, department, client, and site hierarchy
- Unified API surface
- Tenant isolation controls
- Audit logging and AI service integration

## Application Portfolio

| Application | Primary Users | Core Outcomes |
|---|---|---|
| Sentinel Guard | Security Officers | Clock in/out, patrol execution, occurrence submission |
| Sentinel Control | Control Room Operators | Live incident monitoring, dispatch visibility, alerts |
| Sentinel Patrol | Supervisors and Inspectors | Patrol tracking, checkpoint compliance, exceptions |
| Sentinel Operations | Operations Managers | Multi-site command, compliance, KPI and risk monitoring |
| Sentinel Fleet | Fleet Managers | Vehicle readiness, inspections, maintenance, fuel tracking |
| Sentinel Client Portal | Clients | Service visibility, incidents, reporting snapshots |
| Sentinel Executive | Directors and Executives | KPI dashboard, risk views, strategic reporting |
| Sentinel AI Assistant | Operational and Management Users | AI summaries, recommendations, report acceleration |
| Sentinel Admin | System Administrators | Identity, permissions, platform governance |

## Release Strategy
- Build module-by-module with one shared API contract.
- Keep app surfaces focused on user outcomes, not technical layers.
- Avoid data duplication across applications.
- Add app-specific views and workflows only after shared domain contracts are stable.

## Operations MVP Alignment
Operations MVP applications:
- Sentinel Guard
- Sentinel Patrol
- Sentinel Control
- Sentinel Fleet
- Sentinel Operations
- Sentinel Executive
- Sentinel Client Portal

Deferred until post-Operations MVP stabilization:
- Expanded HR platform
- Finance platform
