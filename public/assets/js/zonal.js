/**
 * zonal.js - client behaviour for zonal pages (presentation-only demo).
 * Modal open/close + filter-panel toggle come from divisional-workflows.js
 * via [data-modal-open], [data-modal-close] and [data-filter-toggle].
 */

/* ---- zonalcoordinator/clubs ---- */
if (document.getElementById('health-select')) {

document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('health-grid');
    const search = document.getElementById('health-search');
    const divisionSel = document.getElementById('health-division');
    const sortSel = document.getElementById('health-sort');
    const chips = Array.from(document.querySelectorAll('.health-chip'));
    const emptyNote = document.getElementById('health-empty');
    const toast = document.getElementById('club-toast');
    let toastTimer = null;
    let activeBand = 'all';

    const showToast = (msg) => {
        if (!toast) return;
        toast.textContent = msg;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    };

    const cards = () => grid ? Array.from(grid.querySelectorAll('.health-card')) : [];

    const applyFilter = () => {
        if (!grid) return;
        const q = (search ? search.value : '').trim().toLowerCase();
        const div = divisionSel ? divisionSel.value : 'all';
        let visible = 0;
        cards().forEach((card) => {
            const show = (activeBand === 'all' || card.getAttribute('data-band') === activeBand)
                && (div === 'all' || card.getAttribute('data-division') === div)
                && (!q || (card.getAttribute('data-name') || '').includes(q));
            card.hidden = !show;
            if (show) visible += 1;
        });
        if (emptyNote) emptyNote.hidden = visible !== 0;
    };

    const applySort = () => {
        if (!grid || !sortSel) return;
        const mode = sortSel.value;
        const ordered = cards().sort((a, b) => {
            const sa = parseInt(a.getAttribute('data-score') || '0', 10);
            const sb = parseInt(b.getAttribute('data-score') || '0', 10);
            if (mode === 'score-asc') return sa - sb;
            if (mode === 'name-asc') return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '');
            return sb - sa;
        });
        ordered.forEach((card) => grid.appendChild(card));
    };

    if (search) search.addEventListener('input', applyFilter);
    if (divisionSel) divisionSel.addEventListener('change', applyFilter);
    if (sortSel) sortSel.addEventListener('change', applySort);
    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            chips.forEach((c) => c.classList.remove('is-active'));
            chip.classList.add('is-active');
            activeBand = chip.getAttribute('data-band') || 'all';
            applyFilter();
        });
    });

    const exportBtn = document.getElementById('health-export');
    if (exportBtn && grid) {
        exportBtn.addEventListener('click', () => {
            const rows = [['Club', 'Division', 'Score', 'Status', 'Members', 'Events', 'Finances', 'Attendance']];
            cards().forEach((card) => {
                if (card.hidden) return;
                try {
                    const d = JSON.parse(card.getAttribute('data-club') || '{}');
                    rows.push([d.name || '', d.division || '', d.score || '', d.status || '', d.members_note || '', d.events || '', d.finances || '', d.attendance || '']);
                } catch (err) { /* skip malformed card */ }
            });
            const csv = rows.map((r) => r.map((v) => '"' + String(v).replace(/"/g, '""') + '"').join(',')).join('\r\n');
            const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
            const a = document.createElement('a');
            a.href = url;
            a.download = 'gampaha-zone-club-health.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            showToast('Club health exported (demo CSV).');
        });
    }

    const detail = document.getElementById('club-health-modal');
    const flagModal = document.getElementById('club-flag-modal');
    let current = {};

    const closeOverlay = (overlay) => { if (!overlay) return; overlay.hidden = true; overlay.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
    const openOverlay = (overlay) => { if (!overlay) return; overlay.hidden = false; overlay.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; };
    [detail, flagModal].forEach((overlay) => {
        if (!overlay) return;
        overlay.querySelectorAll('[data-modal-close]').forEach((b) => b.addEventListener('click', () => closeOverlay(overlay)));
        overlay.addEventListener('click', (e) => { if (e.target === overlay || e.target.classList.contains('dw-modal__backdrop')) closeOverlay(overlay); });
    });

    const esc = (v) => String(v == null ? '' : v);

    const openDetail = (d) => {
        current = d;
        document.getElementById('ch-title').textContent = esc(d.name || 'Club');
        document.getElementById('ch-id').textContent = esc(d.id || '');
        const band = document.getElementById('ch-band');
        band.textContent = esc((d.score || '') + '/100 ' + (d.status || ''));
        band.className = 'dw-status dw-status--' + esc(d.status_key || 'pending');
        document.getElementById('ch-about').textContent = esc(d.about || '—');
        document.getElementById('ch-category').textContent = esc(d.category || '—');
        document.getElementById('ch-location').textContent = esc(d.location || '—');
        document.getElementById('ch-established').textContent = esc(d.established || '—');
        document.getElementById('ch-members').textContent = esc(d.members || 0);
        document.getElementById('ch-attendance').textContent = esc(d.avg_attendance || '—');
        document.getElementById('ch-trend').textContent = esc(d.attendance_trend || '');

        const eventsBody = document.getElementById('ch-events');
        eventsBody.textContent = '';
        (d.recent_events || []).forEach((e) => {
            const tr = document.createElement('tr');
            const nameTd = document.createElement('td');
            const nameStrong = document.createElement('strong');
            nameStrong.textContent = esc(e.name || '');
            nameTd.appendChild(nameStrong);
            const venue = document.createElement('div');
            venue.className = 'dw-muted-copy';
            venue.textContent = esc(e.venue || '');
            nameTd.appendChild(venue);
            const dateTd = document.createElement('td');
            dateTd.textContent = esc(e.date || '');
            const statusTd = document.createElement('td');
            const pill = document.createElement('span');
            pill.className = 'dw-status dw-status--' + esc(e.status_key || 'pending');
            pill.textContent = esc((e.status || '').toUpperCase());
            statusTd.appendChild(pill);
            tr.appendChild(nameTd);
            tr.appendChild(dateTd);
            tr.appendChild(statusTd);
            eventsBody.appendChild(tr);
        });

        const execsBox = document.getElementById('ch-execs');
        execsBox.textContent = '';
        (d.execs || []).forEach((x) => {
            const row = document.createElement('div');
            row.className = 'health-exec-row';
            const disc = document.createElement('span');
            disc.className = 'health-exec-disc';
            disc.setAttribute('aria-hidden', 'true');
            disc.textContent = esc(x.name || '').split(/\s+/).map((w) => w.charAt(0)).join('').slice(0, 2).toUpperCase() || '–';
            const copy = document.createElement('div');
            const nm = document.createElement('strong');
            nm.textContent = esc(x.name || '');
            copy.appendChild(nm);
            const rl = document.createElement('div');
            rl.className = 'dw-muted-copy';
            rl.textContent = esc(x.role || '');
            copy.appendChild(rl);
            row.appendChild(disc);
            row.appendChild(copy);
            execsBox.appendChild(row);
        });

        const scoresBox = document.getElementById('ch-scores');
        scoresBox.textContent = '';
        [['Event Score', d.events], ['Finance Score', d.finances], ['Attendance Score', d.attendance], ['Overall Health Score', (d.score || '') + ' / 100']].forEach(([label, val], idx, arr) => {
            const row = document.createElement('p');
            row.className = 'dw-metric' + (idx === arr.length - 1 ? ' health-score-total' : '');
            const lab = document.createElement('span');
            lab.textContent = label;
            const strong = document.createElement('strong');
            strong.textContent = esc(val || '—');
            row.appendChild(lab);
            row.appendChild(strong);
            scoresBox.appendChild(row);
        });
        document.getElementById('ch-trigger-note').textContent = esc(d.trigger || '')
            + ' — this club is read-only and cannot be disbanded except by NYSC Admin.';

        openOverlay(detail);
    };

    if (grid && detail) {
        grid.querySelectorAll('[data-action="club-detail"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const card = btn.closest('.health-card');
                try {
                    openDetail(JSON.parse(card.getAttribute('data-club') || '{}'));
                } catch (err) { /* ignore malformed payload */ }
            });
        });
    }

    const openFlag = () => {
        if (!flagModal) return;
        document.getElementById('cf-club').textContent = esc(current.name || '');
        document.getElementById('cf-score').textContent = esc((current.score || '') + ' / 100');
        document.getElementById('cf-trigger').textContent = esc(current.trigger || '');
        document.getElementById('cf-club-id').value = current.id || '';
        document.getElementById('cf-remarks').value = '';
        document.getElementById('cf-error').hidden = true;
        if (detail) closeOverlay(detail);
        openOverlay(flagModal);
    };

    const flagBtn = document.getElementById('ch-open-flag');
    if (flagBtn) flagBtn.addEventListener('click', openFlag);

    const editBtn = document.getElementById('ch-edit');
    if (editBtn) editBtn.addEventListener('click', () => {
        showToast('Club details are read-only for zonal coordinators — contact NYSC Admin. (demo)');
    });

    const reportBtn = document.getElementById('ch-report');
    if (reportBtn) {
        reportBtn.addEventListener('click', () => {
            const d = current;
            const lines = [
                'Club Health Report (demo) — ' + esc(d.name || ''),
                'ID: ' + esc(d.id || '') + ' | Division: ' + esc(d.division || ''),
                'Overall: ' + esc(d.score || '') + '/100 (' + esc(d.status || '') + ')',
                'Events: ' + esc(d.events || '') + ' | Finances: ' + esc(d.finances || '') + ' | Attendance: ' + esc(d.attendance || ''),
                'Members: ' + esc(d.members_note || '') + ' | Avg attendance: ' + esc(d.avg_attendance || ''),
                'Trigger: ' + esc(d.trigger || ''),
            ];
            const url = URL.createObjectURL(new Blob([lines.join('\r\n')], { type: 'text/plain' }));
            const a = document.createElement('a');
            a.href = url;
            a.download = 'club-health-' + esc(d.id || 'report') + '.txt';
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            showToast('Club report downloaded (demo).');
        });
    }

    const flagForm = document.getElementById('club-flag-form');
    if (flagForm) {
        flagForm.addEventListener('submit', (e) => {
            const remarks = document.getElementById('cf-remarks');
            const err = document.getElementById('cf-error');
            if (!remarks.value.trim()) {
                e.preventDefault();
                err.textContent = 'Please describe the concern for NYSC Admin.';
                err.hidden = false;
                remarks.focus();
            }
        });
    }
});

}

/* ---- zonalcoordinator/events ---- */
if (document.getElementById('coordinator-event-search')) {

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('coordinator-event-list');
    const search = document.getElementById('coordinator-event-search');
    const status = document.getElementById('coordinator-event-status');
    const empty = document.getElementById('coordinator-event-empty');
    const applyFilters = () => {
        if (!list) return;
        let visible = 0;
        list.querySelectorAll('.dw-record-card').forEach((item) => {
            const matchesSearch = !search || !search.value.trim() || (item.dataset.search || '').includes(search.value.trim().toLowerCase());
            const matchesStatus = !status || !status.value || (item.dataset.status || '') === status.value;
            item.hidden = !(matchesSearch && matchesStatus);
            if (!item.hidden) visible += 1;
        });
        if (empty) empty.hidden = visible !== 0;
    };
    [search, status].forEach((element) => {
        if (!element) return;
        element.addEventListener('input', applyFilters);
        element.addEventListener('change', applyFilters);
    });

    const modal = document.getElementById('event-review');
    const form = document.getElementById('event-decision');
    document.querySelectorAll('[data-review]').forEach((button) => button.addEventListener('click', () => {
        document.getElementById('event-id').value = button.dataset.review;
        document.getElementById('event-review-name').textContent = button.dataset.title;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
    }));
    modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', () => {
        const host = button.closest('.dw-modal');
        if (host) { host.hidden = true; host.setAttribute('aria-hidden', 'true'); }
    }));
    modal.addEventListener('click', (event) => { if (event.target === modal || event.target.classList.contains('dw-modal__backdrop')) { modal.hidden = true; modal.setAttribute('aria-hidden', 'true'); } });
    modal.querySelectorAll('[data-decision]').forEach((button) => button.addEventListener('click', () => { form.action = (form.dataset.baseAction || '') + button.dataset.decision; }));
});

}

/* ---- zonalsecretary/events ---- */
if (document.getElementById('zonal-event-open')) {

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('zonal-event-list');
    const search = document.getElementById('zonal-event-search');
    const status = document.getElementById('zonal-event-status');
    const type = document.getElementById('zonal-event-type');
    const empty = document.getElementById('zonal-event-empty');
    const applyFilters = () => {
        let visible = 0;
        list.querySelectorAll('.dw-record-card').forEach((item) => {
            const matchesSearch = !search.value.trim() || item.dataset.search.includes(search.value.trim().toLowerCase());
            const matchesStatus = !status.value || item.dataset.status === status.value;
            const matchesType = !type.value || item.dataset.type.toLowerCase() === type.value.toLowerCase();
            item.hidden = !(matchesSearch && matchesStatus && matchesType);
            if (!item.hidden) visible += 1;
        });
        empty.hidden = visible !== 0;
    };
    [search, status, type].forEach((element) => {
        element.addEventListener('input', applyFilters);
        element.addEventListener('change', applyFilters);
    });

    const modal = document.getElementById('zonal-event-modal');
    const open = document.getElementById('zonal-event-open');
    const form = document.getElementById('zonal-event-form');
    const date = document.getElementById('zonal-event-date');
    const time = document.getElementById('zonal-event-time');
    const dateBanner = document.getElementById('zonal-event-date-banner');
    const dateError = document.getElementById('zonal-event-date-error');
    const formError = document.getElementById('zonal-event-error');
    const close = () => { modal.hidden = true; document.body.style.overflow = ''; };
    const clearDateError = () => {
        dateBanner.hidden = true;
        dateError.hidden = true;
        date.classList.remove('is-invalid');
    };
    open.addEventListener('click', () => { form.reset(); formError.hidden = true; clearDateError(); modal.hidden = false; document.body.style.overflow = 'hidden'; });
    modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    date.addEventListener('input', clearDateError);
    time.addEventListener('input', clearDateError);
    form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) {
            event.preventDefault();
            formError.textContent = 'Complete all required fields.';
            formError.hidden = false;
            return;
        }
        const eventAt = new Date(date.value + 'T' + time.value);
        if (Number.isNaN(eventAt.getTime()) || eventAt <= new Date()) {
            event.preventDefault();
            dateBanner.hidden = false;
            dateError.hidden = false;
            date.classList.add('is-invalid');
            date.focus();
        }
    });
});

}

/* ---- zonaltreasurer/assets ---- */
if (document.getElementById('asset-register')) {
document.querySelectorAll('[data-modal-open]').forEach(button=>button.addEventListener('click',()=>{document.getElementById(button.getAttribute('data-modal-open')).hidden=false;}));document.querySelectorAll('[data-modal-close]').forEach(button=>button.addEventListener('click',()=>{var modal=button.closest('.dw-modal');if(modal)modal.hidden=true;}));document.querySelectorAll('[data-transfer]').forEach(button=>button.addEventListener('click',()=>{document.getElementById('transfer-asset-id').value=button.dataset.transfer;document.getElementById('transfer-asset-name').textContent=button.dataset.name;document.getElementById('asset-transfer').hidden=false;}));
}

/* ---- zonaltreasurer/audit ---- */
if (document.getElementById('zonal-audit')) {

document.addEventListener('DOMContentLoaded', () => {
    const close = (modal) => { modal.classList.remove('show'); document.body.style.overflow = ''; };
    document.querySelectorAll('[data-modal-open]').forEach((button) => button.addEventListener('click', () => { const modal = document.getElementById(button.dataset.modalOpen); modal.querySelectorAll('input[name="flag_id"]').forEach((input) => { input.value = button.dataset.flagId || ''; }); modal.classList.add('show'); document.body.style.overflow = 'hidden'; }));
    document.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', () => close(button.closest('.audit-overlay'))));
    document.querySelectorAll('.audit-overlay').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) close(modal); }));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') document.querySelectorAll('.audit-overlay.show').forEach(close); });
});

}

/* ---- zonaltreasurer/ledger ---- */
if (document.getElementById('log-transaction')) {
document.querySelector('[data-modal-open="log-transaction"]').addEventListener('click',()=>{document.getElementById('log-transaction').hidden=false;});document.querySelectorAll('[data-modal-close]').forEach(button=>button.addEventListener('click',()=>{var modal=button.closest('.dw-modal');if(modal)modal.hidden=true;}));
}

/* ---- zonaltreasurer/voids ---- */
if (document.getElementById('void-review')) {
var voidModal=document.getElementById('void-review'),voidForm=document.getElementById('void-decision-form');document.querySelectorAll('[data-review]').forEach(button=>button.addEventListener('click',()=>{document.getElementById('void-review-id').value=button.dataset.review;document.getElementById('void-review-reference').textContent=button.dataset.division+' - '+button.dataset.reference;document.getElementById('void-remark').value='';voidModal.hidden=false;}));document.querySelectorAll('[data-modal-close]').forEach(button=>button.addEventListener('click',()=>{var modal=button.closest('.dw-modal');if(modal)modal.hidden=true;}));voidForm.querySelectorAll('[data-decision]').forEach(button=>button.addEventListener('click',()=>{voidForm.action=(voidForm.dataset.baseAction || '')+button.dataset.decision;}));
}

/* ---- zonalannouncements ---- */
if (document.getElementById('zonal-announcement-list')) {

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('zonal-announcement-list');
    const toast = document.getElementById('zonal-announce-toast');
    const showToast = (message) => { toast.textContent = message; toast.hidden = false; window.setTimeout(() => { toast.hidden = true; }, 3500); };
    const readCard = (card) => { card.classList.remove('is-unread'); card.dataset.unread = 'false'; card.querySelector('[data-action="mark-read"]')?.remove(); updateUnread(); };
    const updateUnread = () => { const count = list.querySelectorAll('[data-unread="true"]').length; document.getElementById('zonal-unread-count').textContent = count + ' unread'; document.getElementById('zonal-unread-badge').textContent = count; };
    document.querySelectorAll('[data-action="mark-read"]').forEach((button) => button.addEventListener('click', () => readCard(button.closest('.announcement-card'))));
    document.getElementById('zonal-mark-all-read').addEventListener('click', () => { list.querySelectorAll('[data-unread="true"]').forEach(readCard); showToast('All zone announcements marked as read.'); });
    document.getElementById('zonal-clear-filters').addEventListener('click', () => document.querySelectorAll('.announcements-filters input[value="all"]').forEach((input) => { input.checked = true; }));
    document.querySelectorAll('.announcements-filters input').forEach((input) => input.addEventListener('change', () => { const level = document.querySelector('input[name="level"]:checked').value; const state = document.querySelector('input[name="status"]:checked').value; list.querySelectorAll('.announcement-card').forEach((card) => { card.hidden = !((level === 'all' || card.dataset.level === level) && (state === 'all' || (state === 'unread') === (card.dataset.unread === 'true'))); }); }));
    if (document.getElementById('zonal-announcement-list')?.dataset.canPublish === '1') {
    const modal = document.getElementById('zonal-publish-modal'); const emailModal = document.getElementById('zonal-publish-email-modal'); const form = document.getElementById('zonal-publish-form'); const error = document.getElementById('zonal-publish-error'); const file = document.getElementById('zonal-publish-attachment'); const fileName = document.getElementById('zonal-publish-file-name');
    const close = () => { modal.hidden = true; document.body.style.overflow = ''; };
    document.getElementById('zonal-announce-open').addEventListener('click', () => { form.reset(); error.hidden = true; fileName.hidden = true; modal.hidden = false; document.body.style.overflow = 'hidden'; }); modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', close)); modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    file.addEventListener('change', () => { fileName.hidden = !file.files.length; if (file.files.length) fileName.textContent = 'Attached: ' + file.files[0].name; });
    const publish = (emailed) => { const title = document.getElementById('zonal-publish-heading').value.trim(); const body = document.getElementById('zonal-publish-body').value.trim(); const priority = document.getElementById('zonal-publish-priority').value; const card = document.createElement('article'); card.className = 'announcement-card is-unread'; card.dataset.level = 'zonal'; card.dataset.unread = 'true'; card.dataset.announcement = JSON.stringify({ title, summary: body, scope: 'Zonal', date: 'Just now', age: 'Just now', is_new: true, is_unread: true }); card.innerHTML = '<div class="card-header"><div class="header-left"><span class="scope-badge national">Zonal</span><span class="new-badge">New</span></div><span class="age-text">Just now</span></div><h3 class="card-title"></h3><p class="card-summary"></p><div class="card-footer"><button type="button" class="btn-mark-as-read" data-action="mark-read">Mark as Read</button></div>'; card.querySelector('.card-title').textContent = title; card.querySelector('.card-summary').textContent = body; card.querySelector('[data-action="mark-read"]').addEventListener('click', () => readCard(card)); document.getElementById('zonal-unread-section').appendChild(card); document.getElementById('zonal-announcement-total').textContent = String(Number(document.getElementById('zonal-announcement-total').textContent) + 1); updateUnread(); close(); showToast(priority === 'Urgent' && emailed ? 'Urgent zone announcement published and emailed.' : 'Zone announcement published.'); };
    form.addEventListener('submit', (event) => { event.preventDefault(); const title = document.getElementById('zonal-publish-heading').value.trim(); const body = document.getElementById('zonal-publish-body').value.trim(); if (!title || !body) { error.textContent = 'Title and body are required.'; error.hidden = false; return; } if (document.getElementById('zonal-publish-priority').value === 'Urgent') { emailModal.hidden = false; return; } publish(false); }); emailModal.querySelectorAll('[data-close-email]').forEach((button) => button.addEventListener('click', () => { emailModal.hidden = true; publish(false); })); document.getElementById('zonal-email-confirm').addEventListener('click', () => { emailModal.hidden = true; publish(true); });
    }
});

}
