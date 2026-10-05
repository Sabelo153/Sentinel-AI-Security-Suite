# Sprint 1 - Foundation: Detailed Task Breakdown

> Complete sprint plan for building the database, API, and authentication system

---

## 📋 Sprint Overview

**Goal**: Build the solid foundation that all other features will depend on

**Duration**: 2 weeks (Week 1-2)

**Total Effort**: ~48 hours

**Dependencies**: None (this is the foundation)

**Success Criteria**:
- ✅ Database with 40+ tables
- ✅ REST API running
- ✅ Authentication working
- ✅ RBAC system functional
- ✅ Company/Site/User management operational
- ✅ API documentation complete

---

## 🗂️ Task Breakdown by Category

### Category A: Database Design & Migrations (Week 1)

#### Task A1: Create Master Database Design (ERD)
**Effort**: 8 hours  
**Priority**: 🔴 Critical  
**Status**: To Do  

**Description**:
Create comprehensive Entity-Relationship Diagram (ERD) with all tables, relationships, and constraints.

**Tasks to complete**:
```
[ ] Design core tables:
    [ ] companies (id, name, email, phone, status)
    [ ] branches (id, company_id, name, location)
    [ ] sites (id, branch_id, name, gps_lat, gps_lng, address)
    [ ] departments (id, company_id, name, code)
    [ ] employees (id, company_id, site_id, first_name, last_name, email)
    [ ] users (id, employee_id, username, email_hash, password_hash)
    [ ] roles (id, name, description, permissions_json)
    [ ] user_roles (id, user_id, role_id, site_id, valid_from, valid_to)
    [ ] permissions (id, name, description, resource, action)

[ ] Design workforce tables:
    [ ] shifts (id, name, start_time, end_time)
    [ ] shift_assignments (id, employee_id, shift_id, start_date, end_date)
    [ ] attendance (id, employee_id, clock_in, clock_out, site_id, date)
    [ ] leaves (id, employee_id, leave_type_id, start_date, end_date, status)
    [ ] leave_types (id, name, days_allowed)

[ ] Design security operations tables:
    [ ] incidents (id, site_id, title, description, severity, status)
    [ ] incident_types (id, name, description)
    [ ] incident_updates (id, incident_id, user_id, update_text, timestamp)
    [ ] occurrence_book (id, site_id, entry_text, severity, timestamp)
    [ ] visitors (id, site_id, name, phone, company, check_in, check_out)
    [ ] patrols (id, site_id, assigned_to, start_time, end_time, status)
    [ ] patrol_checkpoints (id, patrol_id, checkpoint_name, timestamp)
    [ ] vehicles (id, company_id, registration, type, status)
    [ ] vehicle_inspections (id, vehicle_id, date, status, notes)

[ ] Design supporting tables:
    [ ] api_keys (id, user_id, key_hash, created_at, expires_at)
    [ ] audit_logs (id, user_id, action, resource, changes, timestamp)
    [ ] settings (id, key, value, type)
    [ ] notifications (id, user_id, title, message, read_at)

[ ] Create comprehensive ERD document:
    [ ] Tables overview
    [ ] Relationships diagram
    [ ] Indexes strategy
    [ ] Data types chosen
    [ ] Constraints documented
    [ ] Example data model

[ ] Document schema in docs/database/SCHEMA.md
```

**Acceptance Criteria**:
- [ ] ERD file created (draw.io, Lucidchart, or exported image)
- [ ] All 30+ tables identified
- [ ] Relationships documented
- [ ] Primary keys defined
- [ ] Foreign keys identified
- [ ] Indexes strategy documented
- [ ] docs/database/SCHEMA.md complete
- [ ] Schema reviewed and approved

---

#### Task A2: Create Database Migrations
**Effort**: 6 hours  
**Priority**: 🔴 Critical  
**Status**: To Do  

**Description**:
Convert ERD into executable migration files.

**Tasks to complete**:
```
[ ] Set up migration system:
    [ ] Create database/migrations/ directory structure
    [ ] Create migration template file
    [ ] Create migration naming convention document

[ ] Create core migrations:
    [ ] 001_companies.sql
    [ ] 002_branches.sql
    [ ] 003_sites.sql
    [ ] 004_departments.sql
    [ ] 005_roles.sql
    [ ] 006_permissions.sql
    [ ] 007_employees.sql
    [ ] 008_users.sql
    [ ] 009_user_roles.sql

[ ] Create workforce migrations:
    [ ] 010_shifts.sql
    [ ] 011_shift_assignments.sql
    [ ] 012_attendance.sql
    [ ] 013_leaves.sql
    [ ] 014_leave_types.sql

[ ] Create security migrations:
    [ ] 015_incidents.sql
    [ ] 016_incident_types.sql
    [ ] 017_incident_updates.sql
    [ ] 018_occurrence_book.sql
    [ ] 019_visitors.sql
    [ ] 020_patrols.sql
    [ ] 021_patrol_checkpoints.sql
    [ ] 022_vehicles.sql
    [ ] 023_vehicle_inspections.sql

[ ] Create utility migrations:
    [ ] 024_api_keys.sql
    [ ] 025_audit_logs.sql
    [ ] 026_settings.sql
    [ ] 027_notifications.sql

[ ] Test all migrations:
    [ ] Run migrations against test database
    [ ] Verify all tables created
    [ ] Verify relationships work
    [ ] Create migration rollback scripts
```

**Acceptance Criteria**:
- [ ] All migration files created
- [ ] Migrations run successfully
- [ ] All tables exist in database
- [ ] All relationships functional
- [ ] Rollback scripts work
- [ ] Migration documentation complete

---

#### Task A3: Create Database Seeds (Sample Data)
**Effort**: 3 hours  
**Priority**: 🟡 High  
**Status**: To Do  

**Description**:
Create seed data for development and testing.

**Tasks to complete**:
```
[ ] Create seed structure:
    [ ] database/seeds/ directory

[ ] Create seed files:
    [ ] 001_companies_seed.sql - 3 test companies
    [ ] 002_branches_seed.sql - 5 branches
    [ ] 003_sites_seed.sql - 10 sites
    [ ] 004_roles_seed.sql - Admin, Manager, Security Officer, Viewer, Guest
    [ ] 005_permissions_seed.sql - 50+ permissions
    [ ] 006_users_seed.sql - 10 test users with different roles
    [ ] 007_employees_seed.sql - 50 test employees
    [ ] 008_shifts_seed.sql - Morning, Afternoon, Night shifts
    [ ] 009_incidents_seed.sql - 5 sample incidents
    [ ] 010_visitors_seed.sql - 10 sample visitors

[ ] Verify seed data:
    [ ] All relationships functional
    [ ] Data is realistic
    [ ] Can run seeds multiple times safely
```

**Acceptance Criteria**:
- [ ] Seed files created
- [ ] Seeds run without errors
- [ ] Sample data realistic and usable
- [ ] Relationships maintained
- [ ] Ready for development use

---

### Category B: REST API Foundation (Week 1)

#### Task B1: Set Up API Project Structure
**Effort**: 4 hours  
**Priority**: 🔴 Critical  
**Status**: To Do  

**Description**:
Create the foundational API project structure following DDD principles.

**Tasks to complete**:
```
[ ] Create directory structure:
    services/api/
    ├── src/
    │   ├── domains/
    │   │   ├── authentication/
    │   │   ├── companies/
    │   │   ├── sites/
    │   │   ├── users/
    │   │   └── shared/
    │   ├── middlewares/
    │   ├── routes/
    │   ├── config/
    │   └── app.php
    ├── tests/
    ├── public/
    │   └── index.php
    ├── composer.json
    └── .env.example

[ ] Set up dependencies:
    [ ] Choose framework (Slim, Laravel, or custom)
    [ ] Install via Composer
    [ ] Configure autoloading

[ ] Create core files:
    [ ] app.php (main application file)
    [ ] config/database.php
    [ ] config/auth.php
    [ ] config/app.php
    [ ] .env.example

[ ] Set up routing:
    [ ] Create routes/api.php
    [ ] Configure base API routes /api/v1/*
    [ ] Create route groups by domain

[ ] Create response wrapper:
    [ ] Common response format (success/error)
    [ ] Status codes handler
    [ ] Error messages consistent
```

**Acceptance Criteria**:
- [ ] API project initialized
- [ ] Framework installed
- [ ] Directory structure complete
- [ ] Base routes working
- [ ] Response wrapper implemented
- [ ] GET /api/v1/health returns 200

---

#### Task B2: Implement Core Middleware
**Effort**: 3 hours  
**Priority**: 🔴 Critical  
**Status**: To Do  

**Description**:
Create middleware for authentication, error handling, and logging.

**Tasks to complete**:
```
[ ] Authentication middleware:
    [ ] JWT token validation
    [ ] Token extraction from headers
    [ ] Invalid token handling

[ ] Authorization middleware:
    [ ] Role/permission checking
    [ ] Resource access control

[ ] Error handling middleware:
    [ ] Try/catch wrapper
    [ ] Error response formatting
    [ ] Logging

[ ] Logging middleware:
    [ ] Request logging
    [ ] Response logging
    [ ] Error logging to files

[ ] CORS middleware:
    [ ] Allow cross-origin requests
    [ ] Configure allowed origins

[ ] Request validation middleware:
    [ ] Input sanitization
    [ ] Type checking
```

**Acceptance Criteria**:
- [ ] All middleware implemented
- [ ] Middleware chain working
- [ ] Error responses consistent
- [ ] Logging functional
- [ ] CORS configured

---

### Category C: Authentication System (Week 1-2)

#### Task C1: Implement JWT Authentication
**Effort**: 6 hours  
**Priority**: 🔴 Critical  
**Status**: To Do  

**Description**:
Build secure JWT-based authentication system.

**Tasks to complete**:
```
[ ] Create authentication domain:
    [ ] src/domains/authentication/

[ ] Create authentication services:
    [ ] AuthService - main authentication logic
    [ ] TokenService - JWT token generation/validation
    [ ] PasswordService - hashing and verification

[ ] Implement login endpoint:
    [ ] POST /api/v1/auth/login
    [ ] Accept email and password
    [ ] Validate credentials
    [ ] Return JWT token + refresh token
    [ ] Return user details

[ ] Implement logout endpoint:
    [ ] POST /api/v1/auth/logout
    [ ] Invalidate tokens (optional, JWT stateless)
    [ ] Return success message

[ ] Implement token refresh:
    [ ] POST /api/v1/auth/refresh
    [ ] Accept refresh token
    [ ] Return new JWT token

[ ] Create password reset flow:
    [ ] POST /api/v1/auth/forgot-password
    [ ] POST /api/v1/auth/reset-password
    [ ] Store temporary reset tokens

[ ] Security measures:
    [ ] Use bcrypt for passwords
    [ ] Use strong JWT secrets
    [ ] Implement rate limiting
    [ ] Token expiry: 24 hours
    [ ] Refresh token expiry: 7 days

[ ] Testing:
    [ ] Test valid login
    [ ] Test invalid credentials
    [ ] Test token validation
    [ ] Test token expiry
    [ ] Test refresh token
```

**Acceptance Criteria**:
- [ ] Login returns valid JWT
- [ ] Protected routes require token
- [ ] Token validation working
- [ ] Token refresh working
- [ ] Password hashing secure
- [ ] All tests passing
- [ ] Rate limiting functional

---

#### Task C2: Implement Role-Based Access Control (RBAC)
**Effort**: 5 hours  
**Priority**: 🔴 Critical  
**Status**: To Do  

**Description**:
Build flexible RBAC system for authorization.

**Tasks to complete**:
```
[ ] Create roles:
    [ ] Admin - full system access
    [ ] Manager - manage reports and users in assigned sites
    [ ] Security Officer - manage incidents, patrols, visitors
    [ ] Viewer - read-only access
    [ ] Guest - limited public access

[ ] Define permissions (50+ permissions):
    [ ] companies.create, companies.read, companies.update, companies.delete
    [ ] sites.create, sites.read, sites.update, sites.delete
    [ ] users.create, users.read, users.update, users.delete
    [ ] incidents.create, incidents.read, incidents.update, incidents.close
    [ ] visitors.create, visitors.read, visitors.checkout
    [ ] patrols.create, patrols.read, patrols.complete
    [ ] reports.read, reports.generate, reports.export
    [ ] (and many more...)

[ ] Create authorization service:
    [ ] Check user has permission
    [ ] Check role has permission
    [ ] Check resource-level access

[ ] Implement authorization middleware:
    [ ] @Authorize(['incidents.create'])
    [ ] @RequireRole(['Admin', 'Manager'])
    [ ] Resource-level checks

[ ] Create permission management endpoints:
    [ ] GET /api/v1/roles - list all roles
    [ ] GET /api/v1/roles/:id - get role details
    [ ] POST /api/v1/roles - create role
    [ ] PUT /api/v1/roles/:id - update role
    [ ] GET /api/v1/permissions - list permissions

[ ] Testing:
    [ ] User with Admin role can do everything
    [ ] User with Viewer role cannot delete
    [ ] User without permission gets 403 Forbidden
    [ ] Multi-role users work correctly
```

**Acceptance Criteria**:
- [ ] 5 core roles created
- [ ] 50+ permissions defined
- [ ] Authorization decorator working
- [ ] Permission checking functional
- [ ] 403 Forbidden for unauthorized
- [ ] All tests passing

---

### Category D: Company/Site/User Management (Week 2)

#### Task D1: Implement Company Management API
**Effort**: 4 hours  
**Priority**: 🟡 High  
**Status**: To Do  

**Description**:
Build CRUD operations for companies.

**Tasks to complete**:
```
[ ] Create company domain:
    [ ] src/domains/companies/

[ ] Create company model and repository:
    [ ] Company entity
    [ ] CompanyRepository (query interface)
    [ ] CompanyService (business logic)

[ ] Implement endpoints:
    [ ] POST /api/v1/companies - Create company
         Input: name, email, phone, address, registration_number
         Validation: name required, valid email, phone format
         Return: created company object
    
    [ ] GET /api/v1/companies - List companies
         Query: page, limit, search, sort
         Pagination: 20 items default, max 100
         Return: company list with total count
    
    [ ] GET /api/v1/companies/:id - Get single company
         Return: company with branches count
    
    [ ] PUT /api/v1/companies/:id - Update company
         Input: name, email, phone, address
         Validation: same as create
         Authorization: Admin or Company Manager
         Return: updated company
    
    [ ] DELETE /api/v1/companies/:id - Delete company
         Authorization: Admin only
         Cascade delete: all branches, sites
         Return: success message

[ ] Add company details:
    [ ] GET /api/v1/companies/:id/details
         Return: company + branches + sites + employees count
    
    [ ] GET /api/v1/companies/:id/statistics
         Return: incident count, visitor count, employee count

[ ] Testing:
    [ ] Create multiple companies
    [ ] List with pagination
    [ ] Update company
    [ ] Delete with cascade
    [ ] Search functionality
    [ ] Authorization checks
```

**Acceptance Criteria**:
- [ ] All CRUD endpoints working
- [ ] Pagination functional
- [ ] Validation working
- [ ] Authorization enforced
- [ ] 100% test coverage
- [ ] API documentation complete
- [ ] Postman collection updated

---

#### Task D2: Implement Site/Location Management
**Effort**: 4 hours  
**Priority**: 🟡 High  
**Status**: To Do  

**Description**:
Build site/location management linked to companies.

**Tasks to complete**:
```
[ ] Create sites domain:
    [ ] src/domains/sites/

[ ] Create site model:
    [ ] Site entity
    [ ] SiteRepository
    [ ] SiteService

[ ] Implement endpoints:
    [ ] POST /api/v1/companies/:company_id/sites
         Input: name, address, gps_lat, gps_lng, region
         Link to: company, branch (optional)
         Return: created site
    
    [ ] GET /api/v1/companies/:company_id/sites
         Return: all sites for company
         Include: incident count, visitor count
    
    [ ] GET /api/v1/sites/:id
         Return: site details with statistics
    
    [ ] PUT /api/v1/sites/:id
         Update: name, address, location
         Authorization: Manager+
    
    [ ] DELETE /api/v1/sites/:id
         Cascade: incidents, patrols, visitors
         Authorization: Admin only

[ ] Add site hierarchy:
    [ ] Sites belong to branches
    [ ] Branches belong to companies
    [ ] Enforce hierarchy in queries

[ ] Add site details:
    [ ] GET /api/v1/sites/:id/statistics
         Return: employees, incidents, visitors

[ ] Testing:
    [ ] Create sites under company
    [ ] List sites with filtering
    [ ] Update site location
    [ ] Verify hierarchy
    [ ] Authorization checks
```

**Acceptance Criteria**:
- [ ] All site endpoints working
- [ ] Company-site relationship maintained
- [ ] Location data validated (GPS)
- [ ] Authorization enforced
- [ ] Statistics accurate
- [ ] Tests passing

---

#### Task D3: Implement User Management
**Effort**: 5 hours  
**Priority**: 🟡 High  
**Status**: To Do  

**Description**:
Build user creation, assignment, and role management.

**Tasks to complete**:
```
[ ] Create users domain:
    [ ] src/domains/users/

[ ] Create user model:
    [ ] User entity
    [ ] UserRepository
    [ ] UserService

[ ] Implement endpoints:
    [ ] POST /api/v1/users - Create user
         Input: employee_id, username, email, password
         Generate: password hash using bcrypt
         Set: default role (Viewer)
         Authorization: Admin/Manager
         Return: created user
    
    [ ] GET /api/v1/users - List users
         Pagination: page, limit
         Filter: company_id, site_id, role
         Include: company, site, roles
    
    [ ] GET /api/v1/users/:id
         Return: user profile with all details
    
    [ ] PUT /api/v1/users/:id
         Update: email, username, site assignment
         Authorization: Self or Manager
    
    [ ] DELETE /api/v1/users/:id
         Soft delete (set status=inactive)
         Authorization: Admin only

[ ] Implement role assignment:
    [ ] POST /api/v1/users/:id/roles
         Input: role_id, site_id (optional), valid_from, valid_to
         Multiple roles allowed
         Return: user with new role
    
    [ ] DELETE /api/v1/users/:id/roles/:role_id
         Remove role from user
         Ensure user has at least one role

[ ] Implement user profile:
    [ ] GET /api/v1/users/me
         Return: current user details
    
    [ ] PUT /api/v1/users/me
         Update: password, profile info
         No changing roles/permissions
    
    [ ] GET /api/v1/users/me/permissions
         Return: all permissions for current user

[ ] Testing:
    [ ] Create users
    [ ] Assign roles
    [ ] Update user info
    [ ] List with filtering
    [ ] Profile endpoints
    [ ] Authorization checks
```

**Acceptance Criteria**:
- [ ] User CRUD endpoints working
- [ ] Role assignment functional
- [ ] Multi-role users supported
- [ ] Password properly hashed
- [ ] Profile endpoints working
- [ ] Authorization enforced
- [ ] All tests passing

---

### Category E: API Documentation (Week 2)

#### Task E1: Create API Documentation
**Effort**: 3 hours  
**Priority**: 🟡 High  
**Status**: To Do  

**Description**:
Document all API endpoints with examples.

**Tasks to complete**:
```
[ ] Create docs/api/API_DOCUMENTATION.md:
    [ ] Base URL: http://sentinel.local/api/v1
    [ ] Authentication: JWT Bearer token
    [ ] Response format: Standard JSON responses
    [ ] Error codes: List of all possible errors
    [ ] Rate limits: 100 requests/minute

[ ] Document authentication endpoints:
    [ ] POST /auth/login
    [ ] POST /auth/logout
    [ ] POST /auth/refresh
    [ ] POST /auth/forgot-password
    [ ] POST /auth/reset-password
    [ ] Include: request body, response, error codes

[ ] Document company endpoints:
    [ ] POST /companies
    [ ] GET /companies
    [ ] GET /companies/:id
    [ ] PUT /companies/:id
    [ ] DELETE /companies/:id
    [ ] Include: request/response examples

[ ] Document site endpoints:
    [ ] All CRUD operations for sites

[ ] Document user endpoints:
    [ ] All CRUD operations for users
    [ ] Role assignment
    [ ] Profile endpoints

[ ] Create Postman collection:
    [ ] All endpoints in Postman
    [ ] Auth token in collection
    [ ] Environment variables
    [ ] Example requests
    [ ] Tests for validation

[ ] Export and share:
    [ ] Postman collection JSON
    [ ] GitHub wiki documentation
```

**Acceptance Criteria**:
- [ ] All endpoints documented
- [ ] Postman collection complete
- [ ] Example requests working
- [ ] Error codes listed
- [ ] Authentication documented
- [ ] Rate limiting explained

---

## 📊 Sprint 1 Summary

### Deliverables

| Category | Task | Hours | Status |
|----------|------|-------|--------|
| Database | ERD Design | 8 | To Do |
| Database | Migrations | 6 | To Do |
| Database | Seeds | 3 | To Do |
| API | Project Setup | 4 | To Do |
| API | Middleware | 3 | To Do |
| Auth | JWT Authentication | 6 | To Do |
| Auth | RBAC System | 5 | To Do |
| Features | Company CRUD | 4 | To Do |
| Features | Site CRUD | 4 | To Do |
| Features | User Management | 5 | To Do |
| Docs | API Documentation | 3 | To Do |
| **TOTAL** | | **51 hours** | |

### Timeline

```
Week 1:
  Mon-Tue: Database ERD & Migrations (14 hrs)
  Wed-Thu: API Setup & Middleware (7 hrs)
  Fri: JWT Authentication start (4 hrs)

Week 2:
  Mon: JWT Authentication finish (4 hrs)
  Tue-Wed: RBAC System (5 hrs)
  Thu-Fri: Company/Site/User Management (13 hrs)
  Weekend: Documentation & Testing (4 hrs)
```

### Success Criteria

✅ Database schema complete with 30+ tables  
✅ REST API running and responding  
✅ Login returns JWT token  
✅ Protected routes require authentication  
✅ RBAC working (different users see different data)  
✅ Companies, sites, users manageable  
✅ All code tested  
✅ API fully documented  
✅ Ready for Sprint 2  

---

## 🚀 Next Steps

1. ✅ Set up GitHub repository
2. ✅ Create project board with Sprint 1 tasks
3. ✅ Read this document
4. ✅ Start Task A1: Database Design
5. ✅ Move tasks to "In Progress" as you work

---

**Created**: 2026-07-07  
**Sprint Duration**: 2 weeks (10 business days)  
**Status**: Planning Complete, Ready to Code
