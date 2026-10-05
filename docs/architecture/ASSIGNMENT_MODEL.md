# Sentinel Assignment Model

Status: Draft v1.0
Last updated: 2026-08-07

## Purpose
Define assignment-based access and experience composition for users with multiple responsibilities.

## Core Principle
A user may hold multiple assignments at the same time.
Each assignment can carry different scope, authority, capabilities, personas, and approval powers.

## Assignment Structure
Assignment includes:
- employee_id
- position_id
- authority_level_id
- scope_type and scope_target
- capability bundle
- persona hints
- workspace hints
- start_date and end_date
- status

## Scope Examples
- Site-level supervisor assignment
- Area manager assignment across a site set
- Company-level incident approver assignment
- Temporary acting site manager assignment

## Effective Access Resolution
1. Authenticate identity.
2. Load active assignments for now().
3. Remove expired/suspended assignments.
4. Expand scope to effective record filters.
5. Merge capabilities and permission actions.
6. Apply deny and segregation rules.
7. Resolve active persona/workspace context.
8. Produce runtime payload.

## Conflict Rules
- Deny rules override allow rules.
- Most specific scope wins for conflicting write actions.
- Highest authority does not auto-grant global scope.
- Explicit approval authorities are required for approval actions.

## Delegation and Acting
- Delegation grants temporary authority subset from delegator to delegate.
- Acting assignment creates temporary position coverage for vacancy/leave.
- Both require dates, scope, audit trail, and automatic expiry.

## Required Data
- employee_assignments
- assignment_scopes
- delegations
- acting_assignments
- assignment_capabilities (planned)

## Approval Checklist
- Multi-assignment runtime behavior approved.
- Scope merge and conflict rules approved.
- Delegation and acting controls approved.
- Audit requirements validated.
