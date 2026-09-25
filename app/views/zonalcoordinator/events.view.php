<?php
/**
 * Zonal Coordinator Events — approve zonal events submitted by the
 * Zonal Secretary. A decision remark is required for approval or
 * requested changes.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$eventStats = $eventStats ?? [];
$events = $events ?? [];
$flash = $flash ?? '';
$csrf_token = $csrf_token ?? '';

$title = 'Approve Zonal Events - YouthNexus';
$pageTitle = 'Approve Zonal Events';
$pageDescription = 'Review events submitted by the Zonal Secretary';
$currentRoute = 'zonalcoordinator/events';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/zonal.js'];

$summaryCards = [
    ['value' => (string) ($eventStats['scheduled'] ?? 0), 'label' => 'Submitted events', 'note' => 'in the zone programme', 'icon' => 'calendar', 'tone' => 'blue'],
    ['value' => 'LKR ' . number_format((float) ($eventStats['committed'] ?? 0), 0), 'label' => 'Budget committed', 'note' => 'across submitted events', 'icon' => 'file', 'tone' => 'amber'],
    ['value' => 'LKR ' . number_format((float) ($eventStats['available'] ?? 0), 0), 'label' => 'Available event budget', 'note' => 'zonal balance', 'icon' => 'check', 'tone' => 'green'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="coordinator-events-heading">
    <h1 id="coordinator-events-heading" class="visually-hidden">Approve zonal events</h1>

    <div class="dw-summary-grid dw-summary-grid--three" aria-label="Zonal event summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($flash): ?>
        <div class="dw-alert dw-alert--warning" role="status">
            <span aria-hidden="true"><?= yn_icon('info') ?></span>
            <p><?= $e($flash) ?></p>
        </div>
    <?php endif; ?>

    <div class="dw-toolbar" aria-label="Zonal event approval tools">
        <div class="dw-toolbar__search dw-search dw-search--plain">
            <label class="visually-hidden" for="coordinator-event-search">Search events</label>
            <input id="coordinator-event-search" type="search" placeholder="Search title or location..." autocomplete="off">
        </div>
        <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="coordinator-event-filters" aria-expanded="false">Filters</button>
    </div>

    <section class="dw-filter-panel" id="coordinator-event-filters" hidden>
        <h2 class="dw-filter-panel__heading">Filter Zonal Event Approvals</h2>
        <div class="dw-filter-grid">
            <div class="dw-field">
                <label for="coordinator-event-status">Filter by status</label>
                <select id="coordinator-event-status">
                    <option value="">All statuses</option>
                    <option value="pending approval">Pending approval</option>
                    <option value="approved">Approved</option>
                </select>
            </div>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="event-queue-heading">
        <header class="dw-panel__header">
            <div>
                <p>Gampaha Zone</p>
                <h2 id="event-queue-heading">Zonal event approvals</h2>
            </div>
            <span class="dw-count"><?= count($events) ?> <?= count($events) === 1 ? 'event' : 'events' ?></span>
        </header>
        <div class="dw-panel__body">
            <p class="dw-muted-copy">Review events submitted by the Zonal Secretary. A decision remark is required for approval or requested changes.</p>
            <div class="dw-record-grid" id="coordinator-event-list">
                <?php foreach ($events as $event): ?>
                    <article class="dw-record-card"
                        data-search="<?= $e(strtolower(($event['title'] ?? '') . ' ' . ($event['location'] ?? ''))) ?>"
                        data-status="<?= $e(strtolower($event['status'] ?? 'pending approval')) ?>">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon('calendar') ?></span>
                                <div class="dw-record-card__meta"><span><?= $e($event['type'] ?? '') ?></span></div>
                            </div>
                            <?php $status = $event['status'] ?? 'Pending approval'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e($event['title'] ?? '') ?></h3>
                        <div class="dw-record-card__details">
                            <span><?= $e($event['date'] ?? '') ?>, <?= $e($event['location'] ?? '') ?></span>
                            <span><?= $e($event['type'] ?? '') ?> · LKR <?= number_format($event['budget'] ?? 0, 2) ?> · <?= $e($event['audience'] ?? '') ?></span>
                        </div>
                        <?php if (!empty($event['coordinator_remark'])): ?>
                            <div class="dw-record-card__footer">
                                <span class="dw-record-card__reference">Coordinator remark: <?= $e($event['coordinator_remark']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (($event['status'] ?? '') === 'Pending approval'): ?>
                            <div class="dw-record-card__footer">
                                <div class="dw-row-actions">
                                    <button class="dw-button dw-button--ghost" type="button" data-review="<?= $e($event['id']) ?>" data-title="<?= $e($event['title']) ?>">Review</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <div id="coordinator-event-empty"<?= count($events) === 0 ? '' : ' hidden' ?>>
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

<div id="event-review" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="event-review-title" aria-hidden="true" hidden>
    <div class="dw-modal__backdrop" data-modal-close></div>
    <div class="dw-modal__dialog">
        <header class="dw-modal__header">
            <div>
                <p>Zonal Coordinator decision</p>
                <h2 id="event-review-title">Review event</h2>
            </div>
            <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
        </header>
        <div class="dw-modal__body">
            <p id="event-review-name" class="dw-muted-copy dw-field--span-2"></p>
            <form id="event-decision" method="post" class="dw-field--span-2" data-base-action="<?= ROOT ?>/zonalcoordinator/">
                <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
                <input type="hidden" id="event-id" name="event_id">
                <div class="dw-field">
                    <label for="event-remark">Decision remark</label>
                    <textarea id="event-remark" name="remark" rows="4" maxlength="1000" required></textarea>
                </div>
            </form>
        </div>
        <footer class="dw-modal__footer">
            <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
            <button type="submit" class="dw-button dw-button--secondary" form="event-decision" data-decision="returnevent">Request changes</button>
            <button type="submit" class="dw-button dw-button--primary" form="event-decision" data-decision="approveevent">Approve event</button>
        </footer>
    </div>
</div>



<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
