<?php
$card = is_array($card ?? null) ? $card : [];
$cardTone = preg_replace('/[^a-z-]/', '', (string) ($card['tone'] ?? 'blue'));
$badgeClass = match ($cardTone) {
    'green' => 'ft-badge-green',
    'amber', 'yellow' => 'ft-badge-yellow',
    'red' => 'ft-badge-red',
    default => 'ft-badge-blue',
};

$label = (string) ($card['label'] ?? '');
$value = (string) ($card['value'] ?? '0');
$badge = (string) ($card['badge'] ?? '');
$subtitle = (string) ($card['subtitle'] ?? '');
$note = (string) ($card['note'] ?? '');
$target = (string) ($card['target'] ?? ($card['tab'] ?? ''));

if ($badge === '' && $note !== '') {
    $badge = $note;
}
?>
<article class="dw-summary-card ft-stat-card dw-summary-card--<?= htmlspecialchars($cardTone, ENT_QUOTES, 'UTF-8') ?>"<?= $target !== '' ? ' data-tab-target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
  <div class="ft-stat-head">
    <span class="ft-stat-title dw-summary-card__label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
    <?php if ($badge !== ''): ?>
      <span class="ft-badge <?= $badgeClass ?>"><?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
  </div>
  <div class="ft-stat-big dw-summary-card__value"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></div>
  <?php if ($subtitle !== ''): ?>
    <div class="ft-stat-sub dw-summary-card__note"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></div>
  <?php elseif ($note !== '' && $note !== $badge): ?>
    <div class="ft-stat-sub dw-summary-card__note"><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
</article>