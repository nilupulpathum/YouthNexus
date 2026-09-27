<?php
/**
 * Zonal Treasurer Overview — fund summary + workspace links.
 * Fund figures come from live FundAllocation rows and the zone ledger.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$title = 'Zonal Treasurer Overview - YouthNexus';
$pageTitle = 'Zonal Treasurer';
$pageDescription = 'Funds received by ' . ($zoneName ?? 'Zone') . '.';
$currentRoute = 'zonaltreasurer/index';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/member-dashboard.css?v=20261001'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js'];

$links = [
    ['title' => 'Allocate Funds', 'desc' => 'Distribute NYSC funds to divisions under this zone', 'href' => 'zonaltreasurer/allocate', 'icon' => 'file'],
    ['title' => 'Audit Finance', 'desc' => 'Income and expense breakdown with audit note', 'href' => 'zonaltreasurer/audit', 'icon' => 'eye'],
    ['title' => 'Zonal Assets', 'desc' => 'Zonal-level asset inventory', 'href' => 'zonaltreasurer/assets', 'icon' => 'calendar'],
    ['title' => 'Zonal Ledger', 'desc' => 'Zone income and expenses with running balance', 'href' => 'zonaltreasurer/ledger', 'icon' => 'clipboard'],
    ['title' => 'Void Requests', 'desc' => 'Pending void requests from divisions', 'href' => 'zonaltreasurer/voids', 'icon' => 'clock'],
];

$summaryCards = [
    ['value' => 'LKR ' . number_format($fundStats['budget_cap'] ?? 0, 2), 'label' => 'NYSC funds received', 'note' => ($zoneName ?? 'Zone'), 'icon' => 'file', 'tone' => 'blue'],
    ['value' => 'LKR ' . number_format($fundStats['year_total'] ?? 0, 2), 'label' => 'Allocated to divisions', 'note' => 'Completed zone allocations', 'icon' => 'award', 'tone' => 'green'],
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
                <p><?= $e($zoneName ?? 'Zone') ?></p>
                <h2 id="zonaltreasurer-links-heading">Treasurer workspace</h2>
            </div>
            <span class="dw-count"><?= count($links) ?> shortcuts</span>
        </header>
        <div class="dw-panel__body">
            <div class="dw-record-grid">
                <?php foreach ($links as $s): ?>
                    <article class="dw-record-card">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon($s['icon']) ?></span>
                                <div class="dw-record-card__meta"><span>Treasurer action</span></div>
                            </div>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e($s['title']) ?></h3>
                        <div class="dw-record-card__details">
                            <span><?= $e($s['desc']) ?></span>
                        </div>
                        <div class="dw-record-card__footer">
                            <span class="dw-record-card__reference">Shortcut</span>
                            <div class="dw-record-card__actions">
                                <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/<?= $e($s['href']) ?>">Open</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</section>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
