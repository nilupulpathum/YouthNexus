<?php
/**
 * Zonal Secretary Overview — Z0 scaffolding.
 * Full shortcuts and stats land in Z4.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$links = [
    ['title' => 'Zonal Events', 'desc' => 'Create zonal events with budget mock', 'href' => 'zonalsecretary/events', 'icon' => 'calendar'],
    ['title' => 'Aggregate Reports', 'desc' => 'Division rollups across the zone', 'href' => 'zonalsecretary/reports', 'icon' => 'file'],
    ['title' => 'Announcements', 'desc' => 'Zone-wide communications', 'href' => 'announcements', 'icon' => 'bell'],
];
?>

<section class="club-page" aria-labelledby="zonalsecretary-overview-heading">
    <h1 id="zonalsecretary-overview-heading" class="sr-only">Zonal secretary overview</h1>

    <section class="club-panel" aria-labelledby="zonalsecretary-links-heading">
        <div class="club-panel-header">
            <div>
    <p class="club-eyebrow">Gampaha Zone</p>
                <h2 id="zonalsecretary-links-heading">Secretary workspace</h2>
            </div>
        </div>

        <p class="club-muted">Manage events, attendance statistics, reports, and zone communications.</p>

        <div class="club-list">
            <?php foreach ($links as $s): ?>
                <article class="club-list-item">
                    <div class="club-list-icon" aria-hidden="true"><?= yn_icon($s['icon']) ?></div>
                    <div class="club-list-copy">
                        <h3><?= $escape($s['title']) ?></h3>
                        <p><?= $escape($s['desc']) ?></p>
                    </div>
                    <div class="club-event-side">
                        <a class="club-btn-small" href="<?= ROOT ?>/<?= $escape($s['href']) ?>">Open</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
