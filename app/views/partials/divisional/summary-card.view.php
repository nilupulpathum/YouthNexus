<?php
$card = is_array($card ?? null) ? $card : [];
$cardTone = preg_replace('/[^a-z-]/', '', (string) ($card['tone'] ?? 'blue'));
?>
<article class="yn-stat-card dw-summary-card dw-summary-card--<?= htmlspecialchars($cardTone, ENT_QUOTES, 'UTF-8') ?>">
  <span class="yn-stat-card__icon dw-summary-card__icon" aria-hidden="true"><?= yn_icon((string) ($card['icon'] ?? 'info')) ?></span>
  <div>
    <strong class="yn-stat-card__value dw-summary-card__value"><?= htmlspecialchars((string) ($card['value'] ?? '0'), ENT_QUOTES, 'UTF-8') ?></strong>
    <span class="yn-stat-card__label dw-summary-card__label"><?= htmlspecialchars((string) ($card['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
    <?php if (!empty($card['note'])): ?>
      <small class="dw-summary-card__note"><?= htmlspecialchars((string) $card['note'], ENT_QUOTES, 'UTF-8') ?></small>
    <?php endif; ?>
  </div>
</article>
