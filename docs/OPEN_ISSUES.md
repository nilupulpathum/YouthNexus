# YouthNexus — Open Issues and Decisions

## Purpose
This file tracks unresolved issues that can affect multiple parts of YouthNexus. It is not a list of every temporary bug on every feature branch.

## Issue Format
Each project-level issue should contain:
- ID
- Category
- Priority
- Status
- Description
- Impact
- Evidence
- Recommended action
- Owner/decision-maker if known

## Current Known Concerns

### ISSUE-001 — Shared project context must remain centralized
Category: Documentation / Process
Priority: High
Status: Open

YouthNexus has many feature branches. Shared context must remain centralized on `project-context` rather than duplicated.

Impact: duplicated context can become inconsistent and cause merge conflicts.

Recommended action: maintain one canonical set of shared context documents.

### ISSUE-002 — Project state must distinguish integrated work from branch work
Category: Process
Priority: High
Status: Open

`CURRENT_STATE.md` must describe accepted/integrated project state, not temporary branch work.

Impact: incomplete work could be mistaken for completed functionality.

Recommended action: update CURRENT_STATE.md only when project state has been accepted/integrated.

### ISSUE-003 — Design/database discrepancies must be explicitly resolved
Category: Database / Product
Priority: High
Status: Open

A Figma field does not automatically imply that an equivalent database field exists.

Impact: fabricated fields or sample values can cause incorrect implementation.

Recommended action: document the discrepancy and obtain a confirmed decision.

### ISSUE-004 — Role and organizational scope must remain consistent
Category: Authorization / Product
Priority: High
Status: Open

YouthNexus contains multiple organizational roles and levels. Permissions and scope must remain consistent across modules.

Impact: inconsistent checks can cause unauthorized access or incorrect data visibility.

Recommended action: verify permissions at UI and server-side levels for protected features.

### ISSUE-005 — Secrets must not be stored in the repository
Category: Security
Priority: Critical
Status: Open

Credentials, API keys, passwords, tokens, and other secrets must never be committed to Git.

Impact: committed secrets can expose project accounts and services.

Recommended action: remove/rotate exposed credentials and use appropriate local/environment configuration.

## Feature Issue Rule
If an issue affects only one feature branch, report it in the feature handoff and fix it there. Do not automatically add it here.

If it affects architecture, multiple features, security, database design, or project requirements, report it as a candidate project-level issue and update this file only after confirmation.

## Status Values
- Open
- Investigating
- Decision Required
- In Progress
- Resolved
- Rejected
- Superseded

## Important Rule
Do not silently close or change a project-level issue because an assistant believes it is resolved. Verify repository, implementation, tests, and team decision first.
