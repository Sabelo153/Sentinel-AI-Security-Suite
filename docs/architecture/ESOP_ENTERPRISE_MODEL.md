# ESOP Enterprise Model

## Purpose
Define Sentinel as an Enterprise Security Operations Platform with one backend and multiple client experiences.

## Platform Principle
Sentinel backend APIs are the product surface. Client applications are delivery channels.

## Multi-Platform Topology
- Backend: REST API + RBAC + tenant context + audit logs
- Data: Multi-tenant relational model with strict company isolation
- Intelligence: Rules first, AI augmentation after operational baseline data exists
- Clients:
  - Web for administrative and office workflows
  - Mobile for field workflows
  - Desktop for command center and operational supervision

## Enterprise Hierarchy
- Platform
- Companies (Tenants)
- Branches
- Departments
- Employees
- Clients
- Sites
- Contracts
- Assets
- Vehicles
- Equipment
- Visitors
- Attendance
- Patrols
- Incidents
- AI
- Reporting
- Billing

## Authorization Stack
Access decisions are capability-based and context-aware:
- user
- company
- branch
- department
- role
- permissions
- assigned sites
- assigned clients

## Capability-First Model
Capability definitions live in one source of truth:
- [config/capabilities.php](../../config/capabilities.php)

Each capability maps to a permission bundle and can be reused across:
- Web menus
- Mobile menus
- Desktop workflows
- API authorization policies

## Current Sprint Alignment
- v0.2.0 Authentication complete
- v0.2.1 RBAC complete
- v0.3.0 Company Core in progress

## Release Track
- v0.3.0 Company Core
- v0.4.0 Employee Core
- v0.4.1 Compliance
- v0.5.0 Clients, Contracts, and Sites
- v0.6.0 Assets and Fleet
- v0.7.0 Attendance and Clock-In
- v0.8.0 Patrols and Occurrence Book
- v0.9.0 Incidents and Reporting
- v1.0.0 Commercial Release
- v1.1+ AI Operations
- v2.0+ Sentinel MDM
- v3.0+ Sentinel Secure OS

## Domain Organization Standard
All new backend work should follow domain folders under `apps/api/app/Domains`:
- Controllers
- Services
- Repositories
- Models
- Policies
- Validators
- DTOs

This keeps teams independent and reduces cross-module coupling as Sentinel scales.
