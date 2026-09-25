<?php
/**
 * Club Assets — C7 full UI.
 * Inventory grid (photo thumb, name + generated asset ID, category, serial,
 * purchase date, valuation, custodian, Available status) + search/status/
 * category filters for president, treasurer, secretary. Secretary-only: "Register Asset"
 * button + modal (name, serial, category, purchase date, valuation, photo →
 * generated ID, Available). Treasurer-only: per-Available-row "Transfer"
 * action + custody modal (custodian + date + history note → In Use).
 * Presentation-only: no DB writes; backend contract lands in C13.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$stats         = $stats ?? [];
$assets        = $assets ?? [];
$divisionRequests = $divisionRequests ?? [];
$can_transfer  = !empty($can_transfer);
$can_register  = !empty($can_register);
$can_request   = !empty($can_request);
$custodians    = ['Nuwan Bandara', 'Amal Perera', 'Kasun Fernando', 'Dilini Jayasuriya', 'Ruwan Silva'];

$title = 'Club Assets - YouthNexus';
$pageTitle = 'Club Assets';
$pageDescription = 'Inventory, custody and division requests';
$currentRoute = 'club/assets';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/club.js',
];

$summaryCards = [
    ['value' => (string) ($stats['total'] ?? 0), 'label' => 'Total assets', 'note' => 'Gampaha Youth Development Club', 'icon' => 'briefcase', 'tone' => 'blue'],
    ['value' => (string) ($stats['available'] ?? 0), 'label' => 'Available', 'note' => 'Ready for custody', 'icon' => 'check', 'tone' => 'green'],
    ['value' => (string) ($stats['in_use'] ?? 0), 'label' => 'In use', 'note' => 'Currently assigned', 'icon' => 'clock', 'tone' => 'amber'],
    ['value' => (string) ($stats['valuation'] ?? ''), 'label' => 'Total valuation', 'note' => 'Estimated LKR value', 'icon' => 'wallet', 'tone' => 'blue'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="club-assets-heading">
    <h1 id="club-assets-heading" class="visually-hidden">Club assets</h1>

    <div class="dw-alert dw-alert--success" id="club-toast" role="status" hidden></div>

    <div class="dw-summary-grid" aria-label="Asset summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="dw-toolbar" aria-label="Inventory tools">
        <div class="dw-toolbar__search dw-search dw-search--plain">
            <label class="visually-hidden" for="club-asset-search">Search inventory</label>
            <input id="club-asset-search" type="search" placeholder="Search name or serial..." autocomplete="off">
        </div>
        <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="club-asset-filters" aria-expanded="false">Filters</button>
        <?php if ($can_register): ?>
            <button type="button" class="dw-button dw-button--primary" id="club-asset-open" data-modal-open="club-asset-modal">Register Asset</button>
        <?php endif; ?>
        <?php if ($can_request): ?>
            <button type="button" class="dw-button dw-button--secondary" id="club-request-open" data-modal-open="club-request-modal">Request from Division</button>
        <?php endif; ?>
    </div>

    <section class="dw-filter-panel" id="club-asset-filters" hidden>
        <h2 class="dw-filter-panel__heading">Filter Inventory</h2>
        <div class="dw-filter-grid">
            <div class="dw-field">
                <label for="club-asset-category">Filter by category</label>
                <select id="club-asset-category">
                    <option value="">All categories</option>
                    <option value="sports">Sports</option>
                    <option value="audio video equipments">Audio Video Equipments</option>
                    <option value="cleaning">Cleaning</option>
                    <option value="official equipments">Official Equipments</option>
                </select>
            </div>
            <div class="dw-field">
                <label for="club-asset-status">Filter by status</label>
                <select id="club-asset-status">
                    <option value="">All statuses</option>
                    <option value="available">Available</option>
                    <option value="inuse">In Use</option>
                </select>
            </div>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="club-assets-list-heading">
        <header class="dw-panel__header">
            <div>
                <h2 id="club-assets-list-heading">Inventory</h2>
                <p>Gampaha Youth Development Club</p>
            </div>
            <span class="dw-count"><?= count($assets) ?> <?= count($assets) === 1 ? 'asset' : 'assets' ?></span>
        </header>
        <div class="dw-table-wrap">
            <table class="dw-table">
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Category</th>
                        <th>Serial</th>
                        <th>Purchase date</th>
                        <th>Valuation</th>
                        <th>Custodian</th>
                        <th>Status</th>
                        <?php if ($can_transfer): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="club-asset-body">
                    <?php foreach ($assets as $a): ?>
                        <tr data-search="<?= $e(strtolower(($a['name'] ?? '') . ' ' . ($a['serial'] ?? '') . ' ' . ($a['category'] ?? ''))) ?>"
                            data-status="<?= $e($a['status_key'] ?? 'available') ?>"
                            data-category="<?= $e(strtolower($a['category'] ?? '')) ?>"
                            data-name="<?= $e($a['name'] ?? '') ?>">
                            <td>
                                <div>
                                    <span data-asset-thumb aria-hidden="true"><?= yn_icon('file') ?></span>
                                    <span><strong><?= $e($a['name'] ?? '') ?></strong>
                                    <small><?= $e($a['serial'] ?? '') ?></small></span>
                                </div>
                            </td>
                            <td><?= $e($a['category'] ?? '') ?></td>
                            <td><?= $e($a['serial'] ?? '') ?></td>
                            <td><?= $e($a['purchase_date'] ?? '') ?></td>
                            <td class="dw-money"><?= $e($a['valuation'] ?? '') ?></td>
                            <td><?= $e($a['custodian'] ?? '') ?></td>
                            <td><?php $status = $a['status'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                            <?php if ($can_transfer): ?>
                                <td>
                                    <div class="dw-row-actions">
                                        <?php if (($a['status_key'] ?? '') === 'available'): ?>
                                            <button type="button" class="dw-button dw-button--ghost" data-action="transfer">Transfer</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $emptyTitle = 'No assets match these filters';
        $emptyMessage = 'Try changing the current search or filters.';
        $emptyVisible = count($assets) === 0;
        require __DIR__ . '/../partials/divisional/empty-state.view.php';
        ?>
    </section>

    <?php if ($can_request): ?>
        <section class="dw-panel" aria-labelledby="club-requests-heading">
            <header class="dw-panel__header">
                <div>
                    <h2 id="club-requests-heading">My Requests</h2>
                    <p>Division queue (read-only copy)</p>
                </div>
                <span class="dw-count"><?= count($divisionRequests) ?> <?= count($divisionRequests) === 1 ? 'request' : 'requests' ?></span>
            </header>

            <div class="dw-table-wrap">
                <table class="dw-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Justification</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="club-request-body">
                        <?php foreach ($divisionRequests as $r): ?>
                            <tr>
                                <td><strong><?= $e($r['item'] ?? '') ?></strong></td>
                                <td><?= $e($r['category'] ?? '') ?></td>
                                <td><?= $e($r['quantity'] ?? '') ?></td>
                                <td><?= $e($r['justification'] ?? '') ?></td>
                                <td><?= $e($r['date'] ?? '') ?></td>
                                <td><?php $status = $r['status'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p>Requests are decided in the division queue, which is built by the division developer.</p>
        </section>
    <?php endif; ?>
</section>

<?php if ($can_request): ?>
    <div id="club-request-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="req-modal-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>Treasurer action</p>
                    <h2 id="req-modal-title">Request from Division</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <p>Requests land in the division queue as Pending. The division side is built separately.</p>
                <form id="club-request-form" novalidate>
                    <div class="dw-field">
                        <label for="req-category">Category</label>
                        <select id="req-category" required>
                            <option value="">Select category...</option>
                            <option value="Sports">Sports</option>
                            <option value="Audio Video Equipments">Audio Video Equipments</option>
                            <option value="Cleaning">Cleaning</option>
                            <option value="Official Equipments">Official Equipments</option>
                        </select>
                    </div>
                    <div class="dw-field">
                        <label for="req-item">Item</label>
                        <select id="req-item" required>
                            <option value="">Select a category first...</option>
                        </select>
                    </div>
                    <div class="dw-field">
                        <label for="req-qty">Quantity</label>
                        <input id="req-qty" type="number" required min="1" step="1" placeholder="e.g., 2">
                    </div>
                    <div class="dw-field">
                        <label for="req-date">Needed by</label>
                        <input id="req-date" type="date" required>
                    </div>
                    <div class="dw-field dw-field--span-2">
                        <label for="req-just">Justification / event</label>
                        <textarea id="req-just" rows="3" required maxlength="500" placeholder="Why does the club need this, and for which event?"></textarea>
                    </div>
                    <div class="dw-alert dw-alert--error" id="req-error" role="alert" hidden></div>
                </form>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="submit" class="dw-button dw-button--primary" form="club-request-form">Submit Request</button>
            </footer>
        </div>
    </div>
<?php endif; ?>

<?php if ($can_register): ?>
    <div id="club-asset-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="asset-modal-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>Secretary action</p>
                    <h2 id="asset-modal-title">Register Asset</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <p>New assets enter as Available. A generated asset ID is assigned on save.</p>
                <form id="club-asset-form" novalidate>
                    <div class="dw-field dw-field--span-2">
                        <label for="asset-name">Asset name</label>
                        <input id="asset-name" name="name" type="text" required maxlength="150" autocomplete="off" placeholder="e.g., Camping Tent Set">
                    </div>
                    <div class="dw-field">
                        <label for="asset-serial">Serial number</label>
                        <input id="asset-serial" name="serial" type="text" required maxlength="50" autocomplete="off" placeholder="e.g., TENT-2026-001">
                    </div>
                    <div class="dw-field">
                        <label for="asset-category">Category</label>
                        <select id="asset-category" name="category" required>
                            <option value="">Select category...</option>
                            <option value="Sports">Sports</option>
                            <option value="Audio Video Equipments">Audio Video Equipments</option>
                            <option value="Cleaning">Cleaning</option>
                            <option value="Official Equipments">Official Equipments</option>
                        </select>
                    </div>
                    <div class="dw-field">
                        <label for="asset-date">Purchase date</label>
                        <input id="asset-date" name="purchase_date" type="date" required>
                    </div>
                    <div class="dw-field">
                        <label for="asset-value">Valuation (LKR)</label>
                        <input id="asset-value" name="valuation" type="number" required min="1" step="0.01" placeholder="0.00">
                    </div>
                    <div class="dw-field dw-field--span-2">
                        <label for="asset-photo">Photo (optional)</label>
                        <input id="asset-photo" name="photo" type="file" accept="image/*">
                    </div>
                    <p id="asset-file-name" hidden></p>
                    <div class="dw-alert dw-alert--error" id="asset-error" role="alert" hidden></div>
                </form>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="submit" class="dw-button dw-button--primary" form="club-asset-form">Register Asset</button>
            </footer>
        </div>
    </div>
<?php endif; ?>

<?php if ($can_transfer): ?>
    <div id="club-transfer-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="transfer-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>Treasurer action</p>
                    <h2 id="transfer-title">Transfer Custody</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <p id="transfer-asset"></p>
                <div class="dw-field">
                    <label for="transfer-custodian">Custodian (Available members)</label>
                    <select id="transfer-custodian">
                        <?php foreach ($custodians as $c): ?>
                            <option value="<?= $e($c) ?>"><?= $e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="dw-field">
                    <label for="transfer-date">Transfer date</label>
                    <input id="transfer-date" type="date" required>
                </div>
                <div class="dw-field">
                    <label for="transfer-note">History note (required)</label>
                    <textarea id="transfer-note" rows="3" placeholder="Condition, purpose, expected return..."></textarea>
                </div>
                <div class="dw-alert dw-alert--error" id="transfer-error" role="alert" hidden></div>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="button" class="dw-button dw-button--primary" id="transfer-confirm">Confirm transfer</button>
            </footer>
        </div>
    </div>
<?php endif; ?>



<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
