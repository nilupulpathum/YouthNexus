<?php
require_once __DIR__ . '/icons.view.php';

$emptyTitle = (string) ($emptyTitle ?? 'No records found');
$emptyMessage = (string) ($emptyMessage ?? 'Try changing the current search or filters.');
$emptyVisible = !empty($emptyVisible);
$emptyIcon = (string) ($emptyIcon ?? 'file');
$emptyAttribute = (string) ($emptyAttribute ?? 'data-empty-state');
$emptyClass = (string) ($emptyClass ?? '');
// Callers may supply only a data attribute; its value is never rendered as markup.
$emptyAttribute = preg_match('/^data-[a-z][a-z0-9-]*$/', $emptyAttribute) ? $emptyAttribute : 'data-empty-state';
$emptyClass = preg_replace('/[^a-zA-Z0-9 _-]/', '', $emptyClass);
?>
<div class="yn-empty-state dw-empty-state<?= $emptyVisible ? ' is-visible' : '' ?><?= $emptyClass !== '' ? ' ' . htmlspecialchars($emptyClass, ENT_QUOTES, 'UTF-8') : '' ?>" <?= $emptyAttribute ?>>
  <span class="yn-empty-state__icon dw-empty-state__icon" aria-hidden="true"><?= yn_icon($emptyIcon) ?></span>
  <strong><?= htmlspecialchars($emptyTitle, ENT_QUOTES, 'UTF-8') ?></strong>
  <p><?= htmlspecialchars($emptyMessage, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php unset($emptyIcon, $emptyAttribute, $emptyClass); ?>
