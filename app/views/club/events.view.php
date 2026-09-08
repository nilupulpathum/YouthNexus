<?php
/**
 * Club Events — C1 shell. Approval + creation flows land in C4.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$stats      = $stats ?? [];
$clubEvents = $clubEvents ?? [];
$can_approve = !empty($can_approve);
?>

<section class="club-page" aria-labelledby="club-events-heading">
    <h1 id="club-events-heading" class="sr-only">Club events</h1>

    <div class="club-stat-grid" aria-label="Event summary">
        <article class="club-stat-card">
            <p class="club-stat-label">Pending approval</p>
            <p class="club-stat-value"><?= $escape($stats['pending'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Approved</p>
            <p class="club-stat-value"><?= $escape($stats['approved'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Completed</p>
            <p class="club-stat-value"><?= $escape($stats['completed'] ?? 0) ?></p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="club-events-list-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Youth Development Club</p>
                <h2 id="club-events-list-heading">Events</h2>
            </div>
        </div>

        <div class="club-list">
            <?php foreach ($clubEvents as $e): ?>
                <article class="club-list-item">
                    <div class="club-list-icon" aria-hidden="true"><?= yn_icon('calendar') ?></div>
                    <div class="club-list-copy">
                        <h3><?= $escape($e['title'] ?? '') ?></h3>
                        <p><span class="icon"><?= yn_icon('calendar') ?></span> <?= $escape($e['date'] ?? '') ?> · <span class="icon"><?= yn_icon('pin') ?></span> <?= $escape($e['location'] ?? '') ?></p>
                    </div>
                    <span class="club-pill club-pill--<?= $escape($e['status_key'] ?? 'pending') ?>"><?= $escape($e['status'] ?? '') ?></span>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($can_approve): ?>
            <p class="club-note">Approve / request-changes actions land in C4 — this shell is read-only.</p>
        <?php endif; ?>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
