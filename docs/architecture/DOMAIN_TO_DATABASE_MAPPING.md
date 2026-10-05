# Domain-to-Database Mapping

**How Architectural Domains Map to Database Tables**

---

## Overview

This document shows the relationship between DDD domains and the evolving database schema baseline, ensuring the architecture aligns with implementation.

---

## 1. Identity & Access Domain

### Purpose
User authentication, authorization, and session management.

### Database Tables
```
users
├─ user_id (PK)
├─ email (Unique)
├─ password_hash
├─ is_active
├─ created_at, updated_at

roles
├─ role_id (PK)
├─ company_id (FK)
├─ role_name
├─ created_at

permissions
├─ permission_id (PK)
├─ permission_name
├─ category

user_roles
├─ user_role_id (PK)
├─ user_id (FK)
├─ role_id (FK)
├─ site_id (FK) -- Role at specific site
├─ created_at

role_permissions
├─ role_permission_id (PK)
├─ role_id (FK)
├─ permission_id (FK)

api_keys
├─ api_key_id (PK)
├─ user_id (FK)
├─ key_hash
├─ is_active
├─ last_used_at

login_history
├─ login_id (PK)
├─ user_id (FK)
├─ login_timestamp
├─ ip_address
├─ success_status

password_resets
├─ reset_id (PK)
├─ user_id (FK)
├─ reset_token
├─ expires_at
├─ used_at
```

### Domain Services
```
AuthenticationService
├─ login(email, password)
├─ logout(user_id)
├─ validateToken(token)

AuthorizationService
├─ hasPermission(user_id, site_id, permission)
├─ getUserRoles(user_id, site_id)

SessionService
├─ createSession(user_id)
├─ validateSession(session_id)

APITokenService
├─ generateToken(user_id)
├─ validateToken(token)
```

### Key Events
```
UserLoggedIn → Creates login_history entry
UserLoggedOut → Updates logout_timestamp
PermissionGranted → Updates user_roles
PermissionRevoked → Soft delete user_roles
```

---

## 2. Company Domain

### Purpose
Multi-tenant foundation and service-delivery hierarchy. Every operational record belongs to a company and is delivered through client contracts.

### Database Tables
```
companies
├─ company_id (PK)
├─ company_name
├─ registration_number
├─ industry_type
├─ website
├─ status (Active, Inactive, Suspended)
├─ created_at, updated_at

clients
├─ client_id (PK)
├─ company_id (FK)
├─ client_name
├─ industry
├─ status
├─ created_at, updated_at

contracts
├─ contract_id (PK)
├─ client_id (FK)
├─ contract_number
├─ contract_name
├─ start_date
├─ end_date
├─ monthly_value
├─ status
├─ created_at, updated_at

branches
├─ branch_id (PK)
├─ company_id (FK)
├─ branch_name
├─ branch_code
├─ address
├─ phone_number
├─ manager_id (FK to employees)
├─ created_at

sites (Customer Locations)
├─ site_id (PK)
├─ branch_id (FK)
├─ company_id (FK)
├─ contract_id (FK)
├─ site_name
├─ site_code
├─ address
├─ coordinates (latitude, longitude)
├─ site_type (Office, Warehouse, Retail, etc.)
├─ manager_id (FK to employees)

posts
├─ post_id (PK)
├─ site_id (FK)
├─ post_name
├─ description
├─ gps_lat
├─ gps_long
├─ status

departments
├─ department_id (PK)
├─ company_id (FK)
├─ site_id (FK)
├─ department_name
├─ manager_id (FK to employees)

company_contacts
├─ contact_id (PK)
├─ company_id (FK)
├─ contact_name
├─ contact_email
├─ contact_phone
├─ contact_role

company_documents
├─ document_id (PK)
├─ company_id (FK)
├─ document_type
├─ file_path
├─ uploaded_at

company_settings
├─ setting_id (PK)
├─ company_id (FK)
├─ setting_key
├─ setting_value (JSON)
├─ updated_at
```

### Domain Services
```
CompanyService
├─ createCompany(name, registration_number)
├─ updateSettings(company_id, settings)
├─ getCompanyProfile(company_id)

ClientService
├─ createClient(company_id, client_data)
├─ updateClient(client_id, data)
├─ listClients(company_id)

ContractService
├─ createContract(client_id, contract_data)
├─ updateContract(contract_id, data)
├─ listContracts(client_id)
├─ getActiveContracts(company_id)

BranchService
├─ createBranch(company_id, branch_data)
├─ updateBranch(branch_id, data)
├─ listBranches(company_id)

SiteService
├─ createSite(contract_id, site_data)
├─ updateSite(site_id, data)
├─ listSitesByContract(contract_id)
├─ getSiteByCoordinates(latitude, longitude)

PostService
├─ createPost(site_id, post_data)
├─ updatePost(post_id, data)
├─ listPosts(site_id)

DepartmentService
├─ createDepartment(site_id, name)
├─ updateDepartment(department_id, data)
├─ listDepartments(site_id)
```

### Key Events
```
CompanyCreated
ClientCreated
ContractActivated
BranchAdded
SiteActivated
PostActivated
SettingsUpdated
```

---

## 2A. Finance & ERP Domain

### Purpose
Financial governance and ERP boundaries for accounting, payroll exports, billing, and procurement workflows.

### Database Tables
```
finance_chart_of_accounts
├─ account_id (PK)
├─ company_id (FK)
├─ account_code
├─ account_name
├─ account_type (ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE)
├─ parent_account_id (Self FK)
├─ is_posting_allowed
├─ status
├─ created_at, updated_at

finance_journals
├─ journal_id (PK)
├─ company_id (FK)
├─ journal_number
├─ journal_date
├─ source_module
├─ source_reference
├─ total_debit, total_credit
├─ status (DRAFT, POSTED, REVERSED)
├─ created_by_user_id (FK)
├─ posted_by_user_id (FK)
├─ posted_at

finance_journal_lines
├─ journal_line_id (PK)
├─ journal_id (FK)
├─ company_id (FK)
├─ line_number
├─ account_id (FK)
├─ debit_amount, credit_amount
├─ branch_id (FK, nullable)
├─ department_id (FK, nullable)

payroll_periods
├─ payroll_period_id (PK)
├─ company_id (FK)
├─ period_code
├─ period_start, period_end
├─ payroll_date
├─ status (OPEN, LOCKED, EXPORTED, CLOSED)
├─ approved_by_user_id (FK)
├─ approved_at

payroll_export_batches
├─ payroll_export_batch_id (PK)
├─ company_id (FK)
├─ payroll_period_id (FK)
├─ export_format
├─ checksum_sha256
├─ file_path
├─ record_count
├─ status (PENDING, GENERATED, FAILED)
├─ generated_by_user_id (FK)
├─ generated_at

billing_invoices
├─ invoice_id (PK)
├─ company_id (FK)
├─ client_id (FK)
├─ contract_id (FK, nullable)
├─ invoice_number
├─ invoice_date, due_date
├─ subtotal_amount, tax_amount, total_amount
├─ status (DRAFT, ISSUED, PARTIALLY_PAID, PAID, VOID)
├─ issued_by_user_id (FK)

billing_invoice_lines
├─ invoice_line_id (PK)
├─ invoice_id (FK)
├─ company_id (FK)
├─ line_number
├─ description
├─ quantity, unit_price, line_total
├─ source_module, source_reference
```

### Domain Services
```
FinancePostingService
├─ createJournalEntry(company_id, journal_payload)
├─ postJournalEntry(company_id, journal_id, user_id)

PayrollExportService
├─ lockPayrollPeriod(company_id, payroll_period_id, approver_id)
├─ generateExportBatch(company_id, payroll_period_id, format, user_id)

BillingService
├─ createInvoice(company_id, client_id, invoice_payload)
├─ issueInvoice(company_id, invoice_id, user_id)

Prof1tConnector
├─ exportPayrollBatch(company_id, batch_id)
├─ exportBillingBatch(company_id, batch_reference)
├─ importMasterData(company_id, entities)
```

### Key Events
```
JournalEntryCreated
JournalEntryPosted
FinancePeriodClosed
PayrollPeriodLocked
PayrollExportGenerated
BillingInvoiceIssued
PurchaseOrderApproved
```

### Integration Rules
```
- Attendance domain provides approved hours to Payroll export workflows.
- Workforce domain provides employee and cost-centre master data.
- Company domain provides branch and department dimensions for journal tagging.
- Contract domain provides contract references for billing lines.
- Cross-domain operations must exchange events or explicit contracts, not direct repository calls.
```

---

## 3. Workforce Domain

### Purpose
Employee lifecycle management and skills tracking.

### Database Tables
```
persons (Core Identity)
├─ person_id (PK)
├─ first_name
├─ last_name
├─ email
├─ phone_number
├─ id_number
├─ date_of_birth

employees
├─ employee_id (PK)
├─ person_id (FK)
├─ company_id (FK)
├─ employee_number
├─ department_id (FK)
├─ designation_id (FK)
├─ manager_id (FK to employees)
├─ employment_type_id (FK)
├─ hire_date
├─ status (Active, On Leave, Terminated, etc.)

employee_contacts
├─ contact_id (PK)
├─ employee_id (FK)
├─ contact_name
├─ contact_phone
├─ contact_relationship

employee_documents
├─ document_id (PK)
├─ employee_id (FK)
├─ document_type
├─ document_number
├─ expiry_date

employee_training
├─ training_id (PK)
├─ employee_id (FK)
├─ company_id (FK)
├─ training_name
├─ provider
├─ completion_date
├─ expiry_date
├─ certificate_number

employee_certifications
├─ certification_id (PK)
├─ employee_id (FK)
├─ certification_name
├─ issuing_body
├─ issue_date
├─ expiry_date

performance_reviews
├─ review_id (PK)
├─ employee_id (FK)
├─ reviewer_id (FK to employees)
├─ review_date
├─ rating (1-5)
├─ comments

employment_contracts
├─ contract_id (PK)
├─ employee_id (FK)
├─ contract_type (Full-time, Part-time, Contract)
├─ start_date
├─ end_date
├─ salary
├─ conditions (JSON)
```

### Domain Services
```
EmployeeService
├─ hireEmployee(person_data)
├─ transferEmployee(employee_id, new_site)
├─ terminateEmployee(employee_id, reason)
├─ getEmployeeProfile(employee_id)

TrainingService
├─ recordTraining(employee_id, training_data)
├─ checkCertificationExpiry(employee_id)
├─ listExpiringSoon(company_id, days=30)

PerformanceService
├─ createReview(employee_id, reviewer_id, data)
├─ updateReview(review_id, data)
├─ getPerformanceHistory(employee_id)
```

### Key Events
```
EmployeeHired
EmployeeTransferred
EmployeePromoted
EmployeeTerminated
TrainingCompleted
CertificationExpiring
PerformanceReviewCreated
```

---

## 4. Attendance Domain

### Purpose
Clock in/out, shifts, breaks, leave management, overtime tracking.

### Database Tables
```
shifts
├─ shift_id (PK)
├─ company_id (FK)
├─ site_id (FK)
├─ shift_name
├─ start_time (TIME)
├─ end_time (TIME)
├─ break_duration_minutes
├─ required_staff_count

shift_assignments
├─ assignment_id (PK)
├─ shift_id (FK)
├─ employee_id (FK)
├─ assignment_date
├─ status (Assigned, Completed, Missed, Cancelled)

attendance
├─ attendance_id (PK)
├─ employee_id (FK)
├─ company_id (FK)
├─ site_id (FK)
├─ attendance_date
├─ shift_id (FK)
├─ status (Present, Late, Absent, Excused)
├─ clock_in_time
├─ clock_out_time
├─ total_hours
├─ verified (GPS confirmed)

clock_logs
├─ clock_log_id (PK)
├─ employee_id (FK)
├─ site_id (FK)
├─ clock_in_time
├─ clock_out_time
├─ gps_latitude
├─ gps_longitude
├─ gps_accuracy_meters
├─ device_type (Mobile, RFID, Biometric)
├─ verified_at

breaks
├─ break_id (PK)
├─ clock_log_id (FK)
├─ break_start
├─ break_end
├─ break_duration_minutes

overtime
├─ overtime_id (PK)
├─ employee_id (FK)
├─ company_id (FK)
├─ overtime_date
├─ hours_worked
├─ rate_multiplier
├─ amount (DECIMAL)

leave_types
├─ leave_type_id (PK)
├─ company_id (FK)
├─ leave_type_name (Annual, Sick, Maternity, etc.)
├─ annual_allocation

leave_requests
├─ leave_request_id (PK)
├─ employee_id (FK)
├─ company_id (FK)
├─ leave_type_id (FK)
├─ start_date
├─ end_date
├─ reason
├─ status (Pending, Approved, Rejected)
├─ created_at

leave_approvals
├─ approval_id (PK)
├─ leave_request_id (FK)
├─ approved_by (FK to employees)
├─ approval_date
├─ decision (Approved, Rejected)
├─ comment

attendance_exceptions
├─ exception_id (PK)
├─ attendance_id (FK)
├─ exception_type (Early clock in, Late clock out, etc.)
├─ description
├─ requires_approval (boolean)
├─ approved_at
```

### Domain Services
```
ShiftService
├─ createShift(company_id, shift_data)
├─ assignShift(shift_id, employee_id)
├─ getShifts(site_id, date_range)

AttendanceService
├─ clockIn(employee_id, gps_location)
├─ clockOut(employee_id, gps_location)
├─ getAttendanceRecord(employee_id, date)
├─ calculateTotalHours(employee_id, date_range)
├─ validateGPS(gps_location, site_id)

BreakService
├─ recordBreak(clock_log_id, break_data)
├─ calculateBreakTime(employee_id, date)

OvertimeService
├─ calculateOvertime(employee_id, date_range)
├─ flagExcessiveOvertime(employee_id)

LeaveService
├─ requestLeave(employee_id, leave_data)
├─ approveLeave(leave_request_id, approver_id)
├─ getLeaveBalance(employee_id, year)
```

### Key Events
```
GuardClockIn (Triggers: Rule evaluation, Notification, Dashboard update)
GuardClockOut
ShiftAssigned
LeaveRequested
LeaveApproved
OvertimeDetected
AttendanceException
```

---

## 5. Security Operations Domain

### Purpose
Incident management, investigation, emergency response.

### Database Tables
```
incident_types
├─ type_id (PK)
├─ company_id (FK)
├─ type_name
├─ type_code
├─ default_severity

incidents
├─ incident_id (PK)
├─ company_id (FK)
├─ site_id (FK)
├─ incident_type_id (FK)
├─ reported_by (FK to employees)
├─ incident_date
├─ description
├─ severity (Critical, High, Medium, Low, Info)
├─ status (Open, Investigating, Resolved, Closed)
├─ risk_score (0-100, AI generated)
├─ created_at, updated_at

incident_photos
├─ photo_id (PK)
├─ incident_id (FK)
├─ photo_url
├─ caption
├─ uploaded_by (FK to users)
├─ uploaded_at

incident_witnesses
├─ witness_id (PK)
├─ incident_id (FK)
├─ person_id (FK)
├─ statement
├─ contact_number
├─ recorded_at

incident_updates
├─ update_id (PK)
├─ incident_id (FK)
├─ update_type (Status update, Comment, Action)
├─ content
├─ updated_by (FK to employees)
├─ updated_at

incident_actions
├─ action_id (PK)
├─ incident_id (FK)
├─ action_type (Preventive, Corrective, etc.)
├─ action_description
├─ assigned_to (FK to employees)
├─ due_date
├─ completed_date
├─ status (Open, Completed, Overdue)

incident_responses
├─ response_id (PK)
├─ incident_id (FK)
├─ response_type (Police, Ambulance, Fire, Etc.)
├─ responder_name
├─ response_time
├─ outcome
├─ response_date

incident_escalations
├─ escalation_id (PK)
├─ incident_id (FK)
├─ escalated_to (FK to employees)
├─ escalation_reason
├─ escalated_at
├─ resolved_at

incident_risk_assessment
├─ assessment_id (PK)
├─ incident_id (FK)
├─ risk_level (1-5)
├─ assessment_notes
├─ assessed_by (FK to employees)
├─ assessed_at
```

### Domain Services
```
IncidentService
├─ reportIncident(incident_data)
├─ updateIncident(incident_id, data)
├─ closeIncident(incident_id, reason)
├─ getIncidentDetails(incident_id)

InvestigationService
├─ startInvestigation(incident_id)
├─ recordStatement(incident_id, witness_data)
├─ attachEvidence(incident_id, photo)
├─ generateReport(incident_id)

ResponseService
├─ triggerResponse(incident_id, response_type)
├─ recordResponse(incident_id, response_data)
├─ getResponseHistory(incident_id)

RiskAssessmentService
├─ assessRisk(incident_id)
├─ calculateRiskScore(incident_data) → Uses AI
├─ flagHighRisk(incident_id)
```

### Key Events
```
IncidentReported (Triggers: RuleEngine, Notification, Escalation)
IncidentInvestigating
IncidentResolved
HighRiskIncident (Triggers: Auto-escalation)
ResponseTriggered
WitnessRecorded
EvidenceAttached
```

---

## 6. Occurrence Book Domain

### Purpose
Real-time event log capturing everything on site. Chronological audit trail.

### Database Tables
```
occurrence_book
├─ entry_id (PK)
├─ company_id (FK)
├─ site_id (FK)
├─ entry_type (Attendance, Incident, Patrol, Visitor, Vehicle, etc.)
├─ entry_time
├─ employee_id (FK, nullable)
├─ description
├─ reference_id (Links to source record)
├─ reference_type (incident_id, attendance_id, etc.)
├─ severity (Low, Medium, High, Critical)
├─ requires_action (boolean)
└─ created_at

occurrence_events
├─ event_id (PK)
├─ occurrence_id (FK)
├─ event_type (Guard Arrived, Incident Occurred, etc.)
├─ event_time
├─ event_details (JSON)
└─ created_at
```

### Domain Services
```
OccurrenceService
├─ logEvent(entry_data) -- Called by other domains
├─ createTimeline(incident_id, date_range)
├─ queryOccurrences(filters)
├─ generateOccurrenceReport(site_id, date_range)
├─ searchByEmployee(employee_id, date_range)
└─ exportOccurrenceLog(site_id, format=PDF|Excel)
```

### Key Events
```
EventLogged (Called by other domains)
OccurrenceCreated
TimelineGenerated
```

### Integration Points
```
When GuardClockIn event occurs:
  └─ OccurrenceService.logEvent({
       type: "Attendance",
       description: "Guard XX clocked in",
       employee_id: ...,
       reference_type: "attendance_id",
       reference_id: ...
     })

When Incident reported:
  └─ OccurrenceService.logEvent({
       type: "Incident",
       severity: "High",
       description: "Incident reported: ...",
       reference_type: "incident_id",
       reference_id: ...
     })
```

---

## 7. Patrol Domain

### Purpose
Security patrol management and QR checkpoint verification.

### Database Tables
```
patrol_routes
├─ route_id (PK)
├─ company_id (FK)
├─ site_id (FK)
├─ route_name
├─ description
├─ estimated_duration_minutes

patrol_checkpoints
├─ checkpoint_id (PK)
├─ route_id (FK)
├─ checkpoint_number
├─ checkpoint_name
├─ location_description
├─ gps_latitude
├─ gps_longitude
├─ qr_code_id (Unique)
├─ check_in_window_minutes (Grace period)

patrols
├─ patrol_id (PK)
├─ company_id (FK)
├─ site_id (FK)
├─ route_id (FK)
├─ employee_id (FK)
├─ patrol_date
├─ scheduled_start_time
├─ actual_start_time
├─ actual_end_time
├─ status (Scheduled, In Progress, Completed, Missed)
├─ completion_percentage
├─ notes

patrol_checkpoint_logs
├─ log_id (PK)
├─ patrol_id (FK)
├─ checkpoint_id (FK)
├─ check_in_time
├─ gps_latitude
├─ gps_longitude
├─ gps_accuracy_meters
├─ deviation_from_route (meters)
├─ qr_code_verified (boolean)
├─ notes

patrol_reports
├─ report_id (PK)
├─ patrol_id (FK)
├─ report_date
├─ incidents_observed
├─ observations (TEXT)
├─ recommendations
├─ created_at
```

### Domain Services
```
PatrolRouteService
├─ createRoute(site_id, route_data)
├─ addCheckpoint(route_id, checkpoint_data)
├─ updateRoute(route_id, data)
├─ generateQRCode(checkpoint_id)

PatrolService
├─ startPatrol(route_id, employee_id)
├─ checkInAtCheckpoint(patrol_id, checkpoint_id, gps_location)
├─ endPatrol(patrol_id)
├─ getPatrolStatus(patrol_id)
├─ verifyQRCode(qr_code)

PatrolAnalyticsService
├─ getCompletionRate(site_id, date_range)
├─ detectDeviations(patrol_id)
├─ identifyMissedCheckpoints(patrol_id)
├─ flagCheckpointMissed(patrol_id, checkpoint_id) -- For SRE
```

### Key Events
```
PatrolStarted
CheckpointCompleted
PatrolCompleted
CheckpointMissed (Triggers: SRE Rule, Alert)
PatrolDeviated (Triggers: SRE Rule, Alert)
QRCodeScanned
```

---

## 8. Fleet Domain

### Purpose
Vehicle management, inspections, maintenance, fuel tracking.

### Database Tables
```
vehicle_types
├─ type_id (PK)
├─ type_name
├─ capacity

vehicles
├─ vehicle_id (PK)
├─ company_id (FK)
├─ site_id (FK)
├─ registration_number
├─ vehicle_type_id (FK)
├─ make
├─ model
├─ year
├─ status (Active, Maintenance, Decommissioned)
├─ current_gps_latitude
├─ current_gps_longitude
├─ fuel_level_percentage
├─ mileage
├─ created_at

vehicle_drivers
├─ driver_assignment_id (PK)
├─ vehicle_id (FK)
├─ employee_id (FK)
├─ assignment_date
├─ is_current_driver

vehicle_inspections
├─ inspection_id (PK)
├─ vehicle_id (FK)
├─ inspection_date
├─ inspector_name
├─ status (Pass, Fail)
├─ next_due_date
├─ checklist_items (JSON)
├─ issues_found (TEXT)

vehicle_maintenance
├─ maintenance_id (PK)
├─ vehicle_id (FK)
├─ maintenance_type (Oil change, Tire rotation, etc.)
├─ maintenance_date
├─ next_due_date
├─ cost (DECIMAL)
├─ completed_at
├─ notes

fuel_logs
├─ fuel_log_id (PK)
├─ vehicle_id (FK)
├─ fuel_date
├─ fuel_amount (liters)
├─ cost (DECIMAL)
├─ mileage_at_refuel
├─ refuel_location
├─ fuel_type
├─ created_at

vehicle_usage_logs
├─ usage_log_id (PK)
├─ vehicle_id (FK)
├─ employee_id (FK)
├─ start_time
├─ end_time
├─ distance_km
├─ fuel_consumed
├─ start_mileage
├─ end_mileage
├─ notes
```

### Domain Services
```
VehicleService
├─ registerVehicle(vehicle_data)
├─ updateVehicleStatus(vehicle_id, status)
├─ getVehicleDetails(vehicle_id)
├─ getActiveVehicles(site_id)

InspectionService
├─ scheduleInspection(vehicle_id, due_date)
├─ recordInspection(vehicle_id, inspection_data)
├─ flagFailedInspection(vehicle_id) -- For SRE
├─ getInspectionHistory(vehicle_id)

MaintenanceService
├─ scheduleMaintenance(vehicle_id, maintenance_type)
├─ recordMaintenance(vehicle_id, maintenance_data)
├─ getMaintenanceDue(vehicle_id)
├─ predictMaintenance(vehicle_id)

FuelService
├─ logFuel(vehicle_id, fuel_data)
├─ analyzeFuelConsumption(vehicle_id, date_range)
├─ detectAnomalies(vehicle_id) -- AI/SRE

GPSService
├─ updateLocation(vehicle_id, gps_coordinates)
├─ trackVehicle(vehicle_id, date_range)
├─ getVehicleLocation(vehicle_id)
```

### Key Events
```
VehicleInspected
InspectionFailed (Triggers: SRE Rule - Block site entry)
MaintenanceCompleted
MaintenanceNeeded
FuelLogged
VehicleMovement (GPS update)
```

---

## 9. Visitor Domain

### Purpose
Visitor access management, badge issuance, entry/exit tracking.

### Database Tables
```
visitors
├─ visitor_id (PK)
├─ company_id (FK)
├─ first_name
├─ last_name
├─ email
├─ phone_number
├─ id_number
├─ is_blacklisted (boolean)
├─ created_at

visitor_visits
├─ visit_id (PK)
├─ visitor_id (FK)
├─ company_id (FK)
├─ site_id (FK)
├─ host_employee_id (FK)
├─ visit_purpose_id (FK)
├─ check_in_time
├─ check_out_time
├─ scheduled_checkout_time
├─ actual_checkout_time
├─ approved_by (FK to employees)
├─ status (Approved, Checked In, Checked Out, Cancelled)
├─ created_at

visitor_badges
├─ badge_id (PK)
├─ visit_id (FK)
├─ badge_number
├─ badge_status (Active, Inactive, Revoked)
├─ issue_time
├─ revoke_time
├─ areas_allowed (JSON) -- List of allowed areas
├─ created_at

visitor_access_logs
├─ log_id (PK)
├─ badge_id (FK)
├─ visitor_id (FK)
├─ site_id (FK)
├─ access_point (Door/Gate name)
├─ access_time
├─ access_type (Entry, Exit)
├─ gps_latitude
├─ gps_longitude
├─ allowed (boolean)

visitor_blacklist
├─ blacklist_id (PK)
├─ company_id (FK)
├─ visitor_id (FK)
├─ reason
├─ blacklist_date
├─ removed_at
├─ blacklist_level (Site, Company, Network)

visitor_documents
├─ document_id (PK)
├─ visitor_id (FK)
├─ document_type (ID, Passport, etc.)
├─ document_number
├─ document_url
├─ uploaded_at

visit_purposes
├─ purpose_id (PK)
├─ company_id (FK)
├─ purpose_name (Maintenance, Delivery, Meeting, etc.)
├─ requires_escort (boolean)
```

### Domain Services
```
VisitorRegistrationService
├─ registerVisitor(visitor_data)
├─ checkBlacklist(visitor_id)
├─ updateVisitorInfo(visitor_id, data)

VisitService
├─ requestVisit(visit_data)
├─ approveVisit(visit_id, approver_id)
├─ rejectVisit(visit_id, reason)
├─ getVisitDetails(visit_id)

BadgeService
├─ issueBadge(visit_id)
├─ revokeBadge(badge_id, reason)
├─ getBadgeStatus(badge_id)
├─ updateAllowedAreas(badge_id, areas)

AccessTrackingService
├─ logAccess(badge_id, access_point, gps_location)
├─ verifyAccess(badge_id, area) -- For gate control
├─ getVisitorLocation(visitor_id)
├─ generateVisitReport(visitor_id)

BlacklistService
├─ addToBlacklist(visitor_id, reason)
├─ removeFromBlacklist(visitor_id)
├─ getBlacklist(company_id)
```

### Key Events
```
VisitorArrived
VisitorDeparted
BadgeIssued
AccessGranted
AccessDenied (Triggers: Alert)
CheckoutOverdue (Triggers: SRE Rule)
VisitorBlacklisted
```

---

## 10. Asset Domain

### Purpose
Equipment, uniforms, radios, keys - assignment tracking and maintenance.

### Database Tables
```
asset_categories
├─ category_id (PK)
├─ company_id (FK)
├─ category_name (Radios, Keys, Uniforms, etc.)

assets
├─ asset_id (PK)
├─ company_id (FK)
├─ site_id (FK)
├─ category_id (FK)
├─ asset_code
├─ asset_description
├─ current_value (DECIMAL)
├─ status (Available, Assigned, Maintenance, Disposed)
├─ purchase_date
├─ warranty_expiry_date
├─ created_at

asset_assignments
├─ assignment_id (PK)
├─ asset_id (FK)
├─ employee_id (FK)
├─ assignment_date
├─ return_date (nullable)
├─ condition_on_return (Good, Fair, Damaged, Lost)
├─ notes

asset_maintenance
├─ maintenance_id (PK)
├─ asset_id (FK)
├─ maintenance_type
├─ maintenance_date
├─ next_due_date
├─ cost (DECIMAL)
├─ notes

asset_disposal
├─ disposal_id (PK)
├─ asset_id (FK)
├─ disposal_date
├─ disposal_reason (Damaged, Expired, Lost, etc.)
├─ disposal_method (Sold, Recycled, Destroyed)
├─ disposal_value (DECIMAL, recovered)
```

### Domain Services
```
AssetService
├─ registerAsset(asset_data)
├─ updateAssetStatus(asset_id, status)
├─ getAssetDetails(asset_id)

AssignmentService
├─ assignAsset(asset_id, employee_id)
├─ returnAsset(asset_id, employee_id, condition)
├─ getAssignmentHistory(asset_id)
├─ getEmployeeAssets(employee_id)

MaintenanceService
├─ scheduleMaintenance(asset_id, maintenance_type)
├─ recordMaintenance(asset_id, maintenance_data)
├─ getMaintenanceDue(company_id)

DisposalService
├─ disposeAsset(asset_id, disposal_data)
├─ trackDisposalValue(company_id, date_range)
```

### Key Events
```
AssetAssigned
AssetReturned
MaintenanceNeeded
AssetDisposed
```

---

## 11. AI Domain

### Purpose
Sentinel Rule Engine + Machine Learning + Natural Language Processing.

### Database Tables
```
ai_requests
├─ request_id (PK)
├─ company_id (FK)
├─ request_type (Generate Report, Analyze Pattern, etc.)
├─ context (JSON)
├─ model_used (GPT-4, Claude, etc.)
├─ created_at

ai_responses
├─ response_id (PK)
├─ request_id (FK)
├─ response_text (LONGTEXT)
├─ confidence_score (0-100)
├─ tokens_used (integer)
├─ cost (DECIMAL)
├─ created_at

ai_feedback
├─ feedback_id (PK)
├─ response_id (FK)
├─ user_feedback (Helpful, Incorrect, Incomplete)
├─ user_comment (TEXT)
├─ rating (1-5)

ai_risk_scores
├─ score_id (PK)
├─ company_id (FK)
├─ incident_id (FK, nullable)
├─ employee_id (FK, nullable)
├─ risk_category (Security, Operational, Compliance)
├─ risk_score (0-100)
├─ risk_factors (JSON)
├─ generated_at

ai_anomalies
├─ anomaly_id (PK)
├─ company_id (FK)
├─ anomaly_type (Attendance, Incident, Patrol, etc.)
├─ description
├─ detected_at
├─ severity (Low, Medium, High)
├─ requires_action (boolean)

ai_recommendations
├─ recommendation_id (PK)
├─ company_id (FK)
├─ incident_id (FK, nullable)
├─ recommendation_type (Preventive, Corrective, etc.)
├─ recommendation_text
├─ priority (1-5)
├─ status (New, Reviewed, Implemented, Dismissed)
├─ created_at

ai_model_performance
├─ performance_id (PK)
├─ model_name
├─ accuracy_score
├─ precision
├─ recall
├─ evaluated_at

knowledge_base
├─ article_id (PK)
├─ company_id (FK)
├─ title
├─ content (LONGTEXT)
├─ category
├─ created_at
├─ updated_at

company_policies
├─ policy_id (PK)
├─ company_id (FK)
├─ policy_name
├─ policy_content (LONGTEXT)
├─ version
├─ effective_date
├─ created_at

sop_documents
├─ sop_id (PK)
├─ company_id (FK)
├─ site_id (FK, nullable)
├─ sop_name
├─ document_content (LONGTEXT)
├─ version
├─ created_at

risk_profiles
├─ profile_id (PK)
├─ company_id (FK)
├─ profile_type (Security Posture, Operational Risk)
├─ risk_data (JSON)
├─ calculated_at

incident_patterns
├─ pattern_id (PK)
├─ company_id (FK)
├─ pattern_description
├─ incident_count
├─ time_period
├─ pattern_score (0-100)
├─ identified_at
```

### Domain Services
```
RuleEngineService (SRE)
├─ loadRules(domain, event_type)
├─ evaluateRules(event_data, context)
├─ executeActions(actions)
├─ logEvaluation(evaluation_result)

AIService
├─ generateResponse(request_data)
├─ analyzePattern(incident_data)
├─ calculateRiskScore(entity_data)
├─ detectAnomalies(dataset)
├─ generateRecommendations(incident_id)

KnowledgeService
├─ indexDocument(document)
├─ queryKnowledge(query)
├─ updatePolicies(company_id, policy_data)
├─ generateSOPs(site_id)

PatternService
├─ detectPatterns(company_id, date_range)
├─ analyzeIncidentPatterns(company_id)
├─ generateInsights(pattern_data)
```

### Key Events
```
RuleEvaluated
RuleMatched
PatternDetected
AnomalyDetected
RecommendationGenerated
AIFeedbackReceived
```

---

## 12. Reporting Domain

### Purpose
Report generation, scheduling, export, distribution.

### Database Tables
```
report_templates
├─ template_id (PK)
├─ company_id (FK)
├─ template_name
├─ template_type (Incident, Attendance, Patrol, etc.)
├─ template_format (HTML, PDF, Excel)
├─ sections (JSON)
├─ created_at

reports
├─ report_id (PK)
├─ company_id (FK)
├─ template_id (FK)
├─ report_type
├─ report_title
├─ date_range_start
├─ date_range_end
├─ content (LONGTEXT)
├─ generated_by (FK to users)
├─ generated_at

pdf_exports
├─ export_id (PK)
├─ report_id (FK)
├─ file_url
├─ file_size_kb
├─ export_time
├─ exported_at

scheduled_reports
├─ schedule_id (PK)
├─ company_id (FK)
├─ template_id (FK)
├─ schedule_name
├─ frequency (Daily, Weekly, Monthly)
├─ run_time (TIME)
├─ next_run_date
├─ last_run_date
├─ is_active (boolean)
├─ recipients (JSON) -- Email addresses
├─ created_at

report_history
├─ history_id (PK)
├─ schedule_id (FK)
├─ report_id (FK)
├─ run_date
├─ status (Success, Failed)
├─ error_message (TEXT, if failed)
├─ recipients_sent (integer)
├─ created_at
```

### Domain Services
```
ReportTemplateService
├─ createTemplate(template_data)
├─ updateTemplate(template_id, data)
├─ listTemplates(company_id)
├─ deleteTemplate(template_id)

ReportService
├─ generateReport(template_id, date_range)
├─ getReportContent(report_id)
├─ exportToPDF(report_id)
├─ exportToExcel(report_id)
├─ exportToCSV(report_id)

ScheduledReportService
├─ scheduleReport(schedule_data)
├─ updateSchedule(schedule_id, data)
├─ executeScheduledReport(schedule_id)
├─ getScheduleHistory(schedule_id)

DistributionService
├─ sendReport(report_id, recipients)
├─ sendScheduledReport(schedule_id)
├─ trackDelivery(report_id, recipient)
```

### Key Events
```
ReportGenerated
ReportScheduled
ReportSent
ScheduleUpdated
```

---

## Cross-Domain Communication Map

### Event Flow Example: Guard Clocks In

```
ATTENDANCE DOMAIN (Primary)
├─ Event: GuardClockIn
├─ Creates: attendance_record
├─ Emits: GuardClockInEvent
│
└─→ EVENT BUS broadcasts GuardClockInEvent
    │
    ├─→ OCCURRENCE BOOK DOMAIN (Listens)
    │   └─ Logs: "Guard clocked in at 06:45"
    │
    ├─→ AI DOMAIN (Listens)
    │   ├─ Runs SRE rules
    │   ├─ Evaluates: Is guard late?
    │   ├─ Decision: Create high-severity occurrence
    │   └─ Emits: RuleEvaluationCompleteEvent
    │
    ├─→ NOTIFICATION DOMAIN (Listens)
    │   └─ Sends: Alert to supervisor
    │
    ├─→ REPORTING DOMAIN (Listens)
    │   └─ Updates: Attendance dashboard
    │
    ├─→ SECURITY OPS DOMAIN (Listens)
    │   └─ If incident related: Updates incident status
    │
    └─→ AUDIT DOMAIN (Listens)
        └─ Logs: All changes for compliance
```

---

## Summary

Sentinel domains map to an expanding database baseline organized by business function:

| Domain | Tables | Purpose |
|--------|--------|---------|
| Identity & Access | 9 | Authentication, authorization |
| Company | 7 | Multi-tenant foundation |
| Workforce | 8 | Employee lifecycle |
| Attendance | 10 | Clock in/out, shifts, leave |
| Security Operations | 12 | Incidents, investigation |
| Occurrence Book | 2 | Event audit trail |
| Patrol | 5 | Patrol execution |
| Fleet | 7 | Vehicle management |
| Visitor | 7 | Access management |
| Asset | 4 | Equipment tracking |
| Finance & ERP | 7+ | Ledger, payroll export, billing, procurement foundations |
| AI Domain | 12 | Rules engine, ML, NLP |
| Reporting | 5 | Report generation |
| **TOTAL** | **Evolving** | Expands module-by-module with strict boundaries |

Each domain:
- Owns its business logic
- Has clear responsibilities
- Communicates via events
- Stays loosely coupled
- Can evolve independently

This ensures Sentinel scales cleanly as requirements grow.

