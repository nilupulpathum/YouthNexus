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
$pageStyles              = [
    ROOT . '/assets/css/attendance.css?v=20261024',
    ROOT . '/assets/css/divisional-summary-standard.css?v=20260924',
];

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

    <!-- Event Summary Information -->
    <div class="am-event-summary-card">
        <div class="am-event-summary-line">
            <span class="am-badge <?= strtolower($eventLevel) ?>"><?= htmlspecialchars($eventLevel) ?></span>
            <?php if (!empty($event->event_type)): ?>
                <span class="am-badge type"><?= htmlspecialchars($event->event_type) ?></span>
            <?php endif; ?>
            <span class="am-event-summary-meta">
                <?= $organiser ?> &nbsp;&bull;&nbsp;
                <?= date('M j, Y \a\t g:i A', strtotime($event->start_datetime)) ?>
                <?= !empty($event->location) ? ' &nbsp;&bull;&nbsp; ' . htmlspecialchars($event->location) : '' ?>
            </span>
        </div>
    </div>

    <!-- ============================================================
         Stat Cards (Present / Absent / Attendance Rate)
         ============================================================ -->
    <div class="am-stats">
        <div class="am-stat-card">
            <div class="am-stat-icon present">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="am-stat-value" id="amPresentCount"><?= $present ?></div>
            <div class="am-stat-label">Present</div>
        </div>
        <div class="am-stat-card">
            <div class="am-stat-icon absent">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <div class="am-stat-value" id="amAbsentCount"><?= $absent ?></div>
            <div class="am-stat-label">Absent</div>
        </div>
        <div class="am-stat-card">
            <div class="am-stat-icon rate">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#6d28d9" stroke-width="2"><path d="M5 19V9M12 19V5M19 19v-7" stroke-linecap="round"/><path d="M3 19h18" stroke-linecap="round"/></svg>
            </div>
            <div class="am-stat-value" id="amAttendanceRate"><?= $rate ?>%</div>
            <div class="am-stat-label">Attendance Rate</div>
        </div>
    </div>

    <!-- ============================================================
         Filter Panel for Scope-based Review
         ============================================================ -->
    <?php
    $showZoneFilter     = ($isNYSCAdmin && $eventLevel === 'National');
    $showDivisionFilter = ($isNYSCAdmin && in_array($eventLevel, ['National', 'Zonal'])) 
                       || ($isZonalSecretary && in_array($eventLevel, ['National', 'Zonal']));
    $showClubFilter     = in_array($eventLevel, ['National', 'Zonal', 'Division']);
    $showScopeFilter    = ($showZoneFilter || $showDivisionFilter || $showClubFilter);
    ?>
    <?php if ($showScopeFilter): ?>
    <div class="am-detail-filter-card" id="amDetailFilterCard">
        <div class="am-detail-filter-header">
            <div class="am-detail-filter-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filter Attendance by Scope
            </div>
            <div class="am-detail-filter-actions">
                <span class="am-scope-badge" id="amScopeBadge">
                    All records for this event
                </span>
                <button type="button" class="am-filter-reset-btn" id="amDetailResetFilterBtn">
                    Reset Filter
                </button>
            </div>
        </div>

        <div class="am-detail-filter-grid">
            <?php if ($showZoneFilter): ?>
            <!-- 1. Zone Filter (for National events) -->
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
            <!-- 2. Division Filter (for National and Zonal events) -->
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

            <?php if ($showClubFilter): ?>
            <!-- 3. Club Filter (for National, Zonal, and Division events) -->
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
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================
         Member Roster Table
         ============================================================ -->
    <div class="am-table-wrapper">
        <div class="am-table-toolbar">
            <div class="am-table-search">
                <span class="am-table-search-icon">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </span>
                <input type="text" id="amTableSearch" placeholder="Search members by name, email or club…" autocomplete="off">
            </div>
            <select class="am-table-status-filter" id="amTableStatusFilter">
                <option value="">All Statuses</option>
                <option value="Present">Present</option>
                <option value="Absent">Absent</option>
                <option value="unmarked">Not Recorded</option>
            </select>
            <span style="font-size:13px;color:#6b7280;margin-left:auto;">
                <strong id="amRosterCount"><?= count($roster) ?></strong> members in scope
            </span>
        </div>

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
                <tr id="amRosterEmptyRow" style="display:none;">
                    <td colspan="<?= ($isNYSCAdmin && $eventLevel === 'National') ? ($canEditRoster ? 9 : 8) : ($canEditRoster ? 8 : 7) ?>" style="text-align:center;padding:36px 16px;color:#64748b;font-size:14px;">
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
