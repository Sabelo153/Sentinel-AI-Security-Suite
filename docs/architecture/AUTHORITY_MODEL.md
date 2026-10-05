# Sentinel Authority Model

Status: Draft v1.0
Last updated: 2026-08-07

## Purpose
Define authority as a first-class model separate from role names and job titles.

## Core Principle
- Position describes job identity.
- Authority describes decision power.
- Assignments bind position and authority to organization scope.

## Authority Scale
1. Guard: execute assigned duties.
2. Senior Guard: lead shift checks and patrol verification.
3. Supervisor: manage guards and incidents at site scope.
4. Site Manager: site-wide approvals and performance ownership.
5. Area Manager: multi-site operational control.
6. Operations Manager: regional operations and escalations.
7. General Manager: company-wide operational governance.
8. CEO: group-level strategic authority.
9. Board Member: read-only governance authority.

## Authority Rules
- Authority is evaluated per assignment, not per user globally.
- Higher authority does not bypass scope boundaries by default.
- Board authority is strategic read-only unless explicitly granted approvals.
- Temporary authority changes must use delegation or acting assignment records.

## Runtime Use
Authority participates in:
- approval workflow routing
- escalation decisions
- workspace and dashboard composition
- notification priority and recipients
- AI recommendation visibility and actionability

## Required Data
- authority_levels
- approval_levels
- employee_assignments.authority_level_id
- delegations
- acting_assignments

## Approval Checklist
- Authority scale approved for operations and governance users.
- Scope constraints documented for each level.
- Approval and escalation mapping completed.
- Delegation and acting policies reviewed.
