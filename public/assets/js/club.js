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
        const confirmBtn = document.getElementById('ed-confirm');
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

        confirmBtn.addEventListener('click', () => {
            const changes = resultSel.value === 'request-changes';
            if (changes && !remarks.value.trim()) {
                err.textContent = 'Please provide remarks explaining the requested changes.';
                err.hidden = false;
                remarks.focus();
                return;
            }
            if (target) {
                const pill = target.querySelector('.dw-status');
                const btn = target.querySelector('[data-action="review"]');
                if (!changes) {
                    target.setAttribute('data-status', 'approved');
                    if (pill) { pill.textContent = 'Approved'; pill.className = 'dw-status dw-status--approved'; }
                    if (btn) btn.remove();
                    showToast('Event approved — published to the club calendar (demo).');
                } else {
                    showToast('Change request sent to the secretary (demo).');
                }
            }
            close();
            applyFilters();
        });
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
            e.preventDefault();
            const title = document.getElementById('ev-title').value.trim();
            const dateVal = dateInput.value;
            const timeVal = timeInput.value;
            const location = document.getElementById('ev-location').value.trim();
            const type = document.getElementById('ev-type').value;
            const budgetVal = document.getElementById('ev-budget').value.trim();
            const fail = (m) => { err.textContent = m; err.hidden = false; };
            err.hidden = true;
            if (!title || !dateVal || !timeVal || !location || !type || !budgetVal) return fail('All fields are required.');
            const dt = new Date(dateVal + 'T' + timeVal);
            if (isNaN(dt.getTime())) return fail('Enter a valid date and time.');
            if (dt <= new Date()) {
                banner.hidden = false;
                dateErr.hidden = false;
                dateInput.classList.add('is-invalid');
                dateInput.focus();
                return;
            }
            const budgetNum = Number(budgetVal);
            if (!isFinite(budgetNum) || budgetNum <= 0) return fail('Budget must be a positive amount.');
            const nice = dt.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' +
                dt.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit' });
            const card = document.createElement('article');
            card.className = 'dw-record-card';
            card.setAttribute('data-event-card', '');
            card.setAttribute('data-search', (title + ' ' + location).toLowerCase());
            card.setAttribute('data-status', 'pending');
            card.setAttribute('data-type', type.toLowerCase());
            card.setAttribute('data-title', title);
            const budgetText = 'Rs. ' + Math.round(budgetNum).toLocaleString('en-US');
            const header = document.createElement('div');
            header.className = 'dw-record-card__header';
            const identity = document.createElement('div');
            identity.className = 'dw-record-card__identity';
            const icon = document.createElement('span');
            icon.className = 'dw-record-card__icon';
            icon.setAttribute('aria-hidden', 'true');
            const firstIcon = list.querySelector('.dw-record-card__icon');
            if (firstIcon) icon.innerHTML = firstIcon.innerHTML;
            const idMeta = document.createElement('div');
            idMeta.className = 'dw-record-card__meta';
            const idSpan = document.createElement('span');
            idSpan.textContent = type;
            idMeta.appendChild(idSpan);
            identity.appendChild(icon);
            identity.appendChild(idMeta);
            const pill = document.createElement('span');
            pill.className = 'dw-status dw-status--pending';
            pill.textContent = 'Pending Approval';
            header.appendChild(identity);
            header.appendChild(pill);
            const cardTitle = document.createElement('h3');
            cardTitle.className = 'dw-record-card__title';
            cardTitle.textContent = title;
            const details = document.createElement('div');
            details.className = 'dw-record-card__details';
            const line = document.createElement('span');
            line.textContent = nice + ' · ' + location;
            const meta = document.createElement('span');
            meta.textContent = type + ' · Budget ' + budgetText + ' · Submitted by you';
            details.appendChild(line);
            details.appendChild(meta);
            card.appendChild(header);
            card.appendChild(cardTitle);
            card.appendChild(details);
            list.prepend(card);
            close();
            applyFilters();
            showToast(title + ' submitted for approval (demo — persists in C13 backend).');
        });
    }

    // Mark-complete + evidence modal (secretary records completion evidence).
    const cpModal = document.getElementById('club-complete-modal');
    if (cpModal && list) {
        const metaEl = document.getElementById('cp-meta');
        const sheet = document.getElementById('cp-sheet');
        const photos = document.getElementById('cp-photos');
        const filesNote = document.getElementById('cp-files');
        const err = document.getElementById('cp-error');
        const confirmBtn = document.getElementById('cp-confirm');
        let target = null;

        const closeCp = () => { cpModal.hidden = true; cpModal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
        cpModal.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', closeCp));
        cpModal.addEventListener('click', (e) => { if (e.target === cpModal || e.target.classList.contains('dw-modal__backdrop')) closeCp(); });

        const refreshFiles = () => {
            const names = [];
            if (sheet.files.length) names.push('Sheet: ' + sheet.files[0].name);
            if (photos.files.length) names.push(photos.files.length + ' photo' + (photos.files.length === 1 ? '' : 's'));
            if (names.length) {
                filesNote.textContent = names.join(' · ');
                filesNote.hidden = false;
            } else {
                filesNote.hidden = true;
            }
        };
        sheet.addEventListener('change', refreshFiles);
        photos.addEventListener('change', refreshFiles);

        list.querySelectorAll('[data-action="complete"]').forEach(btn => {
            btn.addEventListener('click', () => {
                target = btn.closest('[data-event-card]');
                metaEl.textContent = target.getAttribute('data-title') || '';
                sheet.value = '';
                photos.value = '';
                filesNote.hidden = true;
                err.hidden = true;
                cpModal.hidden = false;
                cpModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            });
        });

        confirmBtn.addEventListener('click', () => {
            if (!sheet.files.length) {
                err.textContent = 'Attach the attendance sheet — evidence is required to complete an event.';
                err.hidden = false;
                sheet.focus();
                return;
            }
            const who = target ? (target.getAttribute('data-title') || 'Event') : 'Event';
            if (target) {
                target.setAttribute('data-status', 'completed');
                const pill = target.querySelector('.dw-status');
                if (pill) { pill.textContent = 'Completed'; pill.className = 'dw-status dw-status--completed'; }
                const btn = target.querySelector('[data-action="complete"]');
                if (btn) btn.remove();
                const tiles = document.querySelectorAll('.dw-summary-grid .dw-summary-card__value');
                if (tiles.length >= 3) {
                    tiles[1].textContent = String(Math.max(0, (parseInt(tiles[1].textContent, 10) || 0) - 1));
                    tiles[2].textContent = String((parseInt(tiles[2].textContent, 10) || 0) + 1);
                }
            }
            closeCp();
            applyFilters();
            showToast(who + ' marked complete — evidence saved (demo).');
        });
    }
});

}

/* ---- club/attendance ---- */
if (document.getElementById('club-att-body')) {

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

    const DATA = JSON.parse(document.getElementById('club-att-body')?.getAttribute('data-attendance-events') || '[]');
    const pillFor = (key, label) => {
        const s = document.createElement('span');
        s.className = 'dw-status dw-status--' + key;
        s.textContent = label;
        return s;
    };

    // Secretary marking UI.
    const eventSel = document.getElementById('att-event');
    const body = document.getElementById('club-att-body');
    if (eventSel && body) {
        const rosterEvent = document.getElementById('att-roster-event');
        const memberSel = document.getElementById('att-member');
        let current = 0;

        const renderRoster = () => {
            const ev = DATA[current];
            if (rosterEvent && ev) rosterEvent.textContent = ev.title + ' — ' + ev.date;
            body.textContent = '';
            memberSel.textContent = '';
            (ev ? ev.roster : []).forEach(m => {
                const tr = document.createElement('tr');
                tr.setAttribute('data-name', m.name);
                const nameTd = document.createElement('td');
                const strong = document.createElement('strong');
                strong.textContent = m.name;
                nameTd.appendChild(strong);
                tr.appendChild(nameTd);
                const statusTd = document.createElement('td');
                statusTd.appendChild(pillFor(m.status_key, m.status));
                tr.appendChild(statusTd);
                const actionTd = document.createElement('td');
                const toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'dw-button dw-button--ghost';
                toggle.textContent = m.status_key === 'present' ? 'Mark absent' : 'Mark present';
                toggle.addEventListener('click', () => {
                    const nowPresent = statusTd.querySelector('.dw-status').className.includes('present');
                    statusTd.textContent = '';
                    statusTd.appendChild(pillFor(nowPresent ? 'absent' : 'present', nowPresent ? 'Absent' : 'Present'));
                    toggle.textContent = nowPresent ? 'Mark present' : 'Mark absent';
                    showToast(m.name + ' marked ' + (nowPresent ? 'Absent' : 'Present') + ' (demo).');
                });
                actionTd.appendChild(toggle);
                tr.appendChild(actionTd);
                body.appendChild(tr);

                const opt = document.createElement('option');
                opt.value = m.name;
                opt.textContent = m.name;
                memberSel.appendChild(opt);
            });
        };

        eventSel.addEventListener('change', () => {
            current = parseInt(eventSel.value, 10) || 0;
            renderRoster();
        });
        renderRoster();

        // Tabs.
        const tabSingle = document.getElementById('tab-single');
        const tabBulk = document.getElementById('tab-bulk');
        const paneSingle = document.getElementById('pane-single');
        const paneBulk = document.getElementById('pane-bulk');
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

        // Save session (times + remarks).
        const saveBtn = document.getElementById('att-save-session');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                const err = document.getElementById('att-session-error');
                const start = document.getElementById('att-start').value;
                const end = document.getElementById('att-end').value;
                err.hidden = true;
                if (!start || !end) {
                    err.textContent = 'Set both start and end time.';
                    err.hidden = false;
                    return;
                }
                if (end <= start) {
                    err.textContent = 'End time must be after start time.';
                    err.hidden = false;
                    return;
                }
                showToast('Session saved for ' + DATA[current].title + ' (demo — persists in C13 backend).');
            });
        }

        // Single entry.
        const singleForm = document.getElementById('att-single-form');
        if (singleForm) {
            singleForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const who = memberSel.value;
                const present = document.getElementById('att-status').value === 'present';
                body.querySelectorAll('tr').forEach(tr => {
                    if (tr.getAttribute('data-name') === who) {
                        const cell = tr.children[1];
                        cell.textContent = '';
                        cell.appendChild(pillFor(present ? 'present' : 'absent', present ? 'Present' : 'Absent'));
                        tr.querySelector('button').textContent = present ? 'Mark absent' : 'Mark present';
                    }
                });
                showToast(who + ' marked ' + (present ? 'Present' : 'Absent') + ' (demo).');
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
                e.preventDefault();
                bulkErr.hidden = true;
                if (!csv.files.length) {
                    bulkErr.textContent = 'Choose a CSV file first.';
                    bulkErr.hidden = false;
                    return;
                }
                const reader = new FileReader();
                reader.onload = () => {
                    const lines = String(reader.result).split(/\r?\n/).filter(l => l.trim() !== '');
                    const count = Math.max(0, lines.length - 1);
                    showToast(count + ' record' + (count === 1 ? '' : 's') + ' parsed from CSV (demo — no database writes).');
                };
                reader.readAsText(csv.files[0]);
            });
        }
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
            assetModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };
        const close = () => { assetModal.hidden = true; assetModal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
        openBtn.addEventListener('click', open);
        assetModal.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', close));
        assetModal.addEventListener('click', (e) => { if (e.target === assetModal || (e.target.classList && e.target.classList.contains('dw-modal__backdrop'))) close(); });
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
            const thumb = document.createElement('span');
            thumb.setAttribute('data-asset-thumb', '');
            thumb.setAttribute('aria-hidden', 'true');
            if (photo.files.length) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(photo.files[0]);
                img.alt = '';
                thumb.appendChild(img);
            } else {
                const firstThumb = body.querySelector('[data-asset-thumb]');
                if (firstThumb) thumb.innerHTML = firstThumb.innerHTML;
            }
            const copy = document.createElement('span');
            const strong = document.createElement('strong');
            strong.textContent = name;
            const idLine = document.createElement('small');
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
            pill.className = 'dw-status dw-status--available';
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

        const close = () => { transferModal.hidden = true; transferModal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
        transferModal.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', close));
        transferModal.addEventListener('click', (e) => { if (e.target === transferModal || (e.target.classList && e.target.classList.contains('dw-modal__backdrop'))) close(); });

        body.querySelectorAll('[data-action="transfer"]').forEach(btn => {
            btn.addEventListener('click', () => {
                target = btn.closest('tr');
                assetEl.textContent = target.getAttribute('data-name') || '';
                note.value = '';
                dateInput.value = '';
                err.hidden = true;
                transferModal.hidden = false;
                transferModal.setAttribute('aria-hidden', 'false');
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
                pill.className = 'dw-status dw-status--in-use';
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

    // Request-from-Division flow (treasurer; division side excluded — intent + outbox only).
    const reqOpen = document.getElementById('club-request-open');
    const reqModal = document.getElementById('club-request-modal');
    const reqForm = document.getElementById('club-request-form');
    if (reqOpen && reqModal && reqForm) {
        const CATALOG = {
            'Sports': ['Cricket bat', 'Cricket ball', 'Volleyball net', 'Volleyball', 'Sports shoes'],
            'Audio Video Equipments': ['PA system', 'Loudspeakers', 'Microphones'],
            'Cleaning': ['Mamoty', 'Paint roller set'],
            'Official Equipments': ['Office chairs', 'Filing cabinet', 'Notice board'],
        };
        const catSel = document.getElementById('req-category');
        const itemSel = document.getElementById('req-item');
        const err = document.getElementById('req-error');
        const reqBody = document.getElementById('club-request-body');

        const open = () => {
            reqForm.reset();
            itemSel.textContent = '';
            const ph = document.createElement('option');
            ph.value = '';
            ph.textContent = 'Select a category first...';
            itemSel.appendChild(ph);
            err.hidden = true;
            reqModal.hidden = false;
            reqModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };
        const closeReq = () => { reqModal.hidden = true; reqModal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
        reqOpen.addEventListener('click', open);
        reqModal.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', closeReq));
        reqModal.addEventListener('click', (e) => { if (e.target === reqModal || (e.target.classList && e.target.classList.contains('dw-modal__backdrop'))) closeReq(); });

        catSel.addEventListener('change', () => {
            itemSel.textContent = '';
            (CATALOG[catSel.value] || []).forEach(name => {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                itemSel.appendChild(opt);
            });
            if (!itemSel.children.length) {
                const ph = document.createElement('option');
                ph.value = '';
                ph.textContent = 'Select a category first...';
                itemSel.appendChild(ph);
            }
        });

        reqForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const category = catSel.value;
            const item = itemSel.value;
            const qtyVal = document.getElementById('req-qty').value.trim();
            const dateVal = document.getElementById('req-date').value;
            const just = document.getElementById('req-just').value.trim();
            const fail = (m) => { err.textContent = m; err.hidden = false; };
            err.hidden = true;
            if (!category || !item || !qtyVal || !dateVal || !just) return fail('All fields are required.');
            const qty = Number(qtyVal);
            if (!isFinite(qty) || qty < 1) return fail('Quantity must be at least 1.');
            const nice = new Date().toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const row = document.createElement('tr');
            const itemTd = document.createElement('td');
            const strong = document.createElement('strong');
            strong.textContent = item;
            itemTd.appendChild(strong);
            row.appendChild(itemTd);
            [category, String(Math.round(qty)), just, nice].forEach(text => {
                const td = document.createElement('td');
                td.textContent = text;
                row.appendChild(td);
            });
            const statusTd = document.createElement('td');
            const pill = document.createElement('span');
            pill.className = 'dw-status dw-status--pending';
            pill.textContent = 'Pending';
            statusTd.appendChild(pill);
            row.appendChild(statusTd);
            reqBody.prepend(row);
            closeReq();
            showToast(item + ' requested — sent to the division queue (demo).');
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
            logModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };
        const close = () => { logModal.hidden = true; logModal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
        openBtn.addEventListener('click', open);
        logModal.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', close));
        logModal.addEventListener('click', (e) => { if (e.target === logModal || (e.target.classList && e.target.classList.contains('dw-modal__backdrop'))) close(); });
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
            const c3 = document.createElement('td');
            c3.className = 'dw-money dw-money--' + type.toLowerCase();
            c3.textContent = fmtRs(amount);
            row.appendChild(c3);
            const c4 = document.createElement('td'); c4.className = 'dw-money'; c4.textContent = fmtRs(next); row.appendChild(c4);
            const c4b = document.createElement('td');
            const dlBtn = document.createElement('button');
            dlBtn.type = 'button';
            dlBtn.className = 'dw-button dw-button--ghost';
            dlBtn.setAttribute('title', 'Download ' + receipt.files[0].name);
            dlBtn.setAttribute('aria-label', 'Download receipt');
            const firstDl = body.querySelector('[data-action="receipt"]');
            if (firstDl) dlBtn.innerHTML = firstDl.innerHTML;
            else dlBtn.textContent = 'Download';
            const fileUrl = URL.createObjectURL(receipt.files[0]);
            const fileName = receipt.files[0].name;
            dlBtn.addEventListener('click', () => {
                const a = document.createElement('a');
                a.href = fileUrl;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                a.remove();
                showToast(fileName + ' downloaded (demo).');
            });
            c4b.appendChild(dlBtn);
            row.appendChild(c4b);
            const c5 = document.createElement('td'); c5.appendChild(pillFor('verified', 'Verified')); row.appendChild(c5);
            const c6 = document.createElement('td');
            const voidBtn = document.createElement('button');
            voidBtn.type = 'button';
            voidBtn.className = 'dw-button dw-button--ghost';
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

    // Demo receipt downloads (mock rows serve a generated demo file;
    // real files land with the C13 backend).
    body.querySelectorAll('[data-action="receipt"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const lines = [
                'YouthNexus — demo receipt',
                'Transaction: ' + (btn.getAttribute('data-desc') || ''),
                'Amount: ' + (btn.getAttribute('data-amount') || ''),
                'Date: ' + (btn.getAttribute('data-date') || ''),
                'Status: ' + (btn.getAttribute('data-status') || ''),
                '',
                '(Demo file — real receipts land in C13 backend.)',
            ];
            const blob = new Blob([lines.join('\n')], { type: 'text/plain' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = btn.getAttribute('data-file') || 'receipt.txt';
            document.body.appendChild(a);
            a.click();
            a.remove();
            showToast((btn.getAttribute('data-file') || 'Receipt') + ' downloaded (demo).');
        });
    });

    // Request-void modal (treasurer → Divisional Treasurer).
    const voidModal = document.getElementById('club-void-modal');
    if (voidModal && body) {
        const descEl = document.getElementById('void-desc');
        const reason = document.getElementById('void-reason');
        const err = document.getElementById('void-error');
        const confirmBtn = document.getElementById('void-confirm');
        let target = null;

        const close = () => { voidModal.hidden = true; voidModal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
        voidModal.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', close));
        voidModal.addEventListener('click', (e) => { if (e.target === voidModal || (e.target.classList && e.target.classList.contains('dw-modal__backdrop'))) close(); });

        const bindVoidButton = (btn) => {
            btn.addEventListener('click', () => {
                target = btn.closest('tr');
                descEl.textContent = target.getAttribute('data-desc') || '';
                reason.value = '';
                err.hidden = true;
                voidModal.hidden = false;
                voidModal.setAttribute('aria-hidden', 'false');
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
                const statusCell = target.children[6];
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
