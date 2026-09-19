<?php
/**
 * Event card partial — single event in the member events grid.
 * Expects $item (event array) and $escape (callable). Requires icons partial first.
 */
$scopeClass = strtolower(str_replace(' ', '-', $item['scope'] ?? ''));
$statusClass = strtolower($item['status'] ?? '');
$payload = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
?>
<article class="event-card" data-event="<?= $payload ?>">
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
    <p class="card-summary"><?= $escape(mb_substr($item['description'] ?? '', 0, 80)) ?>...</p>

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
