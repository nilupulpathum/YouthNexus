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
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$stats         = $stats ?? [];
$assets        = $assets ?? [];
$can_transfer  = !empty($can_transfer);
$can_register  = !empty($can_register);
$custodians    = ['Nuwan Bandara', 'Amal Perera', 'Kasun Fernando', 'Dilini Jayasuriya', 'Ruwan Silva'];
?>

<section class="club-page" aria-labelledby="club-assets-heading">
    <h1 id="club-assets-heading" class="sr-only">Club assets</h1>

    <div class="club-stat-grid" aria-label="Asset summary">
        <article class="club-stat-card">
            <p class="club-stat-label">Total assets</p>
            <p class="club-stat-value"><?= $escape($stats['total'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Available</p>
            <p class="club-stat-value"><?= $escape($stats['available'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">In use</p>
            <p class="club-stat-value"><?= $escape($stats['in_use'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Total valuation</p>
            <p class="club-stat-value club-stat-value--small"><?= $escape($stats['valuation'] ?? '') ?></p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="club-assets-list-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Youth Development Club</p>
                <h2 id="club-assets-list-heading">Inventory</h2>
            </div>
            <?php if ($can_register): ?>
                <button type="button" class="club-btn-primary" id="club-asset-open">Register Asset</button>
            <?php endif; ?>
        </div>

        <div class="club-filters" role="search" aria-label="Filter assets">
            <label class="club-search" for="club-asset-search">
                <span class="icon"><?= yn_icon('eye') ?></span>
                <span class="sr-only">Search inventory</span>
                <input id="club-asset-search" type="search" placeholder="Search name or serial..." autocomplete="off">
            </label>
            <label class="club-filter-field">
                <span class="sr-only">Filter by category</span>
                <select id="club-asset-category">
                    <option value="">All categories</option>
                    <option value="sports">Sports</option>
                    <option value="audio video equipments">Audio Video Equipments</option>
                    <option value="cleaning">Cleaning</option>
                    <option value="official equipments">Official Equipments</option>
                </select>
            </label>
            <label class="club-filter-field">
                <span class="sr-only">Filter by status</span>
                <select id="club-asset-status">
                    <option value="">All statuses</option>
                    <option value="available">Available</option>
                    <option value="inuse">In Use</option>
                </select>
            </label>
        </div>

        <div class="club-table-wrap">
            <table class="club-table">
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
                        <tr data-search="<?= $escape(strtolower(($a['name'] ?? '') . ' ' . ($a['serial'] ?? '') . ' ' . ($a['category'] ?? ''))) ?>"
                            data-status="<?= $escape($a['status_key'] ?? 'available') ?>"
                            data-category="<?= $escape(strtolower($a['category'] ?? '')) ?>"
                            data-name="<?= $escape($a['name'] ?? '') ?>">
                            <td>
                                <div class="club-asset-cell">
                                    <span class="club-thumb" aria-hidden="true"><?= yn_icon('file') ?></span>
                                    <span><strong><?= $escape($a['name'] ?? '') ?></strong>
                                    <span class="club-asset-id"><?= $escape($a['serial'] ?? '') ?></span></span>
                                </div>
                            </td>
                            <td><?= $escape($a['category'] ?? '') ?></td>
                            <td><?= $escape($a['serial'] ?? '') ?></td>
                            <td><?= $escape($a['purchase_date'] ?? '') ?></td>
                            <td><?= $escape($a['valuation'] ?? '') ?></td>
                            <td><?= $escape($a['custodian'] ?? '') ?></td>
                            <td><span class="club-pill club-pill--<?= $escape($a['status_key'] ?? 'available') ?>"><?= $escape($a['status'] ?? '') ?></span></td>
                            <?php if ($can_transfer): ?>
                                <td>
                                    <?php if (($a['status_key'] ?? '') === 'available'): ?>
                                        <button type="button" class="club-btn-small" data-action="transfer">Transfer</button>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p id="club-asset-empty" class="club-note" hidden>No assets match these filters.</p>
    </section>
</section>

<?php if ($can_register): ?>
    <div id="club-asset-modal" class="popup-overlay" hidden>
        <div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="asset-modal-title">
            <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
            <p class="club-eyebrow">Secretary action</p>
            <h2 id="asset-modal-title">Register Asset</h2>
            <p class="club-sub-note">New assets enter as Available. A generated asset ID is assigned on save.</p>
            <form id="club-asset-form" class="club-form" novalidate>
                <div class="club-form-grid">
                    <div class="club-field club-field--full">
                        <label for="asset-name">Asset name</label>
                        <input id="asset-name" name="name" type="text" required maxlength="150" autocomplete="off" placeholder="e.g., Camping Tent Set">
                    </div>
                    <div class="club-field">
                        <label for="asset-serial">Serial number</label>
                        <input id="asset-serial" name="serial" type="text" required maxlength="50" autocomplete="off" placeholder="e.g., TENT-2026-001">
                    </div>
                    <div class="club-field">
                        <label for="asset-category">Category</label>
                        <select id="asset-category" name="category" required>
                            <option value="">Select category...</option>
                            <option value="Sports">Sports</option>
                            <option value="Audio Video Equipments">Audio Video Equipments</option>
                            <option value="Cleaning">Cleaning</option>
                            <option value="Official Equipments">Official Equipments</option>
                        </select>
                    </div>
                    <div class="club-field">
                        <label for="asset-date">Purchase date</label>
                        <input id="asset-date" name="purchase_date" type="date" required>
                    </div>
                    <div class="club-field">
                        <label for="asset-value">Valuation (LKR)</label>
                        <input id="asset-value" name="valuation" type="number" required min="1" step="0.01" placeholder="0.00">
                    </div>
                    <div class="club-field club-field--full">
                        <label for="asset-photo">Photo (optional)</label>
                        <input id="asset-photo" name="photo" type="file" accept="image/*">
                    </div>
                </div>
                <p id="asset-file-name" class="club-sub-note" hidden></p>
                <p id="asset-error" class="club-form-error" hidden></p>
                <div class="club-modal-footer">
                    <button type="button" class="club-btn-secondary" data-close>Cancel</button>
                    <button type="submit" class="club-btn-primary">Register Asset</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($can_transfer): ?>
    <div id="club-transfer-modal" class="popup-overlay" hidden>
        <div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="transfer-title">
            <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
            <p class="club-eyebrow">Treasurer action</p>
            <h2 id="transfer-title">Transfer Custody</h2>
            <p id="transfer-asset" class="club-sub-note"></p>
            <div class="club-field">
                <label for="transfer-custodian">Custodian (Available members)</label>
                <select id="transfer-custodian">
                    <?php foreach ($custodians as $c): ?>
                        <option value="<?= $escape($c) ?>"><?= $escape($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="club-field">
                <label for="transfer-date">Transfer date</label>
                <input id="transfer-date" type="date" required>
            </div>
            <div class="club-field">
                <label for="transfer-note">History note (required)</label>
                <textarea id="transfer-note" rows="3" placeholder="Condition, purpose, expected return..."></textarea>
            </div>
            <p id="transfer-error" class="club-form-error" hidden></p>
            <div class="club-modal-footer">
                <button type="button" class="club-btn-secondary" data-close>Cancel</button>
                <button type="button" class="club-btn-primary" id="transfer-confirm">Confirm transfer</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div id="club-toast" class="club-toast" role="status" hidden></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.getElementById('club-asset-body');
    const search = document.getElementById('club-asset-search');
    const categorySel = document.getElementById('club-asset-category');
    const statusSel = document.getElementById('club-asset-status');
    const empty = document.getElementById('club-asset-empty');

    const applyFilters = () => {
        if (!body) return;
        const q = (search ? search.value.trim().toLowerCase() : '');
        const c = categorySel ? categorySel.value.toLowerCase() : '';
        const s = statusSel ? statusSel.value : '';
        let visible = 0;
        body.querySelectorAll('tr').forEach(row => {
            const okQ = !q || (row.getAttribute('data-search') || '').includes(q);
            const okC = !c || (row.getAttribute('data-category') || '') === c;
            const okS = !s || (row.getAttribute('data-status') || '') === s;
            const show = okQ && okC && okS;
            row.style.display = show ? '' : 'none';
            if (show) visible += 1;
        });
        if (empty) empty.hidden = visible !== 0;
    };
    [search, categorySel, statusSel].forEach(el => {
        if (el) el.addEventListener('input', applyFilters);
        if (el) el.addEventListener('change', applyFilters);
    });

    const toast = document.getElementById('club-toast');
    let toastTimer = null;
    const showToast = (msg) => {
        if (!toast) return;
        toast.textContent = msg;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    };

    const fmtVal = (n) => 'Rs. ' + Math.round(n).toLocaleString('en-US');
    let nextId = 5;
    const nextAssetId = () => 'AST-2025-' + String(nextId++).padStart(3, '0');

    // Register-asset modal (secretary → Available + generated ID).
    const openBtn = document.getElementById('club-asset-open');
    const assetModal = document.getElementById('club-asset-modal');
    const form = document.getElementById('club-asset-form');
    if (openBtn && assetModal && form && body) {
        const err = document.getElementById('asset-error');
        const photo = document.getElementById('asset-photo');
        const fileName = document.getElementById('asset-file-name');

        const open = () => {
            form.reset();
            fileName.hidden = true;
            err.hidden = true;
            assetModal.hidden = false;
            document.body.style.overflow = 'hidden';
        };
        const close = () => { assetModal.hidden = true; document.body.style.overflow = ''; };
        openBtn.addEventListener('click', open);
        assetModal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
        assetModal.addEventListener('click', (e) => { if (e.target === assetModal) close(); });
        photo.addEventListener('change', () => {
            if (photo.files.length) {
                fileName.textContent = 'Attached: ' + photo.files[0].name;
                fileName.hidden = false;
            } else {
                fileName.hidden = true;
            }
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('asset-name').value.trim();
            const serial = document.getElementById('asset-serial').value.trim();
            const category = document.getElementById('asset-category').value;
            const dateVal = document.getElementById('asset-date').value;
            const valueVal = document.getElementById('asset-value').value.trim();
            const fail = (m) => { err.textContent = m; err.hidden = false; };
            err.hidden = true;
            if (!name || !serial || !category || !dateVal || !valueVal) return fail('All fields except photo are required.');
            const value = Number(valueVal);
            if (!isFinite(value) || value <= 0) return fail('Valuation must be a positive amount.');
            const nice = new Date(dateVal + 'T00:00').toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const assetId = nextAssetId();
            const row = document.createElement('tr');
            row.setAttribute('data-search', (name + ' ' + serial + ' ' + category).toLowerCase());
            row.setAttribute('data-status', 'available');
            row.setAttribute('data-category', category.toLowerCase());
            row.setAttribute('data-name', name);
            const assetCell = document.createElement('td');
            const wrap = document.createElement('div');
            wrap.className = 'club-asset-cell';
            const thumb = document.createElement('span');
            thumb.className = 'club-thumb';
            thumb.setAttribute('aria-hidden', 'true');
            if (photo.files.length) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(photo.files[0]);
                img.alt = '';
                thumb.appendChild(img);
            } else {
                const firstThumb = body.querySelector('.club-thumb');
                if (firstThumb) thumb.innerHTML = firstThumb.innerHTML;
            }
            const copy = document.createElement('span');
            const strong = document.createElement('strong');
            strong.textContent = name;
            const idLine = document.createElement('span');
            idLine.className = 'club-asset-id';
            idLine.textContent = assetId;
            copy.appendChild(strong);
            copy.appendChild(idLine);
            wrap.appendChild(thumb);
            wrap.appendChild(copy);
            assetCell.appendChild(wrap);
            row.appendChild(assetCell);
            [category, serial, nice, fmtVal(value), 'Club Centre'].forEach(text => {
                const td = document.createElement('td');
                td.textContent = text;
                row.appendChild(td);
            });
            const statusTd = document.createElement('td');
            const pill = document.createElement('span');
            pill.className = 'club-pill club-pill--available';
            pill.textContent = 'Available';
            statusTd.appendChild(pill);
            row.appendChild(statusTd);
            body.prepend(row);
            close();
            applyFilters();
            showToast(name + ' registered as ' + assetId + ' (demo — persists in C13 backend).');
        });
    }

    // Transfer-custody modal (treasurer: Available-only + custodian + date + history).
    const transferModal = document.getElementById('club-transfer-modal');
    if (transferModal && body) {
        const assetEl = document.getElementById('transfer-asset');
        const custodianSel = document.getElementById('transfer-custodian');
        const dateInput = document.getElementById('transfer-date');
        const note = document.getElementById('transfer-note');
        const err = document.getElementById('transfer-error');
        const confirmBtn = document.getElementById('transfer-confirm');
        let target = null;

        const close = () => { transferModal.hidden = true; document.body.style.overflow = ''; };
        transferModal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
        transferModal.addEventListener('click', (e) => { if (e.target === transferModal) close(); });

        body.querySelectorAll('[data-action="transfer"]').forEach(btn => {
            btn.addEventListener('click', () => {
                target = btn.closest('tr');
                assetEl.textContent = target.getAttribute('data-name') || '';
                note.value = '';
                dateInput.value = '';
                err.hidden = true;
                transferModal.hidden = false;
                document.body.style.overflow = 'hidden';
            });
        });

        confirmBtn.addEventListener('click', () => {
            if (!dateInput.value) {
                err.textContent = 'Choose the transfer date.';
                err.hidden = false;
                dateInput.focus();
                return;
            }
            if (!note.value.trim()) {
                err.textContent = 'A history note is required — it is logged with the transfer.';
                err.hidden = false;
                note.focus();
                return;
            }
            if (target) {
                target.children[5].textContent = custodianSel.value;
                target.setAttribute('data-status', 'inuse');
                const statusCell = target.children[6];
                statusCell.textContent = '';
                const pill = document.createElement('span');
                pill.className = 'club-pill club-pill--inuse';
                pill.textContent = 'In Use';
                statusCell.appendChild(pill);
                const btn = target.querySelector('[data-action="transfer"]');
                if (btn) btn.remove();
            }
            close();
            applyFilters();
            showToast('Custody transferred to ' + custodianSel.value + ' — history logged (demo).');
        });
    }
});
</script>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
