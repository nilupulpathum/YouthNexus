<?php
/* =====================================================================
   CLUB FLAGGING (NYSC Escalation) — ALL-IN-ONE PROCESS REFERENCE
   =====================================================================

   FEATURE RESPONSIBILITY DEFINITION
   ----------------------------------
   Feature:        Club Flagging for NYSC Review
   Actor:          DivisionalSecretary and DivisionalCoordinator only.
                    DivisionalTreasurer explicitly EXCLUDED this phase —
                    see UNKNOWN/TODO below.
   Responsibility: Let Secretary/Coordinator raise a governance concern
                    about a club, backed by real signals relevant to
                    their own role, and submit it directly to NYSC Admin
                    for review.
   Does NOT own:    - Financial flagging criteria (Treasurer's domain —
                       blocked, no Finance/Ledger module exists yet)
                    - NYSC Admin's review/response flow (no NYSC Admin
                       actor/UI exists anywhere in this project yet —
                       this feature only creates the record; NYSC Admin
                       reviewing it is a future, separate feature)
                    - The overall_health_score calculation (never
                       implemented anywhere — confirmed by checking
                       MonitorClubHealthModel.php, which only ever SELECTs
                       this column, never computes it)

   CONFIRMED DECISIONS (from Nimesh, this session):
     1. A flag goes straight to NYSC Admin — no Coordinator-review-first
        gate on Secretary's flags. Both roles submit directly.
     2. The "Trigger" / signal text shown to the actor is PURELY
        INFORMATIONAL — no hard threshold auto-decides severity. The
        actor always chooses severity themselves.
     3. Treasurer's flagging capability is DEFERRED ENTIRELY until the
        Finance/Ledger module exists — not built as a manual-only
        stub, just not present for that role at all this phase.

   SOURCE OF TRUTH
   -----------------
   flag-club-modal.php ALREADY EXISTS with real, considered UI (severity
   select, required comment textarea, an honesty notice: "Only NYSC
   Admin can disband a club — this submits a flag and notification, it
   does not disband anything"). This file EXTENDS that existing modal
   with real backend + role-differentiated signal content — it does not
   replace or redesign it. The submit button currently has an HONEST
   stub: showToast('This flag was not saved. The review workflow will
   be enabled once the Club Health Flag system is built.') — confirming
   nobody has fabricated a fake success state here already, which is
   good precedent to preserve.

   GAP FOUND while planning this: the JS's currentClubData object
   (public/assets/js/monitor-club-health.js line ~275) does NOT capture
   club_id — only {name, code, status, score, members, division, estDate,
   committee}. This must be added; the flag submission cannot work
   without the real club_id.

   UNKNOWN / TODO:
     - Treasurer: explicitly out of scope this phase — the Flag button
       must be hidden for DivisionalTreasurer specifically (matching the
       existing pattern already built for hiding Flag/Edit from non-
       Coordinator roles, just needs its role list corrected).
     - NYSC Admin's own review screen: does not exist, out of scope.
       ClubFlag rows sit at status='PendingReview' with no UI to act on
       them yet — same pattern as divisional events awaiting a Zonal
       Coordinator that doesn't exist yet either. Expected, not a bug.
   ===================================================================== */

session_start();

// ---------------------------------------------------------------------
// PART A — PROCESS DATA / AUTH
// ---------------------------------------------------------------------

function requireFlaggingActor() {
    $allowed = ['DivisionalCoordinator', 'DivisionalSecretary'];
    if (empty($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', $allowed, true)) {
        header('Location: /auth/signin');
        exit();
    }
}
requireFlaggingActor();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$actorRole   = $_SESSION['user_role'];
$divisionId  = (int)($_SESSION['division_id'] ?? 0);

/*
 * Real SQL this represents (EventModel::findEventsForClub — ALREADY
 * BUILT AND WORKING, reused here exactly as-is, not duplicated):
 * events conducted in the last 3 months + average attendance rate for
 * THIS club specifically. This is the real signal Secretary sees.
 */
$eventSignals = [
    'events_conducted_3mo' => 1,
    'avg_attendance_rate'  => 32, // percent, or null if zero events with attendance
];

/*
 * Financial signal — genuinely unavailable. Never fabricate a number
 * here. Coordinator sees this exact "not available" state, not a zero
 * or a guess.
 */
$financialSignals = null; // Finance/Ledger module does not exist

/*
 * Real SQL (new ClubFlagModel::findByClub($clubId)) — prior flags
 * already raised on this club, for Coordinator's "considers all
 * details" context. Secretary does NOT see this (they only see their
 * own domain's signals, not other actors' prior judgment calls).
 */
$priorFlags = [
    (object)[
        'flagged_by_role' => 'DivisionalSecretary',
        'flagged_by_name' => 'N. Fernando',
        'severity'        => 'Medium',
        'comment'         => 'Attendance has been consistently low for two terms.',
        'flagged_at'      => '2026-07-02 10:14:00',
    ],
];

$currentClub = (object)[
    'club_id' => 42,
    'name'    => 'Maharagama Coolers',
    'score'   => 15,
];
?>

<!-- =====================================================================
     PART B — EXTENDED FLAG MODAL
     Base structure (header, severity select, comment textarea, notice,
     footer buttons) is UNCHANGED from the existing flag-club-modal.php —
     copy that file's exact markup for those parts. What's NEW is the
     "Relevant Signals" section inserted between the info box and the
     severity field, which differs by actor role.
     ===================================================================== -->
<div class="mch-modal-backdrop" id="mchFlagModal" role="dialog" aria-modal="true" aria-labelledby="mchFlagModalTitle">
    <div class="mch-modal-card mch-flag-modal">
        <div class="mch-modal-header">
            <h2 class="mch-modal-title" id="mchFlagModalTitle">Flag Club for NYSC Admin Review</h2>
            <button type="button" class="mch-modal-close-btn" id="mchFlagCloseBtn" aria-label="Close modal">&times;</button>
        </div>

        <div class="mch-modal-body">
            <div class="mch-flag-info-box">
                <div class="mch-flag-info-row">
                    <span>Club</span>
                    <b id="mchFlagClubName"><?= htmlspecialchars($currentClub->name) ?></b>
                </div>
                <div class="mch-flag-info-row">
                    <span>Overall Health Score</span>
                    <b id="mchFlagScore"><?= (int)$currentClub->score ?> / 100</b>
                </div>
            </div>

            <!-- =========================================================
                 NEW — Relevant Signals section, role-differentiated.
                 Purely informational per confirmed decision #2 — no
                 auto-computed severity, just real numbers laid out.
                 ========================================================= -->
            <div class="mch-flag-signals-box">
                <div class="mch-flag-signals-title">Relevant Signals for Your Review</div>

                <?php if ($actorRole === 'DivisionalSecretary'): ?>
                    <div class="mch-flag-signal-row">
                        <span>Events Conducted (Last 3 Months)</span>
                        <b><?= (int)$eventSignals['events_conducted_3mo'] ?></b>
                    </div>
                    <div class="mch-flag-signal-row">
                        <span>Average Attendance Rate</span>
                        <b><?= $eventSignals['avg_attendance_rate'] !== null ? $eventSignals['avg_attendance_rate'] . '%' : '—' ?></b>
                    </div>
                    <p class="mch-flag-signals-note">These are informational only — you decide the severity, the system does not.</p>

                <?php elseif ($actorRole === 'DivisionalCoordinator'): ?>
                    <div class="mch-flag-signal-row">
                        <span>Events Conducted (Last 3 Months)</span>
                        <b><?= (int)$eventSignals['events_conducted_3mo'] ?></b>
                    </div>
                    <div class="mch-flag-signal-row">
                        <span>Average Attendance Rate</span>
                        <b><?= $eventSignals['avg_attendance_rate'] !== null ? $eventSignals['avg_attendance_rate'] . '%' : '—' ?></b>
                    </div>
                    <div class="mch-flag-signal-row">
                        <span>Financial Standing</span>
                        <b class="mch-flag-signal-unavailable">Not available — Finance module not yet built</b>
                    </div>

                    <?php if (!empty($priorFlags)): ?>
                        <div class="mch-flag-prior-flags">
                            <div class="mch-flag-prior-flags-title">Prior Flags Raised on This Club</div>
                            <?php foreach ($priorFlags as $pf): ?>
                                <div class="mch-flag-prior-flag-item">
                                    <span class="mch-flag-prior-badge <?= strtolower($pf->severity) ?>"><?= htmlspecialchars($pf->severity) ?></span>
                                    <span><?= htmlspecialchars($pf->flagged_by_role) ?> (<?= htmlspecialchars($pf->flagged_by_name) ?>) — <?= htmlspecialchars($pf->comment) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="mch-form-group">
                <label for="mchFlagSeverity">Severity</label>
                <select id="mchFlagSeverity" class="mch-form-select">
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>

            <div class="mch-form-group">
                <label for="mchFlagComment">Comment (required)</label>
                <textarea id="mchFlagComment" class="mch-form-textarea" placeholder="Describe the governance concern for NYSC Admin..."></textarea>
            </div>

            <div class="mch-flag-notice">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15" stroke="currentColor" stroke-width="2"/></svg>
                <span>Only NYSC Admin can disband a club — this submits a flag and notification, it does not disband anything.</span>
            </div>
        </div>

        <div class="mch-modal-footer" style="justify-content: flex-end;">
            <button type="button" class="mch-btn-modal-secondary" id="mchFlagCancelBtn">Cancel</button>
            <button type="button" class="mch-btn-modal-primary" id="mchFlagSubmitBtn">Submit Flag to NYSC Admin</button>
        </div>
    </div>
</div>

<!-- =====================================================================
     PART C — CSS additions (new signal box only — everything else
     reuses existing mch-modal-* classes unchanged)
     ===================================================================== -->
<style>
.mch-flag-signals-box {
    background: #fafafa;
    border: 1px solid var(--db-border, #e7e9f0);
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 16px;
}
.mch-flag-signals-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--db-text-grey, #6b7280);
    margin-bottom: 10px;
}
.mch-flag-signal-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    padding: 5px 0;
}
.mch-flag-signal-unavailable {
    color: #9ca3af;
    font-style: italic;
    font-weight: 500;
}
.mch-flag-signals-note {
    font-size: 11.5px;
    color: #9ca3af;
    margin: 8px 0 0;
}
.mch-flag-prior-flags {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--db-border, #e7e9f0);
}
.mch-flag-prior-flags-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--db-text-grey, #6b7280);
    margin-bottom: 8px;
}
.mch-flag-prior-flag-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 12.5px;
    padding: 6px 0;
}
.mch-flag-prior-badge {
    flex-shrink: 0;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 999px;
}
.mch-flag-prior-badge.low      { background: #e0f2fe; color: #0369a1; }
.mch-flag-prior-badge.medium   { background: #fef3c7; color: #92400e; }
.mch-flag-prior-badge.high     { background: #fee2e2; color: #b91c1c; }
.mch-flag-prior-badge.critical { background: #450a0a; color: #fff; }
</style>

<!-- =====================================================================
     PART D — JS (submit handler — REAL, replacing the honest stub)
     ===================================================================== -->
<script>
document.getElementById('mchFlagSubmitBtn').addEventListener('click', function () {
    const comment  = document.getElementById('mchFlagComment').value.trim();
    const severity = document.getElementById('mchFlagSeverity').value;

    if (!comment) {
        alert('Please provide a reason / comment for the NYSC Admin.');
        document.getElementById('mchFlagComment').focus();
        return;
    }

    /*
     * currentClubData must be extended to include club_id — see the
     * GAP FOUND note in PART A. Without it this POST cannot work.
     */
    fetch(ROOT + '/monitorclubhealth/flag/' + currentClubData.club_id, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            csrf_token: document.getElementById('csrfToken')?.value || '',
            severity: severity,
            comment: comment,
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeFlagModal();
            showToast('Flag submitted to NYSC Admin.');
        } else {
            alert(data.error || 'Could not submit flag. Please try again.');
        }
    })
    .catch(() => alert('Could not submit flag. Please try again.'));
});
</script>
