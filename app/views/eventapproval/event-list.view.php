<?php
/**
 * Event Approval — Divisional Coordinator dashboard
 *
 * Uses the shared dashboard layout shell (dashboard-start / dashboard-end).
 * Matches the pattern established by app/views/clubregistration/index.view.php.
 */
$coordinatorName = $_SESSION['user_name'] ?? 'R. Perera';

$title                   = 'Approve Events — YouthNexus';
$pageTitle               = 'Approve Events';
$pageDescription         = 'Review club-level events submitted within your division';
$currentRoute            = 'eventapproval';
$unreadNotificationCount = (int)($counts['Pending'] ?? 0);

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

            <!-- ============================================================
                 Stat cards — ea-stats/ea-stat-card/ea-stat-icon
                 Sized to match cr-stats exactly:
                   flex row, max-width 620px, 32×32 icons, not a 3-col grid
                 ============================================================ -->
            <div class="ea-stats">
                <button type="button" class="ea-stat-card is-active" data-filter="Pending" id="statPending">
                    <div class="ea-stat-icon pending">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#8a5b06" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                    <div class="ea-stat-value"><?= (int)($counts['Pending'] ?? 0) ?></div>
                    <div class="ea-stat-label">Awaiting Your Review</div>
                </button>
                <button type="button" class="ea-stat-card" data-filter="Approved" id="statApproved">
                    <div class="ea-stat-icon approved">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#157a45" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div class="ea-stat-value"><?= (int)($counts['Approved'] ?? 0) ?></div>
                    <div class="ea-stat-label">Approved Events</div>
                </button>
                <button type="button" class="ea-stat-card" data-filter="Rejected" id="statRejected">
                    <div class="ea-stat-icon rejected">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </div>
                    <div class="ea-stat-value"><?= (int)($counts['Rejected'] ?? 0) ?></div>
                    <div class="ea-stat-label">Rejected Events</div>
                </button>
            </div>

            <!-- ============================================================
                 Toolbar & Filters (client-side matching cr- / mch- patterns)
                 ============================================================ -->
            <div class="ea-toolbar">
                <div class="ea-toolbar-left">
                    <div class="ea-search-box">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="text" id="eaSearchInput" placeholder="Search events by title or club name...">
                    </div>
                </div>
                <button type="button" class="ea-filter-btn" id="eaFilterBtn" aria-expanded="false">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                    Filters
                    <span class="ea-filter-count" id="eaFilterCount" hidden>0</span>
                </button>
            </div>

            <!-- Filter panel: hidden until "Filters" is clicked -->
            <div class="ea-filter-panel" id="eaFilterPanel">
                <div class="ea-filter-field">
                    <label for="eaFilterType">Event Type</label>
                    <select id="eaFilterType">
                        <option value="">All Types</option>
                        <option value="Practice">Practice</option>
                        <option value="Match">Match</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Tournament">Tournament</option>
                        <option value="Social">Social</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="ea-filter-field">
                    <label for="eaFilterDateFrom">Date From</label>
                    <input type="date" id="eaFilterDateFrom">
                </div>
                <div class="ea-filter-field">
                    <label for="eaFilterDateTo">Date To</label>
                    <input type="date" id="eaFilterDateTo">
                </div>
                <div class="ea-filter-actions">
                    <button type="button" class="ea-btn" id="eaClearFilterBtn">Clear Filter</button>
                    <button type="button" class="ea-btn ea-btn-primary" id="eaAddFilterBtn">Add Filter</button>
                </div>
            </div>

            <!-- ============================================================
                 Pending Events List
                 ============================================================ -->
            <div class="ea-list" id="eaPendingList">
                <?php if (empty($pendingEvents)): ?>
                    <div class="ea-empty-state">
                        <p>No club events are currently awaiting your approval.</p>
                    </div>
                <?php else: foreach ($pendingEvents as $event): ?>
                    <div class="ea-card" 
                         data-event-id="<?= (int)$event->event_id ?>"
                         data-title="<?= htmlspecialchars(strtolower($event->title), ENT_QUOTES, 'UTF-8') ?>"
                         data-club="<?= htmlspecialchars(strtolower($event->organizer_club_name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                         data-type="<?= htmlspecialchars($event->event_type ?? '', ENT_QUOTES, 'UTF-8') ?>"
                         data-start="<?= htmlspecialchars($event->start_datetime, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="ea-card-top">
                            <span class="ea-badge club">Club Event</span>
                            <span class="ea-badge pending">Pending Approval</span>
                        </div>
                        <h3 class="ea-card-title"><?= htmlspecialchars($event->title) ?></h3>
                        <p class="ea-card-club">
                            <?= htmlspecialchars($event->organizer_club_name ?? '') ?>
                            <small class="ea-club-code"><?= htmlspecialchars($event->organizer_club_code ?? '') ?></small>
                        </p>
                        <div class="ea-card-meta">
                            <span><?= date('M j, Y \a\t g:i A', strtotime($event->start_datetime)) ?></span>
                            <span><?= htmlspecialchars($event->location ?? '—') ?></span>
                        </div>
                        <div class="ea-card-footer">
                            <span class="ea-card-submitter">Submitted by <?= htmlspecialchars($event->creator_name ?? '—') ?> (<?= htmlspecialchars($event->creator_role ?? '—') ?>)</span>
                            <button type="button" class="ea-btn-review" data-event-id="<?= (int)$event->event_id ?>">
                                Review
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

<!-- ============================================================
     Review / Decision Modal
     Placed before dashboard-end.view.php so it renders inside <body>.
     Mirrors cr-decision-panel from ClubRegistrationApproval.
     ============================================================ -->
<div class="ea-modal-backdrop" id="eaReviewModal">
    <div class="ea-modal">
        <div class="ea-modal-header">
            <h3 id="eaModalEventTitle">Event Review</h3>
            <button type="button" class="ea-modal-close" id="eaModalClose" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="ea-modal-scrollable">
            <div class="ea-modal-body" id="eaModalBody">
                <!-- Populated by JS (eventapproval.js) from /eventapproval/review/:id -->
            </div>

            <!-- Decision panel — mirrors cr-decision-panel exactly -->
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
</div>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/eventapproval.css?v=<?= time() ?>">
<script>
    window.ROOT       = "<?= ROOT ?>";
    window.CSRF_TOKEN = <?= json_encode($csrf_token ?? '') ?>;
</script>
<script src="<?= ROOT ?>/assets/js/eventapproval.js?v=<?= time() ?>"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
