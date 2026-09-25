<?php
/**
 * Zonal Events — schedule events and notify divisions and clubs.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$old = is_array($old ?? null) ? $old : [];
$errors = is_array($errors ?? null) ? $errors : [];
$eventStats = $eventStats ?? [];
$events = $events ?? [];
$selected = static function ($option) use ($old) {
    return (($old['audience'] ?? 'All divisions') === $option) ? ' selected' : '';
};
$formatDate = static function ($event) {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($event['date'] ?? ''));
    return $date ? $date->format('M j, Y') . ' at ' . ($event['time'] ?? '') : '';
};

require __DIR__ . '/../partials/icons.view.php';

$title = 'Zonal Events - YouthNexus';
$pageTitle = 'Zonal Events';
$pageDescription = 'Schedule zone events and notify divisions and clubs';
$currentRoute = 'zonalsecretary/events';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/zonal.js'];

$summaryCards = [
    ['value' => (string) ($eventStats['scheduled'] ?? 0), 'label' => 'Scheduled events', 'note' => 'in the zone programme', 'icon' => 'calendar', 'tone' => 'blue'],
    ['value' => 'LKR ' . number_format((float) ($eventStats['committed'] ?? 0), 0), 'label' => 'Budget committed', 'note' => 'across scheduled events', 'icon' => 'file', 'tone' => 'amber'],
    ['value' => 'LKR ' . number_format((float) ($eventStats['available'] ?? 0), 0), 'label' => 'Available event budget', 'note' => 'zonal balance', 'icon' => 'check', 'tone' => 'green'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="zonal-events-heading">
    <h1 id="zonal-events-heading" class="visually-hidden">Zonal events</h1>

    <div class="dw-summary-grid dw-summary-grid--three" aria-label="Zonal event summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($flash)): ?>
        <div class="dw-alert dw-alert--success" role="status">
            <strong>Event scheduled</strong>
            <p><?= $e($flash) ?></p>
        </div>
    <?php endif; ?>

    <div class="dw-toolbar" aria-label="Zonal event tools">
        <div class="dw-toolbar__search dw-search dw-search--plain">
            <label class="visually-hidden" for="zonal-event-search">Search events</label>
            <input id="zonal-event-search" type="search" placeholder="Search title or location..." autocomplete="off">
        </div>
        <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="zonal-event-filters" aria-expanded="false">Filters</button>
        <button type="button" class="dw-button dw-button--primary" id="zonal-event-open" data-modal-open="zonal-event-modal">Create Event</button>
    </div>

    <section class="dw-filter-panel" id="zonal-event-filters" hidden>
        <h2 class="dw-filter-panel__heading">Filter Zonal Events</h2>
        <div class="dw-filter-grid">
            <div class="dw-field">
                <label for="zonal-event-status">Filter by status</label>
                <select id="zonal-event-status">
                    <option value="">All statuses</option>
                    <option value="approved">Approved</option>
                    <option value="pending approval">Pending approval</option>
                    <option value="changes requested">Changes requested</option>
                </select>
            </div>
            <div class="dw-field">
                <label for="zonal-event-type">Filter by type</label>
                <select id="zonal-event-type">
                    <option value="">All types</option>
                    <option value="Leadership">Leadership</option>
                    <option value="Training">Training</option>
                    <option value="Community Service">Community Service</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Meeting">Meeting</option>
                </select>
            </div>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="zonal-events-list-heading">
        <header class="dw-panel__header">
            <div>
                <p>Gampaha Zone</p>
                <h2 id="zonal-events-list-heading">Events</h2>
            </div>
            <span class="dw-count"><?= count($events) ?> <?= count($events) === 1 ? 'event' : 'events' ?></span>
        </header>
        <div class="dw-panel__body">
            <div class="dw-record-grid" id="zonal-event-list">
                <?php foreach ($events as $event): ?>
                    <article class="dw-record-card"
                        data-search="<?= $e(strtolower(($event['title'] ?? '') . ' ' . ($event['location'] ?? ''))) ?>"
                        data-status="<?= $e(strtolower($event['status'] ?? 'pending approval')) ?>"
                        data-type="<?= $e($event['type'] ?? '') ?>">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon('calendar') ?></span>
                                <div class="dw-record-card__meta"><span><?= $e($event['type'] ?? '') ?></span></div>
                            </div>
                            <?php $status = $event['status'] ?? 'Pending approval'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e($event['title'] ?? '') ?></h3>
                        <div class="dw-record-card__details">
                            <span><span class="icon"><?= yn_icon('calendar') ?></span> <?= $e($formatDate($event)) ?> <span class="icon"><?= yn_icon('pin') ?></span> <?= $e($event['location'] ?? '') ?></span>
                            <span><?= $e($event['type'] ?? '') ?> · Budget LKR <?= number_format((float) ($event['budget'] ?? 0), 2) ?> · <?= $e($event['audience'] ?? '') ?></span>
                        </div>
                        <?php if (!empty($event['coordinator_remark'])): ?>
                            <div class="dw-record-card__footer">
                                <span class="dw-record-card__reference">Coordinator remark: <?= $e($event['coordinator_remark']) ?></span>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <div id="zonal-event-empty"<?= count($events) === 0 ? '' : ' hidden' ?>>
                <?php
                $emptyTitle = 'No events match these filters';
                $emptyMessage = 'Try changing the current search or filters.';
                $emptyVisible = true;
                require __DIR__ . '/../partials/divisional/empty-state.view.php';
                ?>
            </div>
        </div>
    </section>
</section>

<div id="zonal-event-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="zonal-event-modal-title" aria-hidden="true"<?= !empty($errors) ? '' : ' hidden' ?>>
    <div class="dw-modal__backdrop" data-modal-close></div>
    <div class="dw-modal__dialog">
        <header class="dw-modal__header">
            <div>
                <p>Zonal secretary action</p>
                <h2 id="zonal-event-modal-title">Create Event</h2>
            </div>
            <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
        </header>
        <div class="dw-modal__body">
            <p>New events are submitted to the Zonal Coordinator for approval before divisions and clubs are notified.</p>
            <form id="zonal-event-form" method="post" action="<?= ROOT ?>/zonalsecretary/events" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
                <?php if (!empty($errors['general'])): ?>
                    <div class="dw-alert dw-alert--error" role="alert">
                        <span aria-hidden="true"><?= yn_icon('info') ?></span>
                        <div><strong>Unable to schedule this event</strong><p><?= $e($errors['general']) ?></p></div>
                    </div>
                <?php endif; ?>
                <div id="zonal-event-date-banner" class="dw-alert dw-alert--error" role="alert" hidden>
                    <span aria-hidden="true"><?= yn_icon('info') ?></span>
                    <div><strong>Event date must be in the future</strong><p>Choose a future date and time to continue.</p></div>
                </div>
                <div class="dw-filter-grid">
                    <div class="dw-field dw-field--span-2">
                        <label for="zonal-event-title">Event title</label>
                        <input id="zonal-event-title" name="title" type="text" required maxlength="150" autocomplete="off" placeholder="Zone Youth Leadership Forum" value="<?= $e($old['title'] ?? '') ?>">
                        <?php if (!empty($errors['title'])): ?><p><?= $e($errors['title']) ?></p><?php endif; ?>
                    </div>
                    <div class="dw-field">
                        <label for="zonal-event-date">Date</label>
                        <input id="zonal-event-date" name="event_date" type="date" required value="<?= $e($old['event_date'] ?? '') ?>">
                        <p id="zonal-event-date-error"<?= !empty($errors['event_date']) ? '' : ' hidden' ?>><?= $e($errors['event_date'] ?? 'This date has already passed') ?></p>
                    </div>
                    <div class="dw-field">
                        <label for="zonal-event-time">Time</label>
                        <input id="zonal-event-time" name="event_time" type="time" required value="<?= $e($old['event_time'] ?? '') ?>">
                        <?php if (!empty($errors['event_time'])): ?><p><?= $e($errors['event_time']) ?></p><?php endif; ?>
                    </div>
                    <div class="dw-field dw-field--span-2">
                        <label for="zonal-event-location">Location</label>
                        <input id="zonal-event-location" name="location" type="text" required maxlength="255" autocomplete="off" placeholder="Venue or coordinates" value="<?= $e($old['location'] ?? '') ?>">
                        <?php if (!empty($errors['location'])): ?><p><?= $e($errors['location']) ?></p><?php endif; ?>
                    </div>
                    <div class="dw-field">
                        <label for="zonal-event-type-input">Event type</label>
                        <select id="zonal-event-type-input" name="event_type" required>
                            <option value="">Select type...</option>
                            <?php foreach (['Leadership', 'Training', 'Community Service', 'Workshop', 'Meeting'] as $type): ?>
                                <option<?= ($old['event_type'] ?? '') === $type ? ' selected' : '' ?>><?= $e($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['event_type'])): ?><p><?= $e($errors['event_type']) ?></p><?php endif; ?>
                    </div>
                    <div class="dw-field">
                        <label for="zonal-event-budget">Estimated budget (LKR)</label>
                        <input id="zonal-event-budget" name="budget" type="number" required min="1" step="0.01" placeholder="0.00" value="<?= $e($old['budget'] ?? '') ?>">
                        <?php if (!empty($errors['budget'])): ?><p><?= $e($errors['budget']) ?></p><?php endif; ?>
                    </div>
                    <div class="dw-field dw-field--span-2">
                        <label for="zonal-event-audience">Notify</label>
                        <select id="zonal-event-audience" name="audience" required>
                            <option value="All divisions"<?= $selected('All divisions') ?>>All divisions and their clubs</option>
                            <option value="Gampaha Division"<?= $selected('Gampaha Division') ?>>Gampaha Division and its clubs</option>
                            <option value="Ja-Ela Division"<?= $selected('Ja-Ela Division') ?>>Ja-Ela Division and its clubs</option>
                            <option value="Negombo Division"<?= $selected('Negombo Division') ?>>Negombo Division and its clubs</option>
                        </select>
                        <?php if (!empty($errors['audience'])): ?><p><?= $e($errors['audience']) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="dw-alert dw-alert--error" id="zonal-event-error" role="alert" hidden></div>
            </form>
        </div>
        <footer class="dw-modal__footer">
            <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
            <button type="submit" class="dw-button dw-button--primary" form="zonal-event-form">Create Event</button>
        </footer>
    </div>
</div>



<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
