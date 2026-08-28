<?php
/**
 * Attendance — Session List
 * Divisional Secretary: Manage Attendance dashboard.
 * Uses the shared dashboard-start/end shell.
 */
$title                   = $title ?? 'Manage Attendance — YouthNexus';
$pageTitle               = 'Manage Attendance';
$pageDescription         = 'Log and review attendance for approved events in your division';
$currentRoute            = 'attendance';
$unreadNotificationCount = 0;

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

            <!-- Action Row -->
            <div class="am-action-row">
                <button type="button" class="am-btn am-btn-primary" id="amAddBtn">
                    + Log Attendance
                </button>
            </div>

            <!-- ============================================================
                 Stat Cards (2 cards — events this year / attendance this year)
                 ============================================================ -->
            <div class="am-stats">
                <div class="am-stat-card">
                    <div class="am-stat-icon events">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16" stroke-linecap="round"/></svg>
                    </div>
                    <div class="am-stat-value"><?= (int)($stats->events_this_year ?? 0) ?></div>
                    <div class="am-stat-label">Approved Events This Year</div>
                </div>
                <div class="am-stat-card">
                    <div class="am-stat-icon recorded">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div class="am-stat-value"><?= (int)($stats->attendance_this_year ?? 0) ?></div>
                    <div class="am-stat-label">Attendance Records This Year</div>
                </div>
            </div>

            <!-- ============================================================
                 Toolbar
                 ============================================================ -->
            <div class="am-toolbar">
                <div class="am-search-input-wrapper">
                    <span class="am-search-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input type="text" id="amSearchInput" placeholder="Search events…" autocomplete="off">
                </div>
                <button type="button" class="am-filter-btn" id="amFilterBtn" aria-expanded="false">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                    Filters
                    <span class="am-filter-count hidden" id="amFilterCount">0</span>
                </button>
            </div>

            <!-- Filter Panel (client-side only — no page reload) -->
            <div class="am-filter-panel" id="amFilterPanel">
                <div class="am-filter-field">
                    <label for="amFilterType">Event Type</label>
                    <select id="amFilterType">
                        <option value="">All Types</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Community Service">Community Service</option>
                        <option value="Training">Training</option>
                        <option value="Sports">Sports</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="am-filter-field">
                    <label for="amFilterScope">Organiser</label>
                    <select id="amFilterScope">
                        <option value="">All</option>
                        <option value="division">Division Events</option>
                        <option value="club">Club Events</option>
                    </select>
                </div>
                <div class="am-filter-actions">
                    <button type="button" class="am-btn am-btn-primary" id="amApplyFilterBtn">Apply</button>
                    <button type="button" class="am-btn" id="amClearFilterBtn">Clear</button>
                </div>
            </div>

            <!-- ============================================================
                 Event Cards
                 ============================================================ -->
            <div class="am-list" id="amCardGrid">
                <?php if (empty($events)): ?>
                    <div class="am-empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01" stroke-linecap="round"/></svg>
                        <p>No approved events found for your division.</p>
                    </div>
                <?php else: foreach ($events as $evt): ?>
                    <?php
                        $isDiv      = !empty($evt->organizer_division_id);
                        $scopeType  = $isDiv ? 'division' : 'club';
                        $organiser  = $isDiv
                            ? htmlspecialchars($evt->organizer_division_name ?? 'Division Event')
                            : htmlspecialchars($evt->organizer_club_name ?? 'Club Event');
                        $recorded   = (int)($evt->attendance_recorded ?? 0);
                        $max        = $evt->max_attendance ? (int)$evt->max_attendance : null;
                        $chipLabel  = $recorded > 0
                            ? ($recorded . ($max ? ' / ' . $max : '') . ' recorded')
                            : 'No records yet';
                        $chipClass  = $recorded > 0 ? '' : ' none';
                    ?>
                    <div class="am-card"
                         data-title="<?= htmlspecialchars(strtolower($evt->title)) ?>"
                         data-type="<?= htmlspecialchars(strtolower($evt->event_type ?? '')) ?>"
                         data-scope="<?= $scopeType ?>">
                        <div class="am-card-top">
                            <span class="am-badge <?= $scopeType ?>"><?= $isDiv ? 'Division' : 'Club' ?></span>
                            <?php if ($evt->event_type): ?>
                                <span class="am-badge type"><?= htmlspecialchars($evt->event_type) ?></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="am-card-title"><?= htmlspecialchars($evt->title) ?></h3>
                        <p class="am-card-organiser"><?= $organiser ?><?= !$isDiv ? ' <small class="ea-club-code">' . htmlspecialchars($evt->organizer_club_code ?? '') . '</small>' : '' ?></p>
                        <div class="am-card-meta">
                            <span><?= date('M j, Y', strtotime($evt->start_datetime)) ?></span>
                            <?php if ($evt->location): ?>
                                <span><?= htmlspecialchars($evt->location) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="am-card-attendance-chip<?= $chipClass ?>"><?= $chipLabel ?></span>
                        <div class="am-card-footer">
                            <a href="<?= ROOT ?>/attendance/detail/<?= (int)$evt->event_id ?>" class="am-btn-view">
                                View Attendance
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

<!-- ============================================================
     Add Attendance Modal
     ============================================================ -->
<div class="am-modal-backdrop" id="amModal">
    <div class="am-modal">
        <div class="am-modal-header">
            <h3>Log Attendance</h3>
            <button type="button" class="am-modal-close" id="amModalClose" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="am-modal-tabs">
            <button class="am-modal-tab active" data-tab="single" id="tabSingle">Single Entry</button>
            <button class="am-modal-tab"         data-tab="bulk"   id="tabBulk">Bulk CSV Upload</button>
        </div>
        <div class="am-modal-body">

            <!-- Single Entry -->
            <div class="am-tab-pane active" id="paneSingle">
                <div class="am-field">
                    <label for="sEventSelect">EVENT</label>
                    <select id="sEventSelect">
                        <option value="">— Select Event —</option>
                        <?php foreach ($events as $evt): ?>
                            <option value="<?= (int)$evt->event_id ?>"><?= htmlspecialchars($evt->title) ?> (<?= date('M j', strtotime($evt->start_datetime)) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="am-field">
                    <label for="sMemberSelect">MEMBER</label>
                    <select id="sMemberSelect" disabled>
                        <option value="">— Select Event first —</option>
                    </select>
                </div>
                <div class="am-fields-row">
                    <div class="am-field">
                        <label for="sStatus">STATUS</label>
                        <select id="sStatus">
                            <option value="Present">Present</option>
                            <option value="Absent">Absent</option>
                        </select>
                    </div>
                    <div class="am-field">
                        <label for="sCheckIn">CHECK-IN TIME (optional)</label>
                        <input type="datetime-local" id="sCheckIn">
                    </div>
                </div>
                <div class="am-field">
                    <label for="sRemark">REMARK (optional)</label>
                    <input type="text" id="sRemark" placeholder="e.g. Late arrival">
                </div>
            </div>

            <!-- Bulk CSV -->
            <div class="am-tab-pane" id="paneBulk">
                <div class="am-field">
                    <label for="bEventSelect">EVENT</label>
                    <select id="bEventSelect">
                        <option value="">— Select Event —</option>
                        <?php foreach ($events as $evt): ?>
                            <option value="<?= (int)$evt->event_id ?>"><?= htmlspecialchars($evt->title) ?> (<?= date('M j', strtotime($evt->start_datetime)) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="am-field">
                    <label for="bCsvFile">CSV FILE</label>
                    <input type="file" id="bCsvFile" accept=".csv">
                </div>
                <div class="am-csv-note">
                    ⚠️ <strong>Expected columns:</strong> <code>member_id, status, check_in_time, remark</code><br>
                    Rows with an invalid or out-of-scope <code>member_id</code> will be skipped and reported back.
                </div>
            </div>

        </div>
        <div class="am-modal-footer">
            <button type="button" class="am-btn-cancel" id="amModalCancelBtn">Cancel</button>
            <button type="button" class="am-btn am-btn-primary" id="amSaveBtn">Save Attendance</button>
        </div>
    </div>
</div>

<div class="am-toast" id="amToast"></div>

<input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
<script>window.ROOT = "<?= ROOT ?>";</script>
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/attendance.css?v=<?= time() ?>">
<script src="<?= ROOT ?>/assets/js/attendance.js?v=<?= time() ?>"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
