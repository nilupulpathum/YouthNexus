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


### ISSUE-006 — App.php routing fix requires integration into feat-signin

**Category:** Integration / Architecture  
**Priority:** High  
**Status:** Open

The Club Registration & Approval implementation currently contains a local
`App.php` routing fix on `feat-club-registration-approval`.

The fix has not yet been integrated into `feat-signin`, which is the branch
against which the current feature PR is open.

**Impact:**

The routing correction may not exist in the actual target branch after
integration unless it is explicitly reviewed and merged.

**Required action:**

Review the local `App.php` routing change and integrate the correct version
into `feat-signin` during the feature merge/review process.

**Do not:**

Treat the local feature-branch patch as globally available until it has
been integrated into the appropriate branch.

### ISSUE-007 — Model filename collisions between club registration branches

**Category:** Integration / Architecture  
**Priority:** High  
**Status:** Open

Five model files have filename collisions between
`feat-club-registration-approval` and `feat-club_registration`.

The current feature branch has locally reconciled these collisions using
method aliasing.

**Impact:**

The local reconciliation has not yet been validated as the final merged
architecture.

Incorrect reconciliation could cause duplicated functionality,
method conflicts, or regressions in either feature.

**Required action:**

Perform a real merge review between the affected branches and confirm the
final model structure and method responsibilities.

**Important:**

The local method aliasing solution should be treated as a proposed
reconciliation, not as a confirmed project-wide architectural decision.

### ISSUE-008 — Club application reference / district / venue-established data fields

**Category:** Product / Database  
**Priority:** High  
**Status:** Decision Required

Three product/database decisions were identified during the Club
Registration & Approval implementation:

- `application_ref`
- `district`
- `venue_established`

**Impact:**

The final data model and UI behaviour depend on the intended meaning,
source, and lifecycle of these fields.

**Required action:**

Obtain a confirmed product/database decision for each field before treating
the current interpretation as final.

**Status rule:**

Do not silently change the database or invent business rules for these
fields based only on the current UI implementation.

### ISSUE-009 — Meaning of nominee VERIFIED badge

**Category:** Product / UI / Verification  
**Priority:** Medium  
**Status:** Decision Required

The Club Registration & Approval review interface contains a nominee
`VERIFIED` badge.

The exact business meaning and verification criteria for this badge have
not yet been confirmed as a project-wide decision.

**Impact:**

Future implementations may interpret the badge differently, resulting in
inconsistent verification behaviour.

**Required action:**

Define:

- what the badge means
- what data makes a nominee eligible for the badge
- whether it represents identity verification, document verification,
  administrative verification, or another state
- which actor is responsible for verification

Until confirmed, the current interpretation should not be treated as a
global business rule.

### ISSUE-010 — Additional bank verification documents

**Category:** Product / Database / Document Management  
**Priority:** Medium  
**Status:** Decision Required

The Club Registration & Approval workflow identified a requirement/question
regarding additional bank verification documents.

The exact documents required and their storage/verification behaviour have
not yet been confirmed as a project-wide decision.

**Impact:**

The final UI, database structure, validation rules, and document workflow
may depend on this decision.

**Required action:**

Confirm:

- which additional documents are required
- whether they are mandatory or optional
- who verifies them
- where they are stored
- what verification status they require

### ISSUE-011 — ClubApplication to Club source relationship

**Category:** Database / Architecture  
**Priority:** Medium  
**Status:** Proposed

The current `Club` table does not contain a relationship back to the
`ClubApplication` that created the club.

A proposed solution is a nullable:

`Club.source_application_id`

foreign key referencing the relevant `ClubApplication`.

**Reason:**

Maintaining this relationship would allow the system to trace a created
Club back to the application from which it originated.

**Safety consideration:**

The proposed change is currently considered low-risk because the relevant
Club-writing code is limited to the current feature branch.

**Required action:**

Confirm the data-model decision before adding the field to the canonical
schema.

**Important:**

This is a proposed database improvement, not yet an approved schema change.

### ISSUE-012 — Gmail app password committed to feat-signin

**Category:** Security / Credentials  
**Priority:** Critical  
**Status:** Open — Immediate Action Required

A real Gmail app password has been committed in:

`feat-signin/config.local.php`

**Impact:**

The credential must be considered compromised because it has been committed
to the Git repository.

Even if the file is later deleted, the credential may remain accessible in
Git history.

**Required actions:**

1. Immediately revoke/rotate the exposed Gmail app password.
2. Replace the credential with a secure configuration mechanism.
3. Remove the secret from tracked source files.
4. Review Git history to determine whether the credential remains present.
5. Ensure the replacement configuration is excluded from Git where
   appropriate.
6. Check whether the credential was exposed to any remote repository or
   pull request.

**Important:**

Do not paste the actual password into this document, an issue, commit
message, or chat message.

The credential should be considered compromised until rotated/revoked.

### ISSUE-013 — Centralized Notification System Architecture

**Category:** Architecture / Cross-Cutting Feature  
**Priority:** Medium  
**Status:** Open / Recommended

The system requires an integrated notification mechanism across multiple modules (Club Registration, Event Approvals, Budget Requests, Announcements).

Currently, feature branches use localized pending queue indicators (such as the topbar bell and sidebar nav badge in Club Registration & Approval).

**Impact:**
Building ad-hoc notification logic independently in each feature branch will cause schema duplication, inconsistent APIs, and merge conflicts.

**Recommended action:**
1. Implement the notification system on a dedicated feature branch (`feat-notifications`).
2. Create a centralized `Notification` table (`notification_id`, `user_id`, `type`, `title`, `message`, `link_url`, `is_read`, `created_at`).
3. Create a shared `NotificationModel` and `/notifications` controller with standard unread/read endpoints.
4. Integrate a global dropdown panel into the shared `dashboard.css` topbar component.
5. Merge into `dev` / `main` so all other feature branches can emit notifications uniformly.


### ISSUE-013 — Development-only authentication helpers must remain excluded

**Category:** Security / Development Process  
**Priority:** High  
**Status:** Open

A local session-forging development login script was accidentally committed
during Club Registration & Approval development and was subsequently
removed.

The script was intended only for local testing without the real 2FA login
flow.

**Impact:**

Development-only authentication helpers can create serious security risks
if committed to the repository or deployed accidentally.

**Required action:**

For future features:

1. Development-only login/session helpers must remain local.
2. `.gitignore` coverage must be verified before committing.
3. `git status` and the staged diff must be reviewed before every commit.
4. No development helper may bypass production authentication in committed
   application code.
5. Any accidentally committed authentication bypass must be removed
   immediately and the repository history/exposure reviewed.

**Important:**

This issue does NOT mean the removed development script is currently part
of the feature. It records the development-process lesson so the same
mistake is not repeated in future branches.

### ISSUE-014 — Prevent fabricated UI data when database support is absent

**Category:** Product / Data Integrity / Development Process  
**Priority:** High  
**Status:** Open

During Club Registration & Approval development, several UI elements were
found to display realistic-looking values that were not actually backed by
the database or confirmed requirements.

Examples included:

- Placeholder registration numbers presented as real values
- Fake credential-delivery counts
- Fake bank-document verification information
- Fake document file sizes
- Fake document upload dates

All identified instances were removed.

**Impact:**

Fabricated values can make a UI appear functionally complete while
misrepresenting the actual system state.

This is particularly dangerous for administrative screens where users may
make decisions based on displayed information.

**Required action:**

When implementing future screens:

1. Every displayed dynamic value must have a confirmed data source.
2. Developers must inspect the actual database/schema before introducing
   fields.
3. Figma-only fields must not automatically become database-backed fields.
4. AI-generated placeholder data must not be presented as real data.
5. If a required value is unavailable, either omit it or clearly indicate
   that it is not provided.
6. Review completed screens specifically for fabricated or hardcoded
   business data.

**Established rule:**

UI completeness must never take priority over data correctness.