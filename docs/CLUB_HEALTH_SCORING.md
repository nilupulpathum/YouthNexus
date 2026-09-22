# Club Health Scoring Contract

This document is the shared implementation contract for divisional club-health screens. All divisional actors use the same controller, model, route, score, and detail view. Actor scope and concern category change by signed-in role; the score does not.

## Authoritative weighting and bands

The project proposal defines a 0-100 score with these weights:

- Events: 40%
- Finances: 30%
- Attendance: 30%

Health bands follow the proposal exactly:

- Healthy (Green): score above 70
- At Risk (Yellow): score from 30 through 70
- Dormant (Red): score below 30

Six consecutive monthly Dormant calculations create an automatic administrative-review flag. A flag never changes the calculated score and does not itself disband a club.

## Reproducible component calculations

The proposal does not define normalization inside each component. YouthNexus therefore uses transparent, database-backed rules over a rolling six-month window:

| Component | Calculation | Data source |
|---|---|---|
| Events | Completed club-organized events / target of 6, capped at 100 | `Event` |
| Attendance | Present attendance / all recorded attendance on completed club-organized events | `Attendance`, `Event` |
| Finance activity | Approved entries / target of 6, capped at 100 | `Ledger`, `LedgerEntry` |
| Receipt coverage | Receipted approved expenses / approved expenses | `LedgerEntry` |
| Reconciliation | Reconciled approved entries / approved entries | `LedgerEntry` |

The finance component is 40% activity, 30% receipt coverage, and 30% reconciliation. The overall score is:

`(events x 0.40) + (finances x 0.30) + (attendance x 0.30)`

No attendance records produce an attendance score of zero. No ledger activity produces a finance score of zero. If activity exists but there are no expense entries, receipt coverage is treated as complete because there are no expense receipts due.

## Actor responsibilities

| Actor | Shared access | Manual concern category |
|---|---|---|
| Divisional Treasurer | Score, club details, events, attendance, finance, audits, history | Financial Concern |
| Divisional Secretary | Same calculated score and evidence | Event or Attendance Concern |
| Divisional Coordinator | Same calculated score and evidence | Governance Concern |
| System | Monthly snapshot calculation | Automatic Dormancy |
| NYSC Administrator | Receives concern notifications | Administrative review and any later disband decision |

Every query is restricted to the signed-in actor's `division_id`. Divisional officers cannot manually override a score or disband a club from this workflow.

## Reuse rule

Use `Divisionalclubhealth`, `DivisionalClubHealthModel`, and `divisionalclubhealth/index.view.php` for every divisional actor. Do not clone actor-specific copies of this page. Reuse the shared divisional workflow stylesheet first; `divisional-club-health.css` contains only health-specific card and drill-down rules.
