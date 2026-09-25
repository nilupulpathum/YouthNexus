<?php
require_once __DIR__ . '/../partials/icons.view.php';
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$title = 'Manage Reports - YouthNexus';
$pageTitle = 'Manage Reports';
$pageDescription = 'Generate and review reports for your division';
$currentRoute = 'divisionalreports';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/divisional-reports.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/divisional-reports.js?v=archive-filter-2'];
$categoryPresentation = [
    'Financial' => ['tone' => 'blue', 'icon' => 'file'],
    'Assets' => ['tone' => 'amber', 'icon' => 'award'],
    'Events' => ['tone' => 'purple', 'icon' => 'calendar'],
    'Attendance' => ['tone' => 'green', 'icon' => 'check'],
    'Club Health' => ['tone' => 'red', 'icon' => 'heart'],
    'User Interactions' => ['tone' => 'slate', 'icon' => 'user'],
];
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page" aria-label="Divisional reports">
  <?php if ($flash): ?><div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= yn_icon($flash['type'] === 'success' ? 'check' : 'info') ?><span><?= $e($flash['message']) ?></span></div><?php endif; ?>

  <div class="dr-library-actions">
    <button class="dw-button dw-button--primary db-primary-action" type="button" data-modal-open="generate-report">Generate Report</button>
  </div>

  <div class="dw-toolbar" aria-label="Report tools">
    <div class="dw-toolbar__search dw-search dw-search--plain"><label class="visually-hidden" for="report-search">Search reports</label><input id="report-search" type="search" placeholder="Search by report type, category, or creator" data-report-search></div>
    <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="report-filters" aria-expanded="false">Filters</button>
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
    <div class="dw-filter-actions"><button class="dw-button dw-button--secondary" type="button" data-report-reset>Reset all</button><button class="dw-button dw-button--primary" type="button" data-report-apply>Apply filters</button></div>
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
              <?php if ($report->format === 'PDF'): ?><a class="dw-button dw-button--ghost" href="<?= ROOT ?>/divisionalreports/pdf/<?= (int) $report->report_id ?>" aria-label="Download <?= $e($report->type_name) ?> as PDF"><?= yn_icon('download') ?> PDF</a><?php elseif ($report->format === 'CSV'): ?><a class="dw-button dw-button--ghost" href="<?= ROOT ?>/divisionalreports/export/<?= (int) $report->report_id ?>" aria-label="Download <?= $e($report->type_name) ?> as CSV"><?= yn_icon('download') ?> CSV</a><?php endif; ?>
              <a class="dw-button dw-button--secondary db-view-button" href="<?= ROOT ?>/divisionalreports/preview/<?= (int) $report->report_id ?>">View Details</a>
            </div>
          </footer>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="dw-empty-state" data-report-empty><span class="dw-empty-state__icon"><?= yn_icon('file') ?></span><strong>No reports found</strong><p>Generate a report or change the current filters.</p></div>
  </section>
</section>

<div class="dw-modal" id="generate-report" role="dialog" aria-modal="true" aria-hidden="true" hidden><div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="<?= ROOT ?>/divisionalreports/generate" method="post" data-report-form>
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header"><h2>Generate Division Report</h2><button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button></header>
    <div class="dw-modal__body">
      <div class="dw-field dw-field--span-2"><label for="generate-report-type">Report type</label><select id="generate-report-type" name="report_type_id" required><option value="">Select a report</option><?php foreach ($catalog as $category => $types): ?><optgroup label="<?= $e($category) ?>"><?php foreach ($types as $type): ?><option value="<?= (int) $type->report_type_id ?>" data-description="<?= $e($type->description) ?>"><?= $e($type->type_name) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select><small data-report-description>Select a report to see what it contains.</small></div>
      <div class="dw-field"><label for="report-start">Period start</label><input id="report-start" name="date_start" type="date" value="<?= date('Y-01-01') ?>" required></div>
      <div class="dw-field"><label for="report-end">Period end</label><input id="report-end" name="date_end" type="date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
      <div class="dw-field dw-field--span-2"><label for="generate-format">Output format</label><select id="generate-format" name="format" required><option value="OnScreen">On-screen preview</option><option value="PDF">PDF download</option><option value="CSV">CSV download</option></select><small>PDF and CSV files download after the report is generated.</small></div>
      <div class="dw-alert dw-alert--warning dw-field--span-2"><?= yn_icon('info') ?><span>The report includes only records belonging to <?= $e($division->division_name) ?>.</span></div>
    </div>
    <footer class="dw-modal__footer"><button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button><button class="dw-button dw-button--primary" type="submit">Generate Report</button></footer>
  </form>
</div>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
