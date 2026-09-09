<?php
/**
 * Club Ledger — C8 full UI.
 * Balance header + filterable running-balance table for president and
 * treasurer. Treasurer-only: "Log Transaction" button + modal (income /
 * expense + mandatory receipt upload, blocked-with-error if missing; new
 * entries post as Active with recalculated balance, per the treasurer
 * workflow) and per-row "Request void" action + reason modal (sent to the
 * Divisional Treasurer → row flips to Pending Void; Voided/Disapproved
 * outcomes land with the backend in C13). Presentation-only: no DB writes.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$stats        = $stats ?? [];
$transactions = $transactions ?? [];
$can_log      = !empty($can_log);
?>

<section class="club-page" aria-labelledby="club-ledger-heading">
    <h1 id="club-ledger-heading" class="sr-only">Club ledger</h1>

    <div class="club-stat-grid" aria-label="Fund summary">
        <article class="club-stat-card">
            <p class="club-stat-label">Fund balance</p>
            <p class="club-stat-value club-stat-value--small"><?= $escape($stats['balance'] ?? '') ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Total income</p>
            <p class="club-stat-value club-stat-value--small"><?= $escape($stats['income'] ?? '') ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Total expenses</p>
            <p class="club-stat-value club-stat-value--small"><?= $escape($stats['expenses'] ?? '') ?></p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="club-ledger-list-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Youth Development Club</p>
                <h2 id="club-ledger-list-heading">Transactions</h2>
            </div>
            <?php if ($can_log): ?>
                <button type="button" class="club-btn-primary" id="club-log-open">Log Transaction</button>
            <?php endif; ?>
        </div>

        <div class="club-filters" role="search" aria-label="Filter transactions">
            <label class="club-search" for="club-ledger-search">
                <span class="icon"><?= yn_icon('eye') ?></span>
                <span class="sr-only">Search transactions</span>
                <input id="club-ledger-search" type="search" placeholder="Search description..." autocomplete="off">
            </label>
            <label class="club-filter-field">
                <span class="sr-only">Filter by type</span>
                <select id="club-ledger-type">
                    <option value="">All types</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </label>
            <label class="club-filter-field">
                <span class="sr-only">Filter by status</span>
                <select id="club-ledger-status">
                    <option value="">All statuses</option>
                    <option value="verified">Verified</option>
                    <option value="pending-void">Pending Void</option>
                    <option value="voided">Voided</option>
                </select>
            </label>
        </div>

        <div class="club-table-wrap">
            <table class="club-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <?php if ($can_log): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="club-ledger-body">
                    <?php foreach ($transactions as $t): ?>
                        <tr data-search="<?= $escape(strtolower($t['description'] ?? '')) ?>"
                            data-type="<?= $escape($t['type_key'] ?? 'income') ?>"
                            data-status="<?= $escape($t['status_key'] ?? 'verified') ?>"
                            data-desc="<?= $escape($t['description'] ?? '') ?>">
                            <td><?= $escape($t['date'] ?? '') ?></td>
                            <td><?= $escape($t['description'] ?? '') ?></td>
                            <td><span class="club-pill club-pill--<?= $escape($t['type_key'] ?? 'income') ?>"><?= $escape($t['type'] ?? '') ?></span></td>
                            <td><?= $escape($t['amount'] ?? '') ?></td>
                            <td><?= $escape($t['balance'] ?? '') ?></td>
                            <td><span class="club-pill club-pill--<?= $escape($t['status_key'] ?? 'verified') ?>"><?= $escape($t['status'] ?? '') ?></span></td>
                            <?php if ($can_log): ?>
                                <td>
                                    <?php if (($t['status_key'] ?? '') === 'verified'): ?>
                                        <button type="button" class="club-btn-small" data-action="void">Request void</button>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p id="club-ledger-empty" class="club-note" hidden>No transactions match these filters.</p>
    </section>
</section>

<?php if ($can_log): ?>
    <div id="club-log-modal" class="popup-overlay" hidden>
        <div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="log-modal-title">
            <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
            <p class="club-eyebrow">Treasurer action</p>
            <h2 id="log-modal-title">Log Transaction</h2>
            <p class="club-sub-note">New entries post as Active and the balance is recalculated. A receipt upload is mandatory.</p>
            <form id="club-log-form" class="club-form" novalidate>
                <div id="log-banner" class="club-error-banner" hidden>
                    <span class="club-error-icon" aria-hidden="true"><?= yn_icon('info') ?></span>
                    <div>
                        <strong>Receipt is required</strong>
                        <p>Every transaction needs a receipt. Please attach a photo or PDF to continue.</p>
                    </div>
                </div>
                <div class="club-form-grid">
                    <div class="club-field">
                        <label for="log-type">Transaction type</label>
                        <select id="log-type" name="type" required>
                            <option value="Income">Income</option>
                            <option value="Expense">Expense</option>
                        </select>
                    </div>
                    <div class="club-field">
                        <label for="log-amount">Amount (LKR)</label>
                        <input id="log-amount" name="amount" type="number" required min="1" step="0.01" placeholder="0.00">
                    </div>
                    <div class="club-field">
                        <label for="log-date">Date</label>
                        <input id="log-date" name="date" type="date" required>
                    </div>
                    <div class="club-field">
                        <label for="log-receipt">Receipt (photo or PDF)</label>
                        <input id="log-receipt" name="receipt" type="file" accept="image/*,.pdf" required>
                    </div>
                    <div class="club-field club-field--full">
                        <label for="log-desc">Description</label>
                        <input id="log-desc" name="description" type="text" required maxlength="255" autocomplete="off" placeholder="e.g., Hall hire for workshop">
                    </div>
                </div>
                <p id="log-file-name" class="club-sub-note" hidden></p>
                <p id="log-error" class="club-form-error" hidden></p>
                <div class="club-modal-footer">
                    <button type="button" class="club-btn-secondary" data-close>Cancel</button>
                    <button type="submit" class="club-btn-primary">Log Transaction</button>
                </div>
            </form>
        </div>
    </div>

    <div id="club-void-modal" class="popup-overlay" hidden>
        <div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="void-title">
            <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
            <p class="club-eyebrow">Treasurer action</p>
            <h2 id="void-title">Request void</h2>
            <p id="void-desc" class="club-sub-note"></p>
            <div class="club-field">
                <label for="void-reason">Reason for voiding (required)</label>
                <textarea id="void-reason" rows="3" placeholder="Explain why this transaction should be voided..."></textarea>
            </div>
            <p id="void-error" class="club-form-error" hidden></p>
            <div id="void-impact" class="club-impact" role="note">
                <strong>Where this goes</strong>
                <p>Void requests go to the Divisional Treasurer. The entry flips to Pending Void until they void it (totals recalculated) or disapprove (entry unchanged).</p>
            </div>
            <div class="club-modal-footer">
                <button type="button" class="club-btn-secondary" data-close>Cancel</button>
                <button type="button" class="club-btn-primary" id="void-confirm">Send void request</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div id="club-toast" class="club-toast" role="status" hidden></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.getElementById('club-ledger-body');
    const search = document.getElementById('club-ledger-search');
    const typeSel = document.getElementById('club-ledger-type');
    const statusSel = document.getElementById('club-ledger-status');
    const empty = document.getElementById('club-ledger-empty');

    const applyFilters = () => {
        if (!body) return;
        const q = (search ? search.value.trim().toLowerCase() : '');
        const t = typeSel ? typeSel.value : '';
        const s = statusSel ? statusSel.value : '';
        let visible = 0;
        body.querySelectorAll('tr').forEach(row => {
            const okQ = !q || (row.getAttribute('data-search') || '').includes(q);
            const okT = !t || (row.getAttribute('data-type') || '') === t;
            const okS = !s || (row.getAttribute('data-status') || '') === s;
            const show = okQ && okT && okS;
            row.style.display = show ? '' : 'none';
            if (show) visible += 1;
        });
        if (empty) empty.hidden = visible !== 0;
    };
    [search, typeSel, statusSel].forEach(el => {
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

    const parseRs = (text) => Number(String(text).replace(/[^0-9.]/g, '')) || 0;
    const fmtRs = (n) => 'Rs. ' + Math.round(n).toLocaleString('en-US');
    const pillFor = (kind, label) => {
        const s = document.createElement('span');
        s.className = 'club-pill club-pill--' + kind;
        s.textContent = label;
        return s;
    };

    // Log-transaction modal (treasurer; receipt is mandatory).
    const openBtn = document.getElementById('club-log-open');
    const logModal = document.getElementById('club-log-modal');
    const form = document.getElementById('club-log-form');
    if (openBtn && logModal && form && body) {
        const banner = document.getElementById('log-banner');
        const err = document.getElementById('log-error');
        const receipt = document.getElementById('log-receipt');
        const fileName = document.getElementById('log-file-name');

        const open = () => {
            form.reset();
            fileName.hidden = true;
            err.hidden = true;
            banner.hidden = true;
            logModal.hidden = false;
            document.body.style.overflow = 'hidden';
        };
        const close = () => { logModal.hidden = true; document.body.style.overflow = ''; };
        openBtn.addEventListener('click', open);
        logModal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
        logModal.addEventListener('click', (e) => { if (e.target === logModal) close(); });
        receipt.addEventListener('change', () => {
            if (receipt.files.length) {
                fileName.textContent = 'Attached: ' + receipt.files[0].name;
                fileName.hidden = false;
                banner.hidden = true;
            } else {
                fileName.hidden = true;
            }
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const type = document.getElementById('log-type').value;
            const amountVal = document.getElementById('log-amount').value.trim();
            const dateVal = document.getElementById('log-date').value;
            const desc = document.getElementById('log-desc').value.trim();
            const fail = (m) => { err.textContent = m; err.hidden = false; };
            err.hidden = true;
            if (!amountVal || !dateVal || !desc) return fail('All fields are required.');
            const amount = Number(amountVal);
            if (!isFinite(amount) || amount <= 0) return fail('Amount must be a positive number.');
            if (!receipt.files.length) {
                banner.hidden = false;
                receipt.focus();
                return;
            }
            const nice = new Date(dateVal + 'T00:00').toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const firstRow = body.querySelector('tr');
            const current = firstRow ? parseRs(firstRow.children[4].textContent) : 0;
            const next = type === 'Income' ? current + amount : current - amount;
            const row = document.createElement('tr');
            row.setAttribute('data-search', desc.toLowerCase());
            row.setAttribute('data-type', type.toLowerCase());
            row.setAttribute('data-status', 'verified');
            row.setAttribute('data-desc', desc);
            const cells = [nice, desc];
            const c0 = document.createElement('td'); c0.textContent = cells[0]; row.appendChild(c0);
            const c1 = document.createElement('td'); c1.textContent = cells[1]; row.appendChild(c1);
            const c2 = document.createElement('td'); c2.appendChild(pillFor(type.toLowerCase(), type)); row.appendChild(c2);
            const c3 = document.createElement('td'); c3.textContent = fmtRs(amount); row.appendChild(c3);
            const c4 = document.createElement('td'); c4.textContent = fmtRs(next); row.appendChild(c4);
            const c5 = document.createElement('td'); c5.appendChild(pillFor('verified', 'Verified')); row.appendChild(c5);
            const c6 = document.createElement('td');
            const voidBtn = document.createElement('button');
            voidBtn.type = 'button';
            voidBtn.className = 'club-btn-small';
            voidBtn.setAttribute('data-action', 'void');
            voidBtn.textContent = 'Request void';
            c6.appendChild(voidBtn);
            row.appendChild(c6);
            bindVoidButton(voidBtn);
            body.prepend(row);
            close();
            applyFilters();
            showToast(desc + ' logged — balance updated (demo — persists in C13 backend).');
        });
    }

    // Request-void modal (treasurer → Divisional Treasurer).
    const voidModal = document.getElementById('club-void-modal');
    if (voidModal && body) {
        const descEl = document.getElementById('void-desc');
        const reason = document.getElementById('void-reason');
        const err = document.getElementById('void-error');
        const confirmBtn = document.getElementById('void-confirm');
        let target = null;

        const close = () => { voidModal.hidden = true; document.body.style.overflow = ''; };
        voidModal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
        voidModal.addEventListener('click', (e) => { if (e.target === voidModal) close(); });

        const bindVoidButton = (btn) => {
            btn.addEventListener('click', () => {
                target = btn.closest('tr');
                descEl.textContent = target.getAttribute('data-desc') || '';
                reason.value = '';
                err.hidden = true;
                voidModal.hidden = false;
                document.body.style.overflow = 'hidden';
            });
        };
        body.querySelectorAll('[data-action="void"]').forEach(bindVoidButton);

        confirmBtn.addEventListener('click', () => {
            if (!reason.value.trim()) {
                err.textContent = 'Please give a reason — the Divisional Treasurer needs it to decide.';
                err.hidden = false;
                reason.focus();
                return;
            }
            if (target) {
                target.setAttribute('data-status', 'pending-void');
                const statusCell = target.children[5];
                statusCell.textContent = '';
                statusCell.appendChild(pillFor('pending-void', 'Pending Void'));
                const btn = target.querySelector('[data-action="void"]');
                if (btn) btn.remove();
            }
            close();
            applyFilters();
            showToast('Void request sent to the Divisional Treasurer (demo).');
        });
    }
});
</script>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
