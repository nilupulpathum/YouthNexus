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
$events                   = $events ?? [];
$initialStatus            = $initialStatus ?? 'Approved';
$eventTypes               = [];

foreach ($events as $event) {
    $eventType = trim((string)($event->event_type ?? ''));
    if ($eventType !== '') {
        $eventTypes[$eventType] = $eventType;
    }
}
natcasesort($eventTypes);

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

            <!-- ============================================================
                 Stat cards — ea-stats/ea-stat-card/ea-stat-icon
                 Sized to match cr-stats exactly:
                   flex row, max-width 620px, 32×32 icons, not a 3-col grid
                 ============================================================ -->
            <div class="ea-stats">
                <button type="button" class="ea-stat-card" data-filter="Pending" id="statPending" aria-pressed="false">
                    <div class="ea-stat-icon pending">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#8a5b06" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                    <div class="ea-stat-value"><?= (int)($counts['Pending'] ?? 0) ?></div>
                    <div class="ea-stat-label">Awaiting Your Review</div>
                </button>
                <button type="button" class="ea-stat-card is-active" data-filter="Approved" id="statApproved" aria-pressed="true">
                    <div class="ea-stat-icon approved">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#157a45" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div class="ea-stat-value"><?= (int)($counts['Approved'] ?? 0) ?></div>
                    <div class="ea-stat-label">Approved Events</div>
                </button>
                <button type="button" class="ea-stat-card" data-filter="Rejected" id="statRejected" aria-pressed="false">
                    <div class="ea-stat-icon rejected">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </div>
                    <div class="ea-stat-value"><?= (int)($counts['Rejected'] ?? 0) ?></div>
                    <div class="ea-stat-label">Rejected Events</div>
                </button>
            </div>

            <!-- ============================================================
                 Search and filters — follows Approve Registration toolbar
                 ============================================================ -->
            <div class="ea-toolbar">
                <div class="ea-search-group">
                    <span class="ea-search-icon" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <label class="visually-hidden" for="eaSearchInput">Search events</label>
                    <input type="search" id="eaSearchInput" placeholder="Search events by title, club or location..." autocomplete="off">
                </div>
                <button type="button" class="ea-filter-btn" id="eaFilterBtn" aria-expanded="false" aria-controls="eaFilterPanel">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                    Filters
                    <span class="ea-filter-count" id="eaFilterCount" hidden>0</span>
                </button>
            </div>

            <div class="ea-filter-panel" id="eaFilterPanel">
                <div class="ea-filter-field">
                    <label for="eaFilterType">Event Type</label>
                    <select id="eaFilterType">
                        <option value="">All Types</option>
                        <?php foreach ($eventTypes as $eventType): ?>
                            <option value="<?= htmlspecialchars(strtolower($eventType)) ?>"><?= htmlspecialchars($eventType) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ea-filter-field">
                    <label for="eaFilterSchedule">Schedule</label>
                    <select id="eaFilterSchedule">
                        <option value="">Any Date</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="past">Past</option>
                    </select>
                </div>
                <div class="ea-filter-actions">
                    <button type="button" class="ea-btn ea-btn-secondary" id="eaClearFiltersBtn">Clear Filters</button>
                </div>
            </div>

            <!-- ============================================================
                 Event List — Approved is the default status
                 ============================================================ -->
            <div class="ea-list" id="eaEventList" data-status="<?= htmlspecialchars($initialStatus) ?>">
                <?php if (empty($events)): ?>
                    <div class="ea-empty-state">
                        <p>No approved club events were found in this division.</p>
                    </div>
                <?php else: foreach ($events as $event): ?>
                    <?php
                        $status = (string)($event->status ?? 'Approved');
                        $badgeClass = $status === 'PendingApproval' ? 'pending' : strtolower($status);
                        $badgeLabel = $status === 'PendingApproval' ? 'Pending Approval' : $status;
                        $clubName = $event->club_name ?? $event->organizer_club_name ?? '';
                        $clubCode = $event->club_code ?? $event->organizer_club_code ?? '';
                    ?>
                    <div class="ea-card" data-event-id="<?= (int)$event->event_id ?>"
                         data-detail-url="<?= ROOT ?>/eventapproval/detail/<?= (int)$event->event_id ?>"
                         data-title="<?= htmlspecialchars(strtolower((string)$event->title)) ?>"
                         data-club="<?= htmlspecialchars(strtolower((string)$clubName)) ?>"
                         data-location="<?= htmlspecialchars(strtolower((string)($event->location ?? ''))) ?>"
                         data-type="<?= htmlspecialchars(strtolower((string)($event->event_type ?? ''))) ?>"
                         data-start="<?= htmlspecialchars((string)($event->start_datetime ?? '')) ?>"
                         role="link" tabindex="0">
                        <div class="ea-card-top">
                            <span class="ea-badge club">Club Event</span>
                            <span class="ea-badge <?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($badgeLabel) ?></span>
                        </div>
                        <h3 class="ea-card-title"><?= htmlspecialchars($event->title) ?></h3>
                        <p class="ea-card-club">
                            <?= htmlspecialchars($clubName) ?>
                            <small class="ea-club-code"><?= htmlspecialchars($clubCode) ?></small>
                        </p>
                        <div class="ea-card-meta">
                            <span><?= date('M j, Y \a\t g:i A', strtotime($event->start_datetime)) ?></span>
                            <span><?= htmlspecialchars($event->location ?? '—') ?></span>
                        </div>
                        <div class="ea-card-footer">
                            <span class="ea-card-submitter">Submitted by <?= htmlspecialchars($event->creator_name ?? '—') ?> (<?= htmlspecialchars($event->creator_role ?? '—') ?>)</span>
                            <div class="ea-card-actions">
                                <a href="<?= ROOT ?>/eventapproval/detail/<?= (int)$event->event_id ?>" class="ea-btn ea-btn-secondary">View Details</a>
                                <?php if ($status === 'PendingApproval'): ?>
                                    <button type="button" class="ea-btn ea-btn-primary ea-btn-review" data-event-id="<?= (int)$event->event_id ?>">Review</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
                <div class="ea-empty-state" id="eaNoMatches" hidden>
                    <p>No events match your current search and filters.</p>
                </div>
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

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/eventapproval.css?v=<?= time() ?>">
<script>
    window.ROOT       = "<?= ROOT ?>";
    window.CSRF_TOKEN = <?= json_encode($csrf_token ?? '') ?>;
</script>
<script src="<?= ROOT ?>/assets/js/eventapproval.js?v=<?= time() ?>"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
