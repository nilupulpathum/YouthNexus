<?php
require_once __DIR__ . '/../partials/icons.view.php';
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$title = 'Manage Reports - YouthNexus';
$pageTitle = 'Manage Reports';
$pageDescription = 'Generate and review reports for your division';
$currentRoute = 'divisionalreports';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/divisional-reports.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/divisional-reports.js'];
$categoryPresentation = [
    'Financial' => ['tone' => 'blue', 'icon' => 'file'],
    'Assets' => ['tone' => 'amber', 'icon' => 'award'],
    'Events' => ['tone' => 'purple', 'icon' => 'calendar'],
    'Attendance' => ['tone' => 'green', 'icon' => 'check'],
    'Club Health' => ['tone' => 'red', 'icon' => 'heart'],
    'User Interactions' => ['tone' => 'slate', 'icon' => 'user'],
];
$aggregateReportTypeName = 'Club Activity Aggregate';
$aggregateTypeAvailable = false;
foreach ($catalog as $types) {
    foreach ($types as $type) {
        if ($type->type_name === $aggregateReportTypeName) {
            $aggregateTypeAvailable = true;
            break 2;
        }
    }
}
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page" aria-label="Divisional reports">
  <?php if ($flash): ?><div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= yn_icon($flash['type'] === 'success' ? 'check' : 'info') ?><span><?= $e($flash['message']) ?></span></div><?php endif; ?>

  <div class="dr-library-actions">
    <?php if ($userRole === 'DivisionalSecretary' && $aggregateTypeAvailable): ?>
      <a class="yn-btn yn-btn--secondary dw-button dw-button--secondary db-secondary-action" href="<?= ROOT ?>/divisionalreports/create?mode=aggregate">Aggregate Reports</a>
    <?php endif; ?>
    <a class="yn-btn yn-btn--primary dw-button dw-button--primary db-primary-action" href="<?= ROOT ?>/divisionalreports/create">Generate Report</a>
  </div>

  <div class="dw-toolbar" aria-label="Report tools">
    <div class="dw-toolbar__search yn-search dw-search"><label class="visually-hidden" for="report-search">Search reports</label><span class="yn-search__icon dw-search__icon" aria-hidden="true"><?= yn_icon('search') ?></span><input id="report-search" type="search" placeholder="Search by report type, category, or creator" data-report-search></div>
    <button class="yn-btn yn-btn--secondary dw-button dw-button--secondary yn-filter-toggle" type="button" data-filter-toggle aria-controls="report-filters" aria-expanded="false"><?= yn_icon('filter') ?> Filters</button>
  </div>

  <section class="dw-filter-panel" id="report-filters" hidden>
    <h2 class="dw-filter-panel__heading">Advanced Filters for Reports</h2>
    <div class="dw-filter-grid">
      <div class="dw-field"><label for="report-category">Category</label><select id="report-category" data-report-category><option value="">All categories</option><?php foreach (array_keys($catalog) as $category): ?><option value="<?= $e(strtolower($category)) ?>"><?= $e($category) ?></option><?php endforeach; ?></select></div>
      <div class="dw-field"><label for="report-format">Format</label><select id="report-format" data-report-format><option value="">All formats</option><option value="onscreen">On-screen</option><option value="pdf">PDF</option><option value="csv">CSV</option></select></div>
      <div class="dw-field"><label for="report-status">Status</label><select id="report-status" data-report-status><option value="active">Active reports</option><option value="archived">Archived reports</option><option value="">All reports</option></select></div>
      <div class="dw-field"><label for="report-date-from">Generated from</label><input id="report-date-from" type="date" data-report-from></div>
      <div class="dw-field"><label for="report-sort">Sort by</label><select id="report-sort" data-report-sort><option value="newest">Newest first</option><option value="oldest">Oldest first</option><option value="type">Report type</option></select></div>
    </div>
    <div class="dw-filter-actions"><button class="yn-btn yn-btn--secondary dw-button dw-button--secondary yn-filter-clear" type="button" data-report-reset>Clear filters</button><button class="yn-btn yn-btn--primary dw-button dw-button--primary yn-filter-apply" type="button" data-report-apply>Apply filters</button></div>
  </section>

  <section class="dr-library" aria-labelledby="report-library-title">
    <header class="dr-library__header"><h2 id="report-library-title" data-report-library-title>Recent Reports</h2><span class="dw-count" data-report-count><?= count($reports) ?> reports</span></header>
    <div class="dr-report-grid" data-report-body>
      <?php foreach ($reports as $report): ?>
        <?php
          $search = strtolower(implode(' ', [$report->type_name, $report->category, $report->format, $report->generated_by_name]));
          $presentation = $categoryPresentation[$report->category] ?? ['tone' => 'slate', 'icon' => 'file'];
          $generatedBy = trim((string) $report->generated_by_name) ?: 'Divisional officer';
          $archivedBy = trim((string) ($report->archived_by_name ?? '')) ?: 'Divisional officer';
        ?>
        <article class="dr-report-card<?= $report->status === 'Archived' ? ' dr-report-card--archived' : '' ?>" data-report-row data-search="<?= $e($search) ?>" data-category="<?= $e(strtolower($report->category)) ?>" data-format="<?= $e(strtolower($report->format)) ?>" data-status="<?= $e(strtolower($report->status)) ?>" data-date="<?= $e(substr($report->generated_at, 0, 10)) ?>" data-type="<?= $e(strtolower($report->type_name)) ?>">
          <div class="dr-report-card__top">
            <span class="dr-report-card__icon dr-report-card__icon--<?= $e($presentation['tone']) ?>"><?= yn_icon($presentation['icon']) ?></span>
            <span class="dr-report-card__pills"><span class="dr-format-pill dr-format-pill--<?= $e(strtolower($report->format)) ?>"><?= $e($report->format === 'OnScreen' ? 'On-screen' : $report->format) ?></span><?php if ($report->status === 'Archived'): ?><span class="dr-archive-pill">Archived</span><?php endif; ?></span>
          </div>
          <div class="dr-report-card__category"><?= $e($report->category) ?></div>
          <h3><?= $e($report->type_name) ?></h3>
          <p class="dr-report-card__scope">Divisional level</p>
          <dl class="dr-report-card__meta">
            <div><dt>Reporting period</dt><dd><?= $e(date('d M Y', strtotime($report->date_range_start))) ?> - <?= $e(date('d M Y', strtotime($report->date_range_end))) ?></dd></div>
            <div><dt>Generated by</dt><dd><?= $e($generatedBy) ?></dd></div>
            <?php if ($report->status === 'Archived'): ?><div><dt>Archived</dt><dd><?= $e($report->archived_at ? date('d M Y, H:i', strtotime($report->archived_at)) . ' by ' . $archivedBy : 'Previously archived') ?></dd></div><?php endif; ?>
          </dl>
          <footer class="dr-report-card__footer">
            <span>Generated <?= $e(date('d M Y', strtotime($report->generated_at))) ?></span>
            <div class="dr-report-card__actions">
              <?php if ($report->format === 'PDF'): ?><a class="yn-btn yn-btn--ghost yn-btn-download dw-button dw-button--ghost" href="<?= ROOT ?>/divisionalreports/pdf/<?= (int) $report->report_id ?>" aria-label="Download <?= $e($report->type_name) ?> as PDF"><?= yn_icon('download') ?> PDF</a><?php elseif ($report->format === 'CSV'): ?><a class="yn-btn yn-btn--ghost yn-btn-download dw-button dw-button--ghost" href="<?= ROOT ?>/divisionalreports/export/<?= (int) $report->report_id ?>" aria-label="Download <?= $e($report->type_name) ?> as CSV"><?= yn_icon('download') ?> CSV</a><?php endif; ?>
              <a class="yn-btn yn-btn--secondary dw-button dw-button--secondary db-view-button" href="<?= ROOT ?>/divisionalreports/preview/<?= (int) $report->report_id ?>">View Details</a>
            </div>
          </footer>
        </article>
      <?php endforeach; ?>
    </div>
    <?php $emptyTitle = 'No reports found'; $emptyMessage = 'Generate a report or change the current filters.'; $emptyVisible = false; $emptyIcon = 'file'; $emptyAttribute = 'data-report-empty'; require __DIR__ . '/../partials/empty-state.view.php'; ?>
  </section>
</section>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
