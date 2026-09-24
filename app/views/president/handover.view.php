<?php
/**
 * Leadership Handover — C6.
 * Successor member-ID verify (invalid → error + abort) + asset-freeze
 * inventory checklist + president confirm modal → handover log + member
 * notify states. Presentation-only: no DB writes; backend contract
 * lands in C13.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$members      = $members ?? [];
$freezeAssets = $freezeAssets ?? [];
?>

<section class="club-page" aria-labelledby="handover-heading">
    <h1 id="handover-heading" class="sr-only">Leadership handover</h1>

    <p class="club-back-row"><a class="club-btn-secondary" href="<?= ROOT ?>/president"><span aria-hidden="true">‹</span> President overview</a></p>

    <section class="club-panel" aria-labelledby="handover-successor-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Step 1 of 2 · Successor</p>
                <h2 id="handover-successor-heading">Nominate Successor</h2>
            </div>
        </div>

        <div class="club-panel-sub">
            <form id="handover-verify-form" class="club-form" novalidate>
                <div class="club-form-grid">
                    <div class="club-field club-field--full">
                        <label for="handover-search">Find member in roster (name or ID)</label>
                        <div class="club-combo">
                            <span class="club-combo-search-icon" aria-hidden="true"><?= yn_icon('search') ?></span>
                            <input id="handover-search" type="text" autocomplete="off" placeholder="Type to filter, or pick from the full list...">
                            <div id="handover-roster-list" class="club-dropdown" role="listbox" hidden></div>
                        </div>
                    </div>
                    <div class="club-field">
                        <label for="handover-id">Successor member ID</label>
                        <input id="handover-id" type="text" required autocomplete="off" placeholder="e.g., M-004">
                    </div>
                    <div class="club-field">
                        <span class="club-field-label" aria-hidden="true">&nbsp;</span>
                        <div class="club-form-footer club-form-footer--inline">
                            <button type="submit" class="club-btn-primary">Verify identity</button>
                        </div>
                    </div>
                </div>
                <p id="handover-id-error" class="club-form-error" hidden></p>
            </form>

            <div id="handover-verified" class="club-verify-card" hidden>
                <div class="club-list-icon" aria-hidden="true"><?= yn_icon('user') ?></div>
                <div class="club-list-copy">
                    <h3 id="handover-verified-name"></h3>
                    <p id="handover-verified-meta"></p>
                </div>
                <span class="club-pill club-pill--verified">Verified</span>
            </div>
        </div>
    </section>

    <section class="club-panel" aria-labelledby="handover-freeze-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Step 2 of 2 · Asset freeze</p>
                <h2 id="handover-freeze-heading">Inventory Checklist</h2>
            </div>
        </div>

        <div class="club-table-wrap">
            <table class="club-table">
                <thead>
                    <tr>
                        <th><span class="sr-only">Free verified</span></th>
                        <th>Asset</th>
                        <th>Serial</th>
                        <th>Custodian</th>
                    </tr>
                </thead>
                <tbody id="handover-checklist">
                    <?php foreach ($freezeAssets as $a): ?>
                        <tr>
                            <td><input type="checkbox" class="club-check" aria-label="Verify <?= $escape($a['name'] ?? '') ?>"></td>
                            <td><strong><?= $escape($a['name'] ?? '') ?></strong></td>
                            <td><?= $escape($a['serial'] ?? '') ?></td>
                            <td><?= $escape($a['custodian'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="club-panel-sub">
            <p id="handover-error" class="club-form-error" hidden></p>
            <div class="club-form-footer">
                <button type="button" class="club-btn-primary" id="handover-confirm-open">Confirm handover</button>
            </div>
        </div>
    </section>

    <section class="club-panel" id="handover-log-panel" aria-labelledby="handover-log-heading" hidden>
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Handover log</p>
                <h2 id="handover-log-heading">Handover Complete</h2>
            </div>
        </div>
        <div class="club-list">
            <article class="club-list-item">
                <div class="club-list-icon" aria-hidden="true"><?= yn_icon('check') ?></div>
                <div class="club-list-copy">
                    <h3 id="handover-log-title"></h3>
                    <p id="handover-log-meta"></p>
                </div>
                <span class="club-pill club-pill--verified">Logged</span>
            </article>
        </div>
        <p class="club-note">All club members have been notified (demo).</p>
    </section>
</section>

<div id="handover-confirm-modal" class="popup-overlay" hidden>
    <div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="hc-title">
        <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
        <p class="club-eyebrow">Atomic demote + promote</p>
        <h2 id="hc-title">Confirm handover</h2>
        <p id="hc-summary" class="club-sub-note"></p>
        <div class="club-impact" role="note">
            <strong>This cannot be undone in demo</strong>
            <p>Confirming demotes you to General Member and promotes the successor to President in one atomic step, and writes the handover log.</p>
        </div>
        <div class="club-modal-footer">
            <button type="button" class="club-btn-secondary" data-close>Cancel</button>
            <button type="button" class="club-btn-primary" id="hc-confirm">Confirm handover</button>
        </div>
    </div>
</div>

<div id="club-toast" class="club-toast" role="status" hidden></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const REGISTRY = <?= json_encode(array_values($members)) ?>;

    const toast = document.getElementById('club-toast');
    let toastTimer = null;
    const showToast = (msg) => {
        if (!toast) return;
        toast.textContent = msg;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    };

    let successor = null;

    // Step 1: verify successor identity (invalid → error + abort).
    const verifyForm = document.getElementById('handover-verify-form');
    const idInput = document.getElementById('handover-id');
    const idErr = document.getElementById('handover-id-error');
    const verifiedCard = document.getElementById('handover-verified');
    if (verifyForm) {
        verifyForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const key = idInput.value.trim().toUpperCase();
            successor = null;
            verifiedCard.hidden = true;
            idErr.hidden = true;
            if (!key) {
                idErr.textContent = 'Enter a successor member ID.';
                idErr.hidden = false;
                return;
            }
            const hit = REGISTRY.find(m => String(m.id).toUpperCase() === key);
            if (!hit) {
                idErr.textContent = key + ' does not match any club member — handover aborted. Check the ID and try again.';
                idErr.hidden = false;
                return;
            }
            if (hit.current) {
                idErr.textContent = 'You cannot nominate yourself — handover aborted. Choose another member.';
                idErr.hidden = false;
                return;
            }
            if (hit.status !== 'Active') {
                idErr.textContent = hit.name + ' is not an active member (' + hit.status + ') — handover aborted.';
                idErr.hidden = false;
                return;
            }
            successor = hit;
            document.getElementById('handover-verified-name').textContent = hit.name + ' (' + hit.id + ')';
            document.getElementById('handover-verified-meta').textContent = hit.role + ' · ' + hit.status + ' member';
            verifiedCard.hidden = false;
            showToast(hit.name + ' verified as successor (demo).');
        });
    }

    // Roster dropdown (search name/ID, empty shows everyone; picking fills + verifies).
    const searchInput = document.getElementById('handover-search');
    const dropList = document.getElementById('handover-roster-list');
    if (searchInput && dropList && verifyForm) {
        REGISTRY.forEach(m => {
            const opt = document.createElement('button');
            opt.type = 'button';
            opt.className = 'club-dropdown-option';
            opt.setAttribute('role', 'option');
            opt.setAttribute('data-search', (m.name + ' ' + m.id).toLowerCase());
            const nm = document.createElement('strong');
            nm.textContent = m.name;
            const meta = document.createElement('span');
            meta.textContent = m.id + ' · ' + m.role + ' · ' + m.status;
            opt.appendChild(nm);
            opt.appendChild(meta);
            opt.addEventListener('click', () => {
                idInput.value = m.id;
                searchInput.value = m.name;
                dropList.hidden = true;
                verifyForm.requestSubmit();
            });
            dropList.appendChild(opt);
        });
        const filterDrop = () => {
            const q = searchInput.value.trim().toLowerCase();
            dropList.querySelectorAll('.club-dropdown-option').forEach(o => {
                o.style.display = (!q || (o.getAttribute('data-search') || '').includes(q)) ? '' : 'none';
            });
        };
        searchInput.addEventListener('input', () => { dropList.hidden = false; filterDrop(); });
        searchInput.addEventListener('focus', () => { dropList.hidden = false; filterDrop(); });
        searchInput.addEventListener('keydown', (e) => { if (e.key === 'Escape') dropList.hidden = true; });
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.club-combo')) dropList.hidden = true;
        });
    }

    // Step 2 + confirm modal.
    const modal = document.getElementById('handover-confirm-modal');
    const openBtn = document.getElementById('handover-confirm-open');
    const err = document.getElementById('handover-error');
    if (openBtn && modal) {
        const close = () => { modal.hidden = true; document.body.style.overflow = ''; };
        modal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
        modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

        const allChecked = () => {
            const boxes = Array.from(document.querySelectorAll('#handover-checklist .club-check'));
            return boxes.length > 0 && boxes.every(b => b.checked);
        };

        openBtn.addEventListener('click', () => {
            err.hidden = true;
            if (!successor) {
                err.textContent = 'Verify a successor first (Step 1).';
                err.hidden = false;
                idInput.focus();
                return;
            }
            if (!allChecked()) {
                err.textContent = 'Complete the asset-freeze checklist first — every asset must be verified.';
                err.hidden = false;
                return;
            }
            document.getElementById('hc-summary').textContent =
                'Nuwan Bandara (outgoing President) → ' + successor.name + ' (' + successor.id + ') as incoming President. 4/4 assets frozen and verified.';
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        });

        document.getElementById('hc-confirm').addEventListener('click', () => {
            close();
            const now = new Date().toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' +
                new Date().toLocaleString('en-US', { hour: 'numeric', minute: '2-digit' });
            document.getElementById('handover-log-title').textContent =
                'Presidency transferred to ' + successor.name;
            document.getElementById('handover-log-meta').textContent =
                successor.id + ' · demote/promote applied atomically · ' + now;
            document.getElementById('handover-log-panel').hidden = false;
            document.getElementById('handover-log-panel').scrollIntoView({ block: 'nearest' });
            showToast('Handover complete — all members notified (demo — persists in C13 backend).');
        });
    }
});
</script>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
