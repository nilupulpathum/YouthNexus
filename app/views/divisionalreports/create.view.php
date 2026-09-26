<?php
require_once __DIR__ . '/../partials/icons.view.php';
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$title = 'Create New Report - YouthNexus';
$pageTitle = 'Create New Report';
$pageDescription = 'Configure a report for your division';
$currentRoute = 'divisionalreports';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/divisional-reports.css'];
$pageScripts = [ROOT . '/assets/js/divisional-report-create.js?v=1'];

$preferredTypeName = $aggregateMode ? 'Club Activity Aggregate' : '';
$selectedType = null;
$selectedCategory = '';
foreach ($catalog as $category => $types) {
    foreach ($types as $type) {
        if ($selectedType === null || $type->type_name === $preferredTypeName) {
            $selectedType = $type;
            $selectedCategory = $category;
        }
        if ($type->type_name === $preferredTypeName) break 2;
    }
}
$isAggregate = $selectedType && $selectedType->type_name === 'Club Activity Aggregate';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page dr-create" aria-label="Create divisional report">
  <?php if ($flash): ?><div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= yn_icon($flash['type'] === 'success' ? 'check' : 'info') ?><span><?= $e($flash['message']) ?></span></div><?php endif; ?>

  <form class="dw-panel dr-create-card" action="<?= ROOT ?>/divisionalreports/generate" method="post" data-report-create-form>
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">

    <header class="dr-create-card__header">
      <div>
        <h2><?= $isAggregate ? 'Aggregate Club Reports' : 'Create New Report' ?></h2>
        <p>Select the report, reporting period, and output format.</p>
      </div>
      <a class="dr-create-card__close" href="<?= ROOT ?>/divisionalreports" aria-label="Close report configuration"><?= yn_icon('close') ?></a>
    </header>

    <div class="dr-create-card__body">
      <div class="dr-create-grid">
        <div class="dw-field">
          <label for="report-category">Report category</label>
          <select id="report-category" data-create-category required>
            <?php foreach (array_keys($catalog) as $category): ?>
              <option value="<?= $e($category) ?>"<?= $category === $selectedCategory ? ' selected' : '' ?>><?= $e($category) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="dw-field">
          <label for="report-type">Report type</label>
          <select id="report-type" name="report_type_id" data-create-type required>
            <?php foreach ($catalog as $category => $types): ?>
              <?php foreach ($types as $type): ?>
                <option value="<?= (int) $type->report_type_id ?>" data-category="<?= $e($category) ?>" data-description="<?= $e($type->description) ?>"<?= $selectedType && (int) $selectedType->report_type_id === (int) $type->report_type_id ? ' selected' : '' ?>><?= $e($type->type_name) ?></option>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="dw-field dr-create-grid__full">
          <label for="report-scope">Aggregation scope</label>
          <input id="report-scope" type="text" value="Divisional summary (clubs in <?= $e($division->division_name) ?>)" readonly>
          <small class="dr-field-note"><?= yn_icon('info') ?><span>The report is restricted to records belonging to <?= $e($division->division_name) ?>.</span></small>
        </div>

        <div class="dw-field">
          <label for="report-start">From date</label>
          <input id="report-start" name="date_start" type="date" value="<?= date('Y-01-01') ?>" max="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="dw-field">
          <label for="report-end">To date</label>
          <input id="report-end" name="date_end" type="date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="dr-period-status dr-create-grid__full" data-period-status role="status" aria-live="polite">
          <span data-period-valid-icon><?= yn_icon('check') ?></span><span data-period-invalid-icon hidden><?= yn_icon('info') ?></span><span>Valid reporting period</span>
        </div>
      </div>

      <fieldset class="dr-format-fieldset">
        <legend>Select output format</legend>
        <div class="dr-format-grid">
          <label class="dr-format-option">
            <input type="radio" name="format" value="PDF">
            <span class="dr-format-option__surface">
              <span class="dr-format-option__top"><span class="dr-format-option__icon dr-format-option__icon--blue"><?= yn_icon('file') ?></span><span class="dr-format-option__marker" aria-hidden="true"></span></span>
              <strong>Printable report (PDF)</strong>
              <small>Formatted for review, download, and printing.</small>
            </span>
          </label>

          <label class="dr-format-option">
            <input type="radio" name="format" value="CSV">
            <span class="dr-format-option__surface">
              <span class="dr-format-option__top"><span class="dr-format-option__icon dr-format-option__icon--green"><?= yn_icon('clipboard') ?></span><span class="dr-format-option__marker" aria-hidden="true"></span></span>
              <strong>Data spreadsheet (CSV)</strong>
              <small>Structured report rows for spreadsheet use.</small>
            </span>
          </label>

          <label class="dr-format-option">
            <input type="radio" name="format" value="OnScreen" checked>
            <span class="dr-format-option__surface">
              <span class="dr-format-option__top"><span class="dr-format-option__icon dr-format-option__icon--purple"><?= yn_icon('reports') ?></span><span class="dr-format-option__marker" aria-hidden="true"></span></span>
              <strong>On-screen report</strong>
              <small>Open the report summary and detailed records.</small>
            </span>
          </label>
        </div>
      </fieldset>

      <div class="dr-report-description"><?= yn_icon('info') ?><span data-create-description><?= $selectedType ? $e($selectedType->description) : 'Select a report type.' ?></span></div>
    </div>

    <footer class="dr-create-card__footer">
      <a class="dw-button dw-button--secondary db-view-button db-view-button--back" href="<?= ROOT ?>/divisionalreports">Back to Reports</a>
      <button class="dw-button dw-button--primary db-confirm-action" type="submit" data-create-submit>Generate Report</button>
    </footer>
  </form>
</section>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
