<?php
/**
 * Zonal Treasurer Overview — Z0 scaffolding.
 * Full fund summary lands in Z7.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$links = [
    ['title' => 'Allocate Funds', 'desc' => 'Distribute NYSC funds to divisions under this zone', 'href' => 'zonaltreasurer/allocate', 'icon' => 'file'],
    ['title' => 'Audit Finance', 'desc' => 'Income and expense breakdown with audit note', 'href' => 'zonaltreasurer/audit', 'icon' => 'eye'],
    ['title' => 'Zonal Assets', 'desc' => 'Zonal-level asset inventory', 'href' => 'zonaltreasurer/assets', 'icon' => 'calendar'],
    ['title' => 'Void Requests', 'desc' => 'Pending void requests from clubs', 'href' => 'zonaltreasurer/voids', 'icon' => 'clock'],
];
?>

<section class="club-page" aria-labelledby="zonaltreasurer-overview-heading">
    <h1 id="zonaltreasurer-overview-heading" class="sr-only">Zonal treasurer overview</h1>

    <p class="club-muted">Funds received by Gampaha Zone. Preview allocations persist for this session.</p>
    <div class="club-stat-grid">
        <?php foreach (['NYSC funds received' => 'budget_cap', 'Allocated to divisions' => 'year_total', 'Available zonal balance' => 'remaining_budget'] as $label => $key): ?>
        <article class="club-stat-card">
            <p class="club-stat-label"><?= $escape($label) ?></p>
            <p class="club-stat-value">LKR <?= number_format($fundStats[$key] ?? 0, 2) ?></p>
        </article>
        <?php endforeach; ?>
    </div>

    <section class="club-panel" aria-labelledby="zonaltreasurer-links-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Zone</p>
                <h2 id="zonaltreasurer-links-heading">Treasurer workspace</h2>
            </div>
        </div>

        <p class="club-muted">Distribute funds to divisions and manage the zone's finance records.</p>

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
