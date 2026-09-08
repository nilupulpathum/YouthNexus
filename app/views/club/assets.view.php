<?php
/**
 * Club Assets — C1 shell. Registration + custody transfer land in C7.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$stats  = $stats ?? [];
$assets = $assets ?? [];
$can_transfer = !empty($can_transfer);
?>

<section class="club-page" aria-labelledby="club-assets-heading">
    <h1 id="club-assets-heading" class="sr-only">Club assets</h1>

    <div class="club-stat-grid" aria-label="Asset summary">
        <article class="club-stat-card">
            <p class="club-stat-label">Total assets</p>
            <p class="club-stat-value"><?= $escape($stats['total'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Available</p>
            <p class="club-stat-value"><?= $escape($stats['available'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">In use</p>
            <p class="club-stat-value"><?= $escape($stats['in_use'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Total valuation</p>
            <p class="club-stat-value club-stat-value--small"><?= $escape($stats['valuation'] ?? '') ?></p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="club-assets-list-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Youth Development Club</p>
                <h2 id="club-assets-list-heading">Inventory</h2>
            </div>
        </div>

        <div class="club-table-wrap">
            <table class="club-table">
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Serial</th>
                        <th>Valuation</th>
                        <th>Custodian</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $a): ?>
                        <tr>
                            <td><strong><?= $escape($a['name'] ?? '') ?></strong></td>
                            <td><?= $escape($a['serial'] ?? '') ?></td>
                            <td><?= $escape($a['valuation'] ?? '') ?></td>
                            <td><?= $escape($a['custodian'] ?? '') ?></td>
                            <td><span class="club-pill club-pill--<?= $escape($a['status_key'] ?? 'available') ?>"><?= $escape($a['status'] ?? '') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($can_transfer): ?>
            <p class="club-note">Custody-transfer actions land in C7 — this shell is read-only.</p>
        <?php endif; ?>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
