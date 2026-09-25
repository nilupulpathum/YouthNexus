<?php
/**
 * Attendance — Session List
 * Supports both NYSC Administrator (National Scope & Cascading Filters)
 * and Divisional Secretary (Division Scope).
 */
$title                   = $title ?? 'Manage Attendance — YouthNexus';
$pageTitle               = $pageTitle ?? 'Manage Attendance';
$pageDescription         = $pageDescription ?? 'Log and review attendance for approved events';
$currentRoute            = 'attendance';
$unreadNotificationCount = 0;
$isNYSCAdmin             = !empty($isNYSCAdmin);

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<div class="am-page-container">

    <!-- Action Row -->
    <div class="am-action-row">
        <button type="button" class="am-btn am-btn-primary db-primary-action" id="amAddBtn">
            Log Attendance
        </button>
    </div>

    <!-- ============================================================
         Stat Cards
         ============================================================ -->
    <div class="am-stats <?= $isNYSCAdmin ? 'am-stats-3' : '' ?>">
        <div class="am-stat-card">
            <div class="am-stat-icon events">
                <?= yn_icon('calendar') ?>
            </div>
            <div class="am-stat-value"><?= (int)($stats->events_this_year ?? 0) ?></div>
            <div class="am-stat-label"><?= $isNYSCAdmin ? 'National Approved Events' : 'Approved Events This Year' ?></div>
        </div>

        <div class="am-stat-card">
            <div class="am-stat-icon recorded">
                <?= yn_icon('check') ?>
            </div>
            <div class="am-stat-value"><?= (int)($stats->attendance_this_year ?? 0) ?></div>
            <div class="am-stat-label"><?= $isNYSCAdmin ? 'Total Attendances Recorded' : 'Attendance Records This Year' ?></div>
        </div>

        <?php if ($isNYSCAdmin): ?>
        <div class="am-stat-card">
            <div class="am-stat-icon rate">
                <?= yn_icon('award') ?>
            </div>
            <div class="am-stat-value"><?= (int)($stats->national_rate ?? 0) ?>%</div>
            <div class="am-stat-label">National Presence Rate</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ============================================================
         Toolbar & Filters
         ============================================================ -->
    <?php
    $activeFilters = 0;
    foreach (['zone_id', 'division_id', 'club_id', 'event_type'] as $filterKey) {
        if (!empty($filters[$filterKey])) {
            $activeFilters++;
        }
    }
    if (!empty($filters['level']) && $filters['level'] !== 'all') {
        $activeFilters++;
    }
    ?>
    <div class="am-toolbar">
        <div class="am-search-group">
            <span class="am-search-icon" aria-hidden="true">
                <?= yn_icon('search') ?>
            </span>
            <div class="am-search-input-wrapper">
                <input
                    type="text"
                    id="amSearchInput"
                    class="am-search-input"
                    placeholder="Search events by title, location, club or organizer..."
                    aria-label="Search events"
                    autocomplete="off"
                    value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                >
            </div>
        </div>
        <button
            type="button"
            class="am-filter-btn"
            id="amFilterBtn"
            aria-expanded="<?= $activeFilters > 0 ? 'true' : 'false' ?>"
            aria-controls="amFilterPanel"
        >
            Filters
            <span
                class="am-filter-count<?= $activeFilters > 0 ? '' : ' hidden' ?>"
                id="amFilterCount"
            ><?= $activeFilters ?></span>
        </button>
    </div>

    <!-- Filter Panel -->
    <?php if ($isNYSCAdmin): ?>
    <!-- NYSC Administrator Cascading Filter Panel (Server & Client supported) -->
    <form method="GET" action="<?= ROOT ?>/attendance" class="am-filter-panel<?= $activeFilters > 0 ? ' open' : '' ?>" id="amFilterPanel">
        <div class="am-filter-grid">
            <!-- 1. Zone Filter -->
            <div class="am-filter-field">
                <label for="filterZone">ZONAL OFFICE</label>
                <select name="zone_id" id="filterZone">
                    <option value="">All Zones (<?= count($zones ?? []) ?>)</option>
                    <?php foreach ($zones as $z): ?>
                        <option value="<?= (int)$z->zonal_id ?>" <?= (($filters['zone_id'] ?? '') == $z->zonal_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($z->zonal_name) ?><?= !empty($z->province) ? ' (' . htmlspecialchars($z->province) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 2. Division Filter (Cascaded) -->
            <div class="am-filter-field">
                <label for="filterDivision">DIVISIONAL SECRETARIAT</label>
                <select name="division_id" id="filterDivision">
                    <option value="">All Divisions</option>
                    <?php foreach ($divisions as $d): ?>
                        <option value="<?= (int)$d->division_id ?>" <?= (($filters['division_id'] ?? '') == $d->division_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d->division_name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 3. Club Filter (Cascaded) -->
            <div class="am-filter-field">
                <label for="filterClub">YOUTH CLUB</label>
                <select name="club_id" id="filterClub">
                    <option value="">All Clubs</option>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= (int)$c->club_id ?>" <?= (($filters['club_id'] ?? '') == $c->club_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c->club_name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 4. Organizer Level Filter -->
            <div class="am-filter-field">
                <label for="filterLevel">ORGANIZER LEVEL</label>
                <select name="level" id="filterLevel">
                    <option value="all" <?= (($filters['level'] ?? '') === 'all') ? 'selected' : '' ?>>All Levels</option>
                    <option value="National" <?= (($filters['level'] ?? '') === 'National') ? 'selected' : '' ?>>National Level</option>
                    <option value="Zonal" <?= (($filters['level'] ?? '') === 'Zonal') ? 'selected' : '' ?>>Zonal Level</option>
                    <option value="Division" <?= (($filters['level'] ?? '') === 'Division') ? 'selected' : '' ?>>Divisional Level</option>
                    <option value="Club" <?= (($filters['level'] ?? '') === 'Club') ? 'selected' : '' ?>>Club Level</option>
                </select>
            </div>

            <!-- 5. Event Type Filter -->
            <div class="am-filter-field">
                <label for="amFilterType">EVENT TYPE</label>
                <select name="event_type" id="amFilterType">
                    <option value="">All Types</option>
                    <option value="Workshop" <?= (($filters['event_type'] ?? '') === 'Workshop') ? 'selected' : '' ?>>Workshop</option>
                    <option value="Community Service" <?= (($filters['event_type'] ?? '') === 'Community Service') ? 'selected' : '' ?>>Community Service</option>
                    <option value="Training" <?= (($filters['event_type'] ?? '') === 'Training') ? 'selected' : '' ?>>Training</option>
                    <option value="Sports" <?= (($filters['event_type'] ?? '') === 'Sports') ? 'selected' : '' ?>>Sports</option>
                    <option value="Cultural" <?= (($filters['event_type'] ?? '') === 'Cultural') ? 'selected' : '' ?>>Cultural</option>
                    <option value="Other" <?= (($filters['event_type'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="am-filter-actions">
            <button type="submit" class="am-btn am-btn-primary" id="amApplyFilterBtn">Apply Filters</button>
            <a href="<?= ROOT ?>/attendance" class="am-btn am-btn-cancel" id="amClearFilterBtn">Reset</a>
        </div>
    </form>

    <?php else: ?>
    <!-- Divisional Secretary Client-Side Filter Panel -->
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
    <?php endif; ?>

    <!-- ============================================================
         Event Cards Grid
         ============================================================ -->
    <div class="am-list" id="amCardGrid">
        <?php if (empty($events)): ?>
            <div class="am-empty-state">
                <span class="am-empty-state-icon"><?= yn_icon('info') ?></span>
                <p>No approved events match the selected criteria.</p>
                <?php if (!empty($filters['zone_id']) || !empty($filters['division_id']) || !empty($filters['club_id'])): ?>
                    <a href="<?= ROOT ?>/attendance" class="am-btn am-btn-sm" style="margin-top:10px;">Clear All Filters</a>
                <?php endif; ?>
            </div>
        <?php else: foreach ($events as $evt): ?>
            <?php
                $lvl        = strtolower($evt->organizer_level ?? 'club');
                $lvlBadge   = $evt->organizer_level ?? 'Club';
                $recorded   = (int)($evt->attendance_recorded ?? 0);
                $present    = (int)($evt->present_count ?? 0);
                $max        = $evt->max_attendance ? (int)$evt->max_attendance : null;
                $chipLabel  = $recorded > 0
                    ? ($recorded . ($max ? ' / ' . $max : '') . ' recorded (' . $present . ' present)')
                    : 'No records yet';
                $chipClass  = $recorded > 0 ? '' : ' none';

                // Resolve full hierarchy for NYSC Admin
                $orgHierarchy = [];
                if (!empty($evt->organizer_club_name))     $orgHierarchy[] = $evt->organizer_club_name;
                if (!empty($evt->organizer_division_name)) $orgHierarchy[] = $evt->organizer_division_name;
                if (!empty($evt->organizer_zonal_name))    $orgHierarchy[] = $evt->organizer_zonal_name;
                $hierarchyText = !empty($orgHierarchy) ? implode(', ', $orgHierarchy) : 'National Administration';
                $searchText = strtolower(implode(' ', array_filter([
                    $evt->title ?? '',
                    $evt->event_type ?? '',
                    $evt->location ?? '',
                    $evt->organizer_club_name ?? '',
                    $evt->organizer_division_name ?? '',
                    $evt->organizer_zonal_name ?? '',
                    $evt->organizer_club_code ?? '',
                ])));
            ?>
            <div class="am-card"
                 data-search="<?= htmlspecialchars($searchText) ?>"
                 data-title="<?= htmlspecialchars(strtolower($evt->title)) ?>"
                 data-type="<?= htmlspecialchars(strtolower($evt->event_type ?? '')) ?>"
                 data-scope="<?= $lvl ?>"
                 data-zone="<?= (int)($evt->event_zonal_id ?? 0) ?>"
                 data-division="<?= (int)($evt->event_division_id ?? 0) ?>"
                 data-club="<?= (int)($evt->organizer_club_id ?? 0) ?>">
                <div class="am-card-top">
                    <span class="am-badge <?= $lvl ?>"><?= htmlspecialchars($lvlBadge) ?></span>
                    <?php if (!empty($evt->event_type)): ?>
                        <span class="am-badge type"><?= htmlspecialchars($evt->event_type) ?></span>
                    <?php endif; ?>
                </div>
                <h3 class="am-card-title"><?= htmlspecialchars($evt->title) ?></h3>
                <p class="am-card-organiser">
                    <?= $hierarchyText ?>
                    <?php if (!empty($evt->organizer_club_code)): ?>
                        <small class="ea-club-code"><?= htmlspecialchars($evt->organizer_club_code) ?></small>
                    <?php endif; ?>
                </p>
                <div class="am-card-meta">
                    <span class="am-card-meta-item">
                        <span class="am-card-meta-icon"><?= yn_icon('calendar') ?></span>
                        <?= date('M j, Y', strtotime($evt->start_datetime)) ?>
                    </span>
                    <?php if (!empty($evt->location)): ?>
                        <span class="am-card-meta-item">
                            <span class="am-card-meta-icon"><?= yn_icon('pin') ?></span>
                            <?= htmlspecialchars($evt->location) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <span class="am-card-attendance-chip<?= $chipClass ?>"><?= $chipLabel ?></span>
                <div class="am-card-footer">
                    <a href="<?= ROOT ?>/attendance/detail/<?= (int)$evt->event_id ?>" class="am-btn-view db-view-button">
                        View Attendance
                    </a>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

</div>

<!-- ============================================================
     Add Attendance Modal (from manage attendance)
     ============================================================ -->
<div class="am-modal-backdrop" id="amModal">
    <div class="am-modal">
        <div class="am-modal-header">
            <h3>Log Attendance</h3>
            <button type="button" class="am-modal-close" id="amModalClose" aria-label="Close">
                <?= yn_icon('close') ?>
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
                    <label for="sEventSelect">EVENT <span style="color:#ef4444;">*</span></label>
                    <select id="sEventSelect">
                        <option value="">— Select Event —</option>
                        <?php foreach ($events as $evt): ?>
                            <option value="<?= (int)$evt->event_id ?>"><?= htmlspecialchars($evt->title) ?> (<?= date('M j, Y', strtotime($evt->start_datetime)) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="am-field">
                    <label for="sMemberSelect">MEMBER <span style="color:#ef4444;">*</span></label>
                    <select id="sMemberSelect" disabled>
                        <option value="">— Select Event first —</option>
                    </select>
                </div>
                <div class="am-fields-row">
                    <div class="am-field">
                        <label for="sStatus">STATUS <span style="color:#ef4444;">*</span></label>
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
                    <input type="text" id="sRemark" placeholder="e.g. On-time / Excused absence">
                </div>
            </div>

            <!-- Bulk CSV -->
            <div class="am-tab-pane" id="paneBulk">
                <div class="am-field">
                    <label for="bEventSelect">EVENT <span style="color:#ef4444;">*</span></label>
                    <select id="bEventSelect">
                        <option value="">— Select Event —</option>
                        <?php foreach ($events as $evt): ?>
                            <option value="<?= (int)$evt->event_id ?>"><?= htmlspecialchars($evt->title) ?> (<?= date('M j, Y', strtotime($evt->start_datetime)) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="am-field">
                    <label for="bCsvFile">CSV FILE <span style="color:#ef4444;">*</span></label>
                    <input type="file" id="bCsvFile" accept=".csv">
                </div>
                <div class="am-csv-note">
                    <strong>Expected CSV columns:</strong> <code>member_id, status, check_in_time, remark</code><br>
                    Rows with an invalid member ID will be skipped and reported back.
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
<script>
    window.ROOT = "<?= ROOT ?>";
    window.isNYSCAdmin = <?= $isNYSCAdmin ? 'true' : 'false' ?>;
</script>
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/attendance.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/divisional-summary-standard.css?v=20260924">
<script src="<?= ROOT ?>/assets/js/attendance.js?v=<?= time() ?>"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
