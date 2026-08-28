<?php
/**
 * Attendance — Session Detail (Member Roster for one event)
 * Divisional Secretary: view/update attendance for a single event.
 * Uses the shared dashboard-start/end shell.
 */
$title                   = $title ?? 'Event Attendance — YouthNexus';
$pageTitle               = htmlspecialchars($event->title ?? 'Event Attendance');
$pageDescription         = 'View and update member attendance for this event';
$currentRoute            = 'attendance';
$unreadNotificationCount = 0;

$isDiv     = !empty($event->organizer_division_id);
$organiser = $isDiv
    ? htmlspecialchars($event->organizer_division_name ?? 'Division Event')
    : htmlspecialchars($event->organizer_club_name ?? 'Club Event');

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

            <!-- ============================================================
                 Detail Header
                 ============================================================ -->
            <div class="am-detail-header">
                <a href="<?= ROOT ?>/attendance" class="am-back-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Back to Events
                </a>
                <div class="am-detail-actions">
                    <button type="button" class="am-btn" id="amDownloadCsvBtn">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Download CSV
                    </button>
                    <button type="button" class="am-btn" id="amExportPdfBtn">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Export PDF
                    </button>
                </div>
            </div>

            <!-- Event Summary -->
            <div style="margin-bottom:6px;">
                <div style="font-size:12.5px; color:#6b7280;">
                    <?= $organiser ?> &nbsp;·&nbsp;
                    <?= date('M j, Y \a\t g:i A', strtotime($event->start_datetime)) ?>
                    <?= $event->location ? ' &nbsp;·&nbsp; ' . htmlspecialchars($event->location) : '' ?>
                </div>
            </div>

            <!-- ============================================================
                 Stat Cards (3 — Present / Absent / Attendance Rate)
                 ============================================================ -->
            <div class="am-stats" style="max-width:760px;">
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
                 Member Table
                 ============================================================ -->
            <div class="am-table-wrapper">
                <div class="am-table-toolbar">
                    <div class="am-table-search">
                        <span class="am-table-search-icon">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </span>
                        <input type="text" id="amTableSearch" placeholder="Search members…" autocomplete="off">
                    </div>
                    <select class="am-table-status-filter" id="amTableStatusFilter">
                        <option value="">All Statuses</option>
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="unmarked">Not Recorded</option>
                    </select>
                    <span style="font-size:13px;color:#6b7280;margin-left:auto;"><?= count($roster) ?> members</span>
                </div>
                <?php if (empty($roster)): ?>
                    <div class="am-empty-state" style="padding:40px 0;">
                        <p>No attendance records found for this event.</p>
                    </div>
                <?php else: ?>
                <table class="am-table" id="amRosterTable">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Club</th>
                            <th>Status</th>
                            <th>Check-in</th>
                            <th>Remark</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roster as $row): ?>
                        <tr class="am-roster-row"
                            data-name="<?= htmlspecialchars(strtolower($row->member_name ?? '')) ?>"
                            data-status="<?= htmlspecialchars($row->att_status ?? 'unmarked') ?>">
                            <td>
                                <strong><?= htmlspecialchars($row->member_name ?? '') ?></strong><br>
                                <small style="color:#9ca3af;"><?= htmlspecialchars($row->email ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($row->club_name ?? '—') ?></td>
                            <td>
                                <?php if ($row->att_status === 'Present'): ?>
                                    <span class="am-status-badge present">Present</span>
                                <?php elseif ($row->att_status === 'Absent'): ?>
                                    <span class="am-status-badge absent">Absent</span>
                                <?php else: ?>
                                    <span class="am-status-badge unmarked">Not recorded</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $row->check_in_time ? date('H:i', strtotime($row->check_in_time)) : '—' ?></td>
                            <td><?= htmlspecialchars($row->remark ?? '—') ?></td>
                            <td>
                                <button type="button"
                                        class="am-btn am-btn-quick-update"
                                        style="font-size:12px;padding:5px 10px;"
                                        data-member-id="<?= (int)$row->user_id ?>"
                                        data-member-name="<?= htmlspecialchars($row->member_name ?? '') ?>"
                                        data-current-status="<?= htmlspecialchars($row->att_status ?? '') ?>">
                                    Update
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- ============================================================
                 Quick Update Panel
                 ============================================================ -->
            <div class="am-quick-update-panel" id="amQuickPanel" style="display:none;">
                <h4>QUICK UPDATE</h4>
                <p style="font-size:13px;color:#374151;margin-bottom:12px;">
                    Updating: <strong id="amQuickMemberName">—</strong>
                </p>
                <div class="am-quick-fields-row">
                    <div class="am-field">
                        <label>STATUS</label>
                        <select id="amQuickStatus">
                            <option value="Present">Present</option>
                            <option value="Absent">Absent</option>
                        </select>
                    </div>
                    <div class="am-field">
                        <label>CHECK-IN TIME (optional)</label>
                        <input type="datetime-local" id="amQuickCheckIn">
                    </div>
                    <div class="am-field">
                        <label>CHECK-OUT TIME (optional)</label>
                        <input type="datetime-local" id="amQuickCheckOut">
                    </div>
                </div>
                <div class="am-quick-remarks am-field">
                    <label>REMARK (optional)</label>
                    <textarea id="amQuickRemark" placeholder="Optional note…"></textarea>
                </div>
                <div class="am-quick-footer">
                    <button type="button" class="am-btn-cancel" id="amQuickCancelBtn">Cancel</button>
                    <button type="button" class="am-btn am-btn-primary" id="amQuickSaveBtn">Save Update</button>
                </div>
            </div>

<div class="am-toast" id="amToast"></div>

<input type="hidden" id="csrfToken"   value="<?= htmlspecialchars($csrf_token ?? '') ?>">
<input type="hidden" id="amEventId"   value="<?= (int)($event->event_id ?? 0) ?>">
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/attendance.css?v=<?= time() ?>">
<script>window.ROOT = "<?= ROOT ?>";</script>
<script src="<?= ROOT ?>/assets/js/attendance.js?v=<?= time() ?>"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
