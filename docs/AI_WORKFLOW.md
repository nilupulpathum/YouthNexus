# YouthNexus — Development Collaboration Workflow

## Purpose
This document defines how ChatGPT, Claude, Antigravity IDE, developers, and GitHub collaborate on YouthNexus.

## Shared Context
The canonical shared context is maintained on the `project-context` branch:
- `docs/PROJECT_CONTEXT.md`
- `docs/AI_WORKFLOW.md`
- `docs/CURRENT_STATE.md`
- `docs/OPEN_ISSUES.md`
- `docs/HANDOFF.md`

These are shared project documents, not feature-branch documents.

## Branch Separation
Feature branches are for implementation. The `project-context` branch is for shared project-context documentation.

Feature branches must NOT:
- copy the shared context files
- maintain alternative versions
- modify shared context during ordinary implementation
- merge `project-context` merely to obtain documentation
- resolve context conflicts by choosing a version themselves

If a feature reveals a project-wide issue, report it through a handoff. The context owner updates the canonical documents separately.

## Tool Responsibilities

### ChatGPT — Architecture / Reasoning / Review
- Requirement analysis
- Architecture and database reasoning
- Security and authorization review
- Implementation planning
- Precise prompts for Antigravity
- Review of completed implementations
- Edge-case and regression analysis

### Claude — Analysis / Figma / UX
- Figma and UI/UX analysis
- User-flow analysis
- Requirement interpretation
- Design vs implementation comparison
- Identification of missing states and inconsistencies
- Implementation specifications

### Antigravity IDE — Repository Implementation
- Inspect actual repository and schema
- Implement approved requirements
- Test implementation
- Review final diff
- Provide structured handoff

## Recommended Workflow
1. Define requirement.
2. Inspect context and repository.
3. Analyze requirements, architecture, database, UI, roles, and edge cases.
4. Produce implementation specification.
5. Antigravity implements only the requested scope.
6. Review implementation.
7. Fix confirmed issues.
8. Commit focused changes.
9. Update project context separately when accepted/integrated.

## Source-of-Truth Priority
1. Actual repository/database state
2. Confirmed team decisions
3. Canonical project context
4. Approved requirements
5. Figma/design
6. AI inference

Never silently reconcile conflicts.

## No-Fabrication Rule
Never invent database fields, IDs, official records, application numbers, financial values, permissions, business rules, API responses, or verification states. Missing information must be reported.

## Handoff Standard
Every substantial implementation should report:
- Objective
- Status
- Files created
- Files modified
- Files intentionally not modified
- Database changes
- Implementation summary
- Tests performed
- Assumptions
- Known issues
- Blockers
- Context updates recommended
- Recommended next step

## Definition of Complete
Implemented = code exists.
Tested = relevant tests/checks performed.
Verified = reviewed against requirements and architecture.
Complete = implemented + tested + verified with no known blocking issue.

## Security
Never commit passwords, API keys, tokens, private credentials, or development backdoors.

## Final Principle
`project-context = shared project knowledge`
`feature branch = implementation work`
Keep these responsibilities separate.
