# Sentinel Product Specification v1.0

## 1. Product Vision

### Product Name
**Sentinel AI Security Suite**

### Tagline

> **Smarter Security. Better Decisions.**

### Mission
To provide security companies with one platform to manage their entire operation, replacing paper-based processes with intelligent digital workflows enhanced by AI.

---

## 2. Problem Statement
Security companies currently rely on:

- Paper occurrence books
- WhatsApp groups
- Excel spreadsheets
- Manual attendance registers
- Paper visitor books
- Printed patrol sheets
- Delayed reporting

Problems:

- Lost paperwork
- Poor accountability
- Slow reporting
- No analytics
- Difficult audits
- No centralized information

Sentinel solves all of these in one platform.

---

## 3. Target Customers

### Primary

- Small to medium security companies (10-500 guards)

### Secondary

- Large security companies
- Industrial sites
- Shopping centres
- Residential estates
- Hospitals
- Schools
- Warehouses
- Construction sites

---

## 4. User Roles

### Super Administrator
Manages the Sentinel platform itself.

### Company Administrator
Manages one security company.

### Operations Manager
Oversees operations across multiple sites.

### Site Supervisor
Manages guards at a specific site.

### Security Guard
Performs daily operational tasks.

### Receptionist
Handles visitors and vehicles.

### Client
Views reports and incidents related to their own sites.

---

## 5. Version 1.0 Modules
These are **in scope** for the first commercial release.

### Core

- Authentication
- Companies
- Clients
- Sites
- Employees
- Users
- Roles
- Permissions

### Operations

- Attendance
- Guard Clock In/Out
- Patrol Routes
- Patrol Checkpoints
- Occurrence Book
- Incident Reporting

### Reporting

- Daily Reports
- PDF Export
- Dashboard
- Search
- Audit Log

### AI

- AI Report Enhancement
- AI Risk Assessment
- AI Recommended Actions

### Version 2.0
These are intentionally **out of scope** for now.

- Visitor Management
- Vehicle Inspections
- Fire Drill Management
- Equipment Management
- Asset Tracking
- Leave Management
- Payroll Integration
- CCTV Integration
- Access Control Integration

### Version 3.0

- Sentinel Messenger
- AI Chat Assistant
- AI Scheduling
- Analytics Engine
- Rule Engine

### Future

- Sentinel Cloud
- Sentinel OS
- IoT Devices
- Drone Integration
- Facial Recognition
- Biometric Attendance

---

## 6. Functional Requirements
Every module gets documented.

### Attendance Module
Purpose:

Track employee attendance.

Features:

- Clock In
- Clock Out
- GPS Verification
- QR Verification
- Shift Assignment
- Attendance History

### Incident Module
Purpose:

Capture security incidents.

Fields:

- Incident Number
- Site
- Date
- Time
- Description
- Photos
- Witnesses
- AI Summary
- Supervisor Approval

### Patrol Module
Purpose:

Track guard patrols.

Features:

- Route Assignment
- Checkpoints
- QR/NFC Scan
- GPS Tracking
- Patrol Timing
- Missed Checkpoints

---

## 7. Non-Functional Requirements

### Performance

- Login < 500ms
- Dashboard < 2s
- Incident Save < 1s

### Security

- JWT
- HTTPS
- Password Hashing
- RBAC
- Audit Logs

### Scalability

- Multi-company
- Multi-site
- Multi-language (future)

### Availability

- 99.9% uptime (cloud version)

---

## 8. MVP Success Criteria
We'll know Version 1.0 is successful if a company can:

- Replace their paper occurrence book.
- Digitally clock guards in and out.
- Record patrols.
- Record incidents.
- Generate PDF reports.
- Receive AI-enhanced reports.
- Use it daily without paper.

If all of those are true, Sentinel is ready for its first paying customer.

---

## 9. Release Plan

### Alpha
Internal testing.

### Beta
1-3 pilot companies.

### Release Candidate
Bug fixes.

### Version 1.0
Commercial launch.

---

## 10. Pricing

| Plan | Monthly Price |
|------|---------------|
| Free | R0 |
| Essential | R49 per active user |
| Professional | R89 per active user |
| Enterprise | Custom |

Prices are monthly in South African rand. VAT is shown before payment if applicable.

South Africa is the default billing market. The public pricing and signup experience also supports the United States, United Kingdom, European Union, Australia and other countries. The selected market is stored on the company record during signup so later checkout can apply the correct currency and tax rules.

### Free

- Up to 10 users and 3 sites
- Core operational records
- No fixed trial period

### Essential

- Up to 30 users and 10 sites
- Workforce administration, attendance and shifts
- Contracts and operational reporting

### Professional

- Up to 100 users and 50 sites
- Patrol, device, GPS and geofence workflows
- Android guard and supervisor workflows

### Enterprise

- Custom integrations
- White-label
- API access
- Dedicated hosting

---

## Product Principles
Every feature we add should satisfy these principles:

1. **Simple** - A guard with minimal technical experience should learn the basics in under an hour.
2. **Fast** - Common tasks (clocking in, creating an incident) should take under a minute.
3. **Reliable** - Data integrity and auditability are more important than flashy features.
4. **Secure** - Every action is authenticated, authorized, and logged.
5. **Scalable** - Support one small company today and many companies tomorrow without redesigning the system.

---

## 11. First Real Backlog

### Epic 1 - Foundation

- Initialize Git repository
- Configure Composer
- Build SAF framework
- Create router
- Create configuration loader
- Create logger
- Create migration system

### Epic 2 - Identity

- Companies
- Clients
- Sites
- People
- Employees
- Users
- Roles
- Permissions

### Epic 3 - Authentication

- Login
- Logout
- JWT
- Password reset
- Session history

---

## 12. Source of Truth Rule
This specification is the product constitution for Version 1.0.

Anything outside this document is out of scope until explicitly approved into a later version or milestone.
