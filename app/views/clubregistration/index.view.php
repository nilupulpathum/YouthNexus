<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/clubregistration.css">
</head>
<body class="dashboard">
<div class="db-app">

    <!-- ============ Sidebar ============ -->
    <aside class="db-sidebar">
        <div class="db-brand">
            <div class="db-brand-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/></svg>
            </div>
            <div class="db-brand-text">
                <b>YouthNexus</b>
                <span>Div. Coordinator</span>
            </div>
        </div>

        <!-- TODO(icons): nav icons below are PLACEHOLDERS — no icon set has
             been decided for this project yet. Swap for the real icon set
             once the team agrees on one; the class names/structure stay
             the same either way. -->
        <nav class="db-nav">
            <a href="<?= ROOT ?>/dashboard" class="db-nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="<?= ROOT ?>/clubregistration" class="db-nav-link active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                Approve Registration
                <span class="db-nav-badge"><?= (int)$counts['Pending'] ?></span>
            </a>
            <a href="<?= ROOT ?>/monitorclubhealth" class="db-nav-link">
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
        <!-- /TODO(icons) -->

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
                <h1>Approve Club Registration</h1>
                <p>Review and approve new club applications</p>
            </div>
            <div class="db-topbar-right">
                <div class="db-search-top">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" placeholder="Search...">
                </div>
                <button class="db-icon-btn" title="Notifications">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="db-badge-dot" id="notifCount">3</span>
                </button>
                <div class="db-topbar-avatar"><?= htmlspecialchars($_SESSION['user_initials'] ?? 'RP') ?></div>
            </div>
        </header>

        <main class="db-content">

            <!-- Stat cards, now clickable filters -->
            <div class="cr-stats">
                <button type="button" class="cr-stat-card is-active" data-filter="Pending" id="statPending">
                    <div class="cr-stat-icon pending">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#8a5b06" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                    <div class="cr-stat-value"><?= (int)$counts['Pending'] ?></div>
                    <div class="cr-stat-label">Pending Applications</div>
                </button>
                <button type="button" class="cr-stat-card" data-filter="Approved" id="statApproved">
                    <div class="cr-stat-icon approved">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#157a45" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div class="cr-stat-value"><?= (int)$counts['Approved'] ?></div>
                    <div class="cr-stat-label">Approved This Term</div>
                </button>
            </div>

            <!-- Search + filter bar -->
            <div class="cr-toolbar">
                <div class="cr-search">
                    <span class="cr-search-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input type="text" id="crSearchInput" placeholder="Search applications...">
                </div>
                <button type="button" class="cr-filter-btn" id="crFilterBtn" aria-expanded="false">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                    Filters
                </button>
            </div>

            <!-- Filter panel: hidden until "Filters" is clicked (matches the
                 inline expandable pattern used elsewhere in the app, e.g.
                 Manage Reports / Monitor Club Health filter bars) -->
            <div class="cr-filter-panel" id="crFilterPanel">
                <div class="cr-filter-field">
                    <label for="crFilterStatus">Status</label>
                    <select id="crFilterStatus">
                        <option value="">All Statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
                <div class="cr-filter-field">
                    <label for="crFilterDocs">Document Completeness</label>
                    <select id="crFilterDocs">
                        <option value="">All</option>
                        <option value="complete">Complete</option>
                        <option value="incomplete">Incomplete</option>
                    </select>
                </div>
                <div class="cr-filter-actions">
                    <button type="button" class="cr-btn" id="crClearFilterBtn">Clear Filter</button>
                    <button type="button" class="cr-btn cr-btn-primary" id="crAddFilterBtn">Add Filter</button>
                </div>
            </div>

            <h3 class="cr-section-heading">Applications</h3>

            <?php if (empty($applications)): ?>
                <div class="cr-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                    <p>No pending applications right now.</p>
                </div>
            <?php else: ?>
                <div class="cr-grid" id="crGrid">
                    <?php foreach ($applications as $app):
                        $submittedDaysAgo = (int)((time() - strtotime($app->submitted_at)) / 86400);
                        $isWaitingLong = $submittedDaysAgo > 7;
                    ?>
                    <div class="cr-card"
                         data-name="<?= htmlspecialchars(strtolower($app->club_name)) ?>"
                         data-proposer="<?= htmlspecialchars(strtolower($app->proposer_name)) ?>"
                         data-status="Pending"
                         data-docstatus="<?= $app->documents_complete ? 'complete' : 'incomplete' ?>">
                        <div class="cr-card-top">
                            <div class="cr-card-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#157a45" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                            </div>
                            <span class="cr-badge pending">Pending</span>
                        </div>

                        <div class="cr-card-date<?= $isWaitingLong ? ' cr-waiting-long' : '' ?>">
                            Submitted <?= date('M j, Y', strtotime($app->submitted_at)) ?><?= $isWaitingLong ? ' · ' . $submittedDaysAgo . ' days ago' : '' ?>
                        </div>

                        <div class="cr-card-name"><?= htmlspecialchars($app->club_name) ?></div>

                        <div class="cr-card-proposer">
                            Proposer: <?= htmlspecialchars($app->proposer_name) ?>
                        </div>

                        <span class="cr-doc-status <?= $app->documents_complete ? 'complete' : 'incomplete' ?>">
                            <?php if ($app->documents_complete): ?>
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                            <?php else: ?>
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                            <?php endif; ?>
                            <?= htmlspecialchars($app->missing_summary) ?>
                        </span>

                        <div class="cr-card-footer">
                            <button type="button" class="cr-btn cr-review-btn" data-id="<?= (int)$app->application_id ?>">Review</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<!-- ============ Review Modal (populated via JS) ============ -->
<div class="cr-modal-backdrop" id="crModalBackdrop">
    <div class="cr-modal" id="crModalContent">
        <!-- filled dynamically by clubregistration.js -->
    </div>
</div>

<div class="cr-toast" id="crToast"></div>

<input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrf_token) ?>">
<script>var ROOT_URL = "<?= ROOT ?>";</script>
<script src="<?= ROOT ?>/assets/js/clubregistration.js"></script>
</body>
</html>
