<?php
/**
 * Manage Zonal Bank Accounts — NYSC Administration
 * Sub-page inside the Fund Transfer module for registering, editing, and deactivating zonal receiving bank accounts.
 */
$title                   = $title ?? 'Manage Zonal Bank Accounts — YouthNexus';
$pageTitle               = $pageTitle ?? 'Zonal Treasury Receiving Accounts';
$pageDescription         = $pageDescription ?? 'Configure verified receiving commercial bank accounts for provincial zonal offices.';
$currentRoute            = 'fundtransfer';
$transferRoute           = 'fundtransfer';
$unreadNotificationCount = 0;
$pageStyles              = [ROOT . '/assets/css/fundtransfer.css?v=20261029'];

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

<div class="fund-transfer-module">

    <!-- Top Action Bar -->
    <div class="ft-header-bar ft-action-row db-action-row" style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="<?= ROOT ?>/fundtransfer" class="ft-btn ft-btn-outline db-secondary-action" id="btnBackToLedger">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back to Fund Transfer Ledger
            </a>
        </div>
        <div class="ft-header-actions">
            <button type="button" class="ft-btn ft-btn-primary db-primary-action" id="btnOpenRegisterModal" aria-haspopup="dialog">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Register Zonal Account
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

    <?php if (!empty($flashErrors)): ?>
        <div class="ft-alert ft-alert-danger" role="alert">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>
                <strong>Please correct the following errors:</strong>
                <ul style="margin: 4px 0 0 16px; padding: 0;">
                    <?php foreach ($flashErrors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- Summary Banner -->
    <div class="ft-accounts-banner">
        <div class="ft-accounts-banner-icon">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="21" width="18" height="2" rx="1"/><polygon points="12 2 2 7 22 7"/><line x1="5" y1="10" x2="5" y2="18"/><line x1="10" y1="10" x2="10" y2="18"/><line x1="14" y1="10" x2="14" y2="18"/><line x1="19" y1="10" x2="19" y2="18"/></svg>
        </div>
        <div class="ft-accounts-banner-text">
            <h3>Registered Receiving Accounts for Central Inter-Governmental Settlement</h3>
            <p>Every zonal office must have an active, verified commercial bank account to receive automated RTGS/SLIPS tranches. Masked account numbers are displayed in this register for data security. Deactivating an account preserves all past audit history.</p>
        </div>
    </div>

    <!-- Accounts Table Card -->
    <div class="ft-table-card">
        <table class="ft-table" id="accountsTable">
            <thead>
                <tr>
                    <th>Target Zone</th>
                    <th>Bank &amp; Branch</th>
                    <th>Account Number</th>
                    <th>Beneficiary Title</th>
                    <th>Gateway</th>
                    <th>Verification</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accounts)): ?>
                    <tr>
                        <td colspan="8" class="ft-td-empty">
                            <div class="ft-empty-state">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="21" width="18" height="2" rx="1"/><polygon points="12 2 2 7 22 7"/><line x1="5" y1="10" x2="5" y2="18"/><line x1="10" y1="10" x2="10" y2="18"/><line x1="14" y1="10" x2="14" y2="18"/><line x1="19" y1="10" x2="19" y2="18"/></svg>
                                <p>No zonal bank accounts registered yet.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($accounts as $acc): ?>
                        <tr class="ft-row <?= $acc->status === 'Inactive' ? 'ft-row-inactive' : '' ?>" id="accRow<?= (int)$acc->bank_account_id ?>">
                            <td class="ft-td-zone">
                                <strong class="ft-val-zone"><?= htmlspecialchars($acc->zonal_name) ?></strong>
                                <?php if (!empty($acc->province)): ?>
                                    <div class="ft-val-sub"><?= htmlspecialchars($acc->province) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: #0f172a;"><?= htmlspecialchars($acc->bank_name) ?></strong>
                                <div class="ft-val-sub"><?= htmlspecialchars($acc->branch_name) ?></div>
                            </td>
                            <td>
                                <code class="ft-account-code" title="Masked for security (****-last4)"><?= htmlspecialchars($maskAccount($acc->account_number)) ?></code>
                            </td>
                            <td>
                                <span class="ft-beneficiary-label"><?= htmlspecialchars($acc->account_label) ?></span>
                            </td>
                            <td>
                                <span class="ft-badge ft-badge-gray"><?= htmlspecialchars($acc->gateway_type) ?></span>
                            </td>
                            <td>
                                <?php if ($acc->verification_status === 'Verified'): ?>
                                    <span class="ft-badge ft-badge-green">Verified</span>
                                <?php else: ?>
                                    <span class="ft-badge ft-badge-yellow">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($acc->status === 'Active'): ?>
                                    <span class="ft-badge ft-badge-blue">Active</span>
                                <?php else: ?>
                                    <span class="ft-badge ft-badge-red">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <button type="button" class="ft-action-icon-btn btnEditAccount"
                                    title="Edit Account Details"
                                    data-id="<?= (int)$acc->bank_account_id ?>"
                                    data-zone-id="<?= (int)$acc->owner_id ?>"
                                    data-bank="<?= htmlspecialchars($acc->bank_name, ENT_QUOTES) ?>"
                                    data-branch="<?= htmlspecialchars($acc->branch_name, ENT_QUOTES) ?>"
                                    data-account="<?= htmlspecialchars($acc->account_number, ENT_QUOTES) ?>"
                                    data-label="<?= htmlspecialchars($acc->account_label, ENT_QUOTES) ?>"
                                    data-gateway="<?= htmlspecialchars($acc->gateway_type, ENT_QUOTES) ?>"
                                    data-verification="<?= htmlspecialchars($acc->verification_status, ENT_QUOTES) ?>">
                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                    Edit
                                </button>
                                
                                <form method="POST" action="<?= ROOT ?>/fundtransfer/toggleaccountstatus" style="display: inline-block; margin-left: 6px;" onsubmit="return confirm('<?= $acc->status === 'Active' ? 'Deactivate this bank account? Existing transfer history will remain completely intact.' : 'Reactivate this bank account for future transfers?' ?>');">
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
                Total <strong><?= count($accounts) ?></strong> registered zonal bank account<?= count($accounts) !== 1 ? 's' : '' ?> across <?= count($zones) ?> zones
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL: Register / Edit Zonal Bank Account
     ========================================================================= -->
<div class="ft-overlay" id="accountModal" hidden aria-hidden="true" role="dialog" aria-labelledby="accModalTitle">
    <div class="ft-popup" id="accountPopup" style="max-width: 580px;">
        <div class="ft-top-strip"></div>

        <div class="ft-popup-header">
            <div>
                <h1 id="accModalTitle">Register Zonal Bank Account</h1>
                <p id="accModalDesc">Central receiving account for provincial fund disbursements</p>
            </div>
            <button type="button" class="ft-close-btn" id="btnCloseAccModal" aria-label="Close dialog">&times;</button>
        </div>

        <form method="POST" action="<?= ROOT ?>/fundtransfer/createaccount" id="accountForm" class="ft-popup-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="bank_account_id" id="acc_bank_account_id" value="">

            <div class="ft-field">
                <label for="acc_zone_id">Target Zonal Office <span class="ft-required">*</span></label>
                <select class="ft-input" name="zone_id" id="acc_zone_id" required>
                    <option value="">— Select Target Zonal Office —</option>
                    <?php foreach ($zones as $z): ?>
                        <option value="<?= (int)$z->zonal_id ?>">
                            <?= htmlspecialchars($z->zonal_name) ?><?= !empty($z->province) ? ' — ' . htmlspecialchars($z->province) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="ft-hint">The provincial zone authorized to receive allocations through this account.</div>
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

            <div class="ft-form-row">
                <div class="ft-field">
                    <label for="acc_number">Full Account Number <span class="ft-required">*</span></label>
                    <input type="text" class="ft-input" name="account_number" id="acc_number" placeholder="e.g. 9005-0011-7457-2716" required>
                    <div class="ft-hint">Account numbers in tables will automatically be masked as (****-last4).</div>
                </div>
                <div class="ft-field">
                    <label for="acc_gateway">Preferred Gateway</label>
                    <select class="ft-input" name="gateway_type" id="acc_gateway">
                        <option value="SLIPS" selected>SLIPS (Inter-bank automated)</option>
                        <option value="RTGS">RTGS (High-value real-time)</option>
                        <option value="API">Direct Bank API Gateway</option>
                        <option value="Cheque">Government Cheque</option>
                    </select>
                </div>
            </div>

            <div class="ft-field">
                <label for="acc_label">Account Title / Beneficiary Name <span class="ft-required">*</span></label>
                <input type="text" class="ft-input" name="account_label" id="acc_label" placeholder="e.g. Western Zone Zonal Treasury Fund" required>
                <div class="ft-hint">The official registered entity title verified with the commercial bank.</div>
            </div>

            <div class="ft-field">
                <label for="acc_verification">Verification Status <span class="ft-required">*</span></label>
                <select class="ft-input" name="verification_status" id="acc_verification" required>
                    <option value="Verified" selected>Verified (Authorized for RTGS / SLIPS Fund Allocation)</option>
                    <option value="Unverified">Unverified (Pending Bank Statement Confirmation)</option>
                </select>
                <div class="ft-hint">Only verified accounts can be chosen in the New Fund Allocation popup.</div>
            </div>
        </form>

        <div class="ft-popup-footer">
            <button type="button" class="ft-btn ft-btn-cancel" id="btnCancelAccModal">Cancel</button>
            <button type="submit" form="accountForm" class="ft-btn ft-btn-authorize" id="btnSubmitAccount">
                Save Account
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    var modal         = document.getElementById('accountModal');
    var btnOpen       = document.getElementById('btnOpenRegisterModal');
    var btnClose      = document.getElementById('btnCloseAccModal');
    var btnCancel     = document.getElementById('btnCancelAccModal');
    var form          = document.getElementById('accountForm');
    var modalTitle    = document.getElementById('accModalTitle');
    var modalDesc     = document.getElementById('accModalDesc');
    var btnSubmit     = document.getElementById('btnSubmitAccount');

    var inpId         = document.getElementById('acc_bank_account_id');
    var selZone       = document.getElementById('acc_zone_id');
    var selBank       = document.getElementById('acc_bank_name');
    var inpBranch     = document.getElementById('acc_branch_name');
    var inpNumber     = document.getElementById('acc_number');
    var inpLabel      = document.getElementById('acc_label');
    var selGateway    = document.getElementById('acc_gateway');
    var selVerif      = document.getElementById('acc_verification');

    function openRegisterModal() {
        if (!modal) return;
        form.action = '<?= ROOT ?>/fundtransfer/createaccount';
        modalTitle.textContent = 'Register Zonal Bank Account';
        modalDesc.textContent = 'Central receiving account for provincial fund disbursements';
        btnSubmit.textContent = 'Register Account';

        inpId.value = '';
        form.reset();
        selGateway.value = 'SLIPS';
        selVerif.value = 'Verified';

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function openEditModal(btn) {
        if (!modal) return;
        form.action = '<?= ROOT ?>/fundtransfer/editaccount';
        modalTitle.textContent = 'Edit Zonal Bank Account';
        modalDesc.textContent = 'Update receiving account information and verification status';
        btnSubmit.textContent = 'Update Account';

        inpId.value        = btn.dataset.id || '';
        selZone.value      = btn.dataset.zoneId || '';
        
        // Match bank name in select or create option if custom
        var bankName = btn.dataset.bank || '';
        selBank.value = bankName;
        if (bankName && selBank.value !== bankName) {
            var opt = new Option(bankName, bankName, true, true);
            selBank.add(opt);
        }

        inpBranch.value    = btn.dataset.branch || '';
        inpNumber.value    = btn.dataset.account || '';
        inpLabel.value     = btn.dataset.label || '';
        selGateway.value   = btn.dataset.gateway || 'SLIPS';
        selVerif.value     = btn.dataset.verification || 'Verified';

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
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

    // Auto-populate label on zone selection if empty
    if (selZone && inpLabel) {
        selZone.addEventListener('change', function() {
            if (!inpLabel.value || inpLabel.value.includes('Zonal Treasury Fund')) {
                var selectedText = selZone.options[selZone.selectedIndex].text.split('—')[0].trim();
                if (selectedText && selectedText !== 'Select Target Zonal Office') {
                    inpLabel.value = selectedText + ' Zonal Treasury Fund';
                }
            }
        });
    }
})();
</script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
