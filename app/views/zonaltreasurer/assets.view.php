<?php
/**
 * Zonal Assets — Z0 scaffolding.
 * Full C7 shape at zonal scope lands in Z9.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="club-page" aria-labelledby="zonal-assets-heading">
    <h1 id="zonal-assets-heading" class="sr-only">Zonal assets</h1>

    <p class="club-back-row"><a class="club-btn-secondary" href="<?= ROOT ?>/zonaltreasurer"><span aria-hidden="true">‹</span> Treasurer Overview</a></p>

    <section class="club-panel" aria-labelledby="zonal-assets-panel-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Zone</p>
                <h2 id="zonal-assets-panel-heading">Zonal assets</h2>
            </div>
        </div>

        <p class="club-muted">Review assets owned and held by the zone.</p>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
