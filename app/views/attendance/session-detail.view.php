<?php
/**
 * Attendance — Session Detail (Member Roster for one event)
 * Supports NYSC Administrator (National / Zonal / Divisional filter panel)
 * and Divisional / Zonal Secretary.
 */
$title                   = $title ?? 'Event Attendance — YouthNexus';
$pageTitle               = htmlspecialchars($event->title ?? 'Event Attendance');
$pageDescription         = 'View and verify member attendance for this event';
$currentRoute            = 'attendance';
$unreadNotificationCount = 0;
$isNYSCAdmin             = !empty($isNYSCAdmin);
$isZonalSecretary        = !empty($isZonalSecretary);
$canEditRoster           = !$isNYSCAdmin; // Administrators review attendance; secretaries edit/record
$userRole                = $userRole ?? ($_SESSION['user_role'] ?? 'UnassignedUser');
$isDivisional            = str_starts_with((string)$userRole, 'Divisional');
$pageStyles              = [
    ROOT . '/assets/css/attendance.css?v=20261024',
    ROOT . '/assets/css/divisional-summary-standard.css?v=20260924',
];
if ($isDivisional) {
    $pageStyles[] = ROOT . '/assets/css/divisional-workflows.css';
}

$eventLevel = $event->organizer_level ?? 'National';
$eventZoneId = $event->organizer_zonal_id ?? $event->division_zonal_id ?? null;
$eventDivisionId = $event->organizer_division_id ?? $event->club_division_id ?? null;

$orgHierarchy = [];
if (!empty($event->organizer_club_name))     $orgHierarchy[] = $event->organizer_club_name;
if (!empty($event->organizer_division_name)) $orgHierarchy[] = $event->organizer_division_name;
if (!empty($event->organizer_zonal_name))    $orgHierarchy[] = $event->organizer_zonal_name;
$organiser = !empty($orgHierarchy) ? implode(' &bull; ', $orgHierarchy) : 'National Administration';

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<div class="am-page-container">

    <!-- ============================================================
         Detail Header
         ============================================================ -->
    <div class="am-detail-header">
        <a href="<?= ROOT ?>/attendance" class="am-back-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Back to Events
        </a>
        <div class="am-detail-actions">
            <a href="<?= ROOT ?>/attendance/download/<?= (int)$event->event_id ?>" class="am-btn db-secondary-action" id="amDownloadCsvBtn">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Download CSV
            </a>
            <button type="button" class="am-btn db-secondary-action" id="amExportPdfBtn" onclick="window.print()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Print / Export
            </button>
        </div>
    </div>

    <!-- Event Brief Details as a Dropdown List -->
    <details class="am-event-details-dropdown" open>
        <summary class="am-event-details-summary">
            <div class="am-event-summary-left">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span class="am-event-summary-title">Event Information &amp; Overview</span>
                <span class="am-badge <?= strtolower($eventLevel) ?>"><?= htmlspecialchars($eventLevel) ?></span>
                <?php if (!empty($event->event_type)): ?>
                    <span class="am-badge type"><?= htmlspecialchars($event->event_type) ?></span>
                <?php endif; ?>
            </div>
            <span class="am-details-toggle-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
        </summary>
        <div class="am-event-details-body">
            <ul class="am-event-details-list">
                <li>
                    <span class="am-detail-label">Organiser:</span>
                    <span class="am-detail-val"><?= $organiser ?></span>
                </li>
                <li>
                    <span class="am-detail-label">Date &amp; Time:</span>
                    <span class="am-detail-val"><?= date('M j, Y \a\t g:i A', strtotime($event->start_datetime)) ?><?= !empty($event->end_datetime) ? ' &ndash; ' . date('M j, Y \a\t g:i A', strtotime($event->end_datetime)) : '' ?></span>
                </li>
                <li>
                    <span class="am-detail-label">Location:</span>
                    <span class="am-detail-val"><?= !empty($event->location) ? htmlspecialchars($event->location) : 'Location not set' ?></span>
                </li>
                <li>
                    <span class="am-detail-label">Scope &amp; Target:</span>
                    <span class="am-detail-val"><?= htmlspecialchars($event->target_scope ?? 'General') ?><?= !empty($event->target_club_name) ? ' &bull; ' . htmlspecialchars($event->target_club_name) : '' ?></span>
                </li>
                <?php if (!empty($event->description)): ?>
                <li>
                    <span class="am-detail-label">Description:</span>
                    <span class="am-detail-val"><?= htmlspecialchars($event->description) ?></span>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </details>

    <!-- ============================================================
         Stat Cards (Present / Absent / Attendance Rate in the Same Row)
         ============================================================ -->
    <div class="am-stats am-detail-stats dw-summary-grid dw-summary-grid--three" aria-label="Event attendance summary">
        <article class="dw-summary-card ft-stat-card dw-summary-card--green">
            <div class="ft-stat-head">
                <span class="ft-stat-title dw-summary-card__label">Present</span>
                <span class="ft-badge ft-badge-green">Verified</span>
            </div>
            <div class="ft-stat-big dw-summary-card__value" id="amPresentCount"><?= $present ?></div>
            <div class="ft-stat-sub dw-summary-card__note">Members checked in for event</div>
        </article>

        <article class="dw-summary-card ft-stat-card dw-summary-card--red">
            <div class="ft-stat-head">
                <span class="ft-stat-title dw-summary-card__label">Absent</span>
                <span class="ft-badge ft-badge-red">Not Attended</span>
            </div>
            <div class="ft-stat-big dw-summary-card__value" id="amAbsentCount"><?= $absent ?></div>
            <div class="ft-stat-sub dw-summary-card__note">Members absent from event</div>
        </article>

        <article class="dw-summary-card ft-stat-card dw-summary-card--blue">
            <div class="ft-stat-head">
                <span class="ft-stat-title dw-summary-card__label">Attendance Rate</span>
                <span class="ft-badge ft-badge-blue">Turnout</span>
            </div>
            <div class="ft-stat-big dw-summary-card__value" id="amAttendanceRate"><?= $rate ?>%</div>
            <div class="ft-stat-sub dw-summary-card__note">Verified attendee percentage</div>
        </article>
    </div>

    <!-- ============================================================
         Search Bar & Filter Button in the Same Row
         ============================================================ -->
    <div class="dw-toolbar am-toolbar" aria-label="Attendance search and filter tools" style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
        <div class="dw-toolbar__search dw-search dw-search--plain am-search-group" style="flex: 1; margin: 0;">
            <label class="visually-hidden" for="amTableSearch">Search members</label>
            <input type="text" id="amTableSearch" placeholder="Search members by name, email or club…" autocomplete="off">
        </div>
        <button class="dw-button dw-button--secondary" type="button" id="amFilterToggleBtn" data-filter-toggle aria-controls="amDetailFilterCard" aria-expanded="false">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filters
        </button>
    </div>

    <!-- ============================================================
         Filter Panel (Youth Club & Status Filter Mechanism)
         ============================================================ -->
    <?php
    $showZoneFilter     = ($isNYSCAdmin && $eventLevel === 'National');
    $showDivisionFilter = ($isNYSCAdmin && in_array($eventLevel, ['National', 'Zonal'])) 
                       || ($isZonalSecretary && in_array($eventLevel, ['National', 'Zonal']));
    $showClubFilter     = in_array($eventLevel, ['National', 'Zonal', 'Division']);
    ?>
    <div class="am-detail-filter-card" id="amDetailFilterCard" hidden>
        <div class="am-detail-filter-header">
            <div class="am-detail-filter-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filter Attendance by Scope &amp; Status
            </div>
            <div class="am-detail-filter-actions">
                <span class="am-scope-badge" id="amScopeBadge">
                    All records for this event
                </span>
                <span style="font-size:12px;color:#64748b;font-weight:600;">
                    <strong id="amRosterCount"><?= count($roster) ?></strong> members in scope
                </span>
                <button type="button" class="am-filter-reset-btn" id="amDetailResetFilterBtn">
                    Reset Filter
                </button>
            </div>
        </div>

        <div class="am-detail-filter-grid">
            <?php if ($showClubFilter): ?>
            <!-- 1. Youth Club Filter: Select a club or all clubs -->
            <div class="am-detail-filter-field" id="clubFilterField">
                <label for="detailFilterClub">YOUTH CLUB</label>
                <select id="detailFilterClub" <?= ($eventLevel !== 'Division' && empty($detailClubs)) ? 'disabled' : '' ?>>
                    <option value="">All Clubs<?= !empty($detailClubs) ? ' (' . count($detailClubs) . ')' : '' ?></option>
                    <?php foreach (($detailClubs ?? []) as $c): ?>
                        <option value="<?= (int)$c->club_id ?>">
                            <?= htmlspecialchars($c->club_name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- 2. Attendance Status Filter -->
            <div class="am-detail-filter-field" id="statusFilterField">
                <label for="amTableStatusFilter">ATTENDANCE STATUS</label>
                <select class="am-table-status-filter" id="amTableStatusFilter">
                    <option value="">All Statuses</option>
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                    <option value="unmarked">Not Recorded</option>
                </select>
            </div>

            <?php if ($showZoneFilter): ?>
            <!-- Zone Filter (for National events) -->
            <div class="am-detail-filter-field" id="zoneFilterField">
                <label for="detailFilterZone">ZONAL OFFICE</label>
                <select id="detailFilterZone">
                    <option value="">All Zones (<?= count($detailZones ?? []) ?>)</option>
                    <?php foreach (($detailZones ?? []) as $z): ?>
                        <option value="<?= (int)$z->zonal_id ?>">
                            <?= htmlspecialchars($z->zonal_name) ?><?= !empty($z->province) ? ' (' . htmlspecialchars($z->province) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($showDivisionFilter): ?>
            <!-- Division Filter (for National and Zonal events) -->
            <div class="am-detail-filter-field" id="divisionFilterField">
                <label for="detailFilterDivision">DIVISIONAL SECRETARIAT</label>
                <select id="detailFilterDivision" <?= ($eventLevel === 'National' && empty($detailDivisions)) ? 'disabled' : '' ?>>
                    <option value="">All Divisions<?= !empty($detailDivisions) ? ' (' . count($detailDivisions) . ')' : '' ?></option>
                    <?php foreach (($detailDivisions ?? []) as $d): ?>
                        <option value="<?= (int)$d->division_id ?>">
                            <?= htmlspecialchars($d->division_name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================
         Member Roster Table
         ============================================================ -->
    <div class="am-table-wrapper">

        <?php if (empty($roster)): ?>
            <div class="am-empty-state" style="padding:40px 0;">
                <p>No member attendance records found for this event.</p>
            </div>
        <?php else: ?>
        <table class="am-table" id="amRosterTable">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Club / Division</th>
                    <?php if ($isNYSCAdmin && $eventLevel === 'National'): ?>
                    <th>Zone</th>
                    <?php endif; ?>
                    <th>Status</th>
                    <th>Check-in Time</th>
                    <th>Remark</th>
                    <th>Recorded By</th>
                    <?php if ($canEditRoster): ?>
                    <th style="text-align:right;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roster as $row):
                    $mName = $row->member_name ?? trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                    $clubLoc = htmlspecialchars($row->club_name ?? '—');
                    if (!empty($row->division_name)) {
                        $clubLoc .= ' <span class="am-muted">(' . htmlspecialchars($row->division_name) . ')</span>';
                    }
                    $attStatus = $row->att_status ?? 'unmarked';
                    $recordedBy = !empty($row->recorded_by_name)
                        ? htmlspecialchars($row->recorded_by_name) . (!empty($row->recorded_by_role) ? ' <small class="am-recorder-role">(' . htmlspecialchars($row->recorded_by_role) . ')</small>' : '')
                        : '<span class="am-muted">—</span>';
                    $zoneIdVal     = (int)($row->zonal_id ?? 0);
                    $divisionIdVal = (int)($row->division_id ?? 0);
                    $clubIdVal     = (int)($row->club_id ?? 0);
                ?>
                <tr class="am-roster-row"
                    data-member-id="<?= (int)$row->user_id ?>"
                    data-zone-id="<?= $zoneIdVal ?>"
                    data-division-id="<?= $divisionIdVal ?>"
                    data-club-id="<?= $clubIdVal ?>"
                    data-name="<?= htmlspecialchars(strtolower($mName)) ?>"
                    data-email="<?= htmlspecialchars(strtolower($row->email ?? '')) ?>"
                    data-club="<?= htmlspecialchars(strtolower($row->club_name ?? '')) ?>"
                    data-division="<?= htmlspecialchars(strtolower($row->division_name ?? '')) ?>"
                    data-status="<?= htmlspecialchars($attStatus) ?>">
                    <td>
                        <strong><?= htmlspecialchars($mName) ?></strong><br>
                        <small style="color:#64748b;"><?= htmlspecialchars($row->email ?? '') ?></small>
                    </td>
                    <td><?= $clubLoc ?></td>
                    <?php if ($isNYSCAdmin && $eventLevel === 'National'): ?>
                    <td><?= htmlspecialchars($row->zonal_name ?? '—') ?></td>
                    <?php endif; ?>
                    <td>
                        <?php if ($attStatus === 'Present'): ?>
                            <span class="am-status-badge present">&#9679; Present</span>
                        <?php elseif ($attStatus === 'Absent'): ?>
                            <span class="am-status-badge absent">&#10007; Absent</span>
                        <?php else: ?>
                            <span class="am-status-badge unmarked">Not recorded</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= !empty($row->check_in_time) ? date('H:i', strtotime($row->check_in_time)) : '<span class="am-muted">—</span>' ?>
                    </td>
                    <td>
                        <?= !empty($row->remark) ? htmlspecialchars($row->remark) : '<span class="am-muted">—</span>' ?>
                    </td>
                    <td>
                        <?= $recordedBy ?>
                        <?php if (!empty($row->recorded_at)): ?>
                            <div style="font-size:11px;color:#94a3b8;margin-top:2px;"><?= date('M j, H:i', strtotime($row->recorded_at)) ?></div>
                        <?php endif; ?>
                    </td>
                    <?php if ($canEditRoster): ?>
                    <td style="text-align:right;">
                        <button type="button"
                                class="am-btn am-btn-quick-update"
                                data-member-id="<?= (int)$row->user_id ?>"
                                data-member-name="<?= htmlspecialchars($mName) ?>"
                                data-current-status="<?= htmlspecialchars($attStatus) ?>"
                                data-current-checkin="<?= !empty($row->check_in_time) ? date('Y-m-d\TH:i', strtotime($row->check_in_time)) : '' ?>"
                                data-current-remark="<?= htmlspecialchars($row->remark ?? '', ENT_QUOTES) ?>">
                            Update
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <tr id="amRosterEmptyRow" class="am-roster-empty-row">
                    <td colspan="<?= ($isNYSCAdmin && $eventLevel === 'National') ? ($canEditRoster ? 9 : 8) : ($canEditRoster ? 8 : 7) ?>" class="am-roster-empty-cell">
                        No members match the selected filter criteria.
                    </td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<?php if ($canEditRoster): ?>
<!-- ============================================================
     Quick Update Modal (Only for authorized editors)
     ============================================================ -->
<div class="am-modal-backdrop" id="amQuickUpdateModal">
    <div class="am-modal" style="max-width:440px;">
        <div class="am-modal-header">
            <h3>Update Member Attendance</h3>
            <button type="button" class="am-modal-close" id="amQuickClose" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="am-modal-body">
            <input type="hidden" id="quEventId" value="<?= (int)$event->event_id ?>">
            <input type="hidden" id="quMemberId">

            <div class="am-field">
                <label>MEMBER</label>
                <div id="quMemberName" style="font-weight:700;font-size:14px;color:#0f172a;padding:8px 0;"></div>
            </div>

            <div class="am-field">
                <label for="quStatus">ATTENDANCE STATUS <span style="color:#ef4444;">*</span></label>
                <select id="quStatus">
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                </select>
            </div>

            <div class="am-field">
                <label for="quCheckIn">CHECK-IN TIME</label>
                <input type="datetime-local" id="quCheckIn">
            </div>

            <div class="am-field">
                <label for="quRemark">REMARK</label>
                <input type="text" id="quRemark" placeholder="e.g. Verified by Admin">
            </div>
        </div>
        <div class="am-modal-footer">
            <button type="button" class="am-btn-cancel" id="amQuickCancel">Cancel</button>
            <button type="button" class="am-btn am-btn-primary db-confirm-action" id="amQuickSaveBtn">Save Update</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="am-toast" id="amToast"></div>

<input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
<script>
    window.ROOT = "<?= ROOT ?>";
    window.currentEventId = <?= (int)$event->event_id ?>;
    window.isNYSCAdmin = <?= $isNYSCAdmin ? 'true' : 'false' ?>;
    window.isZonalSecretary = <?= $isZonalSecretary ? 'true' : 'false' ?>;
    window.eventOrganizerLevel = <?= json_encode($eventLevel) ?>;
    window.eventZoneId = <?= json_encode($eventZoneId) ?>;
    window.eventDivisionId = <?= json_encode($eventDivisionId) ?>;
    window.currentAttendanceUser = {
        name: <?= json_encode($userName ?? 'Administrator', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        role: <?= json_encode($userRole ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    };
</script>
<script src="<?= ROOT ?>/assets/js/attendance.js?v=20261024"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
