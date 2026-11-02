<?php
require_once __DIR__ . '/../partials/icons.view.php';
/**
 * Manage Club Bank Accounts — Divisional Treasurer
 * Sub-page inside the Allocate Funds module for registering, editing, and deactivating club receiving bank accounts.
 * Matches the NYSC Fund Transfer bank account management visual language.
 */
$title                   = $title ?? 'Manage Club Bank Accounts — YouthNexus';
$pageTitle               = $pageTitle ?? 'Club Receiving Accounts';
$pageDescription         = $pageDescription ?? 'Configure verified receiving commercial bank accounts for registered youth clubs.';
$currentRoute            = 'divisionalallocations';
$transferRoute           = 'divisionalallocations';
$unreadNotificationCount = 0;
$pageStyles              = [
    ROOT . '/assets/css/fundtransfer.css?v=20261102',
    ROOT . '/assets/css/divisional-workflows.css',
    ROOT . '/assets/css/divisional-allocations.css?v=20261102g'
];
$pageScripts             = [];

require __DIR__ . '/../layouts/dashboard-start.view.php';

// Masking helper
$maskAccount = function ($acc) {
    if (empty($acc)) return '—';
    $clean = preg_replace('/[^\d]/', '', $acc);
    if (strlen($clean) >= 4) {
        return '****-' . substr($clean, -4);
    }
    return '****-' . substr($acc, -4);
};
?>

<div class="fund-transfer-module divisional-allocations-module">

    <!-- Top Action Bar -->
    <div class="ft-header-bar ft-action-row db-action-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div class="ft-back-nav">
            <a href="<?= ROOT ?>/divisionalallocations" class="dw-button dw-button--secondary" id="btnBackToLedger">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back to Fund Allocation Ledger
            </a>
        </div>
        <div class="ft-header-actions">
            <button type="button" class="dw-button dw-button--primary db-primary-action" id="btnOpenRegisterModal" aria-haspopup="dialog">
                <?= yn_icon('plus') ?>
                Register Club Account
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($flashSuccess)): ?>
        <div class="ft-alert ft-alert-success" role="alert">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div><?= htmlspecialchars($flashSuccess) ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div class="ft-alert ft-alert-danger" role="alert">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div><?= htmlspecialchars($flashError) ?></div>
        </div>
    <?php endif; ?>

    <!-- Summary Banner -->
    <div class="ft-accounts-banner">
        <div class="ft-accounts-banner-icon">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="21" width="18" height="2" rx="1"/><polygon points="12 2 2 7 22 7"/><line x1="5" y1="10" x2="5" y2="18"/><line x1="10" y1="10" x2="10" y2="18"/><line x1="14" y1="10" x2="14" y2="18"/><line x1="19" y1="10" x2="19" y2="18"/></svg>
        </div>
        <div class="ft-accounts-banner-text">
            <h3>Registered Receiving Accounts for Club Fund Disbursement</h3>
            <p>Every registered youth club must have an active, verified commercial bank account to receive automated allocations and disbursements. Masked account numbers are displayed in this register for data security. Deactivating an account preserves all past audit history.</p>
        </div>
    </div>

    <!-- Accounts Table Card -->
    <div class="ft-table-card">
        <table class="ft-table" id="accountsTable">
            <thead>
                <tr>
                    <th>Target Club</th>
                    <th>Bank &amp; Branch</th>
                    <th>Account Number</th>
                    <th>Beneficiary Title</th>
                    <th>Status</th>
                    <th class="ft-text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accounts)): ?>
                    <tr>
                        <td colspan="6" class="ft-td-empty">
                            <div class="ft-empty-state">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="21" width="18" height="2" rx="1"/><polygon points="12 2 2 7 22 7"/><line x1="5" y1="10" x2="5" y2="18"/><line x1="10" y1="10" x2="10" y2="18"/><line x1="14" y1="10" x2="14" y2="18"/><line x1="19" y1="10" x2="19" y2="18"/></svg>
                                <p>No club bank accounts registered yet.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($accounts as $acc): ?>
                        <tr class="ft-row <?= $acc->status === 'Inactive' ? 'ft-row-inactive' : '' ?>" id="accRow<?= (int)$acc->bank_account_id ?>">
                            <td class="ft-td-zone">
                                <strong class="ft-val-zone"><?= htmlspecialchars($acc->club_name) ?></strong>
                                <?php if (!empty($acc->club_code)): ?>
                                    <div class="ft-val-sub">Code: <?= htmlspecialchars($acc->club_code) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="ft-bank-title"><?= htmlspecialchars($acc->bank_name) ?></strong>
                                <div class="ft-val-sub"><?= htmlspecialchars($acc->branch_name) ?></div>
                            </td>
                            <td>
                                <code class="ft-account-code" title="Masked for security (****-last4)"><?= htmlspecialchars($maskAccount($acc->account_number)) ?></code>
                            </td>
                            <td>
                                <span class="ft-beneficiary-label"><?= htmlspecialchars($acc->account_label) ?></span>
                            </td>
                            <td>
                                <?php if ($acc->status === 'Active'): ?>
                                    <span class="ft-badge ft-badge-green">Active</span>
                                <?php else: ?>
                                    <span class="ft-badge ft-badge-red">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="ft-actions-cell" style="text-align: right;">
                                <button type="button" class="ft-action-icon-btn btnEditAccount"
                                    title="Edit Account Details"
                                    data-id="<?= (int)$acc->bank_account_id ?>"
                                    data-club-id="<?= (int)$acc->owner_id ?>"
                                    data-bank="<?= htmlspecialchars($acc->bank_name, ENT_QUOTES) ?>"
                                    data-branch="<?= htmlspecialchars($acc->branch_name, ENT_QUOTES) ?>"
                                    data-account="<?= htmlspecialchars($acc->account_number, ENT_QUOTES) ?>"
                                    data-label="<?= htmlspecialchars($acc->account_label, ENT_QUOTES) ?>">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                    Edit
                                </button>
                                
                                <form method="POST" action="<?= ROOT ?>/divisionalallocations/toggleaccountstatus" class="ft-inline-form" style="display: inline-block; margin-left: 6px;" onsubmit="return confirm('<?= $acc->status === 'Active' ? 'Deactivate this bank account? Past transfer history will remain completely intact.' : 'Reactivate this bank account for future transfers?' ?>');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="bank_account_id" value="<?= (int)$acc->bank_account_id ?>">
                                    <button type="submit" class="ft-action-icon-btn <?= $acc->status === 'Active' ? 'ft-btn-deactivate' : 'ft-btn-activate' ?>" title="<?= $acc->status === 'Active' ? 'Deactivate account' : 'Reactivate account' ?>">
                                        <?= $acc->status === 'Active' ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="ft-table-footer">
            <div class="ft-footer-count">
                Total <strong><?= count($accounts) ?></strong> registered club bank account<?= count($accounts) !== 1 ? 's' : '' ?> across <?= count($clubs) ?> clubs in <?= htmlspecialchars($division->division_name ?? 'Division') ?>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     REGISTER / EDIT CLUB BANK ACCOUNT MODAL (Matches NYSC Register Account UI)
     ========================================================================= -->
<div class="ft-overlay" id="accountModal" hidden aria-hidden="true" role="dialog" aria-labelledby="accModalTitle">
    <div class="ft-popup ft-popup-account" id="accountPopup">
        <div class="ft-top-strip"></div>

        <div class="ft-popup-header">
            <div class="ft-popup-title">
                <div>
                    <h1 id="accModalTitle">Register Club Bank Account</h1>
                    <p id="accModalDesc">Central receiving account for club fund disbursements</p>
                </div>
            </div>
            <button type="button" class="ft-close-btn" id="btnCloseAccModal" aria-label="Close dialog">&times;</button>
        </div>

        <form method="POST" action="<?= ROOT ?>/divisionalallocations/createaccount" id="accountForm" class="ft-popup-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="bank_account_id" id="acc_bank_account_id" value="">

            <div class="ft-field">
                <label for="acc_club_id">Target Club <span class="ft-required">*</span></label>
                <select class="ft-input" name="club_id" id="acc_club_id" required>
                    <option value="">— Select Target Club —</option>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= (int)$c->club_id ?>">
                            <?= htmlspecialchars($c->club_name) ?><?= !empty($c->club_code) ? ' (' . htmlspecialchars($c->club_code) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="ft-hint">The youth club authorized to receive allocations through this account.</div>
            </div>

            <div class="ft-form-row">
                <div class="ft-field">
                    <label for="acc_bank_name">Bank Name <span class="ft-required">*</span></label>
                    <select class="ft-input" name="bank_name" id="acc_bank_name" required>
                        <option value="">— Select Bank —</option>
                        <option value="Bank of Ceylon">Bank of Ceylon (BOC)</option>
                        <option value="People's Bank">People's Bank</option>
                        <option value="Commercial Bank of Ceylon">Commercial Bank of Ceylon</option>
                        <option value="Hatton National Bank">Hatton National Bank (HNB)</option>
                        <option value="Sampath Bank">Sampath Bank</option>
                        <option value="National Savings Bank">National Savings Bank (NSB)</option>
                        <option value="Seylan Bank">Seylan Bank</option>
                        <option value="DFCC Bank">DFCC Bank</option>
                        <option value="Nations Trust Bank">Nations Trust Bank</option>
                    </select>
                </div>
                <div class="ft-field">
                    <label for="acc_branch_name">Branch Name / Code <span class="ft-required">*</span></label>
                    <input type="text" class="ft-input" name="branch_name" id="acc_branch_name" placeholder="e.g. Colombo Fort (001)" required>
                </div>
            </div>

            <div class="ft-field">
                <label for="acc_number">Full Account Number <span class="ft-required">*</span></label>
                <input type="text" class="ft-input" name="account_number" id="acc_number" placeholder="e.g. 9005 0011 7457 2716" required>
                <div class="ft-hint">Account numbers in tables will automatically be masked as (****-last4).</div>
            </div>

            <div class="ft-field">
                <label for="acc_label">Account Title / Beneficiary Name <span class="ft-required">*</span></label>
                <input type="text" class="ft-input" name="account_label" id="acc_label" placeholder="e.g. Riverside Youth Skills Society Fund" required>
                <div class="ft-hint">The official registered entity title verified with the commercial bank.</div>
            </div>
        </form>

        <div class="ft-popup-footer">
            <button type="button" class="ft-btn ft-btn-cancel" id="btnCancelAccModal">Cancel</button>
            <button type="submit" form="accountForm" class="dw-button dw-button--primary db-primary-action ft-btn ft-btn-authorize" id="btnSubmitAccount">
                Save Account
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var modal         = document.getElementById('accountModal');
    var btnOpen       = document.getElementById('btnOpenRegisterModal');
    var btnClose      = document.getElementById('btnCloseAccModal');
    var btnCancel     = document.getElementById('btnCancelAccModal');
    var form          = document.getElementById('accountForm');
    var modalTitle    = document.getElementById('accModalTitle');
    var modalDesc     = document.getElementById('accModalDesc');
    var submitBtn     = document.getElementById('btnSubmitAccount');
    var accIdInput    = document.getElementById('acc_bank_account_id');
    var clubSelect    = document.getElementById('acc_club_id');
    var bankSelect    = document.getElementById('acc_bank_name');
    var branchInput   = document.getElementById('acc_branch_name');
    var numInput      = document.getElementById('acc_number');
    var labelInput    = document.getElementById('acc_label');

    var createAction  = '<?= ROOT ?>/divisionalallocations/createaccount';
    var editAction    = '<?= ROOT ?>/divisionalallocations/editaccount';

    function openRegisterModal() {
        if (!modal) return;
        form.action = createAction;
        modalTitle.textContent = 'Register Club Bank Account';
        modalDesc.textContent = 'Central receiving account for club fund disbursements';
        submitBtn.textContent = 'Register Account';
        accIdInput.value = '';
        form.reset();
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
    }

    function openEditModal(btn) {
        if (!modal) return;
        form.action = editAction;
        modalTitle.textContent = 'Edit Club Bank Account';
        modalDesc.textContent = 'Update receiving account information and verification status';
        submitBtn.textContent = 'Save Changes';

        accIdInput.value  = btn.getAttribute('data-id') || '';
        clubSelect.value  = btn.getAttribute('data-club-id') || '';
        bankSelect.value  = btn.getAttribute('data-bank') || '';
        branchInput.value = btn.getAttribute('data-branch') || '';
        numInput.value    = btn.getAttribute('data-account') || '';
        labelInput.value  = btn.getAttribute('data-label') || '';

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
    }

    if (btnOpen) btnOpen.addEventListener('click', openRegisterModal);
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }

    document.querySelectorAll('.btnEditAccount').forEach(function(btn) {
        btn.addEventListener('click', function() {
            openEditModal(this);
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && !modal.hidden) {
            closeModal();
        }
    });
})();
</script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>