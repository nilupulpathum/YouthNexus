<?php
$statusText = trim((string) ($status ?? 'Unknown')) ?: 'Unknown';
$statusClass = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $statusText));
?>
<span class="yn-status yn-status--<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?> dw-status dw-status--<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
  <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
</span>
