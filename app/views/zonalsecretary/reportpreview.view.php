<?php
require __DIR__ . '/../partials/icons.view.php';

$report = $report ?? null;
$snapshot = is_array($snapshot ?? null) ? $snapshot : [];
$zoneName = $zoneName ?? 'Zone';
$pageStyles = [ROOT . '/assets/css/managereports.css'];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="rpt-content" aria-labelledby="zonal-preview-heading">
    <div class="rpt-header-bar">
        <div>
            <h1 id="zonal-preview-heading" class="rpt-section-title"><?= htmlspecialchars($report->type_name ?? 'Report', ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="rpt-section-desc"><?= htmlspecialchars($zoneName, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(($snapshot['range'][0] ?? '') . ' to ' . ($snapshot['range'][1] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="rpt-header-actions">
            <a class="rpt-btn rpt-btn--ghost" href="<?= ROOT ?>/zonalsecretary/reports">Back to reports</a>
            <a class="rpt-btn rpt-btn--outline" href="<?= ROOT ?>/zonalsecretary/exportreport/<?= (int) ($report->report_id ?? 0) ?>"><?= yn_icon('download') ?> Export CSV</a>
        </div>
    </div>

    <div class="rpt-section-head">
        <h2 class="rpt-section-head__title"><?= htmlspecialchars($snapshot['title'] ?? 'Snapshot', ENT_QUOTES, 'UTF-8') ?></h2>
    </div>
    <?php if (!empty($snapshot['rows'])): ?>
        <div class="rpt-cards">
            <?php foreach ($snapshot['rows'] as $row): ?>
                <?php $cells = array_values(is_array($row) ? $row : (array) $row); ?>
                <article class="rpt-card">
                    <div class="rpt-card__top">
                        <div class="rpt-card__icon"><?= yn_icon('file') ?></div>
                    </div>
                    <h3 class="rpt-card__title"><?= htmlspecialchars((string) ($cells[0] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="rpt-card__desc"><?= htmlspecialchars(implode(' · ', array_slice(array_map('strval', $cells), 1)), ENT_QUOTES, 'UTF-8') ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="rpt-empty-box">
            <?= yn_icon('file') ?>
            <p>This report contains no rows for the selected period.</p>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
