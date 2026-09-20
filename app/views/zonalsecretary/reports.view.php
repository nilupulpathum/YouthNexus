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
        <div class="rpt-small-pill">
            <select name="division" aria-label="Division">
                <option value="">All divisions</option>
                <?php foreach (['Gampaha Division', 'Ja-Ela Division', 'Negombo Division'] as $divisionName): ?>
                    <option value="<?= $escape($divisionName) ?>" <?= $division === $divisionName ? 'selected' : '' ?>><?= $escape($divisionName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="rpt-btn rpt-btn--ghost"><?= yn_icon('file') ?> Apply filters</button>
    </form>

    <div class="rpt-section-head">
        <h2 class="rpt-section-head__title">Available reports <span class="rpt-count-note">(<?= count($reports) ?> shown)</span></h2>
        <?php if ($category !== '' || $division !== '' || $search !== ''): ?>
            <a class="rpt-clear-link" href="<?= ROOT ?>/zonalsecretary/reports">Clear filters</a>
        <?php endif; ?>
    </div>

    <?php if (!$reports): ?>
        <div class="rpt-empty-box">
            <?= yn_icon('file') ?>
            <p>No aggregate reports match these filters.</p>
            <p>Try another category, division, or search term.</p>
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
                    <p class="rpt-card__desc"><?= $escape($report['division']) ?> · Generated <?= $escape($report['date']) ?></p>
                    <div class="rpt-card__foot">
                        <span class="rpt-card__date">Gampaha Zone scope</span>
                        <a class="rpt-view-btn" href="<?= ROOT ?>/zonalsecretary/reportpreview/<?= rawurlencode($report['id']) ?>">Preview</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
