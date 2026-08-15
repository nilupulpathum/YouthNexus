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
