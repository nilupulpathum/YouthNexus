<?php
require __DIR__ . '/../partials/icons.view.php';

$title = 'Report Preview - YouthNexus';
$pageTitle = 'Report Preview';
$pageDescription = 'Aggregate zonal report preview';
$currentRoute = 'zonalcoordinator/reportpreview';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js'];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page" aria-labelledby="coordinator-preview-heading"><h1 id="coordinator-preview-heading" class="visually-hidden">Coordinator report preview</h1><section class="dw-panel"><div class="dw-panel__header"><div><p>Gampaha Zone</p><h2>Aggregate report preview</h2></div><a class="dw-button dw-button--secondary" href="<?= ROOT ?>/zonalcoordinator/reports">Back to reports</a></div><p class="dw-muted-copy">Report <?= htmlspecialchars($reportId, ENT_QUOTES, 'UTF-8') ?> · 19 reporting clubs, 18 recorded programmes and 78% attendance.</p></section></section>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
