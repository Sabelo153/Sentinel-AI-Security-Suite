# Sentinel Approval Workflow Model

Status: Draft v1.0
Last updated: 2026-08-07

## Purpose
Define assignment-aware, authority-aware approval workflows for operations and governance actions.

## Core Principle
Approval is resolved by authority, scope, and assignment context, not only static role name.

## Approval Components
- Approval policy
- Required authority level
- Allowed scopes
- Sequential or parallel stages
- Escalation and timeout rules
- Delegation and acting support
- Audit and notification hooks

## Standard Approval Flow
1. Action submitted.
2. Target scope resolved (site/area/region/company).
3. Candidate approvers resolved from active assignments.
4. Required authority level enforced.
5. Delegation and acting overrides applied if valid.
6. Decision recorded and audited.
7. Next stage or completion triggered.

## Delegation Requirements
- Delegator assignment reference
- Delegate assignment reference
- Delegated permissions/actions
- Delegated scope
- Start and end dates
- Automatic expiry
- Full audit history

## Acting Assignment Requirements
- Covered position/assignment
- Acting employee assignment
- Validity period
- Scope restrictions
- Automatic rollback at expiry

## Required Data
- approval_levels
- workflow_approval_rules (planned)
- delegations
- acting_assignments
- approval_decisions (planned)

## Approval Checklist
- Authority thresholds defined per workflow.
- Scope and escalation rules approved.
- Delegation and acting policies approved.
- Audit and notification integration validated.
