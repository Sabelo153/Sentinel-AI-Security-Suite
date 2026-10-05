# Sentinel Navigation Profiles

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define the navigation model for each application profile so users see the right menu, actions, and screens for their context.

## Navigation Model

```text
Role / Identity
  ↓
Application Profile
  ↓
Menu / Navigation Profile
  ↓
Screen
  ↓
Actions
  ↓
Permissions
```

## Navigation Profiles

### Guard Navigation Profile
- Home
- Start Shift
- Patrols
- Incidents
- Visitors
- Messages
- Emergency
- My Schedule
- My Leave
- My Payslip
- Training
- AI Assistant

### Site Manager Navigation Profile
- Dashboard
- Employees
- Visitors
- Vehicles
- Patrols
- Incidents
- Equipment
- Keys
- Maintenance
- Site Documents
- Site AI
- Reports

### Operations Navigation Profile
- Operations Dashboard
- All Sites
- All Guards
- Shifts
- Vehicles
- Patrol Monitoring
- Control Room
- Incidents
- Response Teams
- Clients
- Scheduling
- Risk
- Reports
- Analytics
- AI Operations Centre

### HR Navigation Profile
- Employees
- Recruitment
- Contracts
- Training
- Disciplinary
- Leave
- Payroll
- Performance
- Documents
- PSIRA
- Medical
- Firearm
- AI HR Assistant

### Finance Navigation Profile
- Dashboard
- Invoices
- Debtors
- Creditors
- Payroll
- GL
- Budgets
- Assets
- Bank
- Purchasing
- Reports

### Executive Navigation Profile
- Executive Dashboard
- Revenue
- Profit
- Contracts
- Operations
- Risk
- Clients
- Employee Health
- Growth
- Compliance
- AI Executive Advisor

### Board Navigation Profile
- Board Portal
- Board Pack
- Monthly Reports
- Financial Statements
- Risk Register
- Audit Findings
- Cyber Score
- AI Strategic Summary
- Meeting Minutes
- Voting
- Approvals

### Client Navigation Profile
- My Sites
- Incidents
- Visitors
- Guard Attendance
- Invoices
- Reports
- Maintenance
- Service Requests
- AI Chat

### Technician Navigation Profile
- Installations
- Maintenance
- Inventory
- Checklists
- Devices
- Work Orders
- AI Troubleshooting

### SOC Navigation Profile
- National Map
- Active Guards
- Incidents
- Vehicles
- LPR
- CCTV
- AI Alerts
- Cyber Alerts
- Weather
- Panic Events
- Response Teams

### Admin Navigation Profile
- Companies
- Users
- Roles
- Permissions
- Integrations
- Audit Logs
- API
- Feature Flags
- Licensing
- Settings

## Navigation Rules
- Navigation lists must be profile-driven and not hard-coded by role alone.
- Menus must be scoped to the active application profile.
- Shared actions should be surfaced differently per profile.
- Cross-application movement should remain explicit and contextual.

## Approval Checklist
- Navigation profiles defined for each application profile
- Menu structure reviewed for overlap and clarity
- Permissions and screen access aligned with profile expectations
