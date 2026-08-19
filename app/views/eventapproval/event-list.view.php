<?php
$coordinatorName = $_SESSION['user_name'] ?? 'R. Perera';
$coordinatorInit = $_SESSION['user_initials'] ?? 'RP';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'Approve Events') ?></title>
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/dashboard.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/eventapproval.css?v=<?= time() ?>">
</head>
<body class="dashboard">

<div class="db-app">

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
            <a href="<?= ROOT ?>/clubregistrationapproval" class="db-nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Approve Registration
            </a>
            <a href="<?= ROOT ?>/eventapproval" class="db-nav-link active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Approve Events
                <span class="db-nav-badge" id="navPendingBadge" style="<?= (int)($counts['Pending'] ?? 0) > 0 ? '' : 'display: none;' ?>"><?= (int)($counts['Pending'] ?? 0) ?></span>
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

        <div class="db-sidebar-footer">
            <div class="db-avatar"><?= htmlspecialchars($coordinatorInit) ?></div>
            <div class="db-who">
                <b><?= htmlspecialchars($coordinatorName) ?></b>
                <span>Div. Coordinator</span>
            </div>
        </div>
    </aside>

    <div class="db-main">
        <header class="db-topbar">
            <div>
                <h1>Approve Events</h1>
                <p>Review club-level events submitted within your division</p>
            </div>
            <div class="db-topbar-right">
                <div class="db-search-top">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" placeholder="Search...">
                </div>
                <div style="position: relative;">
                    <button class="db-icon-btn" id="notifBellBtn" title="<?= (int)($counts['Pending'] ?? 0) > 0 ? (int)($counts['Pending'] ?? 0) . ' events awaiting your review' : 'No pending events' ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span class="db-badge-dot" id="notifCount" style="<?= (int)($counts['Pending'] ?? 0) > 0 ? '' : 'display: none;' ?>"><?= (int)($counts['Pending'] ?? 0) ?></span>
                    </button>
                    <!-- Dropdown -->
                    <div id="notifDropdown" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 8px; width: 280px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000; padding: 12px;">
                        <h4 style="margin: 0 0 8px 0; font-size: 13px; color: #111827; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">Notifications</h4>
                        <?php if ((int)($counts['Pending'] ?? 0) > 0): ?>
                            <a href="#" id="notifDropdownLink" style="display: block; font-size: 13px; color: #2563eb; text-decoration: none; padding: 8px; border-radius: 6px; background: #eff6ff;">
                                <?= (int)$counts['Pending'] ?> events awaiting your review
                            </a>
                        <?php else: ?>
                            <p style="margin: 0; font-size: 13px; color: #6b7280; padding: 8px;">No new notifications</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="db-topbar-avatar"><?= htmlspecialchars($coordinatorInit) ?></div>
            </div>
        </header>

        <main class="db-content">

            <div class="ea-stats">
                <button type="button" class="ea-stat-card is-active" data-filter="Pending" id="statPending">
                    <div class="ea-stat-icon pending">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="ea-stat-value"><?= (int)($counts['Pending'] ?? 0) ?></div>
                    <div class="ea-stat-label">Awaiting Your Review</div>
                </button>
                <button type="button" class="ea-stat-card" data-filter="Approved" id="statApproved">
                    <div class="ea-stat-icon approved">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="ea-stat-value"><?= (int)($counts['Approved'] ?? 0) ?></div>
                    <div class="ea-stat-label">Approved Events</div>
                </button>
                <button type="button" class="ea-stat-card" data-filter="Rejected" id="statRejected">
                    <div class="ea-stat-icon rejected">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    </div>
                    <div class="ea-stat-value"><?= (int)($counts['Rejected'] ?? 0) ?></div>
                    <div class="ea-stat-label">Rejected Events</div>
                </button>
            </div>

            <div class="ea-list" id="eaPendingList">
                <?php if (empty($pendingEvents)): ?>
                    <div class="ea-empty-state">
                        <p>No club events are currently awaiting your approval.</p>
                    </div>
                <?php else: foreach ($pendingEvents as $event): ?>
                    <div class="ea-card" data-event-id="<?= (int)$event->event_id ?>">
                        <div class="ea-card-top">
                            <span class="ea-badge club">Club Event</span>
                            <span class="ea-badge pending">Pending Approval</span>
                        </div>
                        <h3 class="ea-card-title"><?= htmlspecialchars($event->title) ?></h3>
                        <p class="ea-card-club">
                            <?= htmlspecialchars($event->club_name) ?>
                            <small class="ea-club-code"><?= htmlspecialchars($event->club_code) ?></small>
                        </p>
                        <div class="ea-card-meta">
                            <span><?= date('M j, Y \a\t g:i A', strtotime($event->start_datetime)) ?></span>
                            <span><?= htmlspecialchars($event->location) ?></span>
                        </div>
                        <div class="ea-card-footer">
                            <span class="ea-card-submitter">Submitted by <?= htmlspecialchars($event->creator_name) ?> (<?= htmlspecialchars($event->creator_role) ?>)</span>
                            <button type="button" class="ea-btn ea-btn-primary ea-btn-review" data-event-id="<?= (int)$event->event_id ?>">Review</button>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

        </main>
    </div>
</div>

<div class="ea-modal-backdrop" id="eaReviewModal">
    <div class="ea-modal">
        <div class="ea-modal-header">
            <h3 id="eaModalEventTitle">Event Review</h3>
            <button type="button" class="ea-modal-close" id="eaModalClose" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="ea-modal-body" id="eaModalBody">
            <!-- Populated by JS -->
        </div>

        <div class="ea-decision-panel">
            <div class="ea-section-header">
                <h4 class="ea-section-title">FINAL REVIEW DECISION</h4>
            </div>
            <div class="ea-decision-fields-row">
                <div class="ea-field">
                    <label>REVIEW RESULT</label>
                    <select id="eaReviewResultSelect">
                        <option value="approve">Approve Event</option>
                        <option value="reject">Reject Event</option>
                    </select>
                </div>
                <div class="ea-field">
                    <label>REVIEWED BY</label>
                    <input type="text" readonly value="<?= htmlspecialchars($coordinatorName) ?> — Divisional Coordinator" class="ea-readonly-input">
                </div>
            </div>
            <div class="ea-decision-remarks-section">
                <label>OFFICIAL REVIEW REMARKS (REQUIRED IF REJECTING)</label>
                <textarea id="eaRemarks" placeholder="Provide the reason for this decision..."></textarea>
            </div>
            <div class="ea-decision-impact-alert approve" id="eaImpactAlert">
                <div class="ea-impact-text-content">
                    <strong>IMPACT OF APPROVAL</strong>
                    <p>Approving this event will publish it to the division's event calendar and notify the submitting club. This event will then be visible to the Divisional Secretary and eligible for attendance tracking once it occurs.</p>
                </div>
            </div>
            <div class="ea-decision-footer-bar">
                <button type="button" class="ea-btn-cancel-link" id="eaCancelReviewBtn">Cancel</button>
                <button type="button" class="ea-btn ea-btn-submit-decision" id="eaConfirmSubmitBtn">Confirm &amp; Submit Decision</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.ROOT = "<?= ROOT ?>";
    window.CSRF_TOKEN = <?= json_encode($csrf_token ?? '') ?>;
</script>
<script src="<?= ROOT ?>/assets/js/eventapproval.js?v=<?= time() ?>"></script>
</body>
</html>
