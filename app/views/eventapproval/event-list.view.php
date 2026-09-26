<?php
/**
 * Event Approval — Divisional Coordinator dashboard
 *
 * Uses the shared dashboard layout shell (dashboard-start / dashboard-end).
 * Matches the pattern established by app/views/clubregistrationapproval/application-list.view.php.
 */
$coordinatorName = $_SESSION['user_name'] ?? 'Divisional Coordinator';

$title                   = 'Approve Events — YouthNexus';
$pageTitle               = 'Approve Events';
$pageDescription         = 'Review club-level events submitted within your division';
$currentRoute            = 'eventapproval';
$unreadNotificationCount = (int)($counts['Pending'] ?? 0);
$pageStyles              = [
    ROOT . '/assets/css/eventapproval.css',
    ROOT . '/assets/css/divisional-summary-standard.css',
];
$pageScripts             = [ROOT . '/assets/js/eventapproval.js'];

require_once __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

            <!-- ============================================================
                 Stat cards — ea-stats/ea-stat-card/ea-stat-icon
                 ============================================================ -->
            <div class="ea-stats">
                <button type="button" class="yn-stat-card ea-stat-card" data-filter="Pending" id="statPending">
                    <div class="ea-stat-icon pending">
                        <?= yn_icon('clock') ?>
                    </div>
                    <span class="ea-stat-content"><span class="ea-stat-value"><?= (int)($counts['Pending'] ?? 0) ?></span><span class="ea-stat-label">Awaiting Your Review</span></span>
                </button>
                <button type="button" class="yn-stat-card ea-stat-card is-active" data-filter="Approved" id="statApproved">
                    <div class="ea-stat-icon approved">
                        <?= yn_icon('check') ?>
                    </div>
                    <span class="ea-stat-content"><span class="ea-stat-value"><?= (int)($counts['Approved'] ?? 0) ?></span><span class="ea-stat-label">Approved Events</span></span>
                </button>
                <button type="button" class="yn-stat-card ea-stat-card" data-filter="Rejected" id="statRejected">
                    <div class="ea-stat-icon rejected">
                        <?= yn_icon('close') ?>
                    </div>
                    <span class="ea-stat-content"><span class="ea-stat-value"><?= (int)($counts['Rejected'] ?? 0) ?></span><span class="ea-stat-label">Rejected Events</span></span>
                </button>
            </div>

            <div class="ea-toolbar" role="search" aria-label="Find events for review">
                <label class="yn-search ea-toolbar__search">
                    <span class="yn-search__icon" aria-hidden="true"><?= yn_icon('search') ?></span>
                    <input type="search" id="eaSearchInput" placeholder="Search events, clubs or locations..." aria-label="Search events" autocomplete="off">
                </label>
                <button type="button" class="yn-btn yn-btn--secondary yn-filter-toggle" id="eaFilterBtn" aria-controls="eaFilterPanel" aria-expanded="false"><?= yn_icon('filter') ?> Filters</button>
            </div>
            <div class="yn-filter-panel ea-filter-panel" id="eaFilterPanel" hidden>
                <h2 class="yn-filter-heading">Advanced Filters for Event Approvals</h2>
                <div class="yn-filter-fields">
                    <div class="yn-filter-field">
                        <label for="eaFilterLevel">Organizer</label>
                        <select id="eaFilterLevel"><option value="all">All organizers</option><option value="club">Club events</option><option value="division">Divisional events</option></select>
                    </div>
                    <div class="yn-filter-field">
                        <label for="eaFilterDateFrom">Event date from</label>
                        <input type="date" id="eaFilterDateFrom">
                    </div>
                    <div class="yn-filter-field">
                        <label for="eaFilterDateTo">Event date to</label>
                        <input type="date" id="eaFilterDateTo">
                    </div>
                </div>
                <div class="yn-filter-actions">
                    <button type="button" class="yn-btn yn-btn--secondary yn-filter-clear" id="eaClearFilters">Clear filters</button>
                    <button type="button" class="yn-btn yn-btn--primary yn-filter-apply" id="eaApplyFilters">Apply filters</button>
                </div>
            </div>

            <!-- ============================================================
                 Pending Events List
                 ============================================================ -->
            <div class="ea-list" id="eaPendingList">
                <?php if (empty($pendingEvents)): ?>
                    <div class="ea-empty-state">
                        <p>No events are currently awaiting your approval in this division.</p>
                    </div>
                <?php else: foreach ($pendingEvents as $event): ?>
                    <?php 
                        $isDivisionEvent = !empty($event->organizer_division_id);
                        $badgeTypeClass  = $isDivisionEvent ? 'division' : 'club';
                        $badgeTypeLabel  = $isDivisionEvent ? 'Divisional Event' : 'Club Event';
                        $organizerTitle  = $isDivisionEvent 
                            ? 'Divisional Secretariat (' . htmlspecialchars($event->organizer_division_name ?? 'Gampaha') . ')'
                            : htmlspecialchars($event->club_name ?? '');
                    ?>
                    <div class="ea-card" data-event-id="<?= (int)$event->event_id ?>" data-event-date="<?= htmlspecialchars(substr($event->start_datetime ?? '', 0, 10), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="ea-card-top">
                            <span class="ea-badge <?= $badgeTypeClass ?>"><?= $badgeTypeLabel ?></span>
                            <span class="ea-badge pending"><?= $event->status === 'CancellationPending' ? 'Cancellation Review' : 'Pending Approval' ?></span>
                        </div>
                        <h3 class="ea-card-title"><?= htmlspecialchars($event->title) ?></h3>
                        <p class="ea-card-club">
                            <?= $organizerTitle ?>
                            <?php if (!$isDivisionEvent && !empty($event->club_code)): ?>
                                <small class="ea-club-code"><?= htmlspecialchars($event->club_code) ?></small>
                            <?php endif; ?>
                        </p>
                        <div class="ea-card-meta">
                            <span><?= date('M j, Y \a\t g:i A', strtotime($event->start_datetime)) ?></span>
                            <span><?= htmlspecialchars($event->location ?? '—') ?></span>
                        </div>
                        <div class="ea-card-footer">
                            <span class="ea-card-submitter">Submitted by <?= htmlspecialchars($event->creator_name ?? '—') ?> (<?= htmlspecialchars($event->creator_role ?? '—') ?>)</span>
                            <button type="button" class="ea-btn ea-btn-review db-view-button" data-event-id="<?= (int)$event->event_id ?>">View Details</button>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <div class="yn-empty-state ea-filter-empty" id="eaFilterEmpty" role="status" hidden>No events match your search and filters.</div>

<!-- ============================================================
     Review / Decision Modal
     ============================================================ -->
<div class="ea-modal-backdrop" id="eaReviewModal">
    <div class="ea-modal">
        <div class="ea-modal-header">
            <div class="ea-modal-header-info">
                <button type="button" class="db-view-button db-view-button--back ea-back-events" id="eaBackToEventsBtn">Back to Events</button>
                <h3 id="eaModalEventTitle">Event Review</h3>
            </div>
            <button type="button" class="ea-modal-close" id="eaModalClose" aria-label="Close">
                <?= yn_icon('close') ?>
            </button>
        </div>

        <div class="ea-modal-body" id="eaModalBody">
            <!-- Populated by JS (eventapproval.js) from /eventapproval/review/:id -->
        </div>

        <!-- Decision panel -->
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
                <button type="button" class="ea-btn-cancel-link db-close-action" id="eaCancelReviewBtn">Cancel</button>
                <button type="button" class="ea-btn ea-btn-submit-decision db-confirm-action" id="eaConfirmSubmitBtn">Confirm &amp; Submit Decision</button>
            </div>
        </div>
    </div>
</div>

<template id="eaIconUser"><?= yn_icon('user') ?></template>
<template id="eaIconCalendar"><?= yn_icon('calendar') ?></template>
<template id="eaIconPin"><?= yn_icon('pin') ?></template>
<div id="eaPageConfig" hidden data-root="<?= htmlspecialchars(ROOT, ENT_QUOTES, 'UTF-8') ?>" data-csrf-token="<?= htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
