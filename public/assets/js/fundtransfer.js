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
    const allocModal      = $('fundAllocationModal');
    const btnOpenAlloc    = $('btnOpenCreateModal');
    const btnCloseAlloc   = $('btnCloseAllocModal');
    const btnCancelAlloc  = $('btnCancelAllocModal');
    const allocForm       = $('fundAllocationForm');
    const allocErrorAlert = $('allocErrorAlert');
    const allocAmountInp  = $('alloc_amount');
    const allocWordsDiv   = $('alloc_amount_words');
    const methodInput     = $('selectedMethodInput');
    const btnMethodRTGS   = $('btnMethodRTGS');
    const btnMethodCheque = $('btnMethodCheque');
    const allocRefInput   = $('alloc_reference');

    function openAllocModal() {
        if (!allocModal) return;
        allocModal.hidden = false;
        document.body.style.overflow = 'hidden';
        if (allocErrorAlert) allocErrorAlert.hidden = true;
        setTimeout(() => {
            const first = allocForm.querySelector('select, input');
            if (first) first.focus();
        }, 80);
    }

    function closeAllocModal() {
        if (!allocModal) return;
        allocModal.hidden = true;
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
        fetch(`${config.rootUrl}/fundtransfer/getreference?method=${encodeURIComponent(method)}`, {
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

            const btnSubmit = $('btnSubmitAlloc');
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '&#8987; Authorizing Transfer…';
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
                    window.location.href = data.redirect || `${config.rootUrl}/fundtransfer`;
                } else {
                    if (allocErrorAlert) {
                        const msgs = data.errors
                            ? Object.values(data.errors).join('<br>')
                            : (data.error || 'An error occurred. Please verify form details.');
                        allocErrorAlert.innerHTML = msgs;
                        allocErrorAlert.hidden = false;
                    }
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '&#9654; Authorize Transfer';
                    }
                }
            } catch (err) {
                if (allocErrorAlert) {
                    allocErrorAlert.textContent = 'Network or server error. Please try again.';
                    allocErrorAlert.hidden = false;
                }
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '&#9654; Authorize Transfer';
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
        setT('dtModalDatetime', '🕐 ' + (data.datetime || '—'));
        setT('dtModalZone', data.zone);
        setT('dtModalBank', data.bank);
        setT('dtModalBranch', data.branch);
        setT('dtModalAccount', data.account);
        setT('dtModalMethod', data.method);
        setT('dtModalDate', data.date);
        setT('dtModalAuthorized', data.authorized);
        setT('dtModalPurpose', data.purpose);

        // Status chip
        const chip = $('dtModalStatusChip');
        if (chip) {
            chip.textContent = '● ' + data.status;
            chip.className = 'ft-status-chip';
            if (data.status === 'Completed') {
                chip.classList.add('ft-status-completed');
            } else {
                chip.classList.add('ft-status-processing');
            }
        }

        // Receipt Download Link
        if (btnReceiptDl) {
            btnReceiptDl.href = `${config.rootUrl}/fundtransfer/receipt/${data.id}`;
        }

        dtModal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeDetailsModal() {
        if (!dtModal) return;
        dtModal.hidden = true;
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
                id:         this.dataset.id,
                ref:        this.dataset.ref,
                status:     this.dataset.status,
                amount:     this.dataset.amount,
                datetime:   this.dataset.datetime,
                date:       this.dataset.date,
                zone:       this.dataset.zone,
                bank:       this.dataset.bank,
                branch:     this.dataset.branch,
                account:    this.dataset.account,
                method:     this.dataset.method,
                authorized: this.dataset.authorized,
                purpose:    this.dataset.purpose,
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
