<?php
/**
 * Club Events — C4 full UI.
 * President: pending list + approve/request-changes decision modal
 * (mirrors eventapproval decision panel; remarks mandatory on
 * request-changes). Secretary: "Create Event" button + create-event modal
 * (title, date + time, location, type, budget + future-date validation
 * with error banner, per owner mock). Presentation-only: no DB writes;
 * backend contract lands in C13.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$stats       = $stats ?? [];
$clubEvents  = $clubEvents ?? [];
$can_approve = !empty($can_approve);
$can_create  = !empty($can_create);

$title = 'Club Events - YouthNexus';
$pageTitle = 'Club Events';
$pageDescription = 'Pending approvals, club calendar and completion evidence';
$currentRoute = 'club/events';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/club.js',
];

$summaryCards = [
    ['value' => (string) ($stats['pending'] ?? 0), 'label' => 'Pending approval', 'note' => 'Awaiting president decision', 'icon' => 'clock', 'tone' => 'amber'],
    ['value' => (string) ($stats['approved'] ?? 0), 'label' => 'Approved', 'note' => 'On the club calendar', 'icon' => 'check', 'tone' => 'green'],
    ['value' => (string) ($stats['completed'] ?? 0), 'label' => 'Completed', 'note' => 'Evidence recorded', 'icon' => 'calendar', 'tone' => 'blue'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="club-events-heading">
    <h1 id="club-events-heading" class="visually-hidden">Club events</h1>

    <div class="dw-alert dw-alert--success" id="club-toast" role="status" hidden></div>

    <div class="dw-summary-grid dw-summary-grid--three" aria-label="Event summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="dw-toolbar" aria-label="Event tools">
        <div class="dw-toolbar__search dw-search dw-search--plain">
            <label class="visually-hidden" for="club-event-search">Search events</label>
            <input id="club-event-search" type="search" placeholder="Search title or location..." autocomplete="off">
        </div>
        <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="club-event-filters" aria-expanded="false">Filters</button>
        <?php if ($can_create): ?>
            <button type="button" class="dw-button dw-button--primary" id="club-event-open" data-modal-open="club-event-modal">Create Event</button>
        <?php endif; ?>
    </div>

    <section class="dw-filter-panel" id="club-event-filters" hidden>
        <h2 class="dw-filter-panel__heading">Filter Events</h2>
        <div class="dw-filter-grid">
            <div class="dw-field">
                <label for="club-event-status">Filter by status</label>
                <select id="club-event-status">
                    <option value="">All statuses</option>
                    <option value="pending">Pending approval</option>
                    <option value="approved">Approved</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="dw-field">
                <label for="club-event-type">Filter by type</label>
                <select id="club-event-type">
                    <option value="">All types</option>
                    <option value="workshop">Workshop</option>
                    <option value="meeting">Meeting</option>
                    <option value="community service">Community Service</option>
                    <option value="fundraiser">Fundraiser</option>
                </select>
            </div>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="club-events-list-heading">
        <header class="dw-panel__header">
            <div>
                <h2 id="club-events-list-heading">Events</h2>
                <p>Gampaha Youth Development Club</p>
            </div>
            <span class="dw-count"><?= count($clubEvents) ?> <?= count($clubEvents) === 1 ? 'event' : 'events' ?></span>
        </header>
        <div class="dw-record-grid" id="club-event-list">
            <?php foreach ($clubEvents as $ev): ?>
                <article class="dw-record-card" data-event-card
                    data-search="<?= $e(strtolower(($ev['title'] ?? '') . ' ' . ($ev['location'] ?? ''))) ?>"
                    data-status="<?= $e($ev['status_key'] ?? 'pending') ?>"
                    data-type="<?= $e(strtolower($ev['type'] ?? '')) ?>"
                    data-title="<?= $e($ev['title'] ?? '') ?>"
                    data-meta="<?= $e(($ev['date'] ?? '') . ' · ' . ($ev['location'] ?? '')) ?>"
                    data-submitter="<?= $e($ev['submitted_by'] ?? '') ?>">
                    <div class="dw-record-card__header">
                        <div class="dw-record-card__identity">
                            <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon('calendar') ?></span>
                            <div class="dw-record-card__meta"><span><?= $e($ev['type'] ?? '') ?></span></div>
                        </div>
                        <?php $status = $ev['status'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
                    </div>
                    <h3 class="dw-record-card__title"><?= $e($ev['title'] ?? '') ?></h3>
                    <div class="dw-record-card__details">
                        <span><span class="icon"><?= yn_icon('calendar') ?></span> <?= $e($ev['date'] ?? '') ?> · <span class="icon"><?= yn_icon('pin') ?></span> <?= $e($ev['location'] ?? '') ?></span>
                        <span><?= $e($ev['type'] ?? '') ?> · Budget <?= $e($ev['budget'] ?? '') ?> · Submitted by <?= $e($ev['submitted_by'] ?? '') ?></span>
                    </div>
                    <div class="dw-record-card__footer">
                        <div class="dw-record-card__actions">
                            <?php if ($can_approve && (($ev['status_key'] ?? '') === 'pending')): ?>
                                <button type="button" class="dw-button dw-button--ghost" data-action="review">Review</button>
                            <?php endif; ?>
                            <?php if ($can_create && (($ev['status_key'] ?? '') === 'approved')): ?>
                                <button type="button" class="dw-button dw-button--ghost" data-action="complete">Mark complete</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div id="club-event-empty"<?= count($clubEvents) === 0 ? '' : ' hidden' ?>>
            <?php
            $emptyTitle = 'No events match these filters';
            $emptyMessage = 'Try changing the current search or filters.';
            $emptyVisible = true;
            require __DIR__ . '/../partials/divisional/empty-state.view.php';
            ?>
        </div>
    </section>
</section>

<?php if ($can_create): ?>
    <div id="club-event-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="ev-modal-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>Secretary action</p>
                    <h2 id="ev-modal-title">Create Event</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <p>New events enter as Pending Approval for the president.</p>
                <form id="club-event-form" novalidate>
                    <div class="dw-alert dw-alert--error" id="ev-banner" role="alert" hidden>
                        <span aria-hidden="true"><?= yn_icon('info') ?></span>
                        <div>
                            <strong>Event date must be in the future</strong>
                            <p>The date and time you selected has already passed. Please choose a future date to continue.</p>
                        </div>
                    </div>
                    <div class="dw-field">
                        <label for="ev-title">Event Title</label>
                        <input id="ev-title" name="title" type="text" required maxlength="150" autocomplete="off" placeholder="Community Clean-up Drive">
                    </div>
                    <div class="dw-field">
                        <label for="ev-date">Date</label>
                        <input id="ev-date" name="date" type="date" required>
                        <p id="ev-date-error" hidden><small>This date has already passed</small></p>
                    </div>
                    <div class="dw-field">
                        <label for="ev-time">Time</label>
                        <input id="ev-time" name="time" type="time" required>
                    </div>
                    <div class="dw-field">
                        <label for="ev-location">Location</label>
                        <input id="ev-location" name="location" type="text" required maxlength="255" autocomplete="off" placeholder="Venue or coordinates">
                    </div>
                    <div class="dw-field">
                        <label for="ev-type">Event Type</label>
                        <select id="ev-type" name="type" required>
                            <option value="">Select type...</option>
                            <option value="Community Service">Community Service</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Meeting">Meeting</option>
                            <option value="Fundraiser">Fundraiser</option>
                        </select>
                    </div>
                    <div class="dw-field">
                        <label for="ev-budget">Estimated Budget (LKR)</label>
                        <input id="ev-budget" name="budget" type="number" required min="1" step="0.01" placeholder="0.00">
                    </div>
                    <div class="dw-alert dw-alert--error" id="ev-error" role="alert" hidden></div>
                </form>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="submit" class="dw-button dw-button--primary" form="club-event-form">Create Event</button>
            </footer>
        </div>
    </div>
<?php endif; ?>

<?php if ($can_create): ?>
    <div id="club-complete-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="cp-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>Secretary action</p>
                    <h2 id="cp-title">Mark Event Complete</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <p id="cp-meta"></p>
                <div class="dw-field">
                    <label for="cp-sheet">Attendance sheet (required)</label>
                    <input id="cp-sheet" type="file" accept=".csv,.xls,.xlsx,.pdf,image/*">
                </div>
                <div class="dw-field">
                    <label for="cp-photos">Event photos (optional)</label>
                    <input id="cp-photos" type="file" accept="image/*" multiple>
                </div>
                <p id="cp-files" hidden></p>
                <div class="dw-alert dw-alert--error" id="cp-error" role="alert" hidden></div>
                <div role="note">
                    <strong>What happens next</strong>
                    <p>Completion evidence is saved with the event. Completed events feed the Events slice of the club health score.</p>
                </div>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="button" class="dw-button dw-button--primary" id="cp-confirm">Mark completed</button>
            </footer>
        </div>
    </div>
<?php endif; ?>

<?php if ($can_approve): ?>
    <div id="event-decision-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="ed-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>President decision</p>
                    <h2 id="ed-title">Review event</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <p id="ed-meta"></p>
                <p id="ed-submitter"></p>
                <div class="dw-field">
                    <label for="ed-result">Review result</label>
                    <select id="ed-result">
                        <option value="approve">Approve event</option>
                        <option value="request-changes">Request changes</option>
                    </select>
                </div>
                <div class="dw-field">
                    <label for="ed-remarks">Official review remarks (required if requesting changes)</label>
                    <textarea id="ed-remarks" rows="3" placeholder="Provide the reason for this decision..."></textarea>
                </div>
                <div class="dw-alert dw-alert--error" id="ed-error" role="alert" hidden></div>
                <div id="ed-impact" role="note">
                    <strong>Impact of approval</strong>
                    <p>Approving publishes this event to the club calendar and notifies the secretary. It becomes visible for attendance tracking once it occurs.</p>
                </div>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="button" class="dw-button dw-button--primary" id="ed-confirm">Confirm &amp; submit decision</button>
            </footer>
        </div>
    </div>
<?php endif; ?>



<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
