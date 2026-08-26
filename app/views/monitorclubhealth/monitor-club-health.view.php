<?php
/**
 * Monitor Club Health — Divisional Coordinator dashboard
 *
 * Uses the shared dashboard layout shell (dashboard-start / dashboard-end).
 * Only page-specific content lives here.
 */
$title           = $title ?? 'Monitor Club Health — YouthNexus Pulse';
$pageTitle       = 'Monitor Club Health';
$pageDescription = 'Monitor club health scores and identify clubs requiring intervention';
$currentRoute    = 'monitorclubhealth';

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/monitor-club-health.css?v=<?= time() ?>">

    <!-- 3 Stat Cards (Green / Yellow / Red) -->
    <div class="dashboard-stats-row mch-stats">
        <div class="dashboard-stat-card mch-stat-card green" data-filter="Healthy" id="mchStatHealthy" role="button" tabindex="0">
            <div class="mch-stat-icon green">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <div class="mch-stat-val"><?= (int)($counts['Green'] ?? 0) ?></div>
            <div class="mch-stat-label-line"><span>Healthy</span></div>
            <div class="mch-stat-subtext">Score: 85–100</div>
        </div>
        <div class="dashboard-stat-card mch-stat-card yellow" data-filter="At Risk" id="mchStatAtRisk" role="button" tabindex="0">
            <div class="mch-stat-icon yellow">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </div>
            <div class="mch-stat-val"><?= (int)($counts['Yellow'] ?? 0) ?></div>
            <div class="mch-stat-label-line"><span>At Risk</span></div>
            <div class="mch-stat-subtext">Score: 50–69</div>
        </div>
        <div class="dashboard-stat-card mch-stat-card red" data-filter="Dormant" id="mchStatDormant" role="button" tabindex="0">
            <div class="mch-stat-icon red">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <div class="mch-stat-val"><?= (int)($counts['Red'] ?? 0) ?></div>
            <div class="mch-stat-label-line"><span>Dormant</span></div>
            <div class="mch-stat-subtext">Score: &lt;50 / Inactivity</div>
        </div>
    </div>

    <!-- Search + Filter bar -->
    <div class="mch-toolbar">
        <div class="mch-search-group">
            <div class="mch-search-input-wrapper">
                <span class="mch-search-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </span>
                <input type="text" id="mchSearchInput" placeholder="Search clubs...">
                <button type="button" id="mchSearchClear" class="mch-search-clear" aria-label="Clear search">&times;</button>
            </div>
        </div>
        <button type="button" class="mch-filter-btn" id="mchFilterBtn" aria-expanded="false">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
            Filters
        </button>
        <button type="button" class="mch-btn-export" id="mchExportBtn" title="Export club health overview">
            Export
        </button>
    </div>

    <!-- Filter panel -->
    <div class="mch-filter-panel" id="mchFilterPanel">
        <div class="mch-filter-field">
            <label for="mchFilterStatus">Health Status</label>
            <select id="mchFilterStatus">
                <option value="">All Health Statuses</option>
                <option value="Green">Healthy</option>
                <option value="Yellow">At Risk</option>
                <option value="Red">Dormant</option>
            </select>
        </div>
        <div class="mch-filter-actions">
            <button type="button" class="mch-btn" id="mchClearFilterBtn">Clear Filter</button>
            <button type="button" class="mch-btn mch-btn-primary" id="mchAddFilterBtn">Add Filter</button>
        </div>
    </div>

    <!-- Section Header -->
    <div class="mch-section-header-row" id="mchSectionHeaderRow">
        <h3 class="mch-section-heading">Clubs</h3>
        <button type="button" class="mch-sort-toggle-btn" id="mchSortToggleBtn" data-sort="desc">Sort: Highest Score First ▾</button>
    </div>

    <!-- Club Cards Grid -->
    <div class="dashboard-card-grid mch-grid" id="mchClubGrid">
        <?php if (empty($clubs)): ?>
            <div class="mch-empty-state" id="mchEmptyDivision">
                <div class="mch-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3 class="mch-empty-title">No Clubs Found</h3>
                <p class="mch-empty-text">There are currently no registered clubs in <?= htmlspecialchars($divisionName) ?>.</p>
            </div>
        <?php else: ?>
            <?php foreach ($clubs as $club): ?>
                <?php require __DIR__ . '/partials/club-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="mch-empty-state" id="mchNoFilterMatch" style="display: none;">
            <div class="mch-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <h3 class="mch-empty-title">No Matching Clubs</h3>
            <p class="mch-empty-text">No clubs match your current search and filter criteria.</p>
            <button type="button" class="mch-btn-reset-filter" id="mchResetFilters">Clear Filters</button>
        </div>
    </div>

    <?php require __DIR__ . '/partials/club-details-modal.php'; ?>
    <?php require __DIR__ . '/partials/flag-club-modal.php'; ?>

    <div class="mch-toast" id="mchToast"></div>
    <script>const ROOT = '<?= ROOT ?>';</script>
    <script src="<?= ROOT ?>/assets/js/monitor-club-health.js?v=<?= time() ?>"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
