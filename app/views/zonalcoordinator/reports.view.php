<?php
$escape = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/managereports.css">
<section class="rpt-content" aria-labelledby="coordinator-reports-heading">
    <div class="rpt-header-bar"><div><h1 id="coordinator-reports-heading" class="rpt-section-title">Aggregate Divisional Reports</h1><p class="rpt-section-desc">Zone-generated rollups for <?= $escape($zoneName ?? 'the zone') ?> divisions.</p></div><div class="rpt-header-actions"><a class="rpt-btn rpt-btn--outline" href="<?= ROOT ?>/zonalcoordinator/exportreports"><?= yn_icon('download') ?> Export list</a></div></div>
    <div class="rpt-section-head"><h2 class="rpt-section-head__title">Available reports <span class="rpt-count-note">(<?= count($reports) ?> shown)</span></h2></div>
    <?php if (!$reports): ?>
        <div class="rpt-empty-box">
            <?= yn_icon('file') ?>
            <p>No reports generated yet.</p>
            <p>The zonal secretary generates reports from the catalog.</p>
        </div>
    <?php else: ?>
    <div class="rpt-cards"><?php foreach ($reports as $report): ?><article class="rpt-card"><div class="rpt-card__top"><div class="rpt-card__icon"><?= yn_icon('file') ?></div><span class="rpt-card__badge">Report</span></div><div class="rpt-card__category">Aggregate</div><h3 class="rpt-card__title"><?= $escape($report['title']) ?></h3><p class="rpt-card__desc"><?= $escape($report['division']) ?>, Generated <?= $escape($report['date']) ?></p><div class="rpt-card__foot"><span class="rpt-card__date"><?= $escape($zoneName ?? 'Zone') ?> scope</span><a class="rpt-view-btn" href="<?= ROOT ?>/zonalcoordinator/reportpreview/<?= (int) $report['id'] ?>">Preview</a></div></article><?php endforeach; ?></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
