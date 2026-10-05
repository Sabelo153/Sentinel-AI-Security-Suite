# Sentinel AI Persona Catalog

Status: Draft v1.0
Last updated: 2026-08-06

## Purpose
Define the AI assistant experience per persona so each user receives the right intelligence for their task and scope.

## AI Persona Model
Each AI profile defines:
- assistant name
- prompt profile
- allowed tools
- allowed data domains
- action permissions
- human review requirements
- tone and style

## AI Profiles by Persona

### Guard AI Assistant
- Helps with incident drafting, patrol guidance, and shift questions
- Limited to assigned site and shift data
- Supports summaries and checklists

### Site AI Assistant
- Helps review site issues, patrol anomalies, and visitor risks
- Access limited to site-scoped operations data

### Operations AI Assistant
- Supports multi-site incident analysis, risk review, and response planning
- Access to operations and incident data across allowed sites

### Executive AI Assistant
- Produces summaries, KPI insight, operational risk briefings, and strategic observations
- Access limited to executive reporting and approved summaries

### Finance AI Assistant
- Supports financial summary, anomaly review, forecasting, and approvals guidance
- Access to financial and billing data only as permitted

### Board AI Assistant
- Prepares governance summary, risk digest, and board-pack insights
- Access to governance-safe summaries and approved reporting data

### Client AI Assistant
- Answers service questions and report queries for client-scoped data
- Access limited to the client’s own service and incident context

### Technician AI Assistant
- Helps with troubleshooting steps, checklists, and work-order guidance
- Access limited to assigned devices, assets, and work history

### Admin AI Assistant
- Supports admin guidance, audit analysis, and integration health overview
- Access to platform administration and audit surfaces only

## Governance Rules
- AI profiles must respect persona permissions and scope.
- Sensitive actions require human approval.
- AI responses must be logged and auditable.

## Approval Checklist
- AI profile inventory approved
- Persona-to-AI access mapping reviewed
- Human review boundaries documented
