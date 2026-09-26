<?php
/**
 * Zonal Coordinator Overview — Z1.
 * Zone-health tiles (computed zone averages) + per-division average club
 * health. Club cards live on clubs().
 * Presentation-only: no DB writes; backend contract lands in Z12.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$zoneHealth = $zoneHealth ?? [];
$divisions  = $divisions ?? [];
$announcements = $announcements ?? [];
$upcomingEvents = $upcomingEvents ?? [];

$title = 'Zonal Overview - YouthNexus';
$pageTitle = 'Zonal Overview';
$pageDescription = 'Zone health and average club health per division';
$currentRoute = 'zonalcoordinator';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/member-dashboard.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js'];

$summaryCards = [
    ['value' => (string) ($zoneHealth['score'] ?? 0) . '/100', 'label' => 'Zone health score', 'note' => (string) (($zoneHealth['label'] ?? '') . ' · ' . ($zoneHealth['state'] ?? '')), 'icon' => 'reports', 'tone' => 'blue'],
    ['value' => (string) (($zoneHealth['events']['points'] ?? 0) . '/' . ($zoneHealth['events']['max'] ?? 40)), 'label' => 'Events (40%)', 'note' => 'event performance', 'icon' => 'calendar', 'tone' => 'blue'],
    ['value' => (string) (($zoneHealth['finances']['points'] ?? 0) . '/' . ($zoneHealth['finances']['max'] ?? 30)), 'label' => 'Finances (30%)', 'note' => 'finance performance', 'icon' => 'file', 'tone' => 'green'],
    ['value' => (string) (($zoneHealth['attendance']['points'] ?? 0) . '/' . ($zoneHealth['attendance']['max'] ?? 30)), 'label' => 'Attendance (30%)', 'note' => 'attendance performance', 'icon' => 'users', 'tone' => 'green'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="zonalcoordinator-overview-heading">
    <h1 id="zonalcoordinator-overview-heading" class="visually-hidden">Zonal coordinator overview</h1>

    <div class="dw-summary-grid" aria-label="Zone health">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <section class="dw-panel" aria-labelledby="zonal-divisions-heading">
        <header class="dw-panel__header">
            <div>
                <p><?= $e($zoneName ?? 'Zone') ?> · <?= count($divisions) ?> divisions</p>
                <h2 id="zonal-divisions-heading">Average club health per division</h2>
            </div>
            <a class="dw-button dw-button--secondary" href="<?= ROOT ?>/zonalclubhealth">Monitor club health</a>
        </header>

        <div class="dw-table-wrap">
            <table class="dw-table">
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
                            <td><strong><?= $e($d['division'] ?? '') ?></strong></td>
                            <td><?= $e($d['clubs'] ?? 0) ?></td>
                            <td><?= $e($d['average'] ?? 0) ?>/100</td>
                            <td><?php $status = $d['status'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $emptyTitle = 'No division health data';
        $emptyMessage = 'Division averages will appear here once calculated.';
        $emptyVisible = count($divisions) === 0;
        require __DIR__ . '/../partials/divisional/empty-state.view.php';
        ?>
    </section>

    <section class="member-panel" aria-labelledby="zonalcoordinator-announcements-heading">
        <div class="member-panel-header">
            <div><p class="member-eyebrow">Stay informed</p><h2 id="zonalcoordinator-announcements-heading">Zone announcements</h2></div>
            <a class="member-panel-link" href="<?= ROOT ?>/announcements">View all</a>
        </div>
        <div class="member-announcement-list">
            <?php foreach ($announcements as $announcement): ?>
                <article class="member-announcement-item<?= !empty($announcement['is_new']) ? ' is-new' : '' ?>">
                    <span class="member-list-dot" aria-hidden="true"></span>
                    <div class="member-list-copy"><div class="member-list-meta"><span><?= $e($announcement['age']) ?></span><?php if (!empty($announcement['is_new'])): ?><span class="member-badge member-badge--new">New</span><?php endif; ?></div><h3><?= $e($announcement['title']) ?></h3><p><?= $e($announcement['summary']) ?></p><span class="member-scope-tag">Zonal</span></div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="member-panel" aria-labelledby="zonalcoordinator-events-heading">
        <div class="member-panel-header"><div><p class="member-eyebrow">Plan ahead</p><h2 id="zonalcoordinator-events-heading">Upcoming zonal programmes</h2></div></div>
        <div class="member-event-list">
            <?php foreach ($upcomingEvents as $event): ?>
                <article class="member-event-item"><div class="member-event-icon" aria-hidden="true"><?= yn_icon('calendar') ?></div><div class="member-event-copy"><div class="member-event-heading"><h3><?= $e($event['title']) ?></h3><span class="member-scope-text">Zonal</span></div><p><?= $e($event['date']) ?>, <?= $e($event['location']) ?></p></div><span class="member-status member-status--<?= $e($event['status_key']) ?>"><?= $e($event['status']) ?></span></article>
            <?php endforeach; ?>
        </div>
    </section>

</section>


<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
