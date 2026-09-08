<?php
/**
 * Club Members — C1 shell. Role assignment + registration flows land in C3.
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
            <label class="club-search" for="club-member-search">
                <span class="icon"><?= yn_icon('eye') ?></span>
                <span class="sr-only">Search roster</span>
                <input id="club-member-search" type="search" placeholder="Search name, role, email..." autocomplete="off">
            </label>
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
                            data-email="<?= $escape($m['email'] ?? '') ?>">
                            <td><strong><?= $escape($m['name'] ?? '') ?></strong></td>
                            <td><?= $escape($m['role'] ?? '') ?></td>
                            <td><?= $escape($m['email'] ?? '') ?></td>
                            <td><?= $escape($m['phone'] ?? '') ?></td>
                            <td><?= $escape($m['joined'] ?? '') ?></td>
                            <td><span class="club-pill club-pill--<?= $escape($m['status_key'] ?? 'active') ?>"><?= $escape($m['status'] ?? '') ?></span></td>
                            <?php if ($can_manage): ?>
                                <td><button type="button" class="club-btn-small" data-action="assign">Assign role</button></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($can_register): ?>
            <div class="club-divider" aria-hidden="true"></div>
            <div class="club-panel-sub" aria-labelledby="club-register-heading">
                <div>
                    <p class="club-eyebrow">Secretary action</p>
                    <h2 id="club-register-heading">Register Member</h2>
                    <p class="club-sub-note">New members join as General Member. NIC and email must be unique.</p>
                </div>
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
                    <div class="club-form-footer">
                        <button type="submit" class="club-btn-primary">Register as General Member</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </section>
</section>

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
<?php endif; ?>

<div id="club-toast" class="club-toast" role="status" hidden></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('club-member-search');
    const body = document.getElementById('club-roster-body');
    if (input && body) {
        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            body.querySelectorAll('tr').forEach(row => {
                row.style.display = (!q || (row.getAttribute('data-search') || '').includes(q)) ? '' : 'none';
            });
        });
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

        body.querySelectorAll('[data-action="assign"]').forEach(btn => {
            btn.addEventListener('click', () => {
                targetRow = btn.closest('tr');
                memberEl.textContent = targetRow.getAttribute('data-name') || '';
                currentEl.textContent = targetRow.getAttribute('data-role') || '';
                refreshWarning();
                modal.hidden = false;
                document.body.style.overflow = 'hidden';
            });
        });

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
    }

    // Register-member form (secretary).
    const form = document.getElementById('club-register-form');
    if (form && body) {
        const err = document.getElementById('reg-error');
        const existingNics = <?= json_encode(array_values($existing_nics)) ?>;
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
            form.reset();
            showToast(name + ' registered as General Member (demo — persists in C13 backend).');
        });
    }
});
</script>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
