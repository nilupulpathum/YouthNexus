<?php
/* =====================================================================
   ATTENDANCE MANAGEMENT — ALL-IN-ONE PROCESS REFERENCE
   =====================================================================

   FEATURE RESPONSIBILITY DEFINITION
   ----------------------------------
   Feature:        Divisional Attendance Management
   Actor:          Divisional Secretary (role: 'DivisionalSecretary')
   Responsibility: Record and track attendance for events within the
                    Secretary's division and below. Upload attendance in
                    bulk (CSV) or one at a time. View per-event attendance
                    stats and update individual member status.
   Does NOT own:    - Event creation (already built, feat-event-management)
                    - Event approval (already built, feat-event-approval)
                    - Attendance for Club/Zone/NYSC-level events (out of
                       scope — no confirmed actor/Figma for those yet)
                    - RSVP (member-side, separate, not built)

   SOURCE OF TRUTH
   -----------------
   Figma screens (Manage Attendance, both frames) reviewed directly —
   confirmed real, unlike Event Approval which had no Figma at all.
   CONFIRMED FROM FIGMA:
     - List view: 2 stat cards (Total Events This Term, Total Attendance
       Logged), "By Event" search toggle, Advanced Filter, +Add Attendance
       button, attendance record cards (X PRESENT badge, date range, event
       title, location, organizer name + photo count, View button).
     - Filter panel: an explicit access-restriction banner —
       "Level access is restricted by your role – Divisional Secretary
       can view Divisional level and below only." Level/Division/Club
       cascading filter, Event/Date Range/Status filters.
     - Add Attendance modal: Select Event dropdown, Start Time/Date/
       Location shown read-only ("from DB"), Bulk Upload / Single Entry
       toggle, CSV upload (must include Member ID column), End Time
       entry, optional remark.
     - Detail/session view: event breadcrumb, Download CSV / Export PDF,
       3 stat cards (Total Present, Total Absent, Attendance Rate),
       member table (Name/Position/Check-in Time/Status) with search +
       status filter, Quick Update panel (select member, Present/Absent
       toggle, note, Update Status button), Save Changes button.

   DECISIONS ALREADY LOCKED IN (from prior sessions, not re-litigated here):
     - POSITION column (Team Captain, Midfielder, etc.) — DROPPED for MVP.
       Member table shows Name + Role only, per Nimesh's explicit call.
     - "X photos attached" — event-LEVEL via a new EventPhoto table, not
       per-attendance-row. (Confirmed decision, schema below.)
     - Actor is DivisionalSecretary ONLY (same as Event Management —
       Coordinator does not manage attendance, confirmed earlier).
     - Scope: "own division and below," matching the Figma banner exactly
       — same requireSecretary()-style gate + division_id scoping used
       throughout ManageEvents.

   UNKNOWN / TODO (not invented, flagged for Nimesh):
     - "Export PDF" — no PDF generation exists anywhere in this project
       yet. Marking as UNKNOWN/TODO — button renders, click handler is a
       stub that reports "not yet implemented" rather than fabricating
       PDF generation.
     - CSV bulk-upload format beyond "must include Member ID column" —
       exact expected columns (name? status? check-in time?) not fully
       specified by Figma. Reference file below assumes columns:
       member_id, status, check_in_time, remark — FLAG this to Nimesh
       before Antigravity builds the parser, since a wrong assumption
       here means silently mis-importing real attendance data.
   ===================================================================== */

session_start();

// ---------------------------------------------------------------------
// PART A — PROCESS DATA
// ---------------------------------------------------------------------

function requireSecretary() {
    if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'DivisionalSecretary') {
        header('Location: /auth/signin');
        exit();
    }
}
requireSecretary();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$divisionId    = (int)($_SESSION['division_id'] ?? 0);
$secretaryName = $_SESSION['user_name'] ?? 'N. Fernando';

/*
 * Real SQL this represents (AttendanceModel::findSessionsByDivision):
 *
 *   SELECT e.event_id, e.title, e.start_datetime, e.end_datetime, e.location,
 *          c.club_name, u.first_name, u.last_name,
 *          COUNT(a.attendance_id) AS logged_count,
 *          SUM(a.status='Present') AS present_count,
 *          (SELECT COUNT(*) FROM EventPhoto ep WHERE ep.event_id = e.event_id) AS photo_count
 *   FROM Event e
 *   LEFT JOIN Club c ON e.organizer_club_id = c.club_id
 *   LEFT JOIN Attendance a ON a.event_id = e.event_id
 *   LEFT JOIN User u ON a.recorded_by = u.user_id
 *   WHERE (e.organizer_division_id = :divisionId OR c.division_id = :divisionId)
 *     AND e.status = 'Approved'
 *   GROUP BY e.event_id
 *   ORDER BY e.start_datetime DESC
 */
$sessions = [
    (object)[
        'event_id'       => 201,
        'title'          => 'Weekly Practice',
        'start_datetime' => '2026-07-11 08:00:00',
        'end_datetime'   => '2026-07-11 17:00:00',
        'location'       => 'Kaduwela YC Grounds',
        'recorded_by'    => 'K. Perera',
        'present_count'  => 32,
        'photo_count'    => 2,
    ],
    (object)[
        'event_id'       => 205,
        'title'          => 'Divisional Talent Night Rehearsal',
        'start_datetime' => '2026-08-28 18:00:00',
        'end_datetime'   => '2026-08-28 21:00:00',
        'location'       => 'Colombo Divisional Grounds',
        'recorded_by'    => 'R. Perera',
        'present_count'  => 23,
        'photo_count'    => 0,
    ],
];

$stats = [
    'total_events'      => count($sessions), // real: COUNT DISTINCT events this calendar year
    'total_attendance'  => 1842,             // real: SUM(present_count) across the year
];

/*
 * Real SQL (AttendanceModel::findApprovedEventsForDropdown) — populates
 * the "Select Event" dropdown in Add Attendance. Only Approved events in
 * scope, so attendance can't be logged against something still pending.
 */
$approvedEventsForDropdown = [
    (object)['event_id' => 201, 'title' => 'Weekly Practice', 'start_datetime' => '2026-07-11 08:00:00', 'location' => 'Kaduwela YC Grounds'],
    (object)['event_id' => 205, 'title' => 'Divisional Talent Night Rehearsal', 'start_datetime' => '2026-08-28 18:00:00', 'location' => 'Colombo Divisional Grounds'],
];

// Matches manageevents' $activeFilters pattern exactly — counts non-empty
// filter inputs from $_GET to drive the badge + open-by-default state.
$filters = $_GET ?? [];
$activeFilters = count(array_filter([
    $filters['club_id'] ?? '', $filters['event_id'] ?? '',
    $filters['date_from'] ?? '', $filters['status'] ?? '', $filters['search'] ?? '',
]));

/*
 * Real SQL (AttendanceModel::findByEvent($eventId)) — powers the detail
 * view's member table. Members come from Users targeted by the event
 * (via EventTarget → Club → User with club_id), joined to their
 * Attendance row if one exists yet (LEFT JOIN, so un-marked members
 * still show up as "not yet recorded", never silently omitted).
 */
$sessionDetail = [
    'event'   => $sessions[0],
    'present' => 32,
    'absent'  => 8,
    'rate'    => round(32 / 40 * 100), // 80%
    'members' => [
        (object)['user_id' => 501, 'name' => 'Arun Perera',      'role' => 'ClubMember', 'check_in_time' => '2026-07-11 07:45:00', 'status' => 'Present'],
        (object)['user_id' => 502, 'name' => 'Samantha Silva',   'role' => 'ClubMember', 'check_in_time' => '2026-07-11 07:50:00', 'status' => 'Present'],
        (object)['user_id' => 503, 'name' => 'Kasun Jayawardena','role' => 'ClubMember', 'check_in_time' => null,                  'status' => 'Absent'],
    ],
];
?>
<!-- ---------------------------------------------------------------------
     PART B — SHARED DASHBOARD SHELL
     Same pattern as clubregistration/application-list.view.php and the
     now-migrated eventapproval/event-list.view.php — no hardcoded
     sidebar/topbar, uses the shared shell + centrally-configured nav.
     --------------------------------------------------------------------- -->
<?php
$title           = 'Manage Attendance — YouthNexus';
$pageTitle       = 'Manage Attendance';
$pageDescription = 'Track and upload attendance records across division events';
$currentRoute    = 'attendance';
$userRole        = $_SESSION['user_role'] ?? 'DivisionalSecretary';
$userName        = $secretaryName;
$unreadNotificationCount = 0;

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

            <!-- =============================================================
                 PART C — STAT CARDS
                 Exact cr-stats sizing (32x32 icons, flex row, max-width 620px)
                 — the same fix applied to Event Approval's cards, applied
                 here from the start rather than needing a second pass.
                 ============================================================= -->
            <div class="am-stats">
                <div class="am-stat-card">
                    <div class="am-stat-icon events">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div class="am-stat-value"><?= (int)$stats['total_events'] ?></div>
                    <div class="am-stat-label">Total Events (This Term)</div>
                </div>
                <div class="am-stat-card">
                    <div class="am-stat-icon logged">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#157a45" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="am-stat-value"><?= (int)$stats['total_attendance'] ?></div>
                    <div class="am-stat-label">Total Attendance Logged</div>
                </div>
            </div>

            <!-- =============================================================
                 PART D — TOOLBAR + FILTER PANEL
                 CORRECTED to match the ACTUAL project convention: checked
                 every branch — Monitor Club Health and Club Registration
                 Approval both independently converged on CLIENT-SIDE
                 filtering (filterCards() over cards already rendered on
                 the page), not the server-side GET-form pattern Manage
                 Events uses. Two independent implementations agreeing is
                 stronger evidence than one — this is the real standard.
                 ============================================================= -->
            <div class="am-toolbar">
                <div class="am-search-group">
                    <span class="am-search-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input type="text" id="amSearchInput" placeholder="Search attendance records...">
                </div>
                <button type="button" class="am-filter-btn" id="amFilterBtn" aria-expanded="false">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                    Advanced Filter
                </button>
                <button type="button" class="am-btn am-btn-primary" id="amAddAttendanceBtn">+ Add Attendance</button>
            </div>

            <div class="am-filter-panel" id="amFilterPanel">
                <div class="am-scope-banner">
                    Level access is restricted by your role — Divisional Secretary can view Divisional level and below only.
                </div>
                <div class="am-filter-field">
                    <label>Level</label>
                    <select disabled><option>Divisional</option></select>
                </div>
                <div class="am-filter-field">
                    <label>Division</label>
                    <select disabled><option><?= htmlspecialchars($_SESSION['division_name'] ?? 'Colombo Division') ?></option></select>
                </div>
                <div class="am-filter-field">
                    <label>Club</label>
                    <select id="amFilterClub">
                        <option value="">All Clubs</option>
                        <!-- populated from Club WHERE division_id = session division_id -->
                    </select>
                </div>
                <div class="am-filter-field">
                    <label>Event</label>
                    <select id="amFilterEvent">
                        <option value="">All Events</option>
                    </select>
                </div>
                <div class="am-filter-field">
                    <label>Status</label>
                    <select id="amFilterStatus">
                        <option value="">All</option>
                        <option value="present">Has Present Members</option>
                        <option value="absent">Has Absent Members</option>
                    </select>
                </div>
                <div class="am-filter-actions">
                    <button type="button" class="am-btn" id="amClearFilterBtn">Clear Filter</button>
                    <button type="button" class="am-btn am-btn-primary" id="amAddFilterBtn">Add Filter</button>
                </div>
            </div>

            <!-- =============================================================
                 PART E — ATTENDANCE RECORD CARDS
                 ============================================================= -->
            <div class="am-grid" id="amGrid">
                <?php if (empty($sessions)): ?>
                    <div class="am-empty" style="grid-column: 1 / -1;">
                        <p>No attendance records found for your division yet.</p>
                    </div>
                <?php else: foreach ($sessions as $s): ?>
                    <div class="am-card" data-event-id="<?= (int)$s->event_id ?>" data-club-id="" data-has-present="<?= $s->present_count > 0 ? '1' : '0' ?>">
                        <div class="am-card-top">
                            <span class="am-badge present"><?= (int)$s->present_count ?> PRESENT</span>
                        </div>
                        <div class="am-card-meta">
                            <?= date('M j, Y', strtotime($s->start_datetime)) ?> —
                            <?= date('g:i A', strtotime($s->start_datetime)) ?> to <?= date('g:i A', strtotime($s->end_datetime)) ?>
                        </div>
                        <h3 class="am-card-title"><?= htmlspecialchars($s->title) ?></h3>
                        <div class="am-card-location"><?= htmlspecialchars($s->location) ?></div>
                        <div class="am-card-footer">
                            <span class="am-card-recorder">
                                <?= htmlspecialchars($s->recorded_by) ?>
                                <?php if ($s->photo_count > 0): ?>
                                    &middot; <?= (int)$s->photo_count ?> photo<?= $s->photo_count > 1 ? 's' : '' ?> attached
                                <?php endif; ?>
                            </span>
                            <button type="button" class="am-btn am-btn-view" data-event-id="<?= (int)$s->event_id ?>">View</button>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>

<!-- =====================================================================
     PART F — ADD ATTENDANCE MODAL
     (Placed logically before dashboard-end.view.php's closing tags in
     the real MVC view — shown after here only for reference readability.)
     ===================================================================== -->
<div class="am-modal-backdrop" id="amAddModal">
    <div class="am-modal">
        <div class="am-modal-header">
            <h3>Add Attendance</h3>
            <button type="button" class="am-modal-close" id="amAddModalClose">&times;</button>
        </div>
        <div class="am-modal-body">
            <div class="am-field">
                <label>Select Event <span class="req">*</span></label>
                <select id="amEventSelect">
                    <option value="">Choose an approved event…</option>
                    <?php foreach ($approvedEventsForDropdown as $ev): ?>
                        <option value="<?= (int)$ev->event_id ?>"
                            data-start="<?= htmlspecialchars($ev->start_datetime) ?>"
                            data-location="<?= htmlspecialchars($ev->location) ?>">
                            <?= htmlspecialchars($ev->title) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="am-event-readout" id="amEventReadout">
                <!-- filled by JS once an event is selected: Start Time / Date / Location, read-only from DB -->
            </div>

            <div class="am-toggle-group" role="group" aria-label="Entry method">
                <button type="button" class="am-toggle-btn is-active" data-mode="bulk">Bulk Upload</button>
                <button type="button" class="am-toggle-btn" data-mode="single">Single Entry</button>
            </div>

            <!-- Bulk mode -->
            <div class="am-mode-panel" id="amBulkPanel">
                <label>Upload CSV File (must include Member ID column)</label>
                <div class="am-dropzone" id="amDropzone">
                    Click to browse, or drag CSV file here
                    <input type="file" id="amCsvFile" accept=".csv" hidden>
                </div>
                <p class="am-csv-note">
                    Expected columns: <code>member_id, status, check_in_time, remark</code> —
                    UNCONFIRMED against a real Figma spec, flagged for Nimesh to verify
                    before this parser is built for real.
                </p>
            </div>

            <!-- Single mode (hidden by default) -->
            <div class="am-mode-panel" id="amSinglePanel" hidden>
                <label>Member</label>
                <select id="amSingleMember"><option>Select member…</option></select>
                <label>Status</label>
                <div class="am-status-toggle">
                    <button type="button" class="am-status-btn present is-active" data-status="Present">Present</button>
                    <button type="button" class="am-status-btn absent" data-status="Absent">Absent</button>
                </div>
            </div>

            <div class="am-field-row">
                <div class="am-field">
                    <label>Start Time (from event)</label>
                    <input type="text" id="amStartTime" readonly>
                </div>
                <div class="am-field">
                    <label>End Time</label>
                    <input type="time" id="amEndTime">
                </div>
            </div>
            <div class="am-field">
                <label>Remark (optional)</label>
                <textarea id="amRemark" placeholder="e.g. Arrived late due to transport delay"></textarea>
            </div>
        </div>
        <div class="am-modal-footer">
            <button type="button" class="am-btn" id="amAddCancelBtn">Close</button>
            <button type="button" class="am-btn am-btn-primary" id="amSaveDetailsBtn">Save Details</button>
        </div>
    </div>
</div>

<!-- =====================================================================
     PART G — CSS
     Reuses cr-/ea- sizing conventions established in prior features.
     ===================================================================== -->
<style>
.am-stats { display: flex; gap: 16px; max-width: 620px; margin-bottom: 24px; }
.am-stat-card { flex: 1; background: #fff; border: 1px solid var(--db-border, #e7e9f0); border-radius: 12px; padding: 20px 24px; box-shadow: 0px 4px 12px rgba(18,20,26,0.03); display: flex; flex-direction: column; gap: 8px; }
.am-stat-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.am-stat-icon.events { background: #dbeafe; }
.am-stat-icon.logged { background: #d1fae5; }
.am-stat-value { font-size: 28px; font-weight: 800; color: var(--db-text-dark, #12141a); }
.am-stat-label { font-size: 13px; color: var(--db-text-grey, #6b7280); }

.am-toolbar { display: flex; gap: 12px; align-items: center; margin-bottom: 12px; }
.am-search-group { flex: 1; display: flex; align-items: center; gap: 8px; padding: 9px 13px; border: 1px solid var(--db-border, #e7e9f0); border-radius: 8px; }
.am-search-group input { border: none; outline: none; flex: 1; font-size: 13px; }
.am-btn { border: 1px solid var(--db-border, #e7e9f0); background: #fff; border-radius: 8px; padding: 9px 16px; font-size: 13px; font-weight: 600; cursor: pointer; }
.am-btn-primary { background: var(--db-sidebar-bg, #1e40af); color: #fff; border-color: transparent; }

/* Filter button — matches .me-filter-btn/.me-filter-count exactly */
.am-filter-btn {
    display: flex; align-items: center; gap: 6px;
    background: #fff; border: 1px solid var(--db-border, #e7e9f0); border-radius: 8px;
    padding: 10px 16px; font-size: 12.5px; font-weight: 600; cursor: pointer;
    color: var(--db-text-dark, #12141a); height: 40px;
    box-shadow: 0px 1px 2px rgba(18, 20, 26, 0.04);
    transition: background-color 0.15s ease, border-color 0.15s ease;
    white-space: nowrap;
}
.am-filter-btn:hover, .am-filter-btn[aria-expanded="true"] { background: #f8fafc; border-color: #cbd5e1; }
.am-filter-count {
    background: var(--db-sidebar-bg, #1e40af); color: #fff; font-size: 10px; font-weight: 700;
    border-radius: 9999px; padding: 1px 5px; line-height: 1.5; min-width: 16px; text-align: center;
}

/* Filter button + panel — matches mch-filter-btn/mch-filter-panel exactly,
   the pattern already proven across two shipped features */
.am-filter-btn {
    display: flex; align-items: center; gap: 6px;
    background: #fff; border: 1px solid var(--db-border, #e7e9f0); border-radius: 8px;
    padding: 10px 16px; height: 40px; font-size: 12.5px; font-weight: 600;
    color: var(--db-text-dark, #12141a); cursor: pointer;
    transition: all 0.15s ease; box-shadow: 0px 1px 2px rgba(18, 20, 26, 0.04);
}
.am-filter-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
.am-filter-btn[aria-expanded="true"] { background: #eff6ff; border-color: #1e40af; color: #1e40af; }

.am-filter-panel {
    display: none; align-items: flex-end; gap: 16px; flex-wrap: wrap;
    background: #fff; border: 1px solid var(--db-border, #e7e9f0); border-radius: 12px;
    padding: 16px 18px; margin-bottom: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.05);
}
.am-filter-panel.open { display: flex; }
.am-scope-banner { flex-basis: 100%; background: #fef3c7; color: #92400e; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; margin-bottom: 4px; }
.am-filter-field { display: flex; flex-direction: column; gap: 5px; min-width: 200px; }
.am-filter-field label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; color: var(--db-text-grey, #6b7280); }
.am-filter-field select { border: 1px solid var(--db-border, #e7e9f0); border-radius: 8px; padding: 9px 10px; font-size: 13px; background: #fff; outline: none; }
.am-filter-field select:focus { border-color: #1e40af; }
.am-filter-actions { display: flex; gap: 8px; margin-left: auto; }

.am-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
/* Card: kept as a left-aligned info-list (right shape for event data,
   NOT mch-card's centered-avatar layout — that's specific to a health
   score, not applicable here). Borrowed from mch-card instead: the
   colored top accent bar and hover-lift, which are layout-independent
   and worth being consistent everywhere. */
.am-card {
    background: #fff; border: 1px solid var(--db-border, #e7e9f0); border-radius: 12px;
    padding: 16px; display: flex; flex-direction: column; gap: 6px;
    position: relative; box-shadow: 0 2px 4px rgba(0,0,0,.02);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.am-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; border-radius: 12px 12px 0 0; background: #10b981; }
.am-card:hover { transform: translateY(-2px); box-shadow: 0 6px 12px -2px rgba(0,0,0,.08); }
.am-badge.present { background: #d1fae5; color: #047857; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; }
.am-card-meta { font-size: 11.5px; color: #9ca3af; }
.am-card-title { font-size: 15px; font-weight: 700; margin: 0; }
.am-card-location { font-size: 12.5px; color: #6b7280; }
.am-card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; padding-top: 8px; border-top: 1px solid #f1f2f4; }
.am-card-recorder { font-size: 11.5px; color: #9ca3af; }

.am-modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); align-items: center; justify-content: center; z-index: 1000; }
.am-modal-backdrop.open { display: flex; }
.am-modal { background: #fff; border-radius: 14px; width: 560px; max-width: 92vw; max-height: 88vh; display: flex; flex-direction: column; overflow: hidden; }
.am-modal-header { display: flex; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1f2f4; }
.am-modal-body { padding: 16px 20px; overflow-y: auto; }
.am-modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding: 14px 20px; border-top: 1px solid #f1f2f4; }
.am-field { margin-bottom: 14px; }
.am-field label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; }
.am-field select, .am-field input, .am-field textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 10px; font-size: 13.5px; }
.am-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.am-toggle-group { display: flex; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; margin-bottom: 14px; }
.am-toggle-btn { flex: 1; padding: 9px; background: #fff; border: none; cursor: pointer; font-size: 13px; font-weight: 600; }
.am-toggle-btn.is-active { background: var(--db-sidebar-bg, #1e40af); color: #fff; }
.am-dropzone { border: 2px dashed #d1d5db; border-radius: 10px; padding: 24px; text-align: center; font-size: 13px; color: #6b7280; cursor: pointer; }
.am-csv-note { font-size: 11.5px; color: #b45309; background: #fffbeb; padding: 8px 10px; border-radius: 6px; margin-top: 8px; }
.am-status-toggle { display: flex; gap: 8px; }
.am-status-btn { flex: 1; padding: 8px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; cursor: pointer; font-size: 13px; }
.am-status-btn.present.is-active { background: #d1fae5; border-color: #10b981; color: #047857; }
.am-status-btn.absent.is-active { background: #fee2e2; border-color: #ef4444; color: #b91c1c; }
</style>

<!-- =====================================================================
     PART H — JAVASCRIPT
     ===================================================================== -->
<script>
(function () {
    const filterBtn   = document.getElementById('amFilterBtn');
    const filterPanel = document.getElementById('amFilterPanel');
    const searchInput = document.getElementById('amSearchInput');
    const filterClub  = document.getElementById('amFilterClub');
    const filterEvent = document.getElementById('amFilterEvent');
    const filterStatus= document.getElementById('amFilterStatus');
    const addFilterBtn   = document.getElementById('amAddFilterBtn');
    const clearFilterBtn = document.getElementById('amClearFilterBtn');
    const grid = document.getElementById('amGrid');

    filterBtn.addEventListener('click', function () {
        const isOpen = filterPanel.classList.toggle('open');
        filterBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    /*
     * Client-side filtering over cards already rendered — matches
     * monitor-club-health.js / clubregistration.js's filterCards()
     * pattern exactly, not a server round-trip. Fine at this dataset
     * size (a division's events for the current term); revisit only if
     * a division's event volume grows enough to make full-page loads a
     * real cost.
     */
    function filterCards() {
        const query  = (searchInput.value || '').toLowerCase();
        const club   = filterClub.value;
        const event  = filterEvent.value;
        const status = filterStatus.value;
        let anyVisible = false;

        grid.querySelectorAll('.am-card').forEach(card => {
            const title = card.querySelector('.am-card-title').textContent.toLowerCase();
            const matchesQuery  = !query || title.includes(query);
            const matchesClub   = !club  || card.dataset.clubId === club;
            const matchesEvent  = !event || card.dataset.eventId === event;
            const matchesStatus = !status || card.dataset.hasPresent === (status === 'present' ? '1' : '0');
            const visible = matchesQuery && matchesClub && matchesEvent && matchesStatus;
            card.style.display = visible ? '' : 'none';
            if (visible) anyVisible = true;
        });

        let noMatchEl = grid.querySelector('#amNoFilterMatch');
        if (!anyVisible) {
            if (!noMatchEl) {
                noMatchEl = document.createElement('div');
                noMatchEl.id = 'amNoFilterMatch';
                noMatchEl.className = 'am-empty';
                noMatchEl.style.gridColumn = '1 / -1';
                noMatchEl.innerHTML = '<p>No attendance records match your search/filters.</p>';
                grid.appendChild(noMatchEl);
            }
        } else if (noMatchEl) {
            noMatchEl.remove();
        }
    }

    searchInput.addEventListener('input', filterCards);
    addFilterBtn.addEventListener('click', filterCards);
    clearFilterBtn.addEventListener('click', function () {
        searchInput.value = '';
        filterClub.value = '';
        filterEvent.value = '';
        filterStatus.value = '';
        filterCards();
    });

    const addModal  = document.getElementById('amAddModal');
    document.getElementById('amAddAttendanceBtn').addEventListener('click', () => addModal.classList.add('open'));
    document.getElementById('amAddModalClose').addEventListener('click', () => addModal.classList.remove('open'));
    document.getElementById('amAddCancelBtn').addEventListener('click', () => addModal.classList.remove('open'));

    // Bulk/Single toggle
    const toggleBtns  = document.querySelectorAll('.am-toggle-btn');
    const bulkPanel   = document.getElementById('amBulkPanel');
    const singlePanel = document.getElementById('amSinglePanel');
    toggleBtns.forEach(btn => btn.addEventListener('click', () => {
        toggleBtns.forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        const isBulk = btn.dataset.mode === 'bulk';
        bulkPanel.hidden   = !isBulk;
        singlePanel.hidden = isBulk;
    }));

    // Single-entry status toggle
    document.querySelectorAll('.am-status-btn').forEach(btn => btn.addEventListener('click', () => {
        document.querySelectorAll('.am-status-btn').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
    }));

    // Event select → populate read-only Start Time / Location
    const eventSelect = document.getElementById('amEventSelect');
    const readout      = document.getElementById('amEventReadout');
    const startTimeIn     = document.getElementById('amStartTime');
    eventSelect.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!opt.value) { readout.innerHTML = ''; startTimeIn.value = ''; return; }
        const start = opt.dataset.start;
        const loc   = opt.dataset.location;
        startTimeIn.value = start ? new Date(start).toLocaleTimeString() : '';
        readout.innerHTML = '<p><strong>Date:</strong> ' + (start ? new Date(start).toLocaleDateString() : '—') +
                             ' &nbsp; <strong>Location:</strong> ' + (loc || '—') + '</p>';
    });

    /*
     * Real MVC build: Save Details POSTs to /attendance/save with CSRF,
     * either the CSV file (bulk) or a single member_id+status+remark
     * (single), plus the selected event_id and end_time. Server-side:
     * re-verify the event is Approved and in-scope for this Secretary's
     * division before accepting any attendance write — never trust the
     * dropdown alone.
     */
})();
</script>
