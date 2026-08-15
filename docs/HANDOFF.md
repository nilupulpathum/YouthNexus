# YouthNexus — Development Handoff

## Purpose
This file provides a compact handoff point between development sessions and tools. It transfers important continuity between ChatGPT, Claude, Antigravity, and human team members without requiring the entire project history to be repeated.

## Important Distinction
This is not a permanent log of every conversation. The current handoff describes the active or most recent significant work. When a task is finished, outdated information should be replaced or moved into permanent project documentation.

## Current Handoff

### Task
`[feature/task name]`

### Working Branch
`[branch name]`

### Objective
`[what the task is trying to achieve]`

### Current Status
`[Not Started / In Progress / Implemented / Tested / Verified / Blocked / Complete]`

### Confirmed Decisions
- `[decision]`

### Files Created
- `[file]`

### Files Modified
- `[file]`

### Database Changes
- `[change or None]`

### Important Implementation Details
- `[detail]`

### Testing Performed
- `[test]`

### Known Issues
- `[issue]`

### Blockers
- `[blocker]`

### Assumptions
- `[assumption]`

### Context Updates Recommended
- `[recommended update]`

### Next Action
`[specific next action]`

## Handoff Rules
A handoff must distinguish:
- Confirmed facts — verified from repository, database, approved requirements, or team decisions.
- Assumptions — temporary information not yet confirmed.
- Proposed decisions — suggestions requiring confirmation.
- Open questions — questions affecting implementation.

## Claude → Antigravity Handoff
Claude should provide:
- requirements
- UI/Figma interpretation
- business rules
- database implications
- permissions
- validation
- edge cases
- files likely to change
- files that should not change
- testing requirements

## Antigravity → Review Handoff
Antigravity should return:
- implementation summary
- actual files changed
- database changes
- tests
- known issues
- assumptions
- blockers
- next steps

## Project-Context Updates
If a handoff reveals a project-wide change:
- do not update shared documents from a feature branch
- report the required context update
- update the canonical `project-context` branch separately

## Completion Checklist
- [ ] Requirements implemented
- [ ] Existing architecture respected
- [ ] Database inspected
- [ ] Authorization checked
- [ ] Validation checked
- [ ] Security checked
- [ ] Relevant tests performed
- [ ] Diff reviewed
- [ ] No unrelated files changed
- [ ] No secrets committed
- [ ] Known issues documented
- [ ] Next step identified

## Final Principle
A good handoff should allow another developer or assistant to continue without reconstructing the entire previous conversation.
