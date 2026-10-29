/**
 * fundtransfer.js — Fund Transfer Module Interactive Frontend
 * NYSC Administration · YouthNexus
 */

(function () {
    'use strict';

    const config = window.YouthNexusFundTransfer || {
        rootUrl: '',
        csrfToken: '',
    };

    const route = config.route || 'fundtransfer';
    const listRoute = config.listRoute || route;
    const $ = id => document.getElementById(id);

    // ── Number to English Words Helper ────────────────────────
    function numberToWords(num) {
        if (isNaN(num) || num <= 0) return '';
        const units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                       'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                       'Seventeen', 'Eighteen', 'Nineteen'];
        const tens  = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        function convert(n) {
            if (n === 0) return '';
            if (n < 20) return units[n] + ' ';
            if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + units[n % 10] : '') + ' ';
            if (n < 1000) return units[Math.floor(n / 100)] + ' Hundred ' + convert(n % 100);
            if (n < 100000) return convert(Math.floor(n / 1000)) + 'Thousand ' + convert(n % 1000);
            if (n < 10000000) return convert(Math.floor(n / 100000)) + 'Lakh ' + convert(n % 100000);
            return convert(Math.floor(n / 10000000)) + 'Crore ' + convert(n % 10000000);
        }

        return convert(Math.floor(num)).trim() + ' Sri Lankan Rupees';
    }

    // ── Modal 1: New Fund Allocation ──────────────────────────
    const allocModal             = $('fundAllocationModal');
    const btnOpenAlloc           = $('btnOpenCreateModal');
    const btnCloseAlloc          = $('btnCloseAllocModal');
    const btnCancelAlloc         = $('btnCancelAllocModal');
    const allocForm              = $('fundAllocationForm');
    const allocErrorAlert        = $('allocErrorAlert');
    const allocAmountInp         = $('alloc_amount');
    const allocWordsDiv          = $('alloc_amount_words');
    const methodInput            = $('selectedMethodInput');
    const btnMethodRTGS          = $('btnMethodRTGS');
    const btnMethodCheque        = $('btnMethodCheque');
    const allocRefInput          = $('alloc_reference');
    const allocZoneSelect        = $('alloc_zone_id');
    const recipientAccountBox    = $('recipientAccountBox');
    const recipientEmptyState    = $('recipientEmptyState');
    const recipientLoadedState   = $('recipientLoadedState');
    const recipientWarningState  = $('recipientWarningState');
    const recipientBankName      = $('recipientBankName');
    const recipientBranchName    = $('recipientBranchName');
    const recipientAccountMasked = $('recipientAccountMasked');
    const recipientAccountLabel  = $('recipientAccountLabel');
    const recipientIdInput       = $('alloc_recipient_bank_account_id');
    const btnSubmitAlloc         = $('btnSubmitAlloc');

    // Dynamic Recipient Bank Account Card updates
    function updateRecipientCard(zoneId) {
        if (!recipientAccountBox) return;

        if (!zoneId) {
            if (recipientEmptyState) recipientEmptyState.style.display = 'flex';
            if (recipientLoadedState) recipientLoadedState.style.display = 'none';
            if (recipientWarningState) recipientWarningState.style.display = 'none';
            recipientAccountBox.className = 'ft-bank-box ft-bank-box-recipient ft-state-empty';
            if (recipientIdInput) recipientIdInput.value = '';
            if (btnSubmitAlloc) {
                btnSubmitAlloc.disabled = true;
                btnSubmitAlloc.title = 'Please select a target zone with a verified bank account.';
            }
            return;
        }

        const applyVerifiedAccount = acc => {
            if (recipientEmptyState) recipientEmptyState.style.display = 'none';
            if (recipientWarningState) recipientWarningState.style.display = 'none';
            if (recipientLoadedState) recipientLoadedState.style.display = 'flex';
            recipientAccountBox.className = 'ft-bank-box ft-bank-box-recipient ft-state-verified';

            if (recipientBankName) {
                recipientBankName.innerHTML = (acc.bank_name || 'Bank') + ' <span class="ft-bank-sub" id="recipientBranchName">(' + (acc.branch_name || 'Branch') + ')</span>';
            }
            if (recipientAccountMasked) {
                recipientAccountMasked.textContent = acc.masked_account || (acc.account_number ? ('****-' + acc.account_number.slice(-4)) : '—');
            }
            if (recipientAccountLabel) {
                recipientAccountLabel.textContent = acc.account_label || acc.account_name || 'Zonal Treasury Fund';
            }
            if (recipientIdInput) {
                recipientIdInput.value = acc.bank_account_id;
            }
            if (btnSubmitAlloc) {
                btnSubmitAlloc.disabled = false;
                btnSubmitAlloc.title = '';
            }
        };

        const applyWarningNoAccount = () => {
            if (recipientEmptyState) recipientEmptyState.style.display = 'none';
            if (recipientLoadedState) recipientLoadedState.style.display = 'none';
            if (recipientWarningState) recipientWarningState.style.display = 'flex';
            recipientAccountBox.className = 'ft-bank-box ft-bank-box-recipient ft-state-warning';

            if (recipientIdInput) recipientIdInput.value = '';
            if (btnSubmitAlloc) {
                btnSubmitAlloc.disabled = true;
                btnSubmitAlloc.title = 'Cannot transfer: No verified bank account registered for selected zone.';
            }
        };

        // 1. Look up in preloaded config.zonalAccounts
        const acc = (config.zonalAccounts && config.zonalAccounts[zoneId]) ? config.zonalAccounts[zoneId] : null;

        if (acc) {
            if (acc.verification_status === 'Verified' || acc.is_verified == 1) {
                applyVerifiedAccount(acc);
            } else {
                applyWarningNoAccount();
            }
            return;
        }

        // 2. Fallback check via AJAX endpoint if not found in preloaded map
        fetch(`${config.rootUrl}/${route}/getzonalaccount?zone_id=${encodeURIComponent(zoneId)}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.account && (data.account.verification_status === 'Verified' || data.account.is_verified == 1)) {
                applyVerifiedAccount(data.account);
            } else {
                applyWarningNoAccount();
            }
        })
        .catch(() => {
            applyWarningNoAccount();
        });
    }

    if (allocZoneSelect) {
        allocZoneSelect.addEventListener('change', function () {
            updateRecipientCard(this.value);
        });
    }

    function openAllocModal() {
        if (!allocModal) return;
        allocModal.hidden = false;
        allocModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (allocErrorAlert) allocErrorAlert.hidden = true;
        if (allocZoneSelect) updateRecipientCard(allocZoneSelect.value);
        setTimeout(() => {
            const first = allocForm.querySelector('select, input');
            if (first) first.focus();
        }, 80);
    }

    function closeAllocModal() {
        if (!allocModal) return;
        allocModal.hidden = true;
        allocModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (btnOpenAlloc)   btnOpenAlloc.addEventListener('click', openAllocModal);
    if (btnCloseAlloc)  btnCloseAlloc.addEventListener('click', closeAllocModal);
    if (btnCancelAlloc) btnCancelAlloc.addEventListener('click', closeAllocModal);

    if (allocModal) {
        allocModal.addEventListener('click', e => {
            if (e.target === allocModal) closeAllocModal();
        });
    }

    // Method selection toggles
    function setMethod(method) {
        if (!methodInput) return;
        methodInput.value = method;

        if (btnMethodRTGS && btnMethodCheque) {
            if (method === 'RTGS') {
                btnMethodRTGS.classList.add('ft-method-selected');
                btnMethodCheque.classList.remove('ft-method-selected');
            } else {
                btnMethodCheque.classList.add('ft-method-selected');
                btnMethodRTGS.classList.remove('ft-method-selected');
            }
        }

        // Fetch sequential reference for selected method
        fetch(`${config.rootUrl}/${route}/getreference?method=${encodeURIComponent(method)}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.reference && allocRefInput) {
                allocRefInput.value = data.reference;
            }
        })
        .catch(() => {});
    }

    if (btnMethodRTGS) {
        btnMethodRTGS.addEventListener('click', () => setMethod('RTGS'));
    }
    if (btnMethodCheque) {
        btnMethodCheque.addEventListener('click', () => setMethod('ChequeSLIPS'));
    }

    // Amount input formatting & word generator
    if (allocAmountInp && allocWordsDiv) {
        allocAmountInp.addEventListener('input', function () {
            const clean = this.value.replace(/,/g, '').replace(/[^\d.]/g, '');
            const parsed = parseFloat(clean);
            if (!isNaN(parsed) && parsed > 0) {
                allocWordsDiv.textContent = numberToWords(parsed);
            } else {
                allocWordsDiv.textContent = 'Enter disbursement sum in Sri Lankan Rupees';
            }
        });
    }

    // Form Submission (AJAX with fallback)
    if (allocForm) {
        allocForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Strict client-side guard: recipient account must be verified and set
            if (!recipientIdInput || !recipientIdInput.value) {
                if (allocErrorAlert) {
                    allocErrorAlert.textContent = 'Cannot authorize transfer: Selected zone does not have a verified receiving bank account registered. Use Manage Bank Accounts to register one first.';
                    allocErrorAlert.hidden = false;
                }
                return;
            }

            const btnSubmit = $('btnSubmitAlloc');
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.textContent = 'Authorizing Transfer...';
            }
            if (allocErrorAlert) allocErrorAlert.hidden = true;

            try {
                const formData = new FormData(allocForm);
                const response = await fetch(allocForm.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    // Success! Refresh page to update table, ledger balances, and cards
                    window.location.href = data.redirect || `${config.rootUrl}/${listRoute}`;
                } else {
                    if (allocErrorAlert) {
                        const msgs = data.errors
                            ? Object.values(data.errors).join(' ')
                            : (data.error || 'An error occurred. Please verify form details.');
                        allocErrorAlert.textContent = msgs;
                        allocErrorAlert.hidden = false;
                    }
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        btnSubmit.textContent = 'Authorize Transfer';
                    }
                }
            } catch (err) {
                if (allocErrorAlert) {
                    allocErrorAlert.textContent = 'Network or server error. Please try again.';
                    allocErrorAlert.hidden = false;
                }
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Authorize Transfer';
                }
            }
        });
    }

    // ── Modal 2: Transaction Details ──────────────────────────
    const dtModal      = $('transferDetailsModal');
    const btnCloseDt   = $('btnCloseDetailsModal');
    const btnCloseDt2  = $('btnCloseDetailsModal2');
    const btnReceiptDl = $('btnReceiptDownload');

    function openDetailsModal(data) {
        if (!dtModal) return;

        const setT = (id, text) => {
            const el = $(id);
            if (el) el.textContent = text || '—';
        };

        setT('dtModalRef', data.ref);
        setT('dtModalRef2', data.ref);
        setT('dtModalSubtitle', 'Fund disbursement for ' + (data.zone || 'Zonal Office'));
        setT('dtModalAmount', 'LKR ' + data.amount);
        setT('dtModalDatetime', data.datetime || '—');
        setT('dtModalZone', data.zone);
        setT('dtModalSenderBank', data.senderBank ? (data.senderBank + (data.senderBranch ? ' (' + data.senderBranch + ')' : '')) : 'Bank of Ceylon (Colombo Fort 001)');
        setT('dtModalSenderAccount', data.senderAccount || '8620-0012-3456-7890');
        setT('dtModalSenderLabel', data.senderLabel || 'NYSC Colombo Operational Fund');
        setT('dtModalRecipientBank', data.recipientBank ? (data.recipientBank + (data.recipientBranch ? ' (' + data.recipientBranch + ')' : '')) : (data.bank ? (data.bank + (data.branch ? ' (' + data.branch + ')' : '')) : '—'));
        setT('dtModalRecipientAccount', data.recipientAccount || data.account || '—');
        setT('dtModalRecipientLabel', data.recipientLabel || 'Zonal Treasury Fund');
        setT('dtModalBank', data.recipientBank || data.bank);
        setT('dtModalBranch', data.recipientBranch || data.branch);
        setT('dtModalAccount', data.recipientAccount || data.account);
        setT('dtModalMethod', data.method);
        setT('dtModalDate', data.date);
        setT('dtModalAuthorized', data.authorized);
        setT('dtModalPurpose', data.purpose);

        // Status chip
        const chip = $('dtModalStatusChip');
        if (chip) {
            chip.textContent = data.status;
            chip.className = 'ft-status-chip';
            if (data.status === 'Completed') {
                chip.classList.add('ft-status-completed');
            } else {
                chip.classList.add('ft-status-processing');
            }
        }

        // Receipt Download Link
        if (btnReceiptDl) {
            btnReceiptDl.href = `${config.rootUrl}/${route}/receipt/${data.id}`;
        }

        dtModal.hidden = false;
        dtModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeDetailsModal() {
        if (!dtModal) return;
        dtModal.hidden = true;
        dtModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (btnCloseDt)  btnCloseDt.addEventListener('click', closeDetailsModal);
    if (btnCloseDt2) btnCloseDt2.addEventListener('click', closeDetailsModal);

    if (dtModal) {
        dtModal.addEventListener('click', e => {
            if (e.target === dtModal) closeDetailsModal();
        });
    }

    // Attach View Details Click Listeners
    document.querySelectorAll('.ft-btn-view-details').forEach(btn => {
        btn.addEventListener('click', function () {
            openDetailsModal({
                id:               this.dataset.id,
                ref:              this.dataset.ref,
                status:           this.dataset.status,
                amount:           this.dataset.amount,
                datetime:         this.dataset.datetime,
                date:             this.dataset.date,
                zone:             this.dataset.zone,
                senderBank:       this.dataset.senderBank,
                senderBranch:     this.dataset.senderBranch,
                senderAccount:    this.dataset.senderAccount,
                senderLabel:      this.dataset.senderLabel,
                recipientBank:    this.dataset.recipientBank,
                recipientBranch:  this.dataset.recipientBranch,
                recipientAccount: this.dataset.recipientAccount,
                recipientLabel:   this.dataset.recipientLabel,
                bank:             this.dataset.bank,
                branch:           this.dataset.branch,
                account:          this.dataset.account,
                method:           this.dataset.method,
                authorized:       this.dataset.authorized,
                purpose:          this.dataset.purpose,
            });
        });
    });

    // ── Global Keydown (ESC to close any modal) ───────────────
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            if (allocModal && !allocModal.hidden) closeAllocModal();
            if (dtModal    && !dtModal.hidden)    closeDetailsModal();
        }
    });

    // ── Auto-submit filter on dropdown change ─────────────────
    ['ftFilterZone', 'ftFilterQuarter', 'ftFilterStatus'].forEach(id => {
        const el = $(id);
        if (el) {
            el.addEventListener('change', () => {
                const form = $('ftFilterForm');
                if (form) form.submit();
            });
        }
    });

})();
