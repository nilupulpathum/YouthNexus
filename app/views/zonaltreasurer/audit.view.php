<?php
/**
 * Audit Finance — Z0 scaffolding.
 * Full breakdown and flag flow land in Z8.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="club-page" aria-labelledby="zonal-audit-heading">
    <h1 id="zonal-audit-heading" class="sr-only">Audit finance</h1>

    <p class="club-back-row"><a class="club-btn-secondary" href="<?= ROOT ?>/zonaltreasurer"><span aria-hidden="true">‹</span> Treasurer Overview</a></p>

    <section class="club-panel" aria-labelledby="zonal-audit-panel-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Zone</p>
                <h2 id="zonal-audit-panel-heading">Audit finance</h2>
            </div>
        </div>

        <p class="club-muted">Review income and expenses, record an audit note, and flag discrepancies.</p>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
