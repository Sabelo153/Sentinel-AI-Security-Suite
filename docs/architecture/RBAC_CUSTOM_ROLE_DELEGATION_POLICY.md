# RBAC Custom Role Delegation Policy

## Current State

RBAC administration is tenant-scoped through `RuntimeContext`. System roles are protected by the canonical `roles.is_system_role` flag. The current schema has no authority level, delegation scope, or permission-risk classification that can determine whether one custom role may safely grant another custom role's permissions.

## Current Safety Boundary

Only callers authorized by the route-level RBAC administration permissions can create custom roles or change role and user assignments. Cross-company identifiers are hidden, request-supplied company values are ignored, and system roles cannot be changed through the administration API.

## Required Approval Before Delegated Administration

Before non-system administrators can safely grant arbitrary custom roles or permissions, approve one of these explicit policies:

1. An authority-level model that restricts an actor to roles and permissions below its approved authority level.
2. A permission-risk model with explicit non-delegable and privileged permission categories.
3. A centrally managed role catalog where only platform administrators can change role-permission mappings.

Until one policy is approved, route permissions remain the sole boundary for custom-role administration. No implicit role-name hierarchy is enforced.