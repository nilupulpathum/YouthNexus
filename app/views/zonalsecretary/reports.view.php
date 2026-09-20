<?php
/**
 * Aggregate Reports — Z0 scaffolding.
 * Full division rollups land in Z5.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="club-page" aria-labelledby="zonal-reports-heading">
    <h1 id="zonal-reports-heading" class="sr-only">Aggregate reports</h1>

    <p class="club-back-row"><a class="club-btn-secondary" href="<?= ROOT ?>/zonalsecretary"><span aria-hidden="true">‹</span> Secretary Overview</a></p>

    <section class="club-panel" aria-labelledby="zonal-reports-panel-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Zone</p>
                <h2 id="zonal-reports-panel-heading">Aggregate reports</h2>
            </div>
        </div>

        <p class="club-muted">Review division rollups for the selected reporting period.</p>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
