# Sentinel Enterprise Relationship Model (ERD Contract)

Status: Draft v1.0 (constitution artifact 2 of 7)
Last updated: 2026-08-06

## Purpose
Define the canonical relationship model for Sentinel so all applications share one enterprise data graph with strict tenant-safe boundaries.

## Usage Rule
- Before major feature expansion, affected entities and relationships must be defined here.
- Before adding schema foreign keys, cardinality and ownership must be documented here.
- Cross-domain joins must be justified and approved against this model.

## Canonical Root
Company is the tenant root.
All business entities must resolve to company scope directly or through a deterministic chain.

## Primary Hierarchy
Company
- Branch
- Department
- Employee
- Client
  - Client Site
    - Patrol Route
      - Patrol Checkpoint
    - Incident
    - Visitor
    - Device
- Contract
- Shift
- Vehicle
- Equipment
- Asset
- Work Order
- Task
- Payroll
- Billing
- Procurement
- Inventory

## Core Relationship Contracts
1. Company -> Branch: one-to-many
2. Company -> Department: one-to-many
3. Company -> Employee: one-to-many
4. Branch -> Department: one-to-many
5. Department -> Employee: one-to-many
6. Company -> Client: one-to-many
7. Client -> Client Site: one-to-many
8. Client -> Contract: one-to-many
9. Client Site -> Patrol Route: one-to-many
10. Patrol Route -> Patrol Checkpoint: one-to-many
11. Client Site -> Incident: one-to-many
12. Employee -> Shift Assignment: one-to-many
13. Employee -> Training Record: one-to-many
14. Employee -> Qualification: one-to-many
15. Employee -> Leave Request: one-to-many
16. Employee -> Payroll Record: one-to-many
17. Company -> Vehicle: one-to-many
18. Company -> Asset: one-to-many
19. Company -> Supplier: one-to-many
20. Supplier -> Purchase Order: one-to-many

## Cross-Domain Relationship Rules
- Operations entities may reference HR entities only through approved identity keys.
- Finance and payroll entities must never bypass company and employee ownership chains.
- AI entities consume references to source entities; they do not become ownership roots.
- Reporting entities can aggregate across domains but must preserve source traceability.

## Tenant and Scope Propagation
- Every relationship must preserve company_id context.
- Branch and department context is required where operationally relevant.
- Site and shift context must be retained for attendance, patrol, and incident workflows.

## Foreign Key and Cardinality Standard
- Explicit foreign keys for all deterministic relationships.
- Junction tables for many-to-many relationships.
- Soft delete aware references: archive state must be validated in services.
- No hidden ownership through optional chains.

## ERD Approval Checklist (Artifact 2)
For each application area:
1. Parent-child hierarchies documented.
2. Cross-domain references documented.
3. Many-to-many mappings documented.
4. Required foreign keys listed.
5. Tenant propagation validated.
6. Archive and lifecycle effects on relationships defined.

Approval state:
- Draft until all application areas pass checklist.
- Approved v1.0 when architecture + data leads sign off.
