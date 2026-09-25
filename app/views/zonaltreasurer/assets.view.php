<?php
/**
 * Zonal Assets — zonal-level asset inventory; register + transfer custody modals.
 * Presentation-only: no DB writes; backend contract unchanged.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$title = 'Zonal Assets - YouthNexus';
$pageTitle = 'Zonal Assets';
$pageDescription = 'Record ownership and custody of zonal assets.';
$currentRoute = 'zonaltreasurer/assets';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/zonal.js'];

$summaryCards = [
    ['value' => (string) ($stats['total'] ?? 0), 'label' => 'Total assets', 'note' => 'Gampaha Zone', 'icon' => 'file', 'tone' => 'blue'],
    ['value' => (string) ($stats['available'] ?? 0), 'label' => 'Available', 'note' => 'Ready for assignment', 'icon' => 'award', 'tone' => 'green'],
    ['value' => (string) ($stats['in_use'] ?? 0), 'label' => 'In use', 'note' => 'Under custody', 'icon' => 'user', 'tone' => 'amber'],
    ['value' => 'LKR ' . number_format($stats['valuation'] ?? 0, 2), 'label' => 'Total valuation', 'note' => 'Current inventory value', 'icon' => 'clock', 'tone' => 'blue'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="zonal-assets-heading">
    <h1 id="zonal-assets-heading" class="visually-hidden">Zonal assets</h1>

    <?php if ($flash): ?><div class="dw-alert dw-alert--success" role="status"><?= $e($flash) ?></div><?php endif; ?>

    <div class="dw-summary-grid" aria-label="Zonal asset summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="dw-toolbar" aria-label="Asset tools">
        <a class="dw-button dw-button--secondary" href="<?= ROOT ?>/zonaltreasurer/exportassets"><?= yn_icon('download') ?> Export</a>
        <button type="button" class="dw-button dw-button--primary" data-modal-open="asset-register">Register asset</button>
    </div>

    <form method="get" action="<?= ROOT ?>/zonaltreasurer/assets" class="dw-filter-panel" role="search" aria-label="Filter zonal assets">
        <h2 class="dw-filter-panel__heading">Filter Zonal Assets</h2>
        <div class="dw-filter-grid">
            <div class="dw-field">
                <label for="zonal-asset-search">Search assets</label>
                <input id="zonal-asset-search" type="search" name="search" value="<?= $e($search) ?>" placeholder="Search asset, serial or custodian">
            </div>
            <div class="dw-field">
                <label for="zonal-asset-category">Category</label>
                <select id="zonal-asset-category" name="category"><option value="">All categories</option><?php foreach ($categories as $item): ?><option value="<?= $e($item) ?>" <?= $category === $item ? 'selected' : '' ?>><?= $e($item) ?></option><?php endforeach; ?></select>
            </div>
            <div class="dw-field">
                <label for="zonal-asset-status">Status</label>
                <select id="zonal-asset-status" name="status"><option value="">All statuses</option><option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available</option><option value="inuse" <?= $status === 'inuse' ? 'selected' : '' ?>>In use</option></select>
            </div>
        </div>
        <div class="dw-filter-actions">
            <button type="submit" class="dw-button dw-button--primary">Apply filters</button>
        </div>
    </form>

    <section class="dw-panel" aria-labelledby="zonal-assets-list-heading">
        <header class="dw-panel__header">
            <div>
                <h2 id="zonal-assets-list-heading">Zonal asset inventory</h2>
                <p>Record ownership and custody of zonal assets. Physical asset verification is not a zonal workflow.</p>
            </div>
            <span class="dw-count"><?= count($assets) ?> <?= count($assets) === 1 ? 'asset' : 'assets' ?></span>
        </header>
        <div class="dw-table-wrap">
            <table class="dw-table">
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Category</th>
                        <th>Serial</th>
                        <th>Purchase date</th>
                        <th>Valuation</th>
                        <th>Custodian</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $asset): ?>
                        <tr>
                            <td><strong><?= $e($asset['name']) ?></strong><br><span class="dw-table__reference"><?= $e($asset['id']) ?></span></td>
                            <td><?= $e($asset['category']) ?></td>
                            <td><?= $e($asset['serial']) ?></td>
                            <td><?= $e($asset['purchase_date']) ?></td>
                            <td class="dw-money">LKR <?= number_format($asset['valuation'], 2) ?></td>
                            <td><?= $e($asset['custodian']) ?></td>
                            <td><?php $status = $asset['status']; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                            <td>
                                <div class="dw-row-actions">
                                    <button type="button" class="dw-button dw-button--ghost" data-transfer="<?= $e($asset['id']) ?>" data-name="<?= $e($asset['name']) ?>">Transfer custody</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $emptyTitle = 'No zonal assets match these filters';
        $emptyMessage = 'Try changing the current search or filters.';
        $emptyVisible = count($assets) === 0;
        require __DIR__ . '/../partials/divisional/empty-state.view.php';
        ?>
    </section>
</section>

<div id="asset-register" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="register-asset-title" aria-hidden="true" hidden>
    <div class="dw-modal__backdrop" data-modal-close></div>
    <div class="dw-modal__dialog">
        <header class="dw-modal__header">
            <div>
                <p>Zonal Treasurer action</p>
                <h2 id="register-asset-title">Register zonal asset</h2>
            </div>
            <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
        </header>
        <div class="dw-modal__body">
            <form id="asset-register-form" method="post" action="<?= ROOT ?>/zonaltreasurer/addasset">
                <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
                <div class="dw-field dw-field--span-2">
                    <label for="asset-name">Asset name</label>
                    <input id="asset-name" name="name" maxlength="150" required>
                </div>
                <div class="dw-field">
                    <label for="asset-serial">Serial number</label>
                    <input id="asset-serial" name="serial" maxlength="80" required>
                </div>
                <div class="dw-field">
                    <label for="asset-category">Category</label>
                    <select id="asset-category" name="category" required><option value="">Select category</option><?php foreach ($categories as $item): ?><option value="<?= $e($item) ?>"><?= $e($item) ?></option><?php endforeach; ?></select>
                </div>
                <div class="dw-field">
                    <label for="asset-date">Purchase date</label>
                    <input id="asset-date" name="purchase_date" type="date" required>
                </div>
                <div class="dw-field">
                    <label for="asset-value">Valuation (LKR)</label>
                    <input id="asset-value" name="valuation" type="number" min="0.01" step="0.01" required>
                </div>
            </form>
        </div>
        <footer class="dw-modal__footer">
            <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
            <button type="submit" class="dw-button dw-button--primary" form="asset-register-form">Register asset</button>
        </footer>
    </div>
</div>

<div id="asset-transfer" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="transfer-title" aria-hidden="true" hidden>
    <div class="dw-modal__backdrop" data-modal-close></div>
    <div class="dw-modal__dialog">
        <header class="dw-modal__header">
            <div>
                <p>Zonal asset custody</p>
                <h2 id="transfer-title">Transfer custody</h2>
            </div>
            <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
        </header>
        <div class="dw-modal__body">
            <p id="transfer-asset-name"></p>
            <form id="asset-transfer-form" method="post" action="<?= ROOT ?>/zonaltreasurer/transferasset">
                <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
                <input id="transfer-asset-id" type="hidden" name="asset_id">
                <div class="dw-field">
                    <label for="asset-custodian">Custodian</label>
                    <select id="asset-custodian" name="custodian"><option>Gampaha Zone Store</option><option>Gampaha Division</option><option>Ja-Ela Division</option><option>Negombo Division</option></select>
                </div>
                <div class="dw-field">
                    <label for="asset-note">Transfer note</label>
                    <textarea id="asset-note" name="note" rows="3" maxlength="500" required></textarea>
                </div>
            </form>
        </div>
        <footer class="dw-modal__footer">
            <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
            <button type="submit" class="dw-button dw-button--primary" form="asset-transfer-form">Record transfer</button>
        </footer>
    </div>
</div>


<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
