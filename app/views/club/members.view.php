<?php
/**
 * Club Members — roster + search/role/status filters; president assign-role
 * modal + member review modal; secretary register-member modal.
 * Presentation-only: no DB writes; backend contract lands in C13.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$stats  = $stats ?? [];
$roster = $roster ?? [];
$can_manage = !empty($can_manage);
$can_register = !empty($can_register);
$existing_nics = $existing_nics ?? [];
?>

<section class="club-page" aria-labelledby="club-members-heading">
    <h1 id="club-members-heading" class="sr-only">Club members</h1>

    <div class="club-stat-grid" aria-label="Membership summary">
        <article class="club-stat-card">
            <p class="club-stat-label">Total members</p>
            <p class="club-stat-value"><?= $escape($stats['total'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Executives</p>
            <p class="club-stat-value"><?= $escape($stats['executives'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">General members</p>
            <p class="club-stat-value"><?= $escape($stats['members'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Pending approvals</p>
            <p class="club-stat-value"><?= $escape($stats['pending'] ?? 0) ?></p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="club-roster-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Youth Development Club</p>
                <h2 id="club-roster-heading">Member Roster</h2>
            </div>
            <div class="club-header-actions">
                <label class="club-search" for="club-member-search">
                    <span class="icon"><?= yn_icon('eye') ?></span>
                    <span class="sr-only">Search roster</span>
                    <input id="club-member-search" type="search" placeholder="Search name, role, email..." autocomplete="off">
                </label>
                <label class="club-filter-field">
                    <span class="sr-only">Filter by role</span>
                    <select id="club-member-role">
                        <option value="">All roles</option>
                        <option value="president">President</option>
                        <option value="secretary">Secretary</option>
                        <option value="treasurer">Treasurer</option>
                        <option value="member">Member</option>
                    </select>
                </label>
                <label class="club-filter-field">
                    <span class="sr-only">Filter by status</span>
                    <select id="club-member-status">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                    </select>
                </label>
                <?php if ($can_register): ?>
                    <button type="button" class="club-btn-primary" id="club-register-open">Register Member</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="club-table-wrap">
            <table class="club-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <?php if ($can_manage): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="club-roster-body">
                    <?php foreach ($roster as $m): ?>
                        <tr data-search="<?= $escape(strtolower(($m['name'] ?? '') . ' ' . ($m['role'] ?? '') . ' ' . ($m['email'] ?? ''))) ?>"
                            data-name="<?= $escape($m['name'] ?? '') ?>"
                            data-role="<?= $escape($m['role'] ?? '') ?>"
                            data-email="<?= $escape($m['email'] ?? '') ?>"
                            data-phone="<?= $escape($m['phone'] ?? '') ?>"
                            data-address="<?= $escape($m['address'] ?? '') ?>"
                            data-nic="<?= $escape($m['nic'] ?? '') ?>"
                            data-joined="<?= $escape($m['joined'] ?? '') ?>"
                            data-status="<?= $escape($m['status_key'] ?? 'active') ?>">
                            <td><strong><?= $escape($m['name'] ?? '') ?></strong></td>
                            <td><?= $escape($m['role'] ?? '') ?></td>
                            <td><?= $escape($m['email'] ?? '') ?></td>
                            <td><?= $escape($m['phone'] ?? '') ?></td>
                            <td><?= $escape($m['joined'] ?? '') ?></td>
                            <td><span class="club-pill club-pill--<?= $escape($m['status_key'] ?? 'active') ?>"><?= $escape($m['status'] ?? '') ?></span></td>
                            <?php if ($can_manage): ?>
                                <td>
                                    <?php if (($m['status_key'] ?? '') === 'pending'): ?>
                                        <button type="button" class="club-btn-small" data-action="member-review">Review</button>
                                    <?php else: ?>
                                        <button type="button" class="club-btn-small" data-action="assign">Assign role</button>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p id="club-roster-empty" class="club-note" hidden>No members match these filters.</p>

    </section>
</section>

<?php if ($can_register): ?>
    <div id="club-register-modal" class="popup-overlay" hidden>
        <div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="reg-modal-title">
            <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
            <p class="club-eyebrow">Secretary action</p>
            <h2 id="reg-modal-title">Register Member</h2>
            <p class="club-sub-note">New members join as General Member once the president approves. NIC and email must be unique.</p>
            <form id="club-register-form" class="club-form" novalidate>
                <div class="club-form-grid">
                    <div class="club-field">
                        <label for="reg-name">Full name</label>
                        <input id="reg-name" name="name" type="text" required autocomplete="off">
                    </div>
                    <div class="club-field">
                        <label for="reg-nic">NIC</label>
                        <input id="reg-nic" name="nic" type="text" required autocomplete="off">
                    </div>
                    <div class="club-field">
                        <label for="reg-email">Email</label>
                        <input id="reg-email" name="email" type="email" required autocomplete="off">
                    </div>
                    <div class="club-field">
                        <label for="reg-phone">Phone</label>
                        <input id="reg-phone" name="phone" type="tel" required autocomplete="off">
                    </div>
                    <div class="club-field club-field--full">
                        <label for="reg-address">Address</label>
                        <input id="reg-address" name="address" type="text" required autocomplete="off">
                    </div>
                </div>
                <p id="reg-error" class="club-form-error" hidden></p>
                <div class="club-modal-footer">
                    <button type="button" class="club-btn-secondary" data-close>Cancel</button>
                    <button type="submit" class="club-btn-primary">Register Member</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($can_manage): ?>
    <div id="assign-modal" class="popup-overlay" hidden>
        <div class="popup-content club-modal">
            <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
            <p class="club-eyebrow">President action</p>
            <h2 id="assign-title">Assign executive role</h2>
            <p class="club-sub-note">Member: <strong id="assign-member"></strong> (<span id="assign-current"></span>)</p>
            <div class="club-field">
                <label for="assign-role">Target role</label>
                <select id="assign-role">
                    <option value="Secretary">Secretary</option>
                    <option value="Treasurer">Treasurer</option>
                </select>
            </div>
            <p id="assign-warning" class="club-warn" hidden></p>
            <p class="club-sub-note">An audit record is logged on confirm (backend, C13).</p>
            <div class="club-modal-footer">
                <button type="button" class="club-btn-secondary" data-close>Cancel</button>
                <button type="button" class="club-btn-primary" id="assign-confirm">Confirm assignment</button>
            </div>
        </div>
    </div>
    <div id="member-review-modal" class="popup-overlay" hidden>
        <div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="mr-title">
            <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
            <p class="club-eyebrow">President decision</p>
            <h2 id="mr-title">Review member</h2>
            <div id="mr-details" class="club-review-details"></div>
            <div class="club-field">
                <label for="mr-result">Review result</label>
                <select id="mr-result">
                    <option value="approve">Approve member</option>
                    <option value="reject">Reject application</option>
                </select>
            </div>
            <div class="club-field">
                <label for="mr-remarks">Decision note (required if rejecting)</label>
                <textarea id="mr-remarks" rows="3" placeholder="Reason for this decision..."></textarea>
            </div>
            <p id="mr-error" class="club-form-error" hidden></p>
            <div class="club-impact" role="note">
                <strong>What happens next</strong>
                <p>Approving adds them as a General Member of the club. Rejecting discards the application with your note.</p>
            </div>
            <div class="club-modal-footer">
                <button type="button" class="club-btn-secondary" data-close>Cancel</button>
                <button type="button" class="club-btn-primary" id="mr-confirm">Confirm &amp; submit decision</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div id="club-toast" class="club-toast" role="status" hidden></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('club-member-search');
    const body = document.getElementById('club-roster-body');
    const roleSel = document.getElementById('club-member-role');
    const statusSel = document.getElementById('club-member-status');
    const rosterEmpty = document.getElementById('club-roster-empty');
    if (input && body) {
        const applyRosterFilters = () => {
            const q = input.value.trim().toLowerCase();
            const r = roleSel ? roleSel.value.toLowerCase() : '';
            const s = statusSel ? statusSel.value : '';
            let visible = 0;
            body.querySelectorAll('tr').forEach(row => {
                const okQ = !q || (row.getAttribute('data-search') || '').includes(q);
                const okR = !r || (row.getAttribute('data-role') || '').toLowerCase() === r;
                const okS = !s || (row.getAttribute('data-status') || '') === s;
                const show = okQ && okR && okS;
                row.style.display = show ? '' : 'none';
                if (show) visible += 1;
            });
            if (rosterEmpty) rosterEmpty.hidden = visible !== 0;
        };
        input.addEventListener('input', applyRosterFilters);
        if (roleSel) roleSel.addEventListener('change', applyRosterFilters);
        if (statusSel) statusSel.addEventListener('change', applyRosterFilters);
    }

    const toast = document.getElementById('club-toast');
    let toastTimer = null;
    const showToast = (msg) => {
        if (!toast) return;
        toast.textContent = msg;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    };

    // Assign-role modal (president).
    const modal = document.getElementById('assign-modal');
    if (modal && body) {
        const memberEl = document.getElementById('assign-member');
        const currentEl = document.getElementById('assign-current');
        const roleSel = document.getElementById('assign-role');
        const warning = document.getElementById('assign-warning');
        const confirmBtn = document.getElementById('assign-confirm');
        let targetRow = null;

        const occupantOf = (role) => {
            const rows = Array.from(body.querySelectorAll('tr'));
            const hit = rows.find(r => (r.getAttribute('data-role') || '').toLowerCase() === role.toLowerCase());
            return hit ? hit.getAttribute('data-name') : '';
        };

        const refreshWarning = () => {
            const holder = occupantOf(roleSel.value);
            if (holder) {
                warning.textContent = roleSel.value + ' is held by ' + holder + ' — confirming revokes them to General Member first.';
                warning.hidden = false;
            } else {
                warning.hidden = true;
            }
        };

        const openAssign = (btn) => {
            targetRow = btn.closest('tr');
            memberEl.textContent = targetRow.getAttribute('data-name') || '';
            currentEl.textContent = targetRow.getAttribute('data-role') || '';
            refreshWarning();
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        };
        const bindAssign = (btn) => btn.addEventListener('click', () => openAssign(btn));
        body.querySelectorAll('[data-action="assign"]').forEach(bindAssign);

        roleSel.addEventListener('change', refreshWarning);

        const close = () => { modal.hidden = true; document.body.style.overflow = ''; };
        modal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
        modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

        confirmBtn.addEventListener('click', () => {
            if (targetRow) {
                targetRow.setAttribute('data-role', roleSel.value);
                targetRow.children[1].textContent = roleSel.value;
            }
            close();
            showToast('Role assignment recorded (demo — persists in C13 backend).');
        });

        // Member review modal (president: full details, approve or reject with note).
        const mrModal = document.getElementById('member-review-modal');
        if (mrModal) {
            const mrTitle = document.getElementById('mr-title');
            const mrDetails = document.getElementById('mr-details');
            const mrResult = document.getElementById('mr-result');
            const mrRemarks = document.getElementById('mr-remarks');
            const mrErr = document.getElementById('mr-error');
            const mrConfirm = document.getElementById('mr-confirm');
            const MR_FIELDS = [
                ['Email', 'email'], ['Phone', 'phone'], ['Address', 'address'],
                ['NIC', 'nic'], ['Role', 'role'], ['Joined', 'joined'],
            ];
            let reviewRow = null;

            const mrClose = () => { mrModal.hidden = true; document.body.style.overflow = ''; };
            mrModal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', mrClose));
            mrModal.addEventListener('click', (e) => { if (e.target === mrModal) mrClose(); });

            const refreshRosterEmpty = () => {
                if (!rosterEmpty) return;
                const visible = Array.from(body.querySelectorAll('tr'))
                    .filter(r => r.style.display !== 'none').length;
                rosterEmpty.hidden = visible !== 0;
            };

            body.querySelectorAll('[data-action="member-review"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    reviewRow = btn.closest('tr');
                    mrTitle.textContent = reviewRow.getAttribute('data-name') || 'Review member';
                    mrDetails.textContent = '';
                    MR_FIELDS.forEach(([label, key]) => {
                        const row = document.createElement('p');
                        row.className = 'club-review-row';
                        const lab = document.createElement('span');
                        lab.textContent = label;
                        const val = document.createElement('strong');
                        val.textContent = reviewRow.getAttribute('data-' + key) || '—';
                        row.appendChild(lab);
                        row.appendChild(val);
                        mrDetails.appendChild(row);
                    });
                    mrResult.value = 'approve';
                    mrRemarks.value = '';
                    mrErr.hidden = true;
                    mrModal.hidden = false;
                    document.body.style.overflow = 'hidden';
                });
            });

            mrConfirm.addEventListener('click', () => {
                const reject = mrResult.value === 'reject';
                if (reject && !mrRemarks.value.trim()) {
                    mrErr.textContent = 'Please add a note explaining the rejection.';
                    mrErr.hidden = false;
                    mrRemarks.focus();
                    return;
                }
                const who = reviewRow ? (reviewRow.getAttribute('data-name') || 'Member') : 'Member';
                if (reject) {
                    if (reviewRow) reviewRow.remove();
                    refreshRosterEmpty();
                    showToast(who + '’s application rejected with note (demo).');
                } else {
                    if (reviewRow) {
                        reviewRow.setAttribute('data-status', 'active');
                        reviewRow.setAttribute('data-role', 'Member');
                        reviewRow.children[1].textContent = 'Member';
                        const pill = reviewRow.querySelector('.club-pill');
                        if (pill) { pill.textContent = 'Active'; pill.className = 'club-pill club-pill--active'; }
                        const assignBtn = document.createElement('button');
                        assignBtn.type = 'button';
                        assignBtn.className = 'club-btn-small';
                        assignBtn.setAttribute('data-action', 'assign');
                        assignBtn.textContent = 'Assign role';
                        const oldBtn = reviewRow.querySelector('[data-action="member-review"]');
                        if (oldBtn) oldBtn.replaceWith(assignBtn);
                        bindAssign(assignBtn);
                        if (statusSel && statusSel.value && statusSel.value !== 'active') {
                            reviewRow.style.display = 'none';
                        }
                    }
                    showToast(who + ' approved as General Member (demo — persists in C13 backend).');
                }
                reviewRow = null;
                mrClose();
            });
        }
    }

    // Register-member modal (secretary).
    const regOpen = document.getElementById('club-register-open');
    const regModal = document.getElementById('club-register-modal');
    const form = document.getElementById('club-register-form');
    if (regOpen && regModal && form && body) {
        const err = document.getElementById('reg-error');
        const existingNics = <?= json_encode(array_values($existing_nics)) ?>;
        const open = () => {
            form.reset();
            err.hidden = true;
            regModal.hidden = false;
            document.body.style.overflow = 'hidden';
        };
        const close = () => { regModal.hidden = true; document.body.style.overflow = ''; };
        regOpen.addEventListener('click', open);
        regModal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
        regModal.addEventListener('click', (e) => { if (e.target === regModal) close(); });
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('reg-name').value.trim();
            const nic = document.getElementById('reg-nic').value.trim();
            const email = document.getElementById('reg-email').value.trim();
            const phone = document.getElementById('reg-phone').value.trim();
            const address = document.getElementById('reg-address').value.trim();
            const fail = (m) => { err.textContent = m; err.hidden = false; };
            err.hidden = true;
            if (!name || !nic || !email || !phone || !address) return fail('All fields are required.');
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return fail('Enter a valid email address.');
            if (existingNics.includes(nic)) return fail('This NIC is already registered — back to the form.');
            const dupEmail = Array.from(body.querySelectorAll('tr'))
                .some(r => (r.getAttribute('data-email') || '').toLowerCase() === email.toLowerCase());
            if (dupEmail) return fail('This email is already registered — back to the form.');
            const row = document.createElement('tr');
            row.setAttribute('data-search', (name + ' Member ' + email).toLowerCase());
            row.setAttribute('data-name', name);
            row.setAttribute('data-role', 'Member');
            row.setAttribute('data-email', email);
            row.setAttribute('data-phone', phone);
            row.setAttribute('data-address', address);
            row.setAttribute('data-nic', nic);
            row.setAttribute('data-joined', 'Just now');
            row.setAttribute('data-status', 'pending');
            const nameTd = document.createElement('td');
            const strong = document.createElement('strong');
            strong.textContent = name;
            nameTd.appendChild(strong);
            row.appendChild(nameTd);
            [ 'Member', email, phone, 'Just now' ].forEach(text => {
                const td = document.createElement('td');
                td.textContent = text;
                row.appendChild(td);
            });
            const statusTd = document.createElement('td');
            const pill = document.createElement('span');
            pill.className = 'club-pill club-pill--pending';
            pill.textContent = 'Pending';
            statusTd.appendChild(pill);
            row.appendChild(statusTd);
            body.prepend(row);
            close();
            showToast(name + ' added — awaiting president approval (demo).');
        });
    }
});
</script>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
