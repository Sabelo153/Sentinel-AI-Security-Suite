# AI Standards

Status: Draft
Last updated: 2026-08-07

## Purpose
Define consistent AI behavior contracts so AI remains helpful, auditable, and accountable.

## AI Accountability Rule
AI advises; humans remain accountable for final decisions.

## Required AI Capability Definition
Each AI capability must define:
- Trigger
- Input data sources
- Output format
- Confidence score
- Recommended action
- Human approval required (yes/no)
- Responsible authority level
- Audit record requirements

## AI Prompt Standard
- Prompts must be versioned and centrally managed.
- Prompt keys follow: {capability_name}_v{version}.
- Prompt changes require version bump and changelog entry.

## AI Decision Classes
- Advisory: recommendation only.
- Assisted: recommendation + pre-filled action draft.
- Automated-with-approval: action prepared, human approval required.

## Minimum Controls
- Explainability: output must include summary rationale.
- Traceability: source data references must be logged.
- Override: human override path is mandatory.
- Access: output visibility must respect permission and scope rules.

## Example Standard Entry
- Capability: ai.sleep_detection
- Trigger: guard inactivity and patrol schedule mismatch
- Inputs: GPS telemetry, patrol history, shift assignment
- Output: confidence score and recommended supervisor action
- Human approval required: yes
