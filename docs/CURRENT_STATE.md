# YouthNexus — Current Project State

## Purpose
This file describes the accepted/integrated state of YouthNexus. It is intentionally branch-independent.

Temporary work on an unmerged feature branch must not be described here as completed project functionality.

## Project
YouthNexus is a centralized youth-club management platform for the Sri Lankan youth-club environment. The broader system covers registration, administration, activities, attendance, finance, reporting, notifications, and coordination.

## Architecture
- HTML
- CSS
- Vanilla JavaScript
- PHP
- MySQL
- PHP sessions
- PDO/prepared statements

No framework should be introduced without an explicit project decision.

## Major Functional Areas
- Authentication
- Registration
- Club registration
- Club registration approval
- Club/member management
- Events
- Attendance
- Finance
- Club health
- Reports
- Notifications
- PDF/QR functionality
- Administrative coordination

The implementation status of each area must be verified against the repository and accepted project decisions.

## Completed-State Rule
Only functionality accepted/integrated into the recognized project state should be marked as completed here. Presence on a temporary feature branch does not automatically make it completed.

## Development Principle
Before starting work:
1. Inspect the actual branch.
2. Inspect the actual repository.
3. Inspect the database/schema.
4. Inspect relevant feature documentation.
5. Inspect the canonical project context.

## Role and Authorization
YouthNexus uses role-based access and organizational scope. Protected functionality must be enforced server-side.

## Club Registration / Approval
Club registration and approval are established project areas. Detailed feature documentation is maintained separately in `docs/CLUB_REGISTRATION_APPROVAL.md`.

## Future / Planned Areas
The broader scope includes events, attendance, finance, club health, reports, notifications, and higher-level administration. These are not automatically considered implemented merely because they appear in the scope.

## Context Maintenance
When a feature becomes accepted/integrated:
1. Verify implementation.
2. Verify tests.
3. Verify integration status.
4. Update this file on `project-context` if the accepted project state materially changes.

Do not update it from ordinary feature branches.

## Important Limitation
This file never replaces repository inspection. If repository and this file disagree, report and investigate the discrepancy.

## Club Registration & Approval — Current Implementation State

### Feature Status

The Club Registration & Approval feature is functionally complete on the
`feat-club-registration-approval` branch.

Current implementation includes:

- Club application list with persistent DOM grid container
- Application text search and multi-criteria filter panel
- 3-way stat cards: Pending, Approved, and Rejected application views
- Dynamic feedback message on empty filter matches (`#crNoFilterMatch`)
- Topbar notification bell linked to live pending count with auto-hide at 0
- Sidebar navigation badge styled as a 22px circle (white fill, 2px orange border, bold orange text), auto-hiding at 0
- Full seven-section application review modal (interactive for Pending, read-only for Approved/Rejected)
- Figma-aligned design and color palette (`#1e40af` sidebar and modal headers)
- Automatic reviewer name and decision timestamp tracking
- Rejection remarks logging (`ClubApplication.rejection_remarks`)
- Approve flow with executive credential generation and email dispatch
- Reject flow with proposer notification
- NIC-collision account linking
- Native confirmation dialogs (`confirm()`) before executing decisions
- Escape-to-close and backdrop-click modal dismiss behaviors
- Robust datetime formatting for all date fields

The feature is currently on `feat-club-registration-approval` and ready for integration review.

Therefore, the feature should be considered:

**Implemented on feature branch — pending integration.**

### Established Reusable Patterns

This feature establishes two implementation patterns that may be reused
by future internal administrative screens.

#### 1. `dashboard.css` as the internal dashboard shell

`dashboard.css` is now being used as the shared visual shell for internal
actor/dashboard screens.

Future internal actor screens should reuse the established dashboard
structure and styling where appropriate rather than creating unrelated
dashboard layouts.

#### 2. Review Modal Pattern

The Club Registration & Approval review modal establishes a reusable
pattern for approval-style workflows.

Future workflows such as **Approve Void Requests** may reuse the same
review-modal structure and interaction pattern where the requirements are
similar.

### Integration Dependencies

The current feature has the following integration dependencies:

- The `App.php` routing fix currently exists only as a local patch on
  `feat-club-registration-approval` and needs to be properly integrated
  into `feat-signin`.
- Five model filename collisions with `feat-club_registration` were
  reconciled locally using method aliasing. A real merge/integration
  review is still required.

These items must be resolved before treating the feature as fully
integrated project functionality.