<?php
/**
 * Void Requests — Z0 scaffolding.
 * Full Pending-Void queue lands in Z10.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="club-page" aria-labelledby="zonal-voids-heading">
    <h1 id="zonal-voids-heading" class="sr-only">Void requests</h1>

    <p class="club-back-row"><a class="club-btn-secondary" href="<?= ROOT ?>/zonaltreasurer"><span aria-hidden="true">‹</span> Treasurer Overview</a></p>

    <section class="club-panel" aria-labelledby="zonal-voids-panel-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Zone</p>
                <h2 id="zonal-voids-panel-heading">Void requests</h2>
            </div>
        </div>

        <p class="club-muted">Review pending void requests from divisions.</p>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
