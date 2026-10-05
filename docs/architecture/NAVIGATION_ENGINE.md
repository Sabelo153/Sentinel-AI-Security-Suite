# Sentinel Navigation Engine

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define a data-driven navigation engine so menus are composed from workspace and persona configuration rather than hard-coded role checks.

## Navigation Model

```text
Workspace
  ↓
Navigation Profile
  ↓
Menu Items
  ↓
Actions / Screens / Links
```

## Engine Principles
- Navigation is assembled from workspace metadata.
- Navigation items are permission-aware and scope-aware.
- Menu structure can change as the user changes persona or workspace.
- The frontend renders navigation based on a runtime contract from the backend.

## Navigation Contract
Each navigation payload can include:
- menu_id
- label
- route
- icon
- children
- visible
- permissions_required
- order

## Implementation Guidance
Instead of hard-coding navigation in the UI, the frontend should consume navigation metadata from the platform service layer.

## Approval Checklist
- Navigation engine concept approved
- Navigation metadata model reviewed
- Permission-aware rendering rules documented
