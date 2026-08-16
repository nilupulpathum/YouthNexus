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


## Club Registration & Approval — Current Implementation State

### Feature Status

The Club Registration & Approval feature is functionally complete on the
`feat-club-registration-approval` branch and has undergone multiple manual
testing and review rounds.

The feature is currently under a Draft pull request against `feat-signin`.
It has NOT yet been merged and should therefore be treated as:

**Implemented and tested on feature branch — pending integration.**

Current functionality includes:

- Club application list
- Application search
- Client-side sort toggle for the pending applications queue
- Filter panel
- Pending applications view
- Approved applications view
- Rejected applications view
- Full seven-section application review modal
- Figma-aligned review modal
- Approve flow
- Reject flow
- NIC-collision account linking
- Confirm-before-submit dialogs
- Escape-to-close modal behavior
- Centralized image rendering with universal fallback handling

### Testing Coverage

The Club Registration & Approval workflow has been manually tested across
multiple review rounds.

Testing includes:

- Application list loading
- Search
- Sorting
- Filtering
- Pending/Approved/Rejected views
- Opening the review modal
- All seven review sections
- Approve decision flow
- Reject decision flow
- Required rejection remarks
- Confirmation dialogs
- NIC-collision account linking
- Read-only review of processed applications
- Resulting database records for approval operations
- Centralized image fallback behaviour

The implementation specifically tested the NIC-collision path where a
nominee's NIC matches an existing proposer account, exercising account
linking rather than new-account creation.

### Established Reusable UI Patterns

#### 1. `dashboard.css` — Internal Dashboard Shell

`dashboard.css` is now established as the shared shell for internal
authenticated/actor screens.

Future internal dashboard screens should reuse the established shell,
layout structure, and styling conventions rather than creating separate
dashboard shells.

This is especially relevant to future Divisional-level screens.

#### 2. Review Modal Pattern

The Club Registration & Approval review modal establishes a reusable
interaction pattern for approval-style workflows.

Future approval workflows, such as **Approve Void Requests**, should
consider reusing the established review-modal structure where the
requirements are sufficiently similar.

#### 3. Centralized Image Fallback

Dynamic image rendering is now centralized through a shared `renderImg()`
helper with universal fallback handling.

Future screens containing multiple dynamic image sources should reuse this
pattern rather than implementing independent image-error handling for each
image.

### Data Integrity / UI Implementation Lessons

The Club Registration & Approval implementation identified and corrected
several patterns that must be avoided in future screens.

#### No Fabricated Data

Several placeholder values were identified during review and removed,
including:

- Fake registration numbers
- Fake "3/3 credentials sent" success information
- Fake bank-document verification checklist data
- Fake document file sizes
- Fake document upload dates

Future UI implementations must not create realistic-looking values for
information that is not actually represented in the database or confirmed
requirements.

If a value is unavailable, the UI should omit it or clearly indicate that
the information is not provided.

#### No External Fonts

External Google Fonts were temporarily introduced during a styling pass
but were removed because YouthNexus follows the vanilla-only technical
constraint.

The dashboard styling now uses the centralized
`--db-font-family` system-font variable.

Future screens must not introduce external font/CDN dependencies.

#### Development/Test Files Must Not Be Committed

A local session-forging development login script was accidentally committed
during development and subsequently removed.

This reinforces the requirement that development-only authentication
helpers, test backdoors, local configuration files, and similar files must
never be committed.

`.gitignore` coverage should be checked before committing new development
helpers.

### Current Integration Dependencies

The following items still prevent the feature from being considered fully
integrated:

- The `App.php` routing fix currently exists only on
  `feat-club-registration-approval` and still needs to be reviewed and
  integrated into `feat-signin`.
- Five model filename collisions with `feat-club_registration` were
  locally reconciled using method aliasing, but the final merged
  architecture still requires cross-branch review.
- Several product/database decisions remain unresolved; see
  `OPEN_ISSUES.md`.

### Database Relationship Gap

The current `Club` table does not record which `ClubApplication` produced a
given `Club` record.

A nullable `Club.source_application_id` relationship has been proposed to
provide this traceability.

This has not yet been implemented and should remain a proposed database
change until confirmed.

### Next Development Direction

The next planned feature is:

**Monitor Club Health — Divisional Coordinator**

Planned branch:

`feat-monitor-club-health`

This is the natural next step in the Divisional Coordinator workflow after
club approval.

The existing schema already contains the fields required by the current
Club Health monitoring direction:

- `Club.overall_health_score`
- `Club.health_status`
- `Club.flagged`

Therefore, the current plan does not require a database migration for the
initial monitoring implementation.

The planned feature is expected to be based on the current Club
Registration & Approval work. Because the approval branch has not yet been
merged into `feat-signin`, the Club Health branch will require appropriate
rebasing/integration once the approval workflow is merged.

### Important State Rule

The existence of implementation work on
`feat-club-registration-approval` does not mean the feature is integrated
into the project's main development line.

Until the pull request is merged and integration is verified, the feature
must be described as:

**Implemented/tested on feature branch — pending integration.**

