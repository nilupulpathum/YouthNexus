<?php
$escape = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="club-page" aria-labelledby="zonal-assets-heading">
    <h1 id="zonal-assets-heading" class="sr-only">Zonal assets</h1>
    <?php if ($flash): ?><div class="club-error-banner" role="status"><span class="club-error-icon"><?= yn_icon('info') ?></span><p><?= $escape($flash) ?></p></div><?php endif; ?>

    <div class="club-stat-grid" aria-label="Zonal asset summary">
        <article class="club-stat-card"><p class="club-stat-label">Total assets</p><p class="club-stat-value"><?= $escape($stats['total']) ?></p></article>
        <article class="club-stat-card"><p class="club-stat-label">Available</p><p class="club-stat-value"><?= $escape($stats['available']) ?></p></article>
        <article class="club-stat-card"><p class="club-stat-label">In use</p><p class="club-stat-value"><?= $escape($stats['in_use']) ?></p></article>
        <article class="club-stat-card"><p class="club-stat-label">Total valuation</p><p class="club-stat-value club-stat-value--small">LKR <?= number_format($stats['valuation'], 2) ?></p></article>
    </div>

    <section class="club-panel" aria-labelledby="zonal-assets-list-heading">
        <div class="club-panel-header">
            <div><p class="club-eyebrow">Gampaha Zone</p><h2 id="zonal-assets-list-heading">Zonal asset inventory</h2></div>
            <div class="club-event-side">
                <a class="club-btn-secondary" href="<?= ROOT ?>/zonaltreasurer/exportassets"><?= yn_icon('download') ?> Export</a>
                <button type="button" class="club-btn-primary" data-open="asset-register">Register asset</button>
            </div>
        </div>
        <p class="club-muted">Record ownership and custody of zonal assets. Physical asset verification is not a zonal workflow.</p>

        <form method="get" action="<?= ROOT ?>/zonaltreasurer/assets" class="club-filters" role="search" aria-label="Filter zonal assets">
            <label class="club-search"><span class="icon"><?= yn_icon('eye') ?></span><span class="sr-only">Search assets</span><input type="search" name="search" value="<?= $escape($search) ?>" placeholder="Search asset, serial or custodian"></label>
            <label class="club-filter-field"><span class="sr-only">Category</span><select name="category"><option value="">All categories</option><?php foreach ($categories as $item): ?><option value="<?= $escape($item) ?>" <?= $category === $item ? 'selected' : '' ?>><?= $escape($item) ?></option><?php endforeach; ?></select></label>
            <label class="club-filter-field"><span class="sr-only">Status</span><select name="status"><option value="">All statuses</option><option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available</option><option value="inuse" <?= $status === 'inuse' ? 'selected' : '' ?>>In use</option></select></label>
            <button type="submit" class="club-btn-secondary">Apply filters</button>
        </form>

        <div class="club-table-wrap"><table class="club-table"><thead><tr><th>Asset</th><th>Category</th><th>Serial</th><th>Purchase date</th><th>Valuation</th><th>Custodian</th><th>Status</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($assets as $asset): ?><tr><td><div class="club-asset-cell"><span class="club-thumb" aria-hidden="true"><?= yn_icon('file') ?></span><span><strong><?= $escape($asset['name']) ?></strong><span class="club-asset-id"><?= $escape($asset['id']) ?></span></span></div></td><td><?= $escape($asset['category']) ?></td><td><?= $escape($asset['serial']) ?></td><td><?= $escape($asset['purchase_date']) ?></td><td>LKR <?= number_format($asset['valuation'], 2) ?></td><td><?= $escape($asset['custodian']) ?></td><td><span class="club-pill club-pill--<?= $escape($asset['status_key']) ?>"><?= $escape($asset['status']) ?></span></td><td><button type="button" class="club-btn-small" data-transfer="<?= $escape($asset['id']) ?>" data-name="<?= $escape($asset['name']) ?>">Transfer custody</button></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php if (!$assets): ?><p class="club-note">No zonal assets match these filters.</p><?php endif; ?>
    </section>
</section>

<div id="asset-register" class="popup-overlay" hidden><div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="register-asset-title"><button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button><p class="club-eyebrow">Zonal Treasurer action</p><h2 id="register-asset-title">Register zonal asset</h2><form method="post" action="<?= ROOT ?>/zonaltreasurer/addasset" class="club-form"><input type="hidden" name="csrf_token" value="<?= $escape($csrf_token) ?>"><div class="club-form-grid"><div class="club-field club-field--full"><label for="asset-name">Asset name</label><input id="asset-name" name="name" maxlength="150" required></div><div class="club-field"><label for="asset-serial">Serial number</label><input id="asset-serial" name="serial" maxlength="80" required></div><div class="club-field"><label for="asset-category">Category</label><select id="asset-category" name="category" required><option value="">Select category</option><?php foreach ($categories as $item): ?><option value="<?= $escape($item) ?>"><?= $escape($item) ?></option><?php endforeach; ?></select></div><div class="club-field"><label for="asset-date">Purchase date</label><input id="asset-date" name="purchase_date" type="date" required></div><div class="club-field"><label for="asset-value">Valuation (LKR)</label><input id="asset-value" name="valuation" type="number" min="0.01" step="0.01" required></div></div><div class="club-modal-footer"><button type="button" class="club-btn-secondary" data-close>Cancel</button><button type="submit" class="club-btn-primary">Register asset</button></div></form></div></div>

<div id="asset-transfer" class="popup-overlay" hidden><div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="transfer-title"><button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button><p class="club-eyebrow">Zonal asset custody</p><h2 id="transfer-title">Transfer custody</h2><p id="transfer-asset-name" class="club-sub-note"></p><form method="post" action="<?= ROOT ?>/zonaltreasurer/transferasset" class="club-form"><input type="hidden" name="csrf_token" value="<?= $escape($csrf_token) ?>"><input id="transfer-asset-id" type="hidden" name="asset_id"><div class="club-field"><label for="asset-custodian">Custodian</label><select id="asset-custodian" name="custodian"><option>Gampaha Zone Store</option><option>Gampaha Division</option><option>Ja-Ela Division</option><option>Negombo Division</option></select></div><div class="club-field"><label for="asset-note">Transfer note</label><textarea id="asset-note" name="note" rows="3" maxlength="500" required></textarea></div><div class="club-modal-footer"><button type="button" class="club-btn-secondary" data-close>Cancel</button><button type="submit" class="club-btn-primary">Record transfer</button></div></form></div></div>

<script>document.querySelectorAll('[data-open]').forEach(button=>button.addEventListener('click',()=>{document.getElementById(button.dataset.open).hidden=false;}));document.querySelectorAll('[data-close]').forEach(button=>button.addEventListener('click',()=>{button.closest('.popup-overlay').hidden=true;}));document.querySelectorAll('[data-transfer]').forEach(button=>button.addEventListener('click',()=>{document.getElementById('transfer-asset-id').value=button.dataset.transfer;document.getElementById('transfer-asset-name').textContent=button.dataset.name;document.getElementById('asset-transfer').hidden=false;}));</script>
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
