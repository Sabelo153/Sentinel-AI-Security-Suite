# Sentinel Module Manifest

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define the contract for each new module so implementation is plug-in based and consistent with the platform architecture.

## Manifest Structure
Each module should declare:
- module name
- domain
- applications
- personas
- workspaces
- capabilities
- widgets
- reports
- AI assistants
- notifications

## Example Manifest
```yaml
module: Employee Management

domain: HR

applications:
  - Operations
  - HR

personas:
  - Site Operations
  - Regional Operations
  - HR

workspaces:
  - Employee Workspace
  - HR Workspace

capabilities:
  - employee.create
  - employee.update
  - employee.archive
  - employee.assign_site
  - employee.issue_uniform
  - employee.record_training
  - employee.issue_firearm
  - employee.schedule_shift
  - employee.view_payslip
  - employee.approve_leave

widgets:
  - Active Employees
  - Compliance Expiry
  - New Hires

reports:
  - Employee Register
  - PSIRA Compliance

ai:
  - Employee Assistant

notifications:
  - Contract Expiry
  - Licence Expiry
```

## Implementation Rule
No new module should be implemented unless it provides a manifest that can be validated against the platform metadata model.

## Approval Checklist
- Manifest structure approved
- Example manifest reviewed
- Module registration and validation rules documented
