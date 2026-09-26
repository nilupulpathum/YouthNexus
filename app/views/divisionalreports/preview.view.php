<?php
require_once __DIR__ . '/../partials/icons.view.php';
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$title = 'Report Preview - YouthNexus';
$pageTitle = 'Report Preview';
$pageDescription = $report->type_name;
$currentRoute = 'divisionalreports';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/divisional-reports.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/divisional-reports.js'];
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page dr-preview" aria-label="Report preview">
  <article class="dw-panel dr-document">
    <header class="dr-document__header">
      <div>
        <div class="dr-document__badges"><span class="dr-format-pill dr-format-pill--<?= $e(strtolower($report->format)) ?>"><?= $e($report->format === 'OnScreen' ? 'On-screen' : $report->format) ?></span><span class="<?= $report->status === 'Archived' ? 'dr-archive-pill' : 'dr-active-pill' ?>"><?= $report->status === 'Archived' ? 'Archived report' : 'Active report' ?></span></div>
        <h2>Report Preview: <?= $e($report->type_name) ?></h2>
        <div class="dr-document__meta"><span>Scope: <strong>Divisional</strong></span><span>Range: <strong><?= $e(date('d M Y', strtotime($report->date_range_start))) ?> - <?= $e(date('d M Y', strtotime($report->date_range_end))) ?></strong></span><span>Category: <strong><?= $e($report->category) ?></strong></span></div>
      </div>
      <div class="dr-document__reference"><strong>RPT-<?= str_pad((string) $report->report_id, 4, '0', STR_PAD_LEFT) ?></strong><span>Generated <?= $e(date('d M Y, H:i', strtotime($report->generated_at))) ?></span></div>
    </header>

    <div class="dr-document__body">
      <div class="dr-kpi-grid" aria-label="Report summary"><?php foreach ($reportData['kpis'] as $kpi): ?><div class="dr-kpi"><span><?= $e($kpi['label']) ?></span><strong><?= $e($kpi['value']) ?></strong></div><?php endforeach; ?></div>

      <section class="dr-results" aria-labelledby="report-details-title">
        <div class="dr-results__heading"><div><h3 id="report-details-title">Report details</h3><p>Records included in this report snapshot</p></div><span class="dw-count"><?= count($reportData['rows']) ?> <?= count($reportData['rows']) === 1 ? 'record' : 'records' ?></span></div>
        <div class="dw-table-wrap"><table class="dw-table"><thead><tr><?php foreach ($reportData['columns'] as $label): ?><th><?= $e($label) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($reportData['rows'] as $row): ?><tr><?php foreach (array_keys($reportData['columns']) as $key): ?><td><?= $e($row[$key] ?? '-') ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
        <?php if (!$reportData['rows']): ?><div class="dw-empty-state is-visible"><span class="dw-empty-state__icon"><?= yn_icon('file') ?></span><strong>No records found</strong><p>No matching records were recorded during this reporting period.</p></div><?php endif; ?>
      </section>

      <div class="dr-verification-note"><?= yn_icon('info') ?><p><strong>Report snapshot:</strong> The values shown here were recorded when this report was generated for <?= $e($division->division_name) ?>.</p></div>
      <?php if ($report->status === 'Archived'): ?><div class="dr-archive-note"><?= yn_icon('info') ?><p><strong>Archived report:</strong> Archived <?= $e($report->archived_at ? date('d M Y, H:i', strtotime($report->archived_at)) : 'previously') ?><?= trim((string) ($report->archived_by_name ?? '')) !== '' ? ' by ' . $e(trim((string) $report->archived_by_name)) : '' ?>. Reason: <?= $e($report->archive_reason ?: 'Not recorded') ?>. This historical snapshot remains available for review and download.</p></div><?php endif; ?>
    </div>

    <footer class="dr-document__footer">
      <div class="dr-document__footer-left">
        <a class="dw-button dw-button--ghost db-view-button db-view-button--back" href="<?= ROOT ?>/divisionalreports">Back to Reports</a>
        <?php if ($report->status === 'Archived'): ?>
          <form method="post" action="<?= ROOT ?>/divisionalreports/restore/<?= (int) $report->report_id ?>" onsubmit="return confirm('Restore this report to the active report list?');"><input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><button class="dw-button dw-button--primary db-confirm-action" type="submit">Restore Report</button></form>
        <?php endif; ?>
      </div>
      <div class="dr-document__footer-right"><?php if ($report->status !== 'Archived'): ?><button class="dw-button dw-button--danger" type="button" data-modal-open="archive-report-modal">Archive Report</button><?php endif; ?><a class="dw-button dw-button--secondary" href="<?= ROOT ?>/divisionalreports/export/<?= (int) $report->report_id ?>"><?= yn_icon('download') ?> Download CSV</a><a class="dw-button dw-button--primary" href="<?= ROOT ?>/divisionalreports/pdf/<?= (int) $report->report_id ?>"><?= yn_icon('download') ?> Download PDF</a></div>
    </footer>
  </article>
</section>

<?php if ($report->status !== 'Archived'): ?>
<div class="dw-modal" id="archive-report-modal" role="dialog" aria-modal="true" aria-labelledby="archive-report-title" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog dr-archive-dialog" method="post" action="<?= ROOT ?>/divisionalreports/archive/<?= (int) $report->report_id ?>" data-report-archive-form novalidate>
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header"><div><h2 id="archive-report-title">Archive Report</h2><p>Move this report to the historical archive without deleting its snapshot.</p></div><button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button></header>
    <div class="dw-modal__body dr-archive-modal__body">
      <div class="dr-archive-summary"><span>Report</span><strong>RPT-<?= str_pad((string) $report->report_id, 4, '0', STR_PAD_LEFT) ?> · <?= $e($report->type_name) ?></strong><p>The report remains available under Archived Reports and can be restored later.</p></div>
      <div class="dw-field dw-field--span-2"><label for="report-archive-reason">Reason for archiving <span aria-hidden="true">*</span></label><textarea id="report-archive-reason" name="reason" minlength="5" maxlength="1000" aria-required="true" aria-describedby="report-archive-help report-archive-error" placeholder="Explain why this report is being archived"></textarea><small id="report-archive-help">Enter at least 5 characters so the decision is clear in the audit history.</small><p class="dr-field-error" id="report-archive-error" role="alert" hidden>Please provide a reason of at least 5 characters.</p></div>
      <div class="dw-alert dw-alert--warning dw-field--span-2"><?= yn_icon('info') ?><span>Archiving does not delete this report or its downloadable files.</span></div>
    </div>
    <footer class="dw-modal__footer"><button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button><button class="dw-button dw-button--danger" type="submit">Archive Report</button></footer>
  </form>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
