# Feature-First Architecture Guide

> Organizing Sentinel for scalability and developer productivity

---

## 🏗️ Why Feature-First?

### Traditional Layer-First Architecture ❌

```
apps/web/app/
│
├── Controllers/
│   ├── AuthController.php
│   ├── CompanyController.php
│   ├── AttendanceController.php
│   └── IncidentController.php
│
├── Models/
│   ├── User.php
│   ├── Company.php
│   ├── Attendance.php
│   └── Incident.php
│
├── Services/
│   ├── AuthService.php
│   ├── CompanyService.php
│   ├── AttendanceService.php
│   └── IncidentService.php
│
├── Repositories/
│   ├── UserRepository.php
│   ├── CompanyRepository.php
│   ├── AttendanceRepository.php
│   └── IncidentRepository.php
│
└── Routes/
    ├── auth.php
    ├── company.php
    ├── attendance.php
    └── incidents.php
```

**Problems**:
- To work on "Attendance", you jump between Controllers/, Models/, Services/, Repositories/
- Related code scattered across project
- Hard to extract features
- New developers confused about structure
- Large folders become unmaintainable

---

### Feature-First Architecture ✅

```
apps/web/app/
│
├── Domains/
│   │
│   ├── Authentication/
│   │   ├── AuthController.php
│   │   ├── AuthService.php
│   │   ├── AuthRepository.php
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   ├── routes.php
│   │   └── tests/
│   │
│   ├── CompanyManagement/
│   │   ├── CompanyController.php
│   │   ├── CompanyService.php
│   │   ├── CompanyRepository.php
│   │   ├── Company.php
│   │   ├── Branch.php
│   │   ├── Site.php
│   │   ├── routes.php
│   │   └── tests/
│   │
│   ├── AttendanceManagement/
│   │   ├── AttendanceController.php
│   │   ├── AttendanceService.php
│   │   ├── AttendanceRepository.php
│   │   ├── Attendance.php
│   │   ├── ClockLog.php
│   │   ├── AttendanceReport.php
│   │   ├── routes.php
│   │   └── tests/
│   │
│   ├── IncidentManagement/
│   │   ├── IncidentController.php
│   │   ├── IncidentService.php
│   │   ├── IncidentRepository.php
│   │   ├── Incident.php
│   │   ├── IncidentPhoto.php
│   │   ├── IncidentUpdate.php
│   │   ├── IncidentType.php
│   │   ├── routes.php
│   │   └── tests/
│   │
│   ├── VisitorManagement/
│   │   ├── VisitorController.php
│   │   ├── VisitorService.php
│   │   ├── VisitorRepository.php
│   │   ├── Visitor.php
│   │   ├── Badge.php
│   │   ├── routes.php
│   │   └── tests/
│   │
│   ├── PatrolManagement/
│   │   ├── PatrolController.php
│   │   ├── PatrolService.php
│   │   ├── PatrolRepository.php
│   │   ├── Patrol.php
│   │   ├── PatrolCheckpoint.php
│   │   ├── PatrolRoute.php
│   │   ├── routes.php
│   │   └── tests/
│   │
│   ├── AIEngine/
│   │   ├── ReportWriter/
│   │   │   ├── ReportGenerator.php
│   │   │   ├── ReportService.php
│   │   │   ├── PromptTemplate.php
│   │   │   └── tests/
│   │   │
│   │   ├── RiskAssessment/
│   │   │   ├── RiskCalculator.php
│   │   │   ├── RiskService.php
│   │   │   ├── RiskModel.php
│   │   │   └── tests/
│   │   │
│   │   ├── Analytics/
│   │   │   ├── Analyzer.php
│   │   │   ├── AnalyticsService.php
│   │   │   ├── Dashboard.php
│   │   │   └── tests/
│   │   │
│   │   └── routes.php (combines all AI routes)
│   │
│   ├── Shared/
│   │   ├── Middleware/
│   │   │   ├── AuthMiddleware.php
│   │   │   ├── RoleMiddleware.php
│   │   │   ├── ValidationMiddleware.php
│   │   │   └── ErrorHandler.php
│   │   │
│   │   ├── Utilities/
│   │   │   ├── DateHelper.php
│   │   │   ├── FileUpload.php
│   │   │   ├── Encryption.php
│   │   │   └── Logger.php
│   │   │
│   │   ├── Validation/
│   │   │   ├── BaseValidator.php
│   │   │   ├── EmailValidator.php
│   │   │   ├── PasswordValidator.php
│   │   │   └── GPSValidator.php
│   │   │
│   │   ├── Responses/
│   │   │   ├── ApiResponse.php
│   │   │   ├── ErrorResponse.php
│   │   │   └── PaginatedResponse.php
│   │   │
│   │   ├── Exceptions/
│   │   │   ├── BaseException.php
│   │   │   ├── ValidationException.php
│   │   │   ├── AuthenticationException.php
│   │   │   └── AuthorizationException.php
│   │   │
│   │   ├── Constants/
│   │   │   ├── Roles.php
│   │   │   ├── Permissions.php
│   │   │   ├── IncidentTypes.php
│   │   │   └── Status.php
│   │   │
│   │   └── tests/
│   │
│   └── Events/
│       ├── UserCreated.php
│       ├── IncidentReported.php
│       ├── PatrolCompleted.php
│       └── listeners/
│
├── Config/
│   ├── app.php
│   ├── database.php
│   ├── auth.php
│   ├── cache.php
│   └── services.php
│
├── Routes/
│   └── api.php (master route file that imports from each domain)
│
├── Middleware/ (cross-cutting concerns)
│   └── ...
│
└── app.php (main application bootstrap)
```

**Benefits**:
- ✅ To work on "Attendance", open one folder
- ✅ All related code together
- ✅ Easy to extract as microservice later
- ✅ Teams own entire features
- ✅ Clear namespace organization
- ✅ Scalable as project grows

---

## 📁 Domain Structure Details

### Authentication Domain Structure

```
Domains/Authentication/
│
├── AuthController.php
│   - POST /auth/login
│   - POST /auth/logout
│   - POST /auth/refresh
│   - POST /auth/forgot-password
│   - POST /auth/reset-password
│
├── AuthService.php
│   - authenticate(email, password)
│   - generateTokens(user)
│   - validateToken(token)
│   - refreshToken(refreshToken)
│   - sendPasswordResetEmail(email)
│
├── AuthRepository.php
│   - findUserByEmail(email)
│   - findUserByEmailAndPassword(email, password)
│   - findUserById(id)
│   - updateUser(user, data)
│   - updatePassword(user, newPassword)
│
├── Models/
│   ├── User.php
│   ├── Role.php
│   └── Permission.php
│
├── Requests/
│   ├── LoginRequest.php
│   ├── ForgotPasswordRequest.php
│   └── ResetPasswordRequest.php
│
├── Resources/
│   └── UserResource.php (API response format)
│
├── Events/
│   ├── UserLoggedIn.php
│   └── PasswordReset.php
│
├── Listeners/
│   ├── LogUserLogin.php
│   └── SendPasswordResetEmail.php
│
├── Tests/
│   ├── AuthControllerTest.php
│   ├── AuthServiceTest.php
│   └── AuthRepositoryTest.php
│
├── routes.php
│   - Define /auth/* routes
│
└── config.php (domain-specific config if needed)
```

### Attendance Domain Structure

```
Domains/AttendanceManagement/
│
├── AttendanceController.php
│   - POST /attendance/clock-in
│   - POST /attendance/clock-out
│   - GET /attendance/my-records
│   - GET /attendance/reports
│
├── AttendanceService.php
│   - clockIn(employee, location)
│   - clockOut(employee, location)
│   - getAttendanceRecords(employee, date_range)
│   - generateReport(site, period)
│   - verifyGPS(gps_data)
│
├── AttendanceRepository.php
│   - saveClockIn(data)
│   - saveClockOut(data)
│   - getRecords(employee_id, date_range)
│   - getStatistics(site_id, period)
│
├── Models/
│   ├── Attendance.php
│   ├── ClockLog.php
│   ├── Break.php
│   └── Overtime.php
│
├── Requests/
│   ├── ClockInRequest.php
│   ├── ClockOutRequest.php
│   └── AttendanceReportRequest.php
│
├── Resources/
│   ├── AttendanceResource.php
│   └── AttendanceReportResource.php
│
├── Events/
│   ├── EmployeeClockedIn.php
│   ├── EmployeeClockedOut.php
│   └── OverTimeDetected.php
│
├── Listeners/
│   └── CreateOverTimeNotification.php
│
├── Jobs/
│   ├── GenerateAttendanceReport.php
│   └── SendAttendanceReminders.php
│
├── Tests/
│   ├── AttendanceControllerTest.php
│   ├── AttendanceServiceTest.php
│   └── GPSVerificationTest.php
│
├── routes.php
│
└── config.php
```

---

## 🔄 Cross-Domain Communication

### How Domains Interact

```
Workflow: User clocks in
│
├─ AttendanceManagement Domain
│  ├─ Validates user (calls AuthService)
│  ├─ Verifies location (uses Shared\Validation\GPSValidator)
│  ├─ Creates clock-in record
│  └─ Dispatches EmployeeClockedIn event
│
└─ Shared\Listeners
   └─ EmployeeClockedInListener
      ├─ Creates notification
      ├─ Updates analytics
      └─ Checks for anomalies (AIEngine)
```

### Best Practices

1. **Use Dependency Injection**
```php
// AttendanceController.php
class AttendanceController {
    public function __construct(
        AttendanceService $attendanceService,
        AuthService $authService,
        GPSValidator $gpsValidator
    ) {
        $this->attendanceService = $attendanceService;
        $this->authService = $authService;
        $this->gpsValidator = $gpsValidator;
    }
}
```

2. **Use Events for Loose Coupling**
```php
// Instead of direct calls
$attendanceService->clockIn(...);
// Dispatch event
event(new EmployeeClockedIn($attendance));
```

3. **Use Shared Utilities**
```php
// Not domain-specific, used by many
Shared\Utilities\Logger::log(...);
Shared\Validation\EmailValidator::validate(...);
Shared\Responses\ApiResponse::success(...);
```

4. **Never Import Across Domains**
```php
// ❌ DON'T DO THIS
use AttendanceManagement\Models\Attendance;
in IncidentManagement\IncidentService

// ✅ DO THIS - Use repositories or events
IncidentService $incident = app(IncidentService::class);
$attendance = $incident->getRelatedAttendance();
```

---

## 🧪 Testing Structure

Each domain has its own test folder:

```
Domains/Authentication/Tests/
├── AuthControllerTest.php
├── AuthServiceTest.php
├── AuthRepositoryTest.php
├── Middleware/RoleMiddlewareTest.php
└── Integration/
    ├── LoginFlowTest.php
    └── PasswordResetFlowTest.php

Domains/AttendanceManagement/Tests/
├── AttendanceControllerTest.php
├── AttendanceServiceTest.php
├── GPSVerificationTest.php
└── Integration/
    ├── ClockInFlowTest.php
    └── AttendanceReportTest.php
```

**Run tests by domain**:
```bash
# Test one domain
phpunit Domains/Authentication/Tests

# Test all
phpunit
```

---

## 📦 Extracting to Microservices

Because domains are self-contained, they can later become microservices:

**Year 1**: Monolith with domains
```
Monolith
├── Domains/
│   ├── AttendanceManagement/
│   ├── IncidentManagement/
│   └── ...
```

**Year 2-3**: Extract to microservices
```
API Gateway
├── Attendance Service (separate repo/deployment)
├── Incident Service (separate repo/deployment)
└── ...
```

No refactoring needed—just copy the domain folder!

---

## 🚀 Implementation Steps

### Step 1: Create Domain Directories
```bash
mkdir -p Domains/{Authentication,CompanyManagement,AttendanceManagement}
```

### Step 2: Define Routes
```php
// Routes/api.php
require_once app_path('Domains/Authentication/routes.php');
require_once app_path('Domains/CompanyManagement/routes.php');
require_once app_path('Domains/AttendanceManagement/routes.php');
// etc.
```

### Step 3: Register Service Providers
```php
// Config/app.php
'providers' => [
    // ...
    App\Domains\Authentication\AuthServiceProvider::class,
    App\Domains\CompanyManagement\CompanyServiceProvider::class,
    // ...
];
```

### Step 4: Autoload Namespaces
```json
// composer.json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "App\\Domains\\": "app/Domains/",
        "App\\Shared\\": "app/Shared/"
    }
}
```

---

## 📊 File Organization Checklist

For each new domain, create:

- [ ] `DomainNameController.php` - HTTP handlers
- [ ] `DomainNameService.php` - Business logic
- [ ] `DomainNameRepository.php` - Data access
- [ ] `Models/` - Eloquent models
- [ ] `Requests/` - Form requests/validation
- [ ] `Resources/` - API response format
- [ ] `Events/` - Domain events
- [ ] `Listeners/` - Event handlers
- [ ] `Jobs/` - Queued tasks
- [ ] `Tests/` - Test suite
- [ ] `routes.php` - Domain routes
- [ ] `config.php` (optional) - Domain config

---

## 🎯 Guidelines

### DO ✅

- Keep domains focused on single responsibility
- Use dependency injection
- Write tests for each domain
- Use shared utilities for common code
- Dispatch events for cross-domain communication
- Document domain responsibilities

### DON'T ❌

- Import models across domains
- Create circular dependencies
- Put domain-specific code in Shared/
- Skip tests for "simple" features
- Hardcode configuration values
- Create god domains (>5 epics)

---

## 📝 Example: New Feature Addition

**Adding "Payroll Management" feature**:

```
1. Create Domains/PayrollManagement/

2. Files:
   PayrollController.php
   PayrollService.php
   PayrollRepository.php
   Models/Salary.php, Deduction.php, etc.
   routes.php

3. Add to Routes/api.php:
   require_once app_path('Domains/PayrollManagement/routes.php');

4. Define routes in PayrollManagement/routes.php:
   POST /api/payroll/calculate
   GET /api/payroll/slips

5. Create tests in Tests/ subfolder

6. Write documentation in domain README.md

7. Commit as feature branch:
   git checkout -b feature/payroll-management
```

---

## 🏁 Conclusion

Feature-first architecture scales better than layer-first:
- Faster development
- Easier maintenance
- Team autonomy
- Technology flexibility
- Microservice-ready

This is how enterprise products are built. Sentinel is ready for growth!

---

**Status**: Architecture Recommended  
**Implementation**: Sprint 1 (Database) + Sprint 2 (Code)  
**Confidence**: Enterprise Grade ✨
