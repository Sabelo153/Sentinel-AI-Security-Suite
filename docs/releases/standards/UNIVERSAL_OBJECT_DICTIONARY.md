# Universal Business Object Dictionary

Status: Draft
Last updated: 2026-08-07

## Purpose
Define one canonical contract for every core business object so all teams implement objects consistently.

## Required Object Definition Fields
Each object must define:
- Purpose
- Owner
- Lifecycle
- Relationships
- Events
- Permissions
- GPS requirements
- Audit requirements
- AI behavior
- APIs
- Reports

## Standard Object Template
Use this template for every object entry:

- Object name:
- Purpose:
- Domain owner:
- Lifecycle states:
- Parent/child relationships:
- Required events published:
- Required events consumed:
- Permission namespace:
- GPS requirements:
- Audit requirements:
- AI behavior:
- API resources:
- Reporting datasets/KPIs:

## Initial Object Set (Release 0.9 scope)
- Incident
- Patrol
- Checkpoint
- Site
- Guard
- Supervisor
- Assignment
- Shift
- Vehicle
- Client
- Visitor
- Asset
- Inspection
- Leave Request
- Equipment
- Mission
- Task

## Approval Criteria
- Every object in initial set has a completed template entry.
- Object terms are unique and non-overlapping.
- Event and permission namespaces align with standards.
- API resources are named consistently with API standards.
