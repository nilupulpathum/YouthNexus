<?php
/**
 * Attendance — Session Detail (Member Roster for one event)
 * Supports both NYSC Administrator (National Scope) and Divisional Secretary.
 */
$title                   = $title ?? 'Event Attendance — YouthNexus';
$pageTitle               = htmlspecialchars($event->title ?? 'Event Attendance');
$pageDescription         = 'View and verify member attendance for this event';
$currentRoute            = 'attendance';
$unreadNotificationCount = 0;
$isNYSCAdmin             = !empty($isNYSCAdmin);

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
            <a href="<?= ROOT ?>/attendance/download/<?= (int)$event->event_id ?>" class="am-btn" id="amDownloadCsvBtn">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Download CSV
            </a>
            <button type="button" class="am-btn" id="amExportPdfBtn" onclick="window.print()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Print / Export
            </button>
        </div>
    </div>

    <!-- Event Summary Information -->
    <div class="am-event-summary-card">
        <div class="am-event-summary-line">
            <span class="am-badge <?= strtolower($event->organizer_level ?? 'club') ?>"><?= htmlspecialchars($event->organizer_level ?? 'Event') ?></span>
            <?php if (!empty($event->event_type)): ?>
                <span class="am-badge type"><?= htmlspecialchars($event->event_type) ?></span>
            <?php endif; ?>
            <span class="am-event-summary-meta">
                <?= $organiser ?> &nbsp;&bull;&nbsp;
                <?= date('M j, Y \a\t g:i A', strtotime($event->start_datetime)) ?>
                <?= $event->location ? ' &nbsp;&bull;&nbsp; ' . htmlspecialchars($event->location) : '' ?>
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
            <div class="am-stat-value"><?= $present ?></div>
            <div class="am-stat-label">Present</div>
        </div>
        <div class="am-stat-card">
            <div class="am-stat-icon absent">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <div class="am-stat-value"><?= $absent ?></div>
            <div class="am-stat-label">Absent</div>
        </div>
        <div class="am-stat-card">
            <div class="am-stat-icon rate">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#6d28d9" stroke-width="2"><path d="M5 19V9M12 19V5M19 19v-7" stroke-linecap="round"/><path d="M3 19h18" stroke-linecap="round"/></svg>
            </div>
            <div class="am-stat-value"><?= $rate ?>%</div>
            <div class="am-stat-label">Attendance Rate</div>
        </div>
    </div>

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
            <span style="font-size:13px;color:#6b7280;margin-left:auto;"><strong><?= count($roster) ?></strong> members in scope</span>
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
                    <th>Status</th>
                    <th>Check-in Time</th>
                    <th>Remark</th>
                    <th>Recorded By</th>
                    <th style="text-align:right;">Actions</th>
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
                ?>
                <tr class="am-roster-row"
                    data-name="<?= htmlspecialchars(strtolower($mName)) ?>"
                    data-email="<?= htmlspecialchars(strtolower($row->email ?? '')) ?>"
                    data-club="<?= htmlspecialchars(strtolower($row->club_name ?? '')) ?>"
                    data-status="<?= htmlspecialchars($attStatus) ?>">
                    <td>
                        <strong><?= htmlspecialchars($mName) ?></strong><br>
                        <small style="color:#64748b;"><?= htmlspecialchars($row->email ?? '') ?></small>
                    </td>
                    <td><?= $clubLoc ?></td>
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
                    <td style="text-align:right;">
                        <button type="button"
                                class="am-btn am-btn-quick-update"
                                style="font-size:12px;padding:6px 12px;"
                                data-member-id="<?= (int)$row->user_id ?>"
                                data-member-name="<?= htmlspecialchars($mName) ?>"
                                data-current-status="<?= htmlspecialchars($attStatus) ?>"
                                data-current-checkin="<?= !empty($row->check_in_time) ? date('Y-m-d\TH:i', strtotime($row->check_in_time)) : '' ?>"
                                data-current-remark="<?= htmlspecialchars($row->remark ?? '', ENT_QUOTES) ?>">
                            Update
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<!-- ============================================================
     Quick Update Modal
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
            <button type="button" class="am-btn am-btn-primary" id="amQuickSaveBtn">Save Update</button>
        </div>
    </div>
</div>

<div class="am-toast" id="amToast"></div>

<input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
<script>
    window.ROOT = "<?= ROOT ?>";
    window.currentEventId = <?= (int)$event->event_id ?>;
</script>
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/attendance.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/divisional-summary-standard.css?v=20260924">
<script src="<?= ROOT ?>/assets/js/attendance.js?v=<?= time() ?>"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
