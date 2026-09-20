<?php
/**
 * Zonal Events — Z0 scaffolding.
 * Full event form lands in Z4.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="club-page" aria-labelledby="zonal-events-heading">
    <h1 id="zonal-events-heading" class="sr-only">Zonal events</h1>

    <p class="club-back-row"><a class="club-btn-secondary" href="<?= ROOT ?>/zonalsecretary"><span aria-hidden="true">‹</span> Secretary Overview</a></p>

    <section class="club-panel" aria-labelledby="zonal-events-panel-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Zone</p>
                <h2 id="zonal-events-panel-heading">Zonal events</h2>
            </div>
        </div>

        <p class="club-muted">Create zonal events, apply the budget check, and notify divisions and clubs.</p>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
