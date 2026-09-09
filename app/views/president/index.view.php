<?php
/**
 * President Overview — C2.
 * Health score + 40/30/30 breakdown tiles, pending club events cards
 * (review happens on club/events), exec-roster summary table.
 * Presentation-only: no DB writes; backend contract lands in C13.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$health        = $health ?? [];
$pendingEvents = $pendingEvents ?? [];
$execRoster    = $execRoster ?? [];
?>

<section class="club-page" aria-labelledby="president-overview-heading">
    <h1 id="president-overview-heading" class="sr-only">President overview</h1>

    <div class="club-stat-grid" aria-label="Club health">
        <article class="club-stat-card">
            <p class="club-stat-label">Club health score</p>
            <p class="club-stat-value"><?= $escape($health['score'] ?? 0) ?><span class="club-stat-unit">/100</span></p>
            <p><span class="club-pill club-pill--approved"><?= $escape(($health['label'] ?? '') . ' · ' . ($health['state'] ?? '')) ?></span></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Events (40%)</p>
            <p class="club-stat-value"><?= $escape(($health['events']['points'] ?? 0) . '/' . ($health['events']['max'] ?? 40)) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Finances (30%)</p>
            <p class="club-stat-value"><?= $escape(($health['finances']['points'] ?? 0) . '/' . ($health['finances']['max'] ?? 30)) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Attendance (30%)</p>
            <p class="club-stat-value"><?= $escape(($health['attendance']['points'] ?? 0) . '/' . ($health['attendance']['max'] ?? 30)) ?></p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="president-pending-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Awaiting your review</p>
                <h2 id="president-pending-heading">Pending Club Events (<?= count($pendingEvents) ?>)</h2>
            </div>
        </div>

        <div class="club-list">
            <?php foreach ($pendingEvents as $e): ?>
                <article class="club-list-item">
                    <div class="club-list-icon" aria-hidden="true"><?= yn_icon('calendar') ?></div>
                    <div class="club-list-copy">
                        <h3><?= $escape($e['title'] ?? '') ?></h3>
                        <p><span class="icon"><?= yn_icon('calendar') ?></span> <?= $escape($e['date'] ?? '') ?> · <span class="icon"><?= yn_icon('pin') ?></span> <?= $escape($e['location'] ?? '') ?></p>
                        <p class="club-event-meta"><?= $escape($e['type'] ?? '') ?> · Budget <?= $escape($e['budget'] ?? '') ?> · Submitted by <?= $escape($e['submitted_by'] ?? '') ?></p>
                    </div>
                    <div class="club-event-side">
                        <span class="club-pill club-pill--pending">Pending Approval</span>
                        <a class="club-btn-small" href="<?= ROOT ?>/club/events">Review</a>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (empty($pendingEvents)): ?>
                <p class="club-note">Nothing awaiting review.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="club-panel" aria-labelledby="president-exec-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Youth Development Club</p>
                <h2 id="president-exec-heading">Executive Roster</h2>
            </div>
        </div>

        <div class="club-table-wrap">
            <table class="club-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($execRoster as $m): ?>
                        <tr>
                            <td><strong><?= $escape($m['name'] ?? '') ?></strong></td>
                            <td><?= $escape($m['role'] ?? '') ?></td>
                            <td><span class="club-pill club-pill--<?= $escape($m['status_key'] ?? 'active') ?>"><?= $escape($m['status'] ?? '') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
