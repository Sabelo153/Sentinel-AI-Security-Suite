# Sentinel AI Security Suite - Domain-Driven Design Architecture

## Overview

Sentinel follows **Domain-Driven Design (DDD)** principles with clearly separated business domains. Each domain owns its data, business logic, API endpoints, and tests.

## Core Domains

### 1. **Authentication & Authorization Domain**

**Purpose**: User authentication, role-based access control, permissions management

**Responsibilities**:
- User login/logout
- JWT token generation and validation
- Role and permission management
- API key management
- OAuth2 integration (future)

**Key Entities**:
- Users
- Roles
- Permissions
- API Keys
- Session Tokens

**API Endpoints**: `/api/auth/*`

---

### 2. **Organization Domain**

**Purpose**: Manage organizational hierarchy and structure

**Responsibilities**:
- Company registration and management
- Branch/Location management
- Site/Facility management
- Organizational hierarchy
- Department management

**Key Entities**:
- Companies
- Branches
- Sites
- Departments
- Divisions

**API Endpoints**: `/api/companies/*`, `/api/branches/*`, `/api/sites/*`

---

### 3. **Workforce Domain**

**Purpose**: Employee and workforce management

**Responsibilities**:
- Employee profiles and records
- Job titles and designations
- Employment status tracking
- Qualifications and certifications
- Emergency contacts

**Key Entities**:
- Employees
- Job Titles
- Qualifications
- Designations
- Employee Status

**API Endpoints**: `/api/employees/*`, `/api/workforce/*`

---

### 4. **Attendance Domain**

**Purpose**: Track and manage employee attendance

**Responsibilities**:
- Check-in/check-out recording
- Attendance reports
- Leave management
- Shift tracking
- Attendance analytics

**Key Entities**:
- Attendance Records
- Leaves
- Shifts
- Working Hours
- Shift Patterns

**API Endpoints**: `/api/attendance/*`, `/api/shifts/*`

---

### 5. **Security Operations Domain**

**Purpose**: Core security operations and incident management

**Responsibilities**:
- Incident creation and tracking
- Incident severity and priority
- Incident lifecycle management
- Occurrence book maintenance
- Incident resolution

**Key Entities**:
- Incidents
- Incident Types
- Incident Status
- Occurrence Book Entries
- Incident Updates

**API Endpoints**: `/api/incidents/*`, `/api/occurrence-book/*`

---

### 6. **Visitor Management Domain**

**Purpose**: Visitor tracking and access control

**Responsibilities**:
- Visitor check-in/check-out
- Visitor history and records
- Host assignment
- Temporary access passes
- Visitor analytics

**Key Entities**:
- Visitors
- Visit Records
- Host Assignments
- Visitor Categories
- Access Passes

**API Endpoints**: `/api/visitors/*`, `/api/visit-records/*`

---

### 7. **Patrol Management Domain**

**Purpose**: Security patrol operations and tracking

**Responsibilities**:
- Patrol schedules and assignments
- Patrol checkpoint tracking
- Patrol reports
- Route management
- Patrol analytics

**Key Entities**:
- Patrols
- Patrol Checkpoints
- Patrol Routes
- Patrol Reports
- Checkpoint Logs

**API Endpoints**: `/api/patrols/*`, `/api/patrol-checkpoints/*`

---

### 8. **Vehicle Management Domain**

**Purpose**: Company vehicle and transport management

**Responsibilities**:
- Vehicle registration and tracking
- Vehicle maintenance records
- Driver assignments
- Vehicle usage logs
- Fuel consumption tracking

**Key Entities**:
- Vehicles
- Vehicle Types
- Maintenance Records
- Driver Assignments
- Fuel Logs
- Vehicle Usage

**API Endpoints**: `/api/vehicles/*`, `/api/vehicle-maintenance/*`

---

### 9. **Fire Safety Domain**

**Purpose**: Fire safety protocols and emergency management

**Responsibilities**:
- Fire safety equipment tracking
- Fire drill scheduling
- Emergency procedure documentation
- Fire safety compliance
- Equipment inspection records

**Key Entities**:
- Fire Equipment
- Fire Drills
- Emergency Procedures
- Inspections
- Safety Compliance

**API Endpoints**: `/api/fire-safety/*`, `/api/fire-drills/*`

---

### 10. **Asset Management Domain**

**Purpose**: Company asset tracking and management

**Responsibilities**:
- Asset registration and tracking
- Asset condition monitoring
- Depreciation tracking
- Asset assignment to employees
- Asset maintenance

**Key Entities**:
- Assets
- Asset Types
- Asset Assignments
- Maintenance Records
- Asset Conditions

**API Endpoints**: `/api/assets/*`, `/api/asset-assignments/*`

---

### 11. **Reporting Domain**

**Purpose**: Comprehensive reporting and report generation

**Responsibilities**:
- Incident reports
- Attendance reports
- Visitor analytics
- Performance reports
- Custom report generation
- Report scheduling

**Key Entities**:
- Reports
- Report Templates
- Scheduled Reports
- Report Parameters
- Report History

**API Endpoints**: `/api/reports/*`, `/api/report-templates/*`

**Connected to**: AI Domain for report writing automation

---

### 12. **Finance & ERP Domain**

**Purpose**: Financial governance and ERP backbone for full-suite replacement.

**Responsibilities**:
- Chart of accounts ownership and governance
- Journal posting and period close controls
- Payroll period lifecycle and export governance
- Billing and accounts receivable workflow
- Procurement and accounts payable workflow
- Cross-domain financial integration contracts

**Key Entities**:
- Finance Accounts
- Journal Entries
- Journal Lines
- Payroll Periods
- Payroll Export Batches
- Billing Invoices
- Billing Invoice Lines

**API Endpoints**: `/api/finance/*`, `/api/payroll/*`, `/api/billing/*`, `/api/procurement/*`

**Domain Integration Contracts**:
- Attendance -> Payroll (approved hours feed)
- Workforce -> Payroll (employee master data)
- Company -> Finance (company, branch, department dimensions)
- Security Operations -> Billing (billable incident and service adjustments)
- Contracting -> Billing (contract rate and invoicing triggers)

**Key Events**:
- JournalEntryCreated
- JournalEntryPosted
- FinancePeriodClosed
- PayrollPeriodLocked
- PayrollExportGenerated
- BillingInvoiceIssued
- PurchaseOrderApproved

---

### 12. **AI & Intelligence Domain**

**Purpose**: Artificial intelligence and machine learning capabilities

**Sub-domains**:

#### AI Report Writer
- Automated incident report generation
- Natural language report composition
- Report template management
- Grammar and spell checking

#### Risk Assessment Engine
- Risk scoring algorithms
- Anomaly detection
- Pattern recognition
- Trend analysis

#### Recommendation Engine
- Intelligent recommendations
- Predictive analytics
- Best practice suggestions
- Actionable insights

#### Analytics Engine
- Data aggregation
- Statistical analysis
- Trend identification
- Performance metrics

**Key Entities**:
- AI Models
- Recommendations
- Risk Scores
- Predictions
- Analytics Data

**API Endpoints**: `/api/ai/reports/*`, `/api/ai/risk-assessment/*`, `/api/ai/recommendations/*`

---

### 13. **Notification Domain**

**Purpose**: Multi-channel notification delivery

**Responsibilities**:
- Email notifications
- SMS notifications
- Push notifications
- WhatsApp notifications (future)
- Notification scheduling
- Notification tracking

**Key Entities**:
- Notifications
- Notification Templates
- Notification Channels
- Notification Logs
- User Preferences

**API Endpoints**: `/api/notifications/*`, `/api/notification-templates/*`

---

### 14. **Analytics Domain**

**Purpose**: Comprehensive analytics and business intelligence

**Responsibilities**:
- Dashboard data aggregation
- Performance metrics
- KPI tracking
- Trend analysis
- Export functionality

**Key Entities**:
- Dashboards
- Metrics
- KPIs
- Analytics Events
- User Analytics

**API Endpoints**: `/api/analytics/*`, `/api/dashboards/*`, `/api/metrics/*`

---

### 15. **Audit & Compliance Domain**

**Purpose**: Audit logging and compliance tracking

**Responsibilities**:
- Activity logging
- Change tracking
- Compliance monitoring
- Audit trail maintenance
- Data integrity verification

**Key Entities**:
- Audit Logs
- Change Logs
- Compliance Records
- User Activities
- System Events

**API Endpoints**: `/api/audit-logs/*`, `/api/compliance/*`

---

### 16. **File Storage Domain**

**Purpose**: File management and storage

**Responsibilities**:
- File upload and download
- File organization
- Storage management
- File access control
- Backup management

**Directories**:
- `/services/file-storage/images/` - Incident photos, visitor images
- `/services/file-storage/documents/` - Reports, compliance docs
- `/services/file-storage/reports/` - Generated reports
- `/services/file-storage/backups/` - System backups

---

### 17. **Settings & Configuration Domain**

**Purpose**: System and user settings management

**Responsibilities**:
- System configuration
- User preferences
- Feature toggles
- Notification preferences
- API rate limiting

**Key Entities**:
- Settings
- Configurations
- User Preferences
- Feature Flags

**API Endpoints**: `/api/settings/*`, `/api/preferences/*`

---

## Domain Communication

```
┌─────────────────────────────────────────────────────────────┐
│                    Web Dashboard / Mobile App                │
└────────────────────────┬────────────────────────────────────┘
                         │
                   REST API Gateway
                         │
        ┌────────────────┼────────────────┐
        │                │                │
        ▼                ▼                ▼
   ┌─────────┐    ┌─────────┐    ┌────────────────┐
   │  Auth   │    │  Org    │    │  Workforce     │
   │ Domain  │───▶│ Domain  │───▶│  Domain        │
   └─────────┘    └─────────┘    └────────────────┘
        │              │              │
        ├──────┬───────┼──────┬───────┤
        │      │       │      │       │
        ▼      ▼       ▼      ▼       ▼
   ┌────────────────────────────────────────┐
   │    Security Operations Domain           │
   │  (Incidents, Patrols, Visitors)        │
   └────────────────────────────────────────┘
        │       │       │       │
        └───┬───┴───┬───┴───┬───┘
            │       │       │
            ▼       ▼       ▼
   ┌──────────────────────────────────────┐
   │  AI & Intelligence Domain             │
   │  (Analysis, Recommendations, Insights)│
   └──────────────────────────────────────┘
        │
        ├─────┬──────┬─────┐
        ▼     ▼      ▼     ▼
    Reports Analytics Notifications Audit
```

## Shared Resources

**Shared Utilities** (`/shared/`):
- Constants (statuses, severity levels, roles)
- Validation rules
- Error handling
- Response formatting
- Pagination helpers
- Authentication decorators

**Database** (`/database/`):
- Centralized schema
- Shared migrations
- Database procedures
- Seed data

## Benefits of This Architecture

✅ **Scalability** - Each domain can scale independently  
✅ **Maintainability** - Clear separation of concerns  
✅ **Testability** - Domains can be tested in isolation  
✅ **Team Organization** - Teams can own entire domains  
✅ **Future Microservices** - Easy to separate into microservices later  
✅ **Reusability** - Shared utilities prevent code duplication  
✅ **Clarity** - New developers understand structure immediately  

---

**Version**: 1.0  
**Last Updated**: 2026-07-06  
**Status**: Architecture Defined, Ready for Development
