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

                            // Sourced data with honest fallbacks
                            $descText = !empty(trim($club->description ?? '')) ? trim($club->description) : 'No description provided.';
                            $estDateText = !empty($club->registration_date) ? date('F Y', strtotime($club->registration_date)) : 'Not available';
                            $clubCommittee = $committees[$club->club_id] ?? [];

                            // Circular initials avatar calculation
                            $words = explode(' ', trim($club->club_name));
                            $initials = '';
                            foreach ($words as $w) {
                                if (!empty($w) && ctype_alnum($w[0])) {
                                    $initials .= strtoupper($w[0]);
                                }
                            }
                            $clubInitials = substr($initials, 0, 2) ?: 'CL';
                        ?>
                        <div class="mch-card <?= $statusKey ?>"
                             data-id="<?= (int)$club->club_id ?>"
                             data-name="<?= htmlspecialchars($club->club_name, ENT_QUOTES) ?>"
                             data-code="<?= htmlspecialchars($club->club_code ?? '', ENT_QUOTES) ?>"
                             data-score="<?= $score ?>"
                             data-status="<?= htmlspecialchars($healthStatus, ENT_QUOTES) ?>"
                             data-flagged="<?= $isFlagged ?>"
                             data-members="<?= $membersCount ?>"
                             data-desc="<?= htmlspecialchars($descText, ENT_QUOTES) ?>"
                             data-division="<?= htmlspecialchars($club->division_name ?? $divisionName, ENT_QUOTES) ?>"
                             data-date="<?= htmlspecialchars($estDateText, ENT_QUOTES) ?>"
                             data-committee='<?= htmlspecialchars(json_encode($clubCommittee), ENT_QUOTES) ?>'>

                            <!-- 1. Circular Avatar & 2. Flagged Badge -->
                            <div class="mch-card-avatar-wrap">
                                <div class="mch-card-avatar <?= $statusKey ?>">
                                    <?= htmlspecialchars($clubInitials) ?>
                                </div>
                                <?php if ($isFlagged === '1'): ?>
                                    <div class="mch-card-flag-badge" title="Flagged Club">
                                        <svg viewBox="0 0 24 24"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/></svg>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- 3. Score Row -->
                            <div class="mch-card-score-row">
                                <span class="mch-card-score-num <?= $statusKey ?>"><?= $formattedScore ?></span>
                                <span class="mch-card-score-denom">/100</span>
                            </div>

                            <!-- 4. Status Badge -->
                            <div class="mch-card-badge-wrap">
                                <span class="mch-card-badge <?= $statusKey ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </div>

                            <!-- 5. Club Name -->
                            <h3 class="mch-card-club-name"><?= htmlspecialchars($club->club_name) ?></h3>

                            <!-- 6. Location & Member Count -->
                            <p class="mch-card-subline"><?= $locationText ?> • <?= $membersText ?></p>

                            <!-- 7. Bottom View Details Button -->
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
     CLUB HEALTH DETAIL MODAL
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
                    <h2 class="mch-modal-title" id="mchModalTitle">Club Name</h2>
                    <div class="mch-modal-sub-badges">
                        <span class="mch-modal-code-tag" id="mchModalCode">CLB-000000</span>
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
                    <div class="mch-section-heading-sm">About</div>
                    <p class="mch-about-text" id="mchModalDesc">
                        No description provided.
                    </p>

                    <div class="mch-meta-row">
                        <!-- Issue #1 Fixed: Removed hardcoded Category field -->
                        <div class="mch-meta-field">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>Location: <b id="mchModalLocation">Division</b></span>
                        </div>
                        <div class="mch-meta-field">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span>Established: <b id="mchModalEstDate">Not available</b></span>
                        </div>
                    </div>

                    <!-- Issue #5 Fixed: Dynamic Executive Committee list -->
                    <div style="margin-top: 24px;">
                        <div class="mch-section-heading-sm">Executive Committee</div>
                        <div class="mch-exec-list" id="mchModalExecList">
                            <!-- Populated dynamically via JS from real User records -->
                        </div>
                    </div>
                </div>

                <!-- Right Column: Performance Overview + Recent Events -->
                <div>
                    <div class="mch-section-heading-sm">Performance Overview</div>
                    <div class="mch-perf-grid">
                        <div class="mch-perf-card">
                            <div class="mch-perf-card-info">
                                <span>Active Members</span>
                                <b id="mchModalActiveMembers">0</b>
                            </div>
                            <div class="mch-perf-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                        </div>

                        <!-- Issue #4 Fixed: Avg. Attendance stat card removed — pending Attendance table in future phase -->
                    </div>

                    <!-- Issue #6 Fixed: Recent Events placeholder container (no fake rows) -->
                    <div class="mch-events-header">
                        <div class="mch-section-heading-sm" style="margin-bottom: 0;">Recent Events</div>
                    </div>
                    <div class="mch-pending-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <p>Event tracking will be available once the Events module is implemented.</p>
                    </div>
                </div>
            </div>

            <!-- Issue #7 Fixed: Health Detail Breakdown (pending state, real overall score) -->
            <div class="mch-health-breakdown">
                <div class="mch-section-heading-sm">Health Detail</div>
                
                <div class="mch-pending-box" style="margin-bottom: 14px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                    <p>Not yet calculated — pending Event/Finance/Attendance modules</p>
                </div>

                <div class="mch-overall-row">
                    <span>Overall Health Score</span>
                    <b id="mchModalOverallScore">0 / 100</b>
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
     FLAG FOR NYSC ADMIN REVIEW MODAL
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
