<?php
require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/managereports.css">

<section class="rpt-content" aria-labelledby="zonal-preview-heading">
    <div class="rpt-header-bar">
        <div>
            <h1 id="zonal-preview-heading" class="rpt-section-title">Gampaha Zone Report Preview</h1>
            <p class="rpt-section-desc">Aggregate divisional data · <?= htmlspecialchars($reportId, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="rpt-header-actions">
            <a class="rpt-btn rpt-btn--ghost" href="<?= ROOT ?>/zonalsecretary/reports">Back to reports</a>
            <a class="rpt-btn rpt-btn--outline" href="<?= ROOT ?>/zonalsecretary/exportreports"><?= yn_icon('download') ?> Export CSV</a>
        </div>
    </div>

    <div class="rpt-section-head">
        <h2 class="rpt-section-head__title">Zone-wide reporting snapshot</h2>
    </div>
    <div class="rpt-cards">
        <article class="rpt-card">
            <div class="rpt-card__icon"><?= yn_icon('user') ?></div>
            <div class="rpt-card__category">Club network</div>
            <p class="rpt-card__title">19 reporting clubs</p>
            <p class="rpt-card__desc">Across Gampaha, Ja-Ela and Negombo divisions.</p>
        </article>
        <article class="rpt-card">
            <div class="rpt-card__icon"><?= yn_icon('calendar') ?></div>
            <div class="rpt-card__category">Programmes</div>
            <p class="rpt-card__title">18 recorded events</p>
            <p class="rpt-card__desc">Current zonal reporting period.</p>
        </article>
        <article class="rpt-card">
            <div class="rpt-card__icon"><?= yn_icon('check') ?></div>
            <div class="rpt-card__category">Attendance</div>
            <p class="rpt-card__title">78% attendance</p>
            <p class="rpt-card__desc">486 present across 18 recorded sessions.</p>
        </article>
    </div>
</section>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
