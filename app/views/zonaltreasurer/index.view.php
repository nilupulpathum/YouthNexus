<?php
/**
 * Zonal Treasurer Overview — fund summary + workspace links.
 * Presentation-only; preview allocations persist for this session.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$title = 'Zonal Treasurer Overview - YouthNexus';
$pageTitle = 'Zonal Treasurer';
$pageDescription = 'Funds received by Gampaha Zone. Preview allocations persist for this session.';
$currentRoute = 'zonaltreasurer/index';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js'];

$links = [
    ['title' => 'Allocate Funds', 'desc' => 'Distribute NYSC funds to divisions under this zone', 'href' => 'zonaltreasurer/allocate', 'icon' => 'file'],
    ['title' => 'Audit Finance', 'desc' => 'Income and expense breakdown with audit note', 'href' => 'zonaltreasurer/audit', 'icon' => 'eye'],
    ['title' => 'Zonal Assets', 'desc' => 'Zonal-level asset inventory', 'href' => 'zonaltreasurer/assets', 'icon' => 'calendar'],
    ['title' => 'Void Requests', 'desc' => 'Pending void requests from clubs', 'href' => 'zonaltreasurer/voids', 'icon' => 'clock'],
];

$summaryCards = [
    ['value' => 'LKR ' . number_format($fundStats['budget_cap'] ?? 0, 2), 'label' => 'NYSC funds received', 'note' => 'Gampaha Zone', 'icon' => 'file', 'tone' => 'blue'],
    ['value' => 'LKR ' . number_format($fundStats['year_total'] ?? 0, 2), 'label' => 'Allocated to divisions', 'note' => 'Distributed this session', 'icon' => 'award', 'tone' => 'green'],
    ['value' => 'LKR ' . number_format($fundStats['remaining_budget'] ?? 0, 2), 'label' => 'Available zonal balance', 'note' => 'Remaining to allocate', 'icon' => 'clock', 'tone' => 'amber'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="zonaltreasurer-overview-heading">
    <h1 id="zonaltreasurer-overview-heading" class="visually-hidden">Zonal treasurer overview</h1>

    <div class="dw-summary-grid dw-summary-grid--three" aria-label="Zonal fund summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <section class="dw-panel" aria-labelledby="zonaltreasurer-links-heading">
        <header class="dw-panel__header">
            <div>
                <h2 id="zonaltreasurer-links-heading">Treasurer workspace</h2>
                <p>Gampaha Zone — distribute funds to divisions and manage the zone's finance records.</p>
            </div>
            <span class="dw-count"><?= count($links) ?> workspaces</span>
        </header>
        <div class="dw-table-wrap">
            <table class="dw-table">
                <thead>
                    <tr>
                        <th>Workspace</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $s): ?>
                        <tr>
                            <td><strong><?= $e($s['title']) ?></strong></td>
                            <td><?= $e($s['desc']) ?></td>
                            <td>
                                <div class="dw-row-actions">
                                    <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/<?= $e($s['href']) ?>">Open</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
