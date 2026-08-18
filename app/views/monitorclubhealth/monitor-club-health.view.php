<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Monitor Club Health — YouthNexus Pulse') ?></title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/monitor-club-health.css?v=<?= time() ?>">
</head>
<body class="dashboard">
<div class="db-app">

    <!-- ============ Sidebar ============ -->
    <aside class="db-sidebar">
        <div class="db-brand">
            <div class="db-brand-mark">
                <svg viewBox="0 0 24 24" fill="var(--db-sidebar-bg)" stroke="none"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/></svg>
            </div>
            <div class="db-brand-text">
                <b>YouthNexus</b>
                <span>Div. Coordinator</span>
            </div>
        </div>

        <nav class="db-nav">
            <a href="<?= ROOT ?>/dashboard" class="db-nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="<?= ROOT ?>/clubregistration" class="db-nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Approve Registration
            </a>
            <a href="<?= ROOT ?>/clubhealth" class="db-nav-link active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                Monitor Club Health
            </a>
            <a href="<?= ROOT ?>/managereports" class="db-nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                Manage Reports
            </a>
            <a href="<?= ROOT ?>/auth/logout" class="db-nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                Logout
            </a>
        </nav>

        <div class="db-sidebar-footer">
            <div class="db-avatar"><?= htmlspecialchars($_SESSION['user_initials'] ?? 'RP') ?></div>
            <div class="db-who">
                <b><?= htmlspecialchars($_SESSION['user_name'] ?? 'Coordinator') ?></b>
                <span>Div. Coordinator</span>
            </div>
        </div>
    </aside>

    <!-- ============ Main ============ -->
    <div class="db-main">
        <header class="db-topbar">
            <div>
                <h1>Monitor Club Score</h1>
                <p>Monitor club health scores and identify clubs requiring intervention</p>
            </div>
            <div class="db-topbar-right">
                <div class="db-search-top">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" id="mchTopSearch" placeholder="Search...">
                </div>
                <button class="db-icon-btn" id="notifBellBtn" title="Notifications">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="db-badge-dot" style="display: none;"></span>
                </button>
                <div class="db-topbar-avatar"><?= htmlspecialchars($_SESSION['user_initials'] ?? 'RP') ?></div>
            </div>
        </header>

        <main class="db-content">

            <!-- 3 Stat Cards (Green / Yellow / Red) -->
            <div class="mch-stats">
                <!-- Healthy Card (Green) -->
                <div class="mch-stat-card green" data-filter="Healthy" id="mchStatHealthy" role="button" tabindex="0">
                    <div class="mch-stat-val"><?= (int)($counts['Green'] ?? 0) ?></div>
                    <div class="mch-stat-label-line">
                        <span>Healthy</span>
                    </div>
                    <div class="mch-stat-subtext">Score: 85–100</div>
                </div>

                <!-- At Risk Card (Yellow) -->
                <div class="mch-stat-card yellow" data-filter="At Risk" id="mchStatAtRisk" role="button" tabindex="0">
                    <div class="mch-stat-val"><?= (int)($counts['Yellow'] ?? 0) ?></div>
                    <div class="mch-stat-label-line">
                        <span>At Risk</span>
                    </div>
                    <div class="mch-stat-subtext">Score: 50–69</div>
                </div>

                <!-- Dormant Card (Red) -->
                <div class="mch-stat-card red" data-filter="Dormant" id="mchStatDormant" role="button" tabindex="0">
                    <div class="mch-stat-val"><?= (int)($counts['Red'] ?? 0) ?></div>
                    <div class="mch-stat-label-line">
                        <span>Dormant</span>
                    </div>
                    <div class="mch-stat-subtext">Score: &lt;50 / Inactivity</div>
                </div>
            </div>

            <!-- PART 2 — Combined Search + Filter bar (matching Club Registration) -->
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

            <!-- Filter panel: hidden until "Filters" is clicked -->
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

            <!-- Section Header Row with Sort Toggle Button -->
            <div class="mch-section-header-row" id="mchSectionHeaderRow">
                <h3 class="mch-section-heading">Clubs</h3>
                <button type="button" class="mch-sort-toggle-btn" id="mchSortToggleBtn" data-sort="desc">Sort: Highest Score First ▾</button>
            </div>

            <!-- PART 1 — Club Cards Grid (4 Columns, Centered Avatar Layout) -->
            <div class="mch-grid" id="mchClubGrid">
                <?php if (empty($clubs)): ?>
                    <!-- Empty state: zero clubs in division -->
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

                <!-- Empty state for filter mismatch -->
                <div class="mch-empty-state" id="mchNoFilterMatch" style="display: none;">
                    <div class="mch-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <h3 class="mch-empty-title">No Matching Clubs</h3>
                    <p class="mch-empty-text">No clubs match your current search and filter criteria.</p>
                    <button type="button" class="mch-btn-reset-filter" id="mchResetFilters">Clear Filters</button>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require __DIR__ . '/partials/club-details-modal.php'; ?>

<?php require __DIR__ . '/partials/flag-club-modal.php'; ?>

<!-- Toast notification element -->
<div class="mch-toast" id="mchToast"></div>

<script src="<?= ROOT ?>/assets/js/monitor-club-health.js?v=<?= time() ?>"></script>
</body>
</html>
