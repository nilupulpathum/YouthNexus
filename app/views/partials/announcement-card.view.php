<?php
/**
 * Announcement card partial — single announcement in the member list.
 * Expects $item (announcement array) and $escape (callable). Requires icons partial first.
 */
$scopeClass = strtolower(str_replace(' ', '-', $item['scope'] ?? ''));
$payload = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
?>
<article class="announcement-card <?= !empty($item['is_unread']) ? 'is-unread' : '' ?>" data-announcement="<?= $payload ?>">
    <div class="card-header">
        <div class="header-left">
            <span class="scope-badge <?= $escape($scopeClass) ?>"><?= $escape($item['scope'] ?? '') ?></span>
            <?php if (!empty($item['is_new'])): ?>
                <span class="new-badge">New</span>
            <?php endif; ?>
        </div>
        <span class="age-text"><?= $escape($item['age'] ?? '') ?></span>
    </div>
    <h3 class="card-title"><?= $escape($item['title'] ?? '') ?></h3>
    <p class="card-summary"><?= $escape($item['summary'] ?? '') ?></p>

    <?php if (!empty($item['attachment'])): ?>
        <div class="card-attachment">
            <span class="icon"><?= yn_icon('file') ?></span>
            <span class="filename"><?= $escape($item['attachment']) ?></span>
            <span class="icon download-icon"><?= yn_icon('download') ?></span>
        </div>
    <?php endif; ?>

    <div class="card-footer">
        <button type="button" class="btn-read-more" data-action="view">
            <span class="icon"><?= yn_icon('eye') ?></span> Read More
        </button>
        <?php if (!empty($item['attachment'])): ?>
            <button type="button" class="btn-download-attachment">
                <span class="icon"><?= yn_icon('download') ?></span> Download Attachment
            </button>
        <?php endif; ?>
        <button type="button" class="btn-mark-as-read">
            <span class="icon"><?= yn_icon('check') ?></span> Mark as Read
        </button>
    </div>
</article>
