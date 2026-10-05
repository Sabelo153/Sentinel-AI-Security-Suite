# Sentinel Feature Flags

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define a feature flag model that allows controlled rollout of platform features, modules, personas, workspaces, AI tools, and integrations.

## Feature Flag Model

```text
feature_flags
- id
- code
- name
- enabled
- tenant
- application
- workspace
```

## Use Cases
- Enable Finance for one customer while keeping it disabled for others
- Beta test AI features for selected users
- Roll out new modules gradually without changing the codebase
- Expose workspaces and personas based on customer subscription or release stage

## Example Flags
- finance.enabled
- ai.assistant.enabled
- mobile.offline.sync.enabled
- board.portal.enabled
- payroll.beta.enabled
- cyber.alerts.enabled

## Governance Rules
- Feature flags must be auditable.
- Flag state and changes should be logged.
- Permissions and workspace visibility must respect active feature flags.

## Approval Checklist
- Feature flag model approved
- Rollout and governance rules documented
- Tenant and workspace scoping reviewed
