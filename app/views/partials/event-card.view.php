<?php
/**
 * Event card partial — single event in the member events grid.
 * Expects $item (event array) and $escape (callable). Requires icons partial first.
 * Filter keys live in data attributes for the client-side filters (events.js).
 * $item['rsvp_status'] is the member's stored response ('' = undecided), so
 * the served card already paints the correct active button.
 */
$scopeClass = strtolower(str_replace(' ', '-', $item['scope'] ?? ''));
$statusClass = strtolower($item['status'] ?? '');
$filterStatus = $statusClass === 'pending' ? 'pending' : (($statusClass === 'upcoming' || $statusClass === 'ongoing') ? 'upcoming' : 'completed');
$payload = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
?>
<article class="event-card" data-event="<?= $payload ?>"
    data-level="<?= $escape(strtolower($item['scope'] ?? '')) ?>"
    data-status="<?= $escape($filterStatus) ?>"
    data-start="<?= $escape($item['start_iso'] ?? '') ?>"
    data-posted="<?= $escape($item['posted_iso'] ?? '') ?>"
    data-attendance="<?= (int) ($item['attendance'] ?? 0) ?>"
    data-rsvp="<?= $escape($item['rsvp_status'] ?? '') ?>"
    data-event-id="<?= (int) ($item['id'] ?? 0) ?>">
    <div class="card-header">
        <div class="header-left">
            <span class="scope-badge <?= $escape($scopeClass) ?>"><?= $escape($item['scope'] ?? '') ?></span>
            <span class="status-badge <?= $escape($statusClass) ?>"><?= $escape($item['status'] ?? '') ?></span>
        </div>
        <div class="event-meta">
            <div class="meta-item">
                <span class="icon"><?= yn_icon('calendar') ?></span> <?= $escape($item['date'] ?? '') ?>
            </div>
            <div class="meta-item">
                <span class="icon"><?= yn_icon('pin') ?></span> <?= $escape($item['location'] ?? '') ?>
            </div>
        </div>
    </div>
    <h3 class="card-title"><?= $escape($item['title'] ?? '') ?></h3>
    <?php $summary = trim(mb_substr((string) ($item['description'] ?? ''), 0, 80)); ?>
    <p class="card-summary"><?= $summary !== '' ? $escape($summary) . '...' : 'No description provided.' ?></p>

    <div class="card-remaining"><?= $escape($item['remaining'] ?? '') ?></div>

    <div class="card-footer">
        <button type="button" class="btn-view-details" data-action="view">
            <span class="icon"><?= yn_icon('eye') ?></span> View Details
        </button>
        <button type="button" class="btn-participate <?= ($item['rsvp_status'] ?? '') === 'attending' ? 'is-active' : '' ?>" data-action="rsvp" data-value="attending">
            <span class="icon"><?= yn_icon('check') ?></span> Participate
        </button>
        <button type="button" class="btn-not-participate <?= ($item['rsvp_status'] ?? '') === 'declined' ? 'is-active' : '' ?>" data-action="rsvp" data-value="declined">
            <span class="icon"><?= yn_icon('close') ?></span> Not participating
        </button>
    </div>
</article>
