<?php
/**
 * Zonal Coordinator Overview — Z1.
 * Zone-health tiles (computed zone averages) + per-division average club
 * health. Club cards live on clubs().
 * Presentation-only: no DB writes; backend contract lands in Z12.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$zoneHealth = $zoneHealth ?? [];
$divisions  = $divisions ?? [];
$announcements = $announcements ?? [];
$upcomingEvents = $upcomingEvents ?? [];
?>

<section class="club-page" aria-labelledby="zonalcoordinator-overview-heading">
    <h1 id="zonalcoordinator-overview-heading" class="sr-only">Zonal coordinator overview</h1>

    <div class="club-stat-grid" aria-label="Zone health">
        <article class="club-stat-card">
            <p class="club-stat-label">Zone health score</p>
            <p class="club-stat-value"><?= $escape($zoneHealth['score'] ?? 0) ?><span class="club-stat-unit">/100</span></p>
            <p><span class="club-pill club-pill--pending"><?= $escape(($zoneHealth['label'] ?? '') . ' · ' . ($zoneHealth['state'] ?? '')) ?></span></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Events (40%)</p>
            <p class="club-stat-value"><?= $escape(($zoneHealth['events']['points'] ?? 0) . '/' . ($zoneHealth['events']['max'] ?? 40)) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Finances (30%)</p>
            <p class="club-stat-value"><?= $escape(($zoneHealth['finances']['points'] ?? 0) . '/' . ($zoneHealth['finances']['max'] ?? 30)) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Attendance (30%)</p>
            <p class="club-stat-value"><?= $escape(($zoneHealth['attendance']['points'] ?? 0) . '/' . ($zoneHealth['attendance']['max'] ?? 30)) ?></p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="zonal-divisions-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Zone · <?= count($divisions) ?> divisions</p>
                <h2 id="zonal-divisions-heading">Average club health per division</h2>
            </div>
            <a class="club-btn-secondary" href="<?= ROOT ?>/zonalcoordinator/clubs">Monitor club health</a>
        </div>

        <div class="club-table-wrap">
            <table class="club-table">
                <thead>
                    <tr>
                        <th>Division</th>
                        <th>Clubs</th>
                        <th>Average score</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($divisions as $d): ?>
                        <tr>
                            <td><strong><?= $escape($d['division'] ?? '') ?></strong></td>
                            <td><?= $escape($d['clubs'] ?? 0) ?></td>
                            <td><?= $escape($d['average'] ?? 0) ?>/100</td>
                            <td><span class="club-pill club-pill--<?= $escape($d['status_key'] ?? 'pending') ?>"><?= $escape($d['status'] ?? '') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="member-panel" aria-labelledby="zonalcoordinator-announcements-heading">
        <div class="member-panel-header">
            <div><p class="member-eyebrow">Stay informed</p><h2 id="zonalcoordinator-announcements-heading">Zone announcements</h2></div>
            <a class="member-panel-link" href="<?= ROOT ?>/zonalannouncements">View all</a>
        </div>
        <div class="member-announcement-list">
            <?php foreach ($announcements as $announcement): ?>
                <article class="member-announcement-item<?= !empty($announcement['is_new']) ? ' is-new' : '' ?>">
                    <span class="member-list-dot" aria-hidden="true"></span>
                    <div class="member-list-copy"><div class="member-list-meta"><span><?= $escape($announcement['age']) ?></span><?php if (!empty($announcement['is_new'])): ?><span class="member-badge member-badge--new">New</span><?php endif; ?></div><h3><?= $escape($announcement['title']) ?></h3><p><?= $escape($announcement['summary']) ?></p><span class="member-scope-tag">Zonal</span></div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="member-panel" aria-labelledby="zonalcoordinator-events-heading">
        <div class="member-panel-header"><div><p class="member-eyebrow">Plan ahead</p><h2 id="zonalcoordinator-events-heading">Upcoming zonal programmes</h2></div></div>
        <div class="member-event-list">
            <?php foreach ($upcomingEvents as $event): ?>
                <article class="member-event-item"><div class="member-event-icon" aria-hidden="true"><?= yn_icon('calendar') ?></div><div class="member-event-copy"><div class="member-event-heading"><h3><?= $escape($event['title']) ?></h3><span class="member-scope-text">Zonal</span></div><p><?= $escape($event['date']) ?>, <?= $escape($event['location']) ?></p></div><span class="member-status member-status--<?= $escape($event['status_key']) ?>"><?= $escape($event['status']) ?></span></article>
            <?php endforeach; ?>
        </div>
    </section>

</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/member-dashboard.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
