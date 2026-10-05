# Sentinel Position Catalog Model

Status: Draft v1.0
Last updated: 2026-08-07

## Purpose
Separate organizational job positions from authorities, personas, and capabilities.

## Core Principle
- Position: what the person is employed as.
- Authority: what decisions they may take.
- Assignment: where and when that applies.

## Example Positions
- Security Officer
- Senior Guard
- Supervisor
- Site Manager
- Area Manager
- Operations Manager
- Control Room Operator
- Control Room Manager
- General Manager
- CEO
- Board Member
- Inspector
- Mobile Response Officer

## Position Metadata
Each position should define:
- code and name
- department and function
- default authority range
- required qualifications and compliance
- default persona/workspace suggestions
- approval eligibility flags
- operational/offline requirements

## Position-to-Assignment Rules
- One employee can have multiple concurrent assignments.
- Same position can exist at different scopes.
- Higher position title does not imply unrestricted scope.
- Position changes must preserve assignment history.

## Required Data
- positions
- employee_assignments
- position_requirements (planned)
- position_persona_defaults (planned)

## Approval Checklist
- Position taxonomy approved.
- Position-to-authority mapping approved.
- Required qualifications linked.
- Assignment history and lifecycle rules approved.
