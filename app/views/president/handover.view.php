<?php
/**
 * Leadership Handover — C6.
 * Successor member-ID verify (invalid → error + abort) + asset-freeze
 * inventory checklist + president confirm modal → handover log + member
 * notify states. Presentation-only: no DB writes; backend contract
 * lands in C13.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$members      = $members ?? [];
$freezeAssets = $freezeAssets ?? [];

$title = 'Leadership Handover - YouthNexus';
$pageTitle = 'Leadership Handover';
$pageDescription = 'Nominate a successor and freeze assets';
$currentRoute = 'president/handover';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/club.js'];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="handover-heading">
    <h1 id="handover-heading" class="visually-hidden">Leadership handover</h1>

    <div class="dw-alert dw-alert--success" id="club-toast" role="status" hidden></div>
    <?php if (!empty($flash)): ?>
        <div class="dw-alert dw-alert--<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status">
            <?= $e($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <p><a class="dw-button dw-button--ghost" href="<?= ROOT ?>/president"><span aria-hidden="true">‹</span> President overview</a></p>

    <section class="dw-panel" aria-labelledby="handover-successor-heading">
        <header class="dw-panel__header">
            <div>
                <p>Step 1 of 2 · Successor</p>
                <h2 id="handover-successor-heading">Nominate Successor</h2>
            </div>
        </header>

        <div class="dw-panel__body">
            <form id="handover-verify-form" novalidate>
                <div class="dw-filter-grid">
                    <div class="dw-field dw-field--span-2">
                        <label for="handover-search">Find member in roster (name or ID)</label>
                        <div class="club-combo">
                            <span class="club-combo-search-icon" aria-hidden="true"><?= yn_icon('search') ?></span>
                            <input id="handover-search" type="text" autocomplete="off" placeholder="Type to filter, or pick from the full list...">
                            <div id="handover-roster-list" class="club-dropdown" role="listbox" hidden></div>
                        </div>
                    </div>
                    <div class="dw-field">
                        <label for="handover-id">Successor member ID</label>
                        <input id="handover-id" type="text" required autocomplete="off" placeholder="e.g., 12">
                    </div>
                    <div class="dw-field">
                        <span class="dw-field__label" aria-hidden="true">&nbsp;</span>
                        <div class="dw-row-actions">
                            <button type="submit" class="dw-button dw-button--primary">Verify identity</button>
                        </div>
                    </div>
                </div>
                <div class="dw-alert dw-alert--error" id="handover-id-error" role="alert" hidden></div>
            </form>

            <div id="handover-verified" class="dw-record-card" hidden>
                <div class="dw-record-card__header">
                    <div class="dw-record-card__identity">
                        <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon('user') ?></span>
                        <div class="dw-record-card__meta"><span>Successor nominee</span></div>
                    </div>
                    <?php $status = 'Verified'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
                </div>
                <h3 class="dw-record-card__title" id="handover-verified-name"></h3>
                <p id="handover-verified-meta"></p>
            </div>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="handover-freeze-heading">
        <header class="dw-panel__header">
            <div>
                <p>Step 2 of 2 · Asset freeze</p>
                <h2 id="handover-freeze-heading">Inventory Checklist</h2>
            </div>
            <span class="dw-count"><?= count($freezeAssets) ?> assets</span>
        </header>

        <div class="dw-table-wrap">
            <table class="dw-table">
                <thead>
                    <tr>
                        <th><span class="visually-hidden">Verified</span></th>
                        <th>Asset</th>
                        <th>SKU</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody id="handover-checklist">
                    <?php foreach ($freezeAssets as $a): ?>
                        <tr>
                            <td><input type="checkbox" class="club-check" data-item-id="<?= (int) ($a['id'] ?? 0) ?>" aria-label="Verify <?= $e($a['name'] ?? '') ?>"></td>
                            <td><strong><?= $e($a['name'] ?? '') ?></strong></td>
                            <td><?= $e($a['sku'] ?? '') ?></td>
                            <td><?= (int) ($a['quantity'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="dw-panel__body">
            <div class="dw-alert dw-alert--error" id="handover-error" role="alert" hidden></div>
            <div class="dw-filter-actions">
                <button type="button" class="dw-button dw-button--primary" id="handover-confirm-open" data-member-registry="<?= $e(json_encode(array_values($members ?? []))) ?>">Confirm handover</button>
            </div>
        </div>
    </section>

    <section class="dw-panel" id="handover-log-panel" aria-labelledby="handover-log-heading">
        <header class="dw-panel__header">
            <div>
                <p>Handover log</p>
                <h2 id="handover-log-heading">Past Handovers</h2>
            </div>
            <span class="dw-count"><?= count($handoverLog ?? []) ?></span>
        </header>
        <div class="dw-panel__body">
            <?php if (empty($handoverLog)): ?>
                <p>No handovers recorded yet.</p>
            <?php else: ?>
                <?php foreach ($handoverLog as $h): ?>
                    <article class="dw-record-card">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon('check') ?></span>
                                <div class="dw-record-card__meta"><span>Log #<?= (int) $h->handover_id ?></span></div>
                            </div>
                            <?php $status = 'Logged'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e(($h->outgoing_name ?? '') . ' → ' . ($h->incoming_name ?? '')) ?></h3>
                        <p><?= $e(date('M d, Y', strtotime((string) $h->created_at))) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <div id="handover-confirm-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="hc-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>Atomic demote + promote</p>
                    <h2 id="hc-title">Confirm handover</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <p id="hc-summary"></p>
                <form id="handover-confirm-form" action="<?= ROOT ?>/president/confirmHandover" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">
                    <input type="hidden" id="hc-successor-id" name="successor_id" value="">
                    <input type="hidden" id="hc-checklist" name="checklist" value="[]">
                </form>
                <div role="note">
                    <strong>This changes leadership immediately</strong>
                    <p>Confirming demotes you to General Member and promotes the successor to President in one atomic step, and writes the handover log.</p>
                </div>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="submit" class="dw-button dw-button--primary" form="handover-confirm-form">Confirm handover</button>
            </footer>
        </div>
    </div>
</section>



<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
