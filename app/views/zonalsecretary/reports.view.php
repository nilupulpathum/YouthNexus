<?php
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/managereports.css">

<section class="rpt-content" aria-labelledby="zonal-reports-heading">
    <div class="rpt-header-bar">
        <div>
            <h1 id="zonal-reports-heading" class="rpt-section-title">Aggregate Divisional Reports</h1>
            <p class="rpt-section-desc">Report catalog and rollups for Gampaha Zone divisions only.</p>
        </div>
        <div class="rpt-header-actions">
            <a class="rpt-btn rpt-btn--outline" href="<?= ROOT ?>/zonalsecretary/exportreports">
                <?= yn_icon('download') ?> Export zone summary
            </a>
        </div>
    </div>

    <?php if (!empty($flash)): ?>
        <div class="dw-alert dw-alert--<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status">
            <?= $escape($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <form method="get" action="<?= ROOT ?>/zonalsecretary/reports" class="rpt-toolbar">
        <div class="rpt-filter-pill">
            <?= yn_icon('file') ?>
            <span class="rpt-select-wrap">
                <select name="category" aria-label="Report category">
                    <option value="">All report categories</option>
                    <?php foreach ($catalog as $categoryName => $types): ?>
                        <option value="<?= $escape($categoryName) ?>" <?= $category === $categoryName ? 'selected' : '' ?>><?= $escape($categoryName) ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
            <span class="rpt-pill-divider"></span>
            <input class="rpt-pill-input" type="search" name="search" value="<?= $escape($search) ?>" placeholder="Search reports">
        </div>
        <button type="submit" class="rpt-btn rpt-btn--ghost"><?= yn_icon('file') ?> Apply filters</button>
    </form>

    <div class="rpt-section-head">
        <h2 class="rpt-section-head__title">Generate a report</h2>
    </div>
    <form method="post" action="<?= ROOT ?>/zonalsecretary/generateReport" class="rpt-toolbar">
        <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token'] ?? '') ?>">
        <div class="rpt-filter-pill">
            <span class="rpt-select-wrap">
                <select name="report_type_id" aria-label="Report type" required>
                    <option value="">Select report type...</option>
                    <?php foreach ($catalog as $categoryName => $types): ?>
                        <optgroup label="<?= $escape($categoryName) ?>">
                            <?php foreach ($types as $type): ?>
                                <option value="<?= (int) $type->report_type_id ?>"><?= $escape($type->type_name) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </span>
            <span class="rpt-pill-divider"></span>
            <input class="rpt-pill-input" type="date" name="start" required aria-label="Start date">
            <input class="rpt-pill-input" type="date" name="end" required aria-label="End date">
            <span class="rpt-select-wrap">
                <select name="format" aria-label="Format">
                    <option value="OnScreen">On-screen</option>
                    <option value="CSV">CSV</option>
                    <option value="PDF">PDF</option>
                </select>
            </span>
        </div>
        <button type="submit" class="rpt-btn rpt-btn--ghost">Generate</button>
    </form>

    <div class="rpt-section-head">
        <h2 class="rpt-section-head__title">Available reports <span class="rpt-count-note">(<?= count($reports) ?> shown)</span></h2>
        <?php if ($category !== '' || $search !== ''): ?>
            <a class="rpt-clear-link" href="<?= ROOT ?>/zonalsecretary/reports">Clear filters</a>
        <?php endif; ?>
    </div>

    <?php if (!$reports): ?>
        <div class="rpt-empty-box">
            <?= yn_icon('file') ?>
            <p>No aggregate reports match these filters.</p>
            <p>Try another category or search term, or generate one above.</p>
        </div>
    <?php else: ?>
        <div class="rpt-cards">
            <?php foreach ($reports as $report): ?>
                <article class="rpt-card">
                    <div class="rpt-card__top">
                        <div class="rpt-card__icon"><?= yn_icon('file') ?></div>
                        <span class="rpt-card__badge"><?= $escape($report['format']) ?></span>
                    </div>
                    <div class="rpt-card__category"><?= $escape($report['category']) ?></div>
                    <h3 class="rpt-card__title"><?= $escape($report['type']) ?></h3>
                    <p class="rpt-card__desc"><?= $escape($report['division']) ?> · Generated <?= $escape($report['date']) ?><?= $escape($report['by'] !== '' ? ' by ' . $report['by'] : '') ?></p>
                    <div class="rpt-card__foot">
                        <span class="rpt-card__date"><?= $escape($zoneName ?? 'Zone') ?> scope</span>
                        <a class="rpt-view-btn" href="<?= ROOT ?>/zonalsecretary/reportpreview/<?= (int) $report['id'] ?>">Preview</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
