<?php
$emptyTitle = (string) ($emptyTitle ?? 'No records found');
$emptyMessage = (string) ($emptyMessage ?? 'Try changing the current search or filters.');
$emptyVisible = !empty($emptyVisible);
?>
<div class="yn-empty-state dw-empty-state<?= $emptyVisible ? ' is-visible' : '' ?>" data-empty-state>
  <span class="yn-empty-state__icon dw-empty-state__icon" aria-hidden="true"><?= yn_icon('file') ?></span>
  <strong><?= htmlspecialchars($emptyTitle, ENT_QUOTES, 'UTF-8') ?></strong>
  <p><?= htmlspecialchars($emptyMessage, ENT_QUOTES, 'UTF-8') ?></p>
</div>
