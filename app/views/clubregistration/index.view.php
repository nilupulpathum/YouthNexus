<?php
/**
 * Club Registration Approval — Divisional Coordinator dashboard
 *
 * Uses the shared dashboard layout shell (dashboard-start / dashboard-end).
 * Only page-specific content lives here.
 */
$title           = $title ?? 'Approve Club Registration — YouthNexus';
$pageTitle       = 'Approve Club Registration';
$pageDescription = 'Review and approve new club applications';
$currentRoute    = 'clubregistration';

$unreadNotificationCount = (int)($counts['Pending'] ?? 0);

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

    <!-- ============ Stat cards ============ -->
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
            <div class="cr-stat-label">Approved Applications</div>
        </button>
        <button type="button" class="cr-stat-card" data-filter="Rejected" id="statRejected">
            <div class="cr-stat-icon rejected">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <div class="cr-stat-value"><?= (int)$counts['Rejected'] ?></div>
            <div class="cr-stat-label">Rejected Applications</div>
        </button>
    </div>

    <!-- ============ Search + filter bar ============ -->
    <div class="cr-toolbar">
        <div class="cr-search-group">
            <div class="cr-search-input-wrapper">
                <span class="cr-search-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </span>
                <input type="text" id="crSearchInput" placeholder="Search applications...">
            </div>
        </div>
        <button type="button" class="cr-filter-btn" id="crFilterBtn" aria-expanded="false">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
            Filters
        </button>
    </div>

    <!-- Filter panel: hidden until "Filters" is clicked -->
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

    <div class="cr-section-header-row" id="crSectionHeaderRow">
        <h3 class="cr-section-heading">Applications</h3>
        <button type="button" class="cr-sort-toggle-btn" id="crSortToggleBtn" data-sort="asc">Sort: Oldest First ▾</button>
    </div>

    <!-- ============ Application cards grid ============ -->
    <div class="cr-grid" id="crGrid">
        <?php if (empty($applications)): ?>
            <div class="cr-empty" style="grid-column: 1 / -1;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                <p>No pending applications right now.</p>
            </div>
        <?php else: ?>
            <?php foreach ($applications as $app):
                $submittedDaysAgo = (int)((time() - strtotime($app->submitted_at)) / 86400);
                $isWaitingLong = $submittedDaysAgo > 7;
            ?>
            <div class="cr-card"
                 data-name="<?= htmlspecialchars(strtolower($app->club_name)) ?>"
                 data-proposer="<?= htmlspecialchars(strtolower($app->proposer_name)) ?>"
                 data-submitted="<?= strtotime($app->submitted_at) ?>"
                 data-status="Pending"
                 data-docstatus="<?= $app->documents_complete ? 'complete' : 'incomplete' ?>">
                <div class="cr-card-top">
                    <?php if ($app->documents_complete): ?>
                        <div class="cr-card-icon complete" title="Documents Complete">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><polyline points="16 13 12 17 9 14"/></svg>
                        </div>
                    <?php else: ?>
                        <div class="cr-card-icon incomplete" title="Documents Incomplete">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
                        </div>
                    <?php endif; ?>
                    <span class="cr-badge pending">Pending</span>
                </div>

                <div class="cr-card-date<?= $isWaitingLong ? ' cr-waiting-long' : '' ?>">
                    SUBMITTED <?= strtoupper(date('M j, Y', strtotime($app->submitted_at))) ?><?= $isWaitingLong ? ' · ' . $submittedDaysAgo . ' DAYS AGO' : '' ?>
                </div>

                <div class="cr-card-name"><?= htmlspecialchars($app->club_name) ?></div>

                <div class="cr-card-proposer">
                    Proposer: <?= htmlspecialchars($app->proposer_name) ?><?= (!empty($app->proposer_nic) && !empty($app->proposer_eligible)) ? ' — NIC Verified' : '' ?>
                </div>

                <div class="cr-card-docs <?= $app->documents_complete ? 'complete' : 'incomplete' ?>">
                    Documents: <?= $app->documents_complete ? 'Complete' : htmlspecialchars($app->missing_summary) ?>
                </div>

                <div class="cr-card-footer">
                    <button type="button" class="cr-btn cr-review-btn" data-id="<?= (int)$app->application_id ?>">Review</button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<!-- ============ Review Modal (populated via JS) ============ -->
<div class="cr-modal-backdrop" id="crModalBackdrop">
    <div class="cr-modal" id="crModalContent">
        <!-- filled dynamically by clubregistration.js -->
    </div>
</div>

<!-- ============ Dual-Sided NIC Verification Modal ============ -->
<div class="cr-modal-backdrop cr-submodal-backdrop" id="crNicModalBackdrop">
    <div class="cr-modal cr-nic-modal" id="crNicModalContent">
        <!-- filled dynamically -->
    </div>
</div>

<!-- ============ Media Gallery / Lightbox Modal ============ -->
<div class="cr-modal-backdrop cr-submodal-backdrop" id="crGalleryModalBackdrop">
    <div class="cr-modal cr-gallery-modal" id="crGalleryModalContent">
        <!-- filled dynamically -->
    </div>
</div>

<div class="cr-toast" id="crToast"></div>

<input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrf_token) ?>">
<script>var ROOT_URL = "<?= ROOT ?>"; var COORDINATOR_NAME = "<?= htmlspecialchars($_SESSION['user_name'] ?? 'R. Perera') ?>";</script>
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/clubregistration.css">
<script src="<?= ROOT ?>/assets/js/clubregistration.js?v=<?= time() ?>"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
