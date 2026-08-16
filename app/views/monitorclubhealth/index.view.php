<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Monitor Club Health — YouthNexus Pulse') ?></title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/monitorclubhealth.css?v=<?= time() ?>">
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
            <a href="<?= ROOT ?>/monitorclubhealth" class="db-nav-link active">
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

            <!-- Controls Bar: Search, Filter Chips, Sort, Export -->
            <div class="mch-controls-bar">
                <div class="mch-controls-left">
                    <div class="mch-search-box">
                        <svg class="mch-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="text" id="mchSearchInput" class="mch-search-input" placeholder="Search clubs...">
                        <button type="button" id="mchSearchClear" class="mch-search-clear" aria-label="Clear search">&times;</button>
                    </div>

                    <div class="mch-filter-chips">
                        <button type="button" class="mch-chip is-active" data-filter="All">
                            All Clubs
                        </button>
                        <button type="button" class="mch-chip" data-filter="Healthy">
                            <span class="mch-chip-dot green"></span> Healthy
                        </button>
                        <button type="button" class="mch-chip" data-filter="At Risk">
                            <span class="mch-chip-dot yellow"></span> At Risk
                        </button>
                        <button type="button" class="mch-chip" data-filter="Dormant">
                            <span class="mch-chip-dot red"></span> Dormant
                        </button>
                    </div>
                </div>

                <div class="mch-controls-right">
                    <select id="mchSortSelect" class="mch-sort-select">
                        <option value="score-desc" selected>Sort: Highest Score</option>
                        <option value="score-asc">Sort: Lowest Score</option>
                        <option value="name-asc">Sort: Club Name (A-Z)</option>
                        <option value="name-desc">Sort: Club Name (Z-A)</option>
                        <option value="members-desc">Sort: Most Members</option>
                    </select>

                    <button type="button" class="mch-btn-export" id="mchExportBtn" title="Export club health overview">
                        Export
                    </button>
                </div>
            </div>

            <!-- Club Cards Grid (4 Columns) -->
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
                        <?php
                            $healthStatus = $club->health_status ?? 'Green';
                            $statusKey = strtolower($healthStatus); // 'green', 'yellow', 'red'
                            $score = (float)($club->overall_health_score ?? 0);
                            $formattedScore = number_format($score, 0);
                            $statusLabel = 'HEALTHY';
                            if ($healthStatus === 'Yellow') {
                                $statusLabel = 'AT RISK';
                            } elseif ($healthStatus === 'Red') {
                                $statusLabel = 'DORMANT';
                            }
                            $isFlagged = !empty($club->flagged) ? '1' : '0';
                            $membersCount = (int)($club->live_members ?? 0);
                            $membersText = $membersCount > 0 ? ($membersCount . ' active members') : 'No activity recorded';
                            $locationText = htmlspecialchars($club->division_name ?? $divisionName);
                        ?>
                        <div class="mch-card <?= $statusKey ?>"
                             data-id="<?= (int)$club->club_id ?>"
                             data-name="<?= htmlspecialchars($club->club_name, ENT_QUOTES) ?>"
                             data-code="<?= htmlspecialchars($club->club_code ?? '', ENT_QUOTES) ?>"
                             data-score="<?= $score ?>"
                             data-status="<?= htmlspecialchars($healthStatus, ENT_QUOTES) ?>"
                             data-flagged="<?= $isFlagged ?>"
                             data-members="<?= $membersCount ?>"
                             data-desc="<?= htmlspecialchars($club->description ?? 'Empowering local youth through community initiatives, education, and skill development programs.', ENT_QUOTES) ?>"
                             data-division="<?= htmlspecialchars($club->division_name ?? $divisionName, ENT_QUOTES) ?>"
                             data-date="<?= htmlspecialchars($club->registration_date ?? 'March 2021', ENT_QUOTES) ?>">

                            <!-- Top Row: Icon + Badge -->
                            <div class="mch-card-top">
                                <div class="mch-card-icon-wrap">
                                    <div class="mch-card-icon <?= $statusKey ?>">
                                        <?php if ($statusKey === 'green'): ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        <?php elseif ($statusKey === 'yellow'): ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        <?php else: ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isFlagged === '1'): ?>
                                        <span class="mch-flag-icon-mini" title="Flagged Club">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="#dc2626"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15" stroke="#dc2626" stroke-width="2"/></svg>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <span class="mch-card-badge <?= $statusKey ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </div>

                            <!-- Score Row -->
                            <div class="mch-card-score-row">
                                <span class="mch-card-score-num <?= $statusKey ?>"><?= $formattedScore ?></span>
                                <span class="mch-card-score-denom">/100</span>
                            </div>

                            <!-- Club Info -->
                            <div class="mch-card-info">
                                <h3 class="mch-card-club-name"><?= htmlspecialchars($club->club_name) ?></h3>
                                <p class="mch-card-subline"><?= $locationText ?> • <?= $membersText ?></p>
                            </div>

                            <!-- Bottom Action Button -->
                            <div class="mch-card-bottom">
                                <button type="button" class="mch-card-arrow-btn" title="View details for <?= htmlspecialchars($club->club_name) ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                                </button>
                            </div>
                        </div>
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

<!-- ==========================================================================
     CLUB HEALTH DETAIL MODAL (Figma Center Panel)
     ========================================================================== -->
<div class="mch-modal-backdrop" id="mchDetailModal" role="dialog" aria-modal="true" aria-labelledby="mchModalTitle">
    <div class="mch-modal-card mch-detail-modal">
        <!-- Modal Header -->
        <div class="mch-modal-header">
            <div class="mch-modal-title-group">
                <div class="mch-modal-avatar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <h2 class="mch-modal-title" id="mchModalTitle">Kaduwela Youth Circle</h2>
                    <div class="mch-modal-sub-badges">
                        <span class="mch-modal-code-tag" id="mchModalCode">CLB-COL-2026-001</span>
                        <span class="mch-modal-status-pill green" id="mchModalStatusPill">• Healthy (Green)</span>
                    </div>
                </div>
            </div>
            <button type="button" class="mch-modal-close-btn" id="mchDetailCloseBtn" aria-label="Close modal">&times;</button>
        </div>

        <!-- Modal Body -->
        <div class="mch-modal-body">
            <div class="mch-detail-columns">
                <!-- Left Column: About + Executive Committee -->
                <div>
                    <div class="mch-section-heading">About</div>
                    <p class="mch-about-text" id="mchModalDesc">
                        Empowering local youth through community service, environmental awareness, and skill development programs in the region.
                    </p>

                    <div class="mch-meta-row">
                        <div class="mch-meta-field">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                            <span>Category: <b>Community & Environment</b></span>
                        </div>
                        <div class="mch-meta-field">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>Location: <b id="mchModalLocation">Western Province, Colombo</b></span>
                        </div>
                        <div class="mch-meta-field">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span>Established: <b id="mchModalEstDate">March 2021</b></span>
                        </div>
                    </div>

                    <div style="margin-top: 24px;">
                        <div class="mch-section-heading">Executive Committee</div>
                        <div class="mch-exec-list">
                            <div class="mch-exec-member">
                                <div class="mch-exec-avatar">AR</div>
                                <div class="mch-exec-info">
                                    <b>A. Ranasinghe</b>
                                    <span>President</span>
                                </div>
                            </div>
                            <div class="mch-exec-member">
                                <div class="mch-exec-avatar">MP</div>
                                <div class="mch-exec-info">
                                    <b>M. Perera</b>
                                    <span>Secretary</span>
                                </div>
                            </div>
                            <div class="mch-exec-member">
                                <div class="mch-exec-avatar">SW</div>
                                <div class="mch-exec-info">
                                    <b>S. Wickramasinghe</b>
                                    <span>Treasurer</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Performance Overview + Recent Events -->
                <div>
                    <div class="mch-section-heading">Performance Overview</div>
                    <div class="mch-perf-grid">
                        <div class="mch-perf-card">
                            <div class="mch-perf-card-info">
                                <span>Active Members</span>
                                <b id="mchModalActiveMembers">38</b>
                            </div>
                            <div class="mch-perf-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                        </div>

                        <div class="mch-perf-card">
                            <div class="mch-perf-card-info">
                                <span>Avg. Attendance</span>
                                <b>85%</b>
                            </div>
                            <div class="mch-perf-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="mch-events-header">
                        <div class="mch-section-heading" style="margin-bottom: 0;">Recent Events</div>
                        <a href="#" class="mch-link-view-all" onclick="return false;">View all</a>
                    </div>
                    <table class="mch-events-table">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="mch-event-name-cell">
                                    <b>Beach Cleanup Drive</b>
                                    <span>Community Hall</span>
                                </td>
                                <td>Oct. 12, 2023</td>
                                <td><span class="mch-event-badge completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td class="mch-event-name-cell">
                                    <b>Youth Mentorship Workshop</b>
                                    <span>Community Hall</span>
                                </td>
                                <td>Sep. 28, 2023</td>
                                <td><span class="mch-event-badge completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td class="mch-event-name-cell">
                                    <b>IT Skills Training</b>
                                    <span>Local Library</span>
                                </td>
                                <td>Nov. 05, 2023</td>
                                <td><span class="mch-event-badge upcoming">Upcoming</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Health Detail Breakdown (Bottom Section) -->
            <div class="mch-health-breakdown">
                <div class="mch-section-heading">Health Detail</div>
                <div class="mch-score-breakdown-list">
                    <div class="mch-breakdown-row">
                        <span>Event Score</span>
                        <b id="mchModalEventScore">14 / 30</b>
                    </div>
                    <div class="mch-breakdown-row">
                        <span>Finance Score</span>
                        <b id="mchModalFinanceScore">22 / 30</b>
                    </div>
                    <div class="mch-breakdown-row">
                        <span>Attendance Score</span>
                        <b id="mchModalAttendanceScore">9 / 30</b>
                    </div>
                </div>

                <div class="mch-breakdown-divider"></div>

                <div class="mch-overall-row">
                    <span>Overall Health Score</span>
                    <b id="mchModalOverallScore">75 / 100</b>
                </div>

                <div class="mch-callout-warning">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span>Overall Health Score is &lt; 30 for 3 months, or a governance issue — this club is read-only and cannot be disbanded except by NYSC Admin.</span>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="mch-modal-footer">
            <button type="button" class="mch-btn-modal-flag" id="mchOpenFlagModalBtn">
                Flag for NYSC Admin Review
            </button>
            <div class="mch-modal-footer-right">
                <button type="button" class="mch-btn-modal-secondary" id="mchEditDetailsBtn">
                    Edit Details
                </button>
                <button type="button" class="mch-btn-modal-primary" id="mchDownloadReportBtn">
                    Download Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
     FLAG FOR NYSC ADMIN REVIEW MODAL (Figma Right Panel)
     ========================================================================== -->
<div class="mch-modal-backdrop" id="mchFlagModal" role="dialog" aria-modal="true" aria-labelledby="mchFlagModalTitle">
    <div class="mch-modal-card mch-flag-modal">
        <!-- Header -->
        <div class="mch-modal-header">
            <h2 class="mch-modal-title" id="mchFlagModalTitle">Flag Club for NYSC Admin Review</h2>
            <button type="button" class="mch-modal-close-btn" id="mchFlagCloseBtn" aria-label="Close modal">&times;</button>
        </div>

        <!-- Body -->
        <div class="mch-modal-body">
            <div class="mch-flag-info-box">
                <div class="mch-flag-info-row">
                    <span>Club</span>
                    <b id="mchFlagClubName">Maharagama Coolers</b>
                </div>
                <div class="mch-flag-info-row">
                    <span>Overall Health Score</span>
                    <b id="mchFlagScore">15 / 100</b>
                </div>
                <div class="mch-flag-info-row">
                    <span>Trigger</span>
                    <span>Score &lt; 30 for 3 consecutive months</span>
                </div>
            </div>

            <div class="mch-form-group">
                <label for="mchFlagSeverity">Severity</label>
                <select id="mchFlagSeverity" class="mch-form-select">
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>

            <div class="mch-form-group">
                <label for="mchFlagComment">Comment (required)</label>
                <textarea id="mchFlagComment" class="mch-form-textarea" placeholder="Describe the governance concern for NYSC Admin..."></textarea>
            </div>

            <div class="mch-flag-notice">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15" stroke="currentColor" stroke-width="2"/></svg>
                <span>Only NYSC Admin can disband a club — this submits a flag and notification, it does not disband anything.</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="mch-modal-footer" style="justify-content: flex-end;">
            <button type="button" class="mch-btn-modal-secondary" id="mchFlagCancelBtn">Cancel</button>
            <button type="button" class="mch-btn-modal-primary" id="mchFlagSubmitBtn">Submit Flag to NYSC Admin</button>
        </div>
    </div>
</div>

<!-- Toast notification element -->
<div class="mch-toast" id="mchToast"></div>

<script src="<?= ROOT ?>/assets/js/monitorclubhealth.js?v=<?= time() ?>"></script>
</body>
</html>
