# ADR-0014: Metadata-Driven Platform Architecture

- Status: Proposed
- Date: 2026-08-06

## Decision
Adopt a metadata-driven platform architecture in which applications, personas, workspaces, navigation, widgets, reports, AI assistants, notifications, capabilities, and feature flags are configured as data rather than hard-coded in the application.

## Context
The platform is growing beyond simple screens and modules. To remain extensible and easier to customize across customers, Sentinel needs a runtime metadata model that composes the experience dynamically.

## Alternatives Considered
- Hard-coded UI and navigation per module
- Role-based branching in the frontend
- Separate app codebases for each persona or module

## Consequences
- Greater extensibility and easier rollout of new features
- Better support for customer-specific configurations and phased replacement
- Requires a metadata engine and a strong runtime validation model

## Related Blueprint Documents
- Platform Metadata
- Capability Registry
- Feature Flags
- Module Manifest
- Access Architecture
- Workspace Architecture
