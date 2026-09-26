<?php
/**
 * Manage Reports — NYSC Administration
 * Main dashboard view using the shared dashboard layout.
 */

$title                   = $title   ?? 'Manage Reports — YouthNexus';
$pageTitle               = $pageTitle ?? 'National Reports Management';
$pageDescription         = $pageDescription ?? 'Create, filter, and aggregate reports across all youth clubs, divisions, and zones';
$currentRoute            = 'reports';
$unreadNotificationCount = 0;
$pageStyles              = [ROOT . '/assets/css/managereports.css?v=20260927'];

require __DIR__ . '/../layouts/dashboard-start.view.php';

// ── Category → icon mapping ────────────────────────────────────────
$categoryIcons = [
    'Financial'         => 'financial',
    'Assets'            => 'assets',
    'Events'            => 'events',
    'Attendance'        => 'attendance',
    'Club Health'       => 'health',
    'User Interactions' => 'users',
];

$categoryColors = [
    'Financial'         => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'Assets'            => ['bg' => '#fef3c7', 'text' => '#d97706'],
    'Events'            => ['bg' => '#ede9fe', 'text' => '#7c3aed'],
    'Attendance'        => ['bg' => '#dcfce7', 'text' => '#16a34a'],
    'Club Health'       => ['bg' => '#fee2e2', 'text' => '#dc2626'],
    'User Interactions' => ['bg' => '#e0f2fe', 'text' => '#0284c7'],
];

$formatColors = [
    'PDF'      => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'CSV'      => ['bg' => '#dcfce7', 'text' => '#16a34a'],
    'OnScreen' => ['bg' => '#ede9fe', 'text' => '#7c3aed'],
];
?>

<div class="rpt-content">

    <?php if (!empty($flashSuccess)): ?>
        <div class="rpt-flash rpt-flash--success" role="alert">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <?= htmlspecialchars($flashSuccess) ?>
            <button class="rpt-flash__close" onclick="this.parentElement.remove()" aria-label="Dismiss">&times;</button>
        </div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="rpt-flash rpt-flash--error" role="alert">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($flashError) ?>
            <button class="rpt-flash__close" onclick="this.parentElement.remove()" aria-label="Dismiss">&times;</button>
        </div>
    <?php endif; ?>

    <!-- ── Page action row ─────────────────────────────────────────── -->
    <div class="rpt-header-bar rpt-action-row db-action-row">
        <div class="rpt-header-actions">
            <a href="<?= ROOT ?>/reports/create" class="rpt-btn rpt-btn--outline db-secondary-action" id="btn-aggregate">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Aggregate Reports
            </a>
            <a href="<?= ROOT ?>/reports/create" class="rpt-btn rpt-btn--primary db-primary-action" id="btn-create-report">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Create New Report
            </a>
        </div>
    </div>

    <!-- ── Toolbar: filters ────────────────────────────────────── -->
    <form method="get" action="<?= ROOT ?>/reports" class="rpt-toolbar" id="filter-form">

        <!-- Combined category dropdown + keyword search pill -->
        <div class="rpt-filter-pill">
            <span class="rpt-select-wrap">
                <select name="category" id="categorySelect" onchange="this.form.submit()">
                    <option value="">All Reports</option>
                    <?php foreach ($catalog as $catName => $types): ?>
                        <option value="<?= htmlspecialchars($catName) ?>" <?= $selectedCategory === $catName ? 'selected' : '' ?>><?= htmlspecialchars($catName) ?></option>
                    <?php endforeach; ?>
                </select>
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" class="rpt-select-arrow"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
            <span class="rpt-pill-divider"></span>
            <input class="rpt-pill-input" type="text" name="search" id="searchInput"
                   placeholder="Search reports..." value="<?= htmlspecialchars($searchText) ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="color:#9ca3af;flex-shrink:0"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </div>

        <!-- Report Type dropdown (depends on category) -->
        <div class="rpt-small-pill">
            <select name="type" id="typeSelect" <?= $selectedCategory === '' ? 'disabled' : '' ?>>
                <option value="">All Types</option>
                <?php foreach ($typeOptions as $typeName): ?>
                    <option value="<?= htmlspecialchars($typeName) ?>" <?= $selectedType === $typeName ? 'selected' : '' ?>><?= htmlspecialchars($typeName) ?></option>
                <?php endforeach; ?>
            </select>
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" class="rpt-select-arrow"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        <button type="submit" class="rpt-btn rpt-btn--ghost" id="btn-filter">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filter Options
        </button>
    </form>

    <!-- ── Section heading ────────────────────────────────────── -->
    <div class="rpt-section-head">
        <h2 class="rpt-section-head__title">
            Recent Reports
            <?php if ($hasFilter): ?>
                <span class="rpt-count-note">(<?= count($reports) ?> found)</span>
            <?php endif; ?>
        </h2>
        <?php if ($hasFilter): ?>
            <a class="rpt-clear-link" href="<?= ROOT ?>/reports">Clear filters</a>
        <?php endif; ?>
    </div>

    <!-- ── Report cards ──────────────────────────────────────── -->
    <?php if (count($reports) === 0): ?>
        <div class="rpt-empty-box">
            <svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="1.4" style="color:#d1d5db;margin-bottom:12px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <p>No reports found for the selected filters.</p>
            <p style="font-size:13px;color:#9ca3af">Try another category, report type, or keyword.</p>
        </div>
    <?php else: ?>
        <div class="rpt-cards" id="reports-grid">
            <?php foreach ($reports as $r):
                $cat    = htmlspecialchars($r->category ?? '');
                $colors = $categoryColors[$r->category ?? ''] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                $fmtColors = $formatColors[$r->format ?? 'PDF'] ?? ['bg' => '#dbeafe', 'text' => '#1d4ed8'];
                $date   = !empty($r->generated_at) ? date('M j, Y', strtotime($r->generated_at)) : '-';
                $by     = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
                if (empty($by)) $by = 'N. Fernando';
                $scope  = ucfirst(strtolower($r->scope_level ?? 'National'));
            ?>
                <div class="rpt-card">
                    <div class="rpt-card__top">
                        <div class="rpt-card__icon" style="background:<?= $colors['bg'] ?>;color:<?= $colors['text'] ?>">
                            <?php
                            // Inline SVG per category
                            $svgs = [
                                'Financial'         => '<path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
                                'Assets'            => '<rect x="3" y="5" width="18" height="14" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/>',
                                'Events'            => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
                                'Attendance'        => '<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
                                'Club Health'       => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
                                'User Interactions' => '<circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>',
                            ];
                            $svgPath = $svgs[$r->category ?? ''] ?? '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>';
                            echo '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8">' . $svgPath . '</svg>';
                            ?>
                        </div>
                        <span class="rpt-card__badge" style="background:<?= $fmtColors['bg'] ?>;color:<?= $fmtColors['text'] ?>">
                            <?= htmlspecialchars($r->format ?? 'PDF') ?>
                        </span>
                    </div>

                    <div class="rpt-card__category"><?= $cat ?></div>
                    <div class="rpt-card__title"><?= htmlspecialchars($r->type_name ?? '') ?></div>
                    <div class="rpt-card__desc"><?= htmlspecialchars($scope) ?> Level</div>

                    <div class="rpt-card__foot">
                        <span class="rpt-card__date">Generated <?= $date ?></span>
                        <a class="rpt-view-btn"
                           href="<?= ROOT ?>/reports/preview/<?= (int)($r->report_id ?? 0) ?>"
                           id="view-report-<?= (int)($r->report_id ?? 0) ?>">
                            View
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div><!-- /.rpt-content -->

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
