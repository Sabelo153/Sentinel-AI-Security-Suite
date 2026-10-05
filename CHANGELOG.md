# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-07-20

### Added - Foundation Baseline

- Sentinel Application Framework bootstrap, router, DI container, config loader, logging, and request pipeline
- Root Sentinel CLI for migrate, seed, serve, route:list, cache:clear, and config:clear
- Milestone 1 Foundation documentation alignment across root project docs
- Sentinel Engineering Handbook in `docs/SEH/` covering vision, architecture, database, coding, API, UI, Git, testing, security, and deployment

### Changed - Engineering Phase

- Project language shifted from planning-first to engineering-first delivery
- Root documentation now tracks version `0.1.0` with codename `Foundation`
- Migration and seed runners now load root environment variables and log into `storage/logs`

## [Unreleased - Engineering Phase]

### Added - ERP Migration Strategy Baseline (2026-08-05)

- Added PROF1T migration strategy documentation with 4-stage adoption model
- Updated roadmap milestones for ERP-oriented release progression
- Added backlog epics for integration, payroll handoff, and financial replacement track

### Added - Company Core Sprint 3 Foundation (2026-08-05)

- Added `tenant` middleware and automatic tenant context (`company_id`, optional `branch_id`, optional `department_id`) for protected business routes
- Enforced tenant-scoped controller behavior for companies, clients, sites, users, roles, and dashboard endpoints
- Added service-level ownership checks to block cross-tenant relationship misuse (client, branch, role, user, employee)
- Added Company domain modules for branches, departments, and company settings with tenant-aware APIs
- Added tenant isolation verification matrix with 9/9 tests passing

### Added - RBAC Sprint 2 (2026-08-05)

- Added a centralized permission catalog in `config/permissions.php`
- Wired protected business routes to route-specific permissions
- Added RBAC seeding for roles, permissions, and role-permission mappings
- Added an RBAC verification matrix with 11/11 tests passing

### Added - Authentication Sprint 1 (2026-08-05)

- Added login, logout, refresh, me, forgot-password, and reset-password auth endpoints
- Added JWT issuance and validation for protected API access
- Added login history recording, account lockout support, and password reset token flow
- Added auth session persistence for refresh token handling
- Added username and UUID lookup methods to the user repository
- Added dedicated auth testing plans under `docs/testing/`
- Verified the live authentication matrix at 27/27 passing

### Added - Architectural Blueprint (2026-07-08)

**Major Milestone**: Transitioned from **database-centric thinking to architecture-centric thinking**

#### Architecture & Design
- ✅ **Clean Architecture** with layered design pattern
- ✅ **Domain-Driven Design (DDD)** with 12 business domains:
  - Identity & Access Domain
  - Company Domain
  - Workforce Domain
  - Attendance Domain
  - Security Operations Domain
  - Occurrence Book Domain
  - Patrol Domain
  - Fleet Domain
  - Visitor Domain
  - Asset Domain
  - AI Domain
  - Reporting Domain

#### Core Components
- ✅ **Event-Driven Architecture**: Loosely coupled domains communicating via events
- ✅ **Sentinel Rule Engine (SRE)**: Intelligent automation without external API calls
  - 6 rule types: Time-based, Frequency, Threshold, Status, Compound, Sequential
  - Rule evaluation in <100ms
  - Complete audit trail of all rule evaluations
  - Multi-tenant rule isolation

#### Integration & Documentation
- ✅ **Domain-to-Database Mapping**: 12 domains → 99 tables
- ✅ **Command Centre Dashboard**: Real-time operational visibility
- ✅ **Event System Architecture**: One event triggers cascade of domain updates

#### Documentation Created
- `docs/architecture/ARCHITECTURE.md` (Complete architectural vision)
- `docs/architecture/SRE_SPECIFICATION.md` (Rule engine specification, 40+ rules)
- `docs/architecture/DOMAIN_TO_DATABASE_MAPPING.md` (All 12 domains with tables & services)
- `docs/architecture/README.md` (Architecture documentation index)

#### Key Design Decisions Documented
1. **Separation of Person & Employment** - One person can have multiple roles
2. **Event-Driven Communication** - No direct domain-to-domain calls
3. **SRE Before AI** - Business rules evaluated first, AI used for enhancement
4. **Multi-Tenancy** - Strict company_id isolation on every table
5. **Soft Deletes** - Complete audit trail, data never permanently removed

#### Database Schema (Completed Previous Session)
- ✅ 99 tables across 15 domains
- ✅ 150+ foreign key relationships
- ✅ Naming standards with 16-point validation checklist
- ✅ Complete specifications in `docs/database/SCHEMA.md`

### Architectural Principles Established
- Business logic independent of frameworks (Clean Architecture)
- Each domain owns its logic and data (DDD)
- Events are the only inter-domain communication mechanism
- SRE provides deterministic, auditable automation
- Dashboard built from event streams (real-time CQRS-ready)

### Implementation Strategy
- Phase 1: Foundation
- Phase 2: Identity & Security
- Phase 3: Workforce
- Phase 4: Operations
- Phase 5: Reports
- Phase 6: AI
- Phase 7: Android
- Phase 8: Cloud
- Phase 9: Enterprise
- Phase 10: Commercial Release

---

## [1.0.0] - 2026-07-06

### Added
- Initial project structure and monorepo setup
- Git repository initialization with branching strategy
- Project documentation framework
- Directory structure for all core domains
- README and contributing guidelines
- License (MIT)

### Planned for v1.0
- [ ] Web Dashboard foundation
- [ ] REST API core endpoints
- [ ] Database schema with 40-50 tables
- [ ] Android app MVP
- [ ] Basic incident management
- [ ] User and attendance management
- [ ] Visitor management system
- [ ] Vehicle management system
- [ ] Basic reporting
- [ ] Notification system foundation

### Planned for v1.1
- [ ] AI Report Writer
- [ ] Risk Assessment Engine
- [ ] Recommendation Engine
- [ ] Advanced Analytics Dashboard
- [ ] Audit Logging
- [ ] Enhanced Security

### Planned for v1.2+
- [ ] Windows Desktop Application
- [ ] Advanced Mobile Features
- [ ] WhatsApp Integration
- [ ] Cloud Deployment (AWS/Azure/GCP)
- [ ] Multi-language Support
- [ ] Advanced Machine Learning Models

---

## Version History

### Future Versions
- **v1.0.0-alpha** - Current: Initial setup phase
- **v1.0.0-beta** - Core features complete, ready for testing
- **v1.0.0-rc1** - Release candidate, production ready
- **v1.0.0** - Production release

---

## Guidelines

- **Added** for new features
- **Changed** for changes in existing functionality
- **Deprecated** for soon-to-be removed features
- **Removed** for now removed features
- **Fixed** for any bug fixes
- **Security** in case of vulnerabilities

### Update Frequency
- Update with every merge to `develop`
- Finalize before release to `main`
