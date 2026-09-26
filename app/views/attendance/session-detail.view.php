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
$pageStyles              = [
    ROOT . '/assets/css/attendance.css?v=' . time(),
    ROOT . '/assets/css/divisional-summary-standard.css?v=20260924',
];
$pageScripts             = [ROOT . '/assets/js/attendance.js?v=20260926'];
require_once __DIR__ . '/../partials/icons.view.php';

$orgHierarchy = [];
if (!empty($event->organizer_club_name))     $orgHierarchy[] = $event->organizer_club_name;
if (!empty($event->organizer_division_name)) $orgHierarchy[] = $event->organizer_division_name;
if (!empty($event->organizer_zonal_name))    $orgHierarchy[] = $event->organizer_zonal_name;
$organiser = !empty($orgHierarchy) ? implode(', ', $orgHierarchy) : 'National Administration';

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<div class="am-page-container">

    <!-- ============================================================
         Detail Header
         ============================================================ -->
    <div class="am-detail-header">
        <a href="<?= ROOT ?>/attendance" class="am-back-btn yn-btn-back">
            Back to Events
        </a>
        <div class="am-detail-actions">
            <a href="<?= ROOT ?>/attendance/download/<?= (int)$event->event_id ?>" class="am-btn yn-btn yn-btn--secondary yn-btn-download" id="amDownloadCsvBtn">
                <?= yn_icon('download') ?>
                Download CSV
            </a>
            <button type="button" class="am-btn yn-btn yn-btn--secondary" id="amExportPdfBtn" onclick="window.print()">
                <?= yn_icon('file') ?>
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
                <?= htmlspecialchars($organiser, ENT_QUOTES, 'UTF-8') ?>,
                <?= date('M j, Y \a\t g:i A', strtotime($event->start_datetime)) ?>
                <?= $event->location ? ', ' . htmlspecialchars($event->location, ENT_QUOTES, 'UTF-8') : '' ?>
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
            <span class="am-roster-count"><strong><?= count($roster) ?></strong> members in scope</span>
        </div>

        <?php if (empty($roster)): ?>
            <div class="am-empty-state am-empty-state--padded">
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
                    <th class="yn-text-right">Actions</th>
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
                    data-member-id="<?= (int)$row->user_id ?>"
                    data-name="<?= htmlspecialchars(strtolower($mName)) ?>"
                    data-email="<?= htmlspecialchars(strtolower($row->email ?? '')) ?>"
                    data-club="<?= htmlspecialchars(strtolower($row->club_name ?? '')) ?>"
                    data-status="<?= htmlspecialchars($attStatus) ?>">
                    <td>
                        <strong><?= htmlspecialchars($mName) ?></strong><br>
                        <small class="am-member-email"><?= htmlspecialchars($row->email ?? '') ?></small>
                    </td>
                    <td><?= $clubLoc ?></td>
                    <td>
                        <?php if ($attStatus === 'Present'): ?>
                            <span class="am-status-badge present">Present</span>
                        <?php elseif ($attStatus === 'Absent'): ?>
                            <span class="am-status-badge absent">Absent</span>
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
                            <div class="am-recorder-time"><?= date('M j, H:i', strtotime($row->recorded_at)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="yn-text-right">
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
    <div class="am-modal am-modal--narrow">
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
                <div id="quMemberName" class="am-member-name"></div>
            </div>

            <div class="am-field">
                <label for="quStatus">ATTENDANCE STATUS <span class="yn-required">*</span></label>
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

<div class="am-toast" id="amToast"></div>

<input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
<div id="attendanceConfig" hidden data-root="<?= htmlspecialchars(ROOT, ENT_QUOTES, 'UTF-8') ?>" data-nysc-admin="<?= $isNYSCAdmin ? 'true' : 'false' ?>" data-user-name="<?= htmlspecialchars((string) ($userName ?? 'Administrator'), ENT_QUOTES, 'UTF-8') ?>" data-user-role="<?= htmlspecialchars((string) ($userRole ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
