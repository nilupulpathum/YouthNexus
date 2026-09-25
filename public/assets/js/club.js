/**
 * club.js — client behaviour for club roster pages (presentation-only demo).
 * Modal open/close + filter-panel toggle come from divisional-workflows.js
 * via [data-modal-open], [data-modal-close] and [data-filter-toggle].
 */
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('club-member-search');
    const body = document.getElementById('club-roster-body');
    const roleSel = document.getElementById('club-member-role');
    const statusSel = document.getElementById('club-member-status');
    const emptyState = document.querySelector('[data-empty-state]');

    const refreshEmpty = () => {
        if (!emptyState || !body) return;
        const visible = Array.from(body.querySelectorAll('tr'))
            .filter((r) => r.style.display !== 'none').length;
        emptyState.classList.toggle('is-visible', visible === 0);
    };

    const applyRosterFilters = () => {
        if (!input || !body) return;
        const q = input.value.trim().toLowerCase();
        const r = roleSel ? roleSel.value.toLowerCase() : '';
        const s = statusSel ? statusSel.value : '';
        body.querySelectorAll('tr').forEach((row) => {
            const okQ = !q || (row.getAttribute('data-search') || '').includes(q);
            const okR = !r || (row.getAttribute('data-role') || '').toLowerCase() === r;
            const okS = !s || (row.getAttribute('data-status') || '') === s;
            row.style.display = okQ && okR && okS ? '' : 'none';
        });
        refreshEmpty();
    };

    if (input && body) {
        input.addEventListener('input', applyRosterFilters);
        if (roleSel) roleSel.addEventListener('change', applyRosterFilters);
        if (statusSel) statusSel.addEventListener('change', applyRosterFilters);
        document.querySelector('[data-roster-apply]')?.addEventListener('click', applyRosterFilters);
        document.querySelector('[data-roster-reset]')?.addEventListener('click', () => {
            if (roleSel) roleSel.value = '';
            if (statusSel) statusSel.value = '';
            if (input) input.value = '';
            applyRosterFilters();
        });
    }

    const toast = document.getElementById('roster-toast');
    let toastTimer = null;
    const showToast = (msg) => {
        if (!toast) return;
        toast.textContent = msg;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    };

    const closeModal = (modal) => {
        const btn = modal?.querySelector('.dw-modal__dialog [data-modal-close]');
        if (btn) btn.click();
        else if (modal) modal.hidden = true;
    };

    const pillFor = (statusKey, text) => {
        const pill = document.createElement('span');
        pill.className = `dw-status dw-status--${statusKey}`;
        pill.textContent = text;
        return pill;
    };

    // Assign-role modal (president) — fills the real POST form.
    const modal = document.getElementById('assign-modal');
    if (modal && body) {
        const memberEl = document.getElementById('assign-member');
        const currentEl = document.getElementById('assign-current');
        const assignRoleSel = document.getElementById('assign-role');
        const warning = document.getElementById('assign-warning');
        const memberIdInput = document.getElementById('assign-member-id');

        const roleLabel = (value) => value.replace(/^Club/, '') === 'Member' ? 'Member' : value.replace(/^Club/, '');
        const occupantOf = (label) => {
            const rows = Array.from(body.querySelectorAll('tr'));
            const hit = rows.find((r) => (r.getAttribute('data-role') || '').toLowerCase() === label.toLowerCase());
            return hit ? hit.getAttribute('data-name') : '';
        };

        const refreshWarning = () => {
            const label = roleLabel(assignRoleSel.value);
            const holder = occupantOf(label);
            if (holder) {
                warning.textContent = `${label} is held by ${holder} — confirming revokes them to General Member first.`;
                warning.hidden = false;
            } else {
                warning.hidden = true;
            }
        };

        const bindAssign = (btn) => btn.addEventListener('click', () => {
            const targetRow = btn.closest('tr');
            memberEl.textContent = targetRow.getAttribute('data-name') || '';
            currentEl.textContent = targetRow.getAttribute('data-role') || '';
            if (memberIdInput) memberIdInput.value = targetRow.getAttribute('data-id') || '';
            refreshWarning();
        });
        body.querySelectorAll('[data-action="assign"]').forEach(bindAssign);

        assignRoleSel.addEventListener('change', refreshWarning);

        // Member review modal (president) — fills the real POST form.
        const mrModal = document.getElementById('member-review-modal');
        if (mrModal) {
            const mrTitle = document.getElementById('mr-title');
            const mrDetails = document.getElementById('mr-details');
            const mrResult = document.getElementById('mr-result');
            const mrRemarks = document.getElementById('mr-remarks');
            const mrErr = document.getElementById('mr-error');
            const mrMemberId = document.getElementById('mr-member-id');
            const mrForm = document.getElementById('member-review-form');
            const MR_FIELDS = [
                ['Email', 'email'], ['Phone', 'phone'], ['Address', 'address'],
                ['NIC', 'nic'], ['Role', 'role'], ['Joined', 'joined'],
            ];

            body.querySelectorAll('[data-action="member-review"]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const reviewRow = btn.closest('tr');
                    if (mrMemberId) mrMemberId.value = reviewRow.getAttribute('data-id') || '';
                    mrTitle.textContent = reviewRow.getAttribute('data-name') || 'Review member';
                    mrDetails.textContent = '';
                    MR_FIELDS.forEach(([label, key]) => {
                        const row = document.createElement('div');
                        row.className = 'dw-metric';
                        const lab = document.createElement('span');
                        lab.textContent = label;
                        const val = document.createElement('strong');
                        val.textContent = reviewRow.getAttribute(`data-${key}`) || '—';
                        row.appendChild(lab);
                        row.appendChild(val);
                        mrDetails.appendChild(row);
                    });
                    mrResult.value = 'approve';
                    mrRemarks.value = '';
                    mrErr.hidden = true;
                });
            });

            if (mrForm) {
                mrForm.addEventListener('submit', (e) => {
                    if (mrResult.value === 'reject' && !mrRemarks.value.trim()) {
                        e.preventDefault();
                        mrErr.textContent = 'Please add a note explaining the rejection.';
                        mrErr.hidden = false;
                        mrRemarks.focus();
                    }
                });
            }
        }
    }

    // Register-member modal (secretary) — client checks, then real POST.
    const regModal = document.getElementById('club-register-modal');
    const form = document.getElementById('club-register-form');
    if (regModal && form && body) {
        const err = document.getElementById('reg-error');
        let existingNics = [];
        try {
            existingNics = JSON.parse(form.getAttribute('data-existing-nics') || '[]');
        } catch {
            existingNics = [];
        }
        form.addEventListener('submit', (e) => {
            const name = document.getElementById('reg-name').value.trim();
            const nic = document.getElementById('reg-nic').value.trim();
            const email = document.getElementById('reg-email').value.trim();
            const phone = document.getElementById('reg-phone').value.trim();
            const address = document.getElementById('reg-address').value.trim();
            const fail = (m) => { e.preventDefault(); err.textContent = m; err.hidden = false; };
            err.hidden = true;
            if (!name || !nic || !email || !phone || !address) return fail('All fields are required.');
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return fail('Enter a valid email address.');
            if (existingNics.includes(nic)) return fail('This NIC is already registered — back to the form.');
            const dupEmail = Array.from(body.querySelectorAll('tr'))
                .some((r) => (r.getAttribute('data-email') || '').toLowerCase() === email.toLowerCase());
            if (dupEmail) return fail('This email is already registered — back to the form.');
        });
    }
});

/* ---- club/events ---- */
if (document.getElementById('club-event-search')) {

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('club-event-list');
    const search = document.getElementById('club-event-search');
    const statusSel = document.getElementById('club-event-status');
    const typeSel = document.getElementById('club-event-type');
    const empty = document.getElementById('club-event-empty');

    const applyFilters = () => {
        if (!list) return;
        const q = (search ? search.value.trim().toLowerCase() : '');
        const s = statusSel ? statusSel.value : '';
        const t = typeSel ? typeSel.value.toLowerCase() : '';
        let visible = 0;
        list.querySelectorAll('[data-event-card]').forEach(card => {
            const okQ = !q || (card.getAttribute('data-search') || '').includes(q);
            const okS = !s || (card.getAttribute('data-status') || '') === s;
            const okT = !t || (card.getAttribute('data-type') || '') === t;
            const show = okQ && okS && okT;
            card.style.display = show ? '' : 'none';
            if (show) visible += 1;
        });
        if (empty) empty.hidden = visible !== 0;
    };
    [search, statusSel, typeSel].forEach(el => {
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

    // President decision modal (mirrors eventapproval: remarks mandatory on request-changes).
    const modal = document.getElementById('event-decision-modal');
    if (modal && list) {
        const titleEl = document.getElementById('ed-title');
        const metaEl = document.getElementById('ed-meta');
        const submitterEl = document.getElementById('ed-submitter');
        const resultSel = document.getElementById('ed-result');
        const remarks = document.getElementById('ed-remarks');
        const err = document.getElementById('ed-error');
        const impact = document.getElementById('ed-impact');
        const eventIdInput = document.getElementById('ed-event-id');
        const decisionForm = document.getElementById('event-decision-form');
        let target = null;

        const refreshImpact = () => {
            const changes = resultSel.value === 'request-changes';
            impact.classList.toggle('is-reject', changes);
            impact.querySelector('strong').textContent = changes ? 'Impact of requesting changes' : 'Impact of approval';
            impact.querySelector('p').textContent = changes
                ? 'Requesting changes notifies the secretary with your official remarks. The event stays unapproved and off the club calendar.'
                : 'Approving publishes this event to the club calendar and notifies the secretary. It becomes visible for attendance tracking once it occurs.';
        };
        resultSel.addEventListener('change', () => { err.hidden = true; refreshImpact(); });

        const close = () => { modal.hidden = true; modal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
        modal.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', close));
        modal.addEventListener('click', (e) => { if (e.target === modal || e.target.classList.contains('dw-modal__backdrop')) close(); });

        list.querySelectorAll('[data-action="review"]').forEach(btn => {
            btn.addEventListener('click', () => {
                target = btn.closest('[data-event-card]');
                if (eventIdInput) eventIdInput.value = target.getAttribute('data-id') || '';
                titleEl.textContent = target.getAttribute('data-title') || 'Review event';
                metaEl.textContent = target.getAttribute('data-meta') || '';
                const by = target.getAttribute('data-submitter') || '';
                submitterEl.textContent = by ? ('Submitted by ' + by) : '';
                resultSel.value = 'approve';
                remarks.value = '';
                err.hidden = true;
                refreshImpact();
                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            });
        });

        if (decisionForm) {
            decisionForm.addEventListener('submit', (e) => {
                if (resultSel.value === 'request-changes' && !remarks.value.trim()) {
                    e.preventDefault();
                    err.textContent = 'Please provide remarks explaining the requested changes.';
                    err.hidden = false;
                    remarks.focus();
                }
            });
        }
    }

    // Secretary create-event modal (per owner mock: error banner + red
    // date state when the selected date/time has already passed).
    const openBtn = document.getElementById('club-event-open');
    const evModal = document.getElementById('club-event-modal');
    const form = document.getElementById('club-event-form');
    if (openBtn && evModal && form && list) {
        const banner = document.getElementById('ev-banner');
        const err = document.getElementById('ev-error');
        const dateInput = document.getElementById('ev-date');
        const timeInput = document.getElementById('ev-time');
        const dateErr = document.getElementById('ev-date-error');

        const clearDateError = () => {
            banner.hidden = true;
            dateErr.hidden = true;
            dateInput.classList.remove('is-invalid');
        };

        const open = () => {
            form.reset();
            err.hidden = true;
            clearDateError();
            evModal.hidden = false;
            evModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };
        const close = () => { evModal.hidden = true; evModal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
        openBtn.addEventListener('click', open);
        evModal.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', close));
        evModal.addEventListener('click', (e) => { if (e.target === evModal || e.target.classList.contains('dw-modal__backdrop')) close(); });
        dateInput.addEventListener('input', clearDateError);
        timeInput.addEventListener('input', clearDateError);

        form.addEventListener('submit', (e) => {
            const title = document.getElementById('ev-title').value.trim();
            const dateVal = dateInput.value;
            const timeVal = timeInput.value;
            const location = document.getElementById('ev-location').value.trim();
            const type = document.getElementById('ev-type').value;
            const fail = (m) => { e.preventDefault(); err.textContent = m; err.hidden = false; };
            err.hidden = true;
            if (!title || !dateVal || !timeVal || !location || !type) return fail('All fields are required.');
            const dt = new Date(dateVal + 'T' + timeVal);
            if (isNaN(dt.getTime())) return fail('Enter a valid date and time.');
            if (dt <= new Date()) {
                e.preventDefault();
                banner.hidden = false;
                dateErr.hidden = false;
                dateInput.classList.add('is-invalid');
                dateInput.focus();
            }
        });
    }
    // Mark-complete + evidence modal (secretary) — fills the real POST form.
    const cpModal = document.getElementById('club-complete-modal');
    if (cpModal && list) {
        const metaEl = document.getElementById('cp-meta');
        const sheet = document.getElementById('cp-sheet');
        const err = document.getElementById('cp-error');
        const eventIdInput = document.getElementById('cp-event-id');
        const completeForm = document.getElementById('club-complete-form');

        list.querySelectorAll('[data-action="complete"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.closest('[data-event-card]');
                if (eventIdInput) eventIdInput.value = target.getAttribute('data-id') || '';
                metaEl.textContent = target.getAttribute('data-title') || '';
                sheet.value = '';
                err.hidden = true;
            });
        });

        if (completeForm) {
            completeForm.addEventListener('submit', (e) => {
                if (!sheet.files.length) {
                    e.preventDefault();
                    err.textContent = 'Attach the attendance sheet — evidence is required to complete an event.';
                    err.hidden = false;
                    sheet.focus();
                }
            });
        }
    }
});

}

/* ---- club/attendance ---- */
if (document.getElementById('att-event')) {

document.addEventListener('DOMContentLoaded', () => {
    // Secretary marking UI — rosters are server-rendered per event;
    // forms POST to club/saveAttendance natively after client checks.
    const eventSel = document.getElementById('att-event');
    const singleEvent = document.getElementById('att-single-event');
    const bulkEvent = document.getElementById('att-bulk-event');
    if (eventSel) {
        eventSel.addEventListener('change', () => {
            const id = eventSel.value;
            if (singleEvent) singleEvent.value = id;
            if (bulkEvent) bulkEvent.value = id;
            document.querySelectorAll('[data-attendance-panel]').forEach((panel) => {
                panel.hidden = panel.getAttribute('data-attendance-panel') !== id;
            });
        });
    }

    // Tabs.
    const tabSingle = document.getElementById('tab-single');
    const tabBulk = document.getElementById('tab-bulk');
    const paneSingle = document.getElementById('pane-single');
    const paneBulk = document.getElementById('pane-bulk');
    if (tabSingle && tabBulk) {
        const selectTab = (single) => {
            tabSingle.classList.toggle('is-active', single);
            tabBulk.classList.toggle('is-active', !single);
            tabSingle.setAttribute('aria-selected', single ? 'true' : 'false');
            tabBulk.setAttribute('aria-selected', single ? 'false' : 'true');
            paneSingle.hidden = !single;
            paneBulk.hidden = single;
        };
        tabSingle.addEventListener('click', () => selectTab(true));
        tabBulk.addEventListener('click', () => selectTab(false));
    }

    // Time-range check shared by both forms (empty times allowed).
    const checkRange = (inEl, outEl, errEl) => {
        errEl.hidden = true;
        if (inEl.value && outEl.value && outEl.value <= inEl.value) {
            errEl.textContent = 'End time must be after start time.';
            errEl.hidden = false;
            return false;
        }
        return true;
    };

    // Single entry.
    const singleForm = document.getElementById('att-single-form');
    if (singleForm) {
        singleForm.addEventListener('submit', (e) => {
            const err = document.getElementById('att-session-error');
            if (!checkRange(document.getElementById('att-in'), document.getElementById('att-out'), err)) {
                e.preventDefault();
            }
        });
    }

    // Bulk CSV.
    const bulkForm = document.getElementById('att-bulk-form');
    if (bulkForm) {
        const csv = document.getElementById('att-csv');
        const csvName = document.getElementById('att-csv-name');
        const bulkErr = document.getElementById('att-bulk-error');
        csv.addEventListener('change', () => {
            if (csv.files.length) {
                csvName.textContent = 'Selected: ' + csv.files[0].name;
                csvName.hidden = false;
            } else {
                csvName.hidden = true;
            }
        });
        bulkForm.addEventListener('submit', (e) => {
            bulkErr.hidden = true;
            if (!csv.files.length) {
                e.preventDefault();
                bulkErr.textContent = 'Choose a CSV file first.';
                bulkErr.hidden = false;
                return;
            }
            if (!checkRange(document.getElementById('att-bulk-in'), document.getElementById('att-bulk-out'), bulkErr)) {
                e.preventDefault();
            }
        });
    }
});

}

/* ---- club/assets ---- */
if (document.getElementById('club-asset-search')) {

document.addEventListener('DOMContentLoaded', () => {
    const body = document.getElementById('club-asset-body');
    const search = document.getElementById('club-asset-search');
    const categorySel = document.getElementById('club-asset-category');
    const statusSel = document.getElementById('club-asset-status');
    const empty = document.querySelector('[data-empty-state]');

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
        if (empty) empty.classList.toggle('is-visible', visible === 0);
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

    // Register-asset modal (secretary) — validates, then real POST.
    const assetModal = document.getElementById('club-asset-modal');
    const form = document.getElementById('club-asset-form');
    if (assetModal && form && body) {
        const err = document.getElementById('asset-error');

        form.addEventListener('submit', (e) => {
            const item = document.getElementById('asset-item').value;
            const qtyVal = document.getElementById('asset-qty').value.trim();
            const fail = (m) => { e.preventDefault(); err.textContent = m; err.hidden = false; };
            err.hidden = true;
            if (!item || !qtyVal) return fail('Select an item and enter a quantity.');
            const qty = Number(qtyVal);
            if (!isFinite(qty) || qty < 1) return fail('Quantity must be at least 1.');
        });
    }

    // Transfer-custody modal (treasurer) — fills the real POST form.
    const transferModal = document.getElementById('club-transfer-modal');
    if (transferModal && body) {
        const assetEl = document.getElementById('transfer-asset');
        const dateInput = document.getElementById('transfer-date');
        const note = document.getElementById('transfer-note');
        const err = document.getElementById('transfer-error');
        const itemIdInput = document.getElementById('transfer-item-id');
        const transferForm = document.getElementById('club-transfer-form');

        body.querySelectorAll('[data-action="transfer"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.closest('tr');
                if (itemIdInput) itemIdInput.value = target.getAttribute('data-id') || '';
                assetEl.textContent = target.getAttribute('data-name') || '';
                note.value = '';
                dateInput.value = '';
                err.hidden = true;
            });
        });

        if (transferForm) {
            transferForm.addEventListener('submit', (e) => {
                if (!dateInput.value) {
                    e.preventDefault();
                    err.textContent = 'Choose the transfer date.';
                    err.hidden = false;
                    dateInput.focus();
                    return;
                }
                if (!note.value.trim()) {
                    e.preventDefault();
                    err.textContent = 'A history note is required — it is logged with the transfer.';
                    err.hidden = false;
                    note.focus();
                }
            });
        }
    }

    // Request-from-Division flow (treasurer) — validates, then real POST.
    const reqForm = document.getElementById('club-request-form');
    if (reqForm) {
        const err = document.getElementById('req-error');

        reqForm.addEventListener('submit', (e) => {
            const item = document.getElementById('req-item').value;
            const qtyVal = document.getElementById('req-qty').value.trim();
            const just = document.getElementById('req-just').value.trim();
            const fail = (m) => { e.preventDefault(); err.textContent = m; err.hidden = false; };
            err.hidden = true;
            if (!item || !qtyVal || !just) return fail('Item, quantity and reason are required.');
            const qty = Number(qtyVal);
            if (!isFinite(qty) || qty < 1) return fail('Quantity must be at least 1.');
        });
    }
});

}

/* ---- club/ledger ---- */
if (document.getElementById('club-ledger-body')) {

document.addEventListener('DOMContentLoaded', () => {
    const body = document.getElementById('club-ledger-body');
    const search = document.getElementById('club-ledger-search');
    const typeSel = document.getElementById('club-ledger-type');
    const statusSel = document.getElementById('club-ledger-status');
    const empty = document.querySelector('[data-empty-state]');

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
        if (empty) empty.classList.toggle('is-visible', visible === 0);
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
        s.className = 'dw-status dw-status--' + kind;
        s.textContent = label;
        return s;
    };

    // Log-transaction modal (treasurer) — validates, then real POST.
    const logModal = document.getElementById('club-log-modal');
    const form = document.getElementById('club-log-form');
    if (logModal && form && body) {
        const banner = document.getElementById('log-banner');
        const err = document.getElementById('log-error');
        const receipt = document.getElementById('log-receipt');
        const fileName = document.getElementById('log-file-name');

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
            const amountVal = document.getElementById('log-amount').value.trim();
            const dateVal = document.getElementById('log-date').value;
            const desc = document.getElementById('log-desc').value.trim();
            const fail = (m) => { e.preventDefault(); err.textContent = m; err.hidden = false; };
            err.hidden = true;
            if (!amountVal || !dateVal || !desc) return fail('All fields are required.');
            const amount = Number(amountVal);
            if (!isFinite(amount) || amount <= 0) return fail('Amount must be a positive number.');
            if (!receipt.files.length) {
                e.preventDefault();
                banner.hidden = false;
                receipt.focus();
            }
        });
    }

    // Request-void modal (treasurer) — fills the real POST form.
    const voidModal = document.getElementById('club-void-modal');
    if (voidModal && body) {
        const descEl = document.getElementById('void-desc');
        const reason = document.getElementById('void-reason');
        const err = document.getElementById('void-error');
        const entryIdInput = document.getElementById('void-entry-id');
        const voidForm = document.getElementById('club-void-form');

        body.querySelectorAll('[data-action="void"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.closest('tr');
                if (entryIdInput) entryIdInput.value = target.getAttribute('data-id') || '';
                descEl.textContent = target.getAttribute('data-desc') || '';
                reason.value = '';
                err.hidden = true;
            });
        });

        if (voidForm) {
            voidForm.addEventListener('submit', (e) => {
                if (!reason.value.trim()) {
                    e.preventDefault();
                    err.textContent = 'Please give a reason — the Divisional Treasurer needs it to decide.';
                    err.hidden = false;
                    reason.focus();
                }
            });
        }
    }
});

}

/* ---- president ---- */
if (document.getElementById('president-members-empty')) {

document.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('club-toast');
    let toastTimer = null;
    const showToast = (msg) => {
        if (!toast) return;
        toast.textContent = msg;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    };

    // Member review modal (president: full details, approve or reject with note).
    const list = document.getElementById('president-members-list');
    const modal = document.getElementById('member-review-modal');
    if (list && modal) {
        const titleEl = document.getElementById('mr-title');
        const detailsEl = document.getElementById('mr-details');
        const resultSel = document.getElementById('mr-result');
        const remarks = document.getElementById('mr-remarks');
        const err = document.getElementById('mr-error');
        const emptyNote = document.getElementById('president-members-empty');
        const heading = document.getElementById('president-members-heading');
        const confirmBtn = document.getElementById('mr-confirm');
        const FIELDS = [
            ['Email', 'email'], ['Phone', 'phone'], ['Address', 'address'],
            ['NIC', 'nic'], ['Joined', 'joined'], ['Registered by', 'registeredBy'],
        ];
        let target = null;

        const close = () => { modal.hidden = true; modal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; document.body.classList.remove('dw-modal-open'); };
        modal.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', close));
        modal.addEventListener('click', (e) => { if (e.target.classList.contains('dw-modal__backdrop')) close(); });

        list.querySelectorAll('[data-action="member-review"]').forEach(btn => {
            btn.addEventListener('click', () => {
                target = btn.closest('.dw-record-card');
                titleEl.textContent = target.getAttribute('data-name') || 'Review member';
                detailsEl.textContent = '';
                FIELDS.forEach(([label, key]) => {
                    const row = document.createElement('div');
                    row.className = 'dw-metric';
                    const lab = document.createElement('span');
                    lab.textContent = label;
                    const val = document.createElement('strong');
                    val.textContent = target.getAttribute('data-' + key) || '—';
                    row.appendChild(lab);
                    row.appendChild(val);
                    detailsEl.appendChild(row);
                });
                resultSel.value = 'approve';
                remarks.value = '';
                err.hidden = true;
                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('dw-modal-open');
                document.body.style.overflow = 'hidden';
            });
        });

        confirmBtn.addEventListener('click', () => {
            const reject = resultSel.value === 'reject';
            if (reject && !remarks.value.trim()) {
                err.textContent = 'Please add a note explaining the rejection.';
                err.hidden = false;
                remarks.focus();
                return;
            }
            const who = target ? (target.getAttribute('data-name') || 'Member') : 'Member';
            if (target) target.remove();
            const remaining = list.querySelectorAll('.dw-record-card').length;
            if (heading) heading.textContent = 'Pending Member Approvals (' + remaining + ')';
            if (remaining === 0 && emptyNote) emptyNote.hidden = false;
            close();
            showToast(reject
                ? who + '’s application rejected with note (demo).'
                : who + ' approved as General Member (demo — persists in C13 backend).');
        });
    }
});

}

/* ---- president/handover ---- */
if (document.getElementById('handover-confirm-open')) {

document.addEventListener('DOMContentLoaded', () => {
    const REGISTRY = JSON.parse(document.getElementById('handover-confirm-open')?.getAttribute('data-member-registry') || '[]');

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
        const close = () => { modal.hidden = true; modal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; document.body.classList.remove('dw-modal-open'); };
        modal.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', close));
        modal.addEventListener('click', (e) => { if (e.target.classList.contains('dw-modal__backdrop')) close(); });

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
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('dw-modal-open');
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

}