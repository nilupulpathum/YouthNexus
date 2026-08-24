/* =====================================================================
   attendance.js — Divisional Attendance Management
   Client-side only for filtering — NO page reloads for filter changes.
   ===================================================================== */

(function () {
    'use strict';

    const ROOT       = window.ROOT || '';
    const CSRF_TOKEN = document.getElementById('csrfToken')?.value || '';

    /* -----------------------------------------------------------------
       TOAST
       ----------------------------------------------------------------- */
    function showToast(message, type = '') {
        const t = document.getElementById('amToast');
        if (!t) return;
        t.textContent = message;
        t.className   = 'am-toast' + (type ? ' ' + type : '');
        // force reflow
        void t.offsetWidth;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3200);
    }

    /* =================================================================
       SESSION-LIST PAGE
       ================================================================= */

    // --- Filter Panel toggle (no page reload) -------------------------
    const filterBtn   = document.getElementById('amFilterBtn');
    const filterPanel = document.getElementById('amFilterPanel');
    if (filterBtn && filterPanel) {
        filterBtn.addEventListener('click', () => {
            const open = filterPanel.classList.toggle('open');
            filterBtn.setAttribute('aria-expanded', open);
        });
    }

    // --- Client-side filterCards() ------------------------------------
    function filterCards() {
        const query     = (document.getElementById('amSearchInput')?.value || '').toLowerCase().trim();
        const typeVal   = (document.getElementById('amFilterType')?.value  || '').toLowerCase();
        const scopeVal  = (document.getElementById('amFilterScope')?.value || '').toLowerCase();
        const cards     = document.querySelectorAll('#amCardGrid .am-card');
        let   visible   = 0;

        cards.forEach(card => {
            const titleMatch = !query   || (card.dataset.title || '').includes(query);
            const typeMatch  = !typeVal || (card.dataset.type  || '') === typeVal;
            const scopeMatch = !scopeVal|| (card.dataset.scope || '') === scopeVal;
            const show       = titleMatch && typeMatch && scopeMatch;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        // Show empty state if nothing visible
        let emptyMsg = document.getElementById('amFilterEmpty');
        if (!emptyMsg) {
            emptyMsg = document.createElement('div');
            emptyMsg.id = 'amFilterEmpty';
            emptyMsg.className = 'am-empty-state';
            emptyMsg.style.gridColumn = '1 / -1';
            emptyMsg.innerHTML = '<p>No events match your filters.</p>';
            document.getElementById('amCardGrid')?.appendChild(emptyMsg);
        }
        emptyMsg.style.display = visible === 0 ? '' : 'none';
    }

    document.getElementById('amSearchInput')?.addEventListener('input', filterCards);

    function updateFilterBadge() {
        const typeVal  = document.getElementById('amFilterType')?.value || '';
        const scopeVal = document.getElementById('amFilterScope')?.value || '';
        let count = 0;
        if (typeVal !== '') count++;
        if (scopeVal !== '') count++;

        const badge = document.getElementById('amFilterCount');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }

    document.getElementById('amApplyFilterBtn')?.addEventListener('click', () => {
        filterCards();
        updateFilterBadge();
        filterPanel?.classList.remove('open');
        filterBtn?.setAttribute('aria-expanded', 'false');
    });
    document.getElementById('amClearFilterBtn')?.addEventListener('click', () => {
        const typeEl  = document.getElementById('amFilterType');
        const scopeEl = document.getElementById('amFilterScope');
        const search  = document.getElementById('amSearchInput');
        if (typeEl)  typeEl.value  = '';
        if (scopeEl) scopeEl.value = '';
        if (search)  search.value  = '';
        filterCards();
        updateFilterBadge();
        filterPanel?.classList.remove('open');
        filterBtn?.setAttribute('aria-expanded', 'false');
    });

    // Initialize badge count on load
    updateFilterBadge();

    // --- Log Attendance Modal -----------------------------------------
    const modal       = document.getElementById('amModal');
    const addBtn      = document.getElementById('amAddBtn');
    const modalClose  = document.getElementById('amModalClose');
    const cancelBtn   = document.getElementById('amModalCancelBtn');

    function openModal()  { modal?.classList.add('open'); }
    function closeModal() { modal?.classList.remove('open'); }

    addBtn?.addEventListener('click', openModal);
    modalClose?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    // Tab switching
    document.querySelectorAll('.am-modal-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.am-modal-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.am-tab-pane').forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            const paneId = 'pane' + tab.dataset.tab.charAt(0).toUpperCase() + tab.dataset.tab.slice(1);
            document.getElementById(paneId)?.classList.add('active');
        });
    });

    // Event → member dropdown population (Single Entry tab)
    const sEventSelect  = document.getElementById('sEventSelect');
    const sMemberSelect = document.getElementById('sMemberSelect');
    if (sEventSelect && sMemberSelect) {
        sEventSelect.addEventListener('change', () => {
            const eventId = sEventSelect.value;
            sMemberSelect.innerHTML = '<option value="">Loading…</option>';
            sMemberSelect.disabled  = true;
            if (!eventId) {
                sMemberSelect.innerHTML = '<option value="">— Select Event first —</option>';
                return;
            }
            fetch(ROOT + '/attendance/detail/' + eventId, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                const members = data.members || [];
                sMemberSelect.innerHTML = '<option value="">— Select Member —</option>';
                members.forEach(m => {
                    const opt   = document.createElement('option');
                    opt.value   = m.user_id;
                    opt.textContent = m.member_name + (m.club_name ? ' (' + m.club_name + ')' : '');
                    sMemberSelect.appendChild(opt);
                });
                sMemberSelect.disabled = false;
            })
            .catch(() => {
                sMemberSelect.innerHTML = '<option value="">Failed to load members</option>';
            });
        });
    }

    // Save button — Single or Bulk
    document.getElementById('amSaveBtn')?.addEventListener('click', () => {
        const activeTab = document.querySelector('.am-modal-tab.active')?.dataset.tab || 'single';

        if (activeTab === 'single') {
            const eventId  = sEventSelect?.value;
            const memberId = sMemberSelect?.value;
            const status   = document.getElementById('sStatus')?.value;
            const checkIn  = document.getElementById('sCheckIn')?.value;
            const remark   = document.getElementById('sRemark')?.value;

            if (!eventId || !memberId) {
                showToast('Please select an event and a member.', 'error');
                return;
            }

            const fd = new FormData();
            fd.append('csrf_token', CSRF_TOKEN);
            fd.append('mode',       'single');
            fd.append('event_id',   eventId);
            fd.append('member_id',  memberId);
            fd.append('status',     status);
            fd.append('check_in_time', checkIn);
            fd.append('remark',     remark);

            fetch(ROOT + '/attendance/save', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        closeModal();
                        showToast('Attendance saved.', 'success');
                    } else {
                        showToast(data.error || 'Save failed.', 'error');
                    }
                })
                .catch(() => showToast('Network error.', 'error'));

        } else {
            // Bulk CSV
            const eventId = document.getElementById('bEventSelect')?.value;
            const csvFile = document.getElementById('bCsvFile')?.files[0];
            if (!eventId) { showToast('Please select an event.', 'error'); return; }
            if (!csvFile)  { showToast('Please select a CSV file.', 'error'); return; }

            const fd = new FormData();
            fd.append('csrf_token', CSRF_TOKEN);
            fd.append('mode',       'bulk');
            fd.append('event_id',   eventId);
            fd.append('csv_file',   csvFile);

            fetch(ROOT + '/attendance/save', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        closeModal();
                        let msg = 'Saved ' + (data.saved || 0) + ' record(s).';
                        if (data.skipped?.length) {
                            msg += ' ' + data.skipped.length + ' row(s) skipped.';
                        }
                        showToast(msg, 'success');
                        if (data.skipped?.length) {
                            console.warn('[Attendance] Skipped rows:', data.skipped);
                        }
                    } else {
                        showToast(data.error || 'Save failed.', 'error');
                    }
                })
                .catch(() => showToast('Network error.', 'error'));
        }
    });

    /* =================================================================
       SESSION-DETAIL PAGE
       ================================================================= */

    // --- Client-side table search + status filter (no page reload) ----
    function filterTable() {
        const query      = (document.getElementById('amTableSearch')?.value || '').toLowerCase().trim();
        const statusVal  = (document.getElementById('amTableStatusFilter')?.value || '').toLowerCase();
        const rows       = document.querySelectorAll('#amRosterTable .am-roster-row');
        rows.forEach(row => {
            const nameMatch   = !query     || (row.dataset.name   || '').includes(query);
            const rowStatus   = (row.dataset.status || '').toLowerCase();
            const statusMatch = !statusVal || rowStatus === statusVal || (statusVal === 'unmarked' && rowStatus === '');
            row.style.display = (nameMatch && statusMatch) ? '' : 'none';
        });
    }

    document.getElementById('amTableSearch')?.addEventListener('input', filterTable);
    document.getElementById('amTableStatusFilter')?.addEventListener('change', filterTable);

    // --- Quick Update Panel -------------------------------------------
    const quickPanel    = document.getElementById('amQuickPanel');
    const quickName     = document.getElementById('amQuickMemberName');
    const quickStatus   = document.getElementById('amQuickStatus');
    const quickCheckIn  = document.getElementById('amQuickCheckIn');
    const quickCheckOut = document.getElementById('amQuickCheckOut');
    const quickRemark   = document.getElementById('amQuickRemark');
    const quickCancel   = document.getElementById('amQuickCancelBtn');
    const quickSave     = document.getElementById('amQuickSaveBtn');
    const eventIdInput  = document.getElementById('amEventId');
    let   activeMemberId = null;

    document.querySelectorAll('.am-btn-quick-update').forEach(btn => {
        btn.addEventListener('click', () => {
            activeMemberId = btn.dataset.memberId;
            if (quickName)   quickName.textContent = btn.dataset.memberName || '—';
            if (quickStatus) quickStatus.value     = btn.dataset.currentStatus || 'Present';
            if (quickCheckIn)  quickCheckIn.value  = '';
            if (quickCheckOut) quickCheckOut.value = '';
            if (quickRemark)   quickRemark.value   = '';
            quickPanel.style.display = '';
            quickPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    quickCancel?.addEventListener('click', () => {
        quickPanel.style.display = 'none';
        activeMemberId = null;
    });

    quickSave?.addEventListener('click', () => {
        if (!activeMemberId) return;
        const fd = new FormData();
        fd.append('csrf_token',      CSRF_TOKEN);
        fd.append('event_id',        eventIdInput?.value || '');
        fd.append('member_id',       activeMemberId);
        fd.append('status',          quickStatus?.value  || 'Present');
        fd.append('check_in_time',   quickCheckIn?.value  || '');
        fd.append('check_out_time',  quickCheckOut?.value || '');
        fd.append('remark',          quickRemark?.value   || '');

        fetch(ROOT + '/attendance/updatestatus', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    quickPanel.style.display = 'none';
                    showToast('Status updated. Reload to see changes.', 'success');
                } else {
                    showToast(data.error || 'Update failed.', 'error');
                }
            })
            .catch(() => showToast('Network error.', 'error'));
    });

    // --- Download CSV (client-side generation) ------------------------
    document.getElementById('amDownloadCsvBtn')?.addEventListener('click', () => {
        const rows = document.querySelectorAll('#amRosterTable .am-roster-row');
        if (!rows.length) { showToast('No data to download.'); return; }
        let csv = 'Member Name,Club,Status,Check-in,Remark\n';
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            // name (strip inner email span), club, status badge text, check-in, remark
            const name   = (cells[0]?.querySelector('strong')?.textContent || '').trim();
            const club   = (cells[1]?.textContent || '').trim();
            const status = (cells[2]?.querySelector('.am-status-badge')?.textContent || '').trim();
            const checkin = (cells[3]?.textContent || '').trim();
            const remark  = (cells[4]?.textContent || '').trim();
            csv += [name, club, status, checkin, remark].map(v => '"' + v.replace(/"/g, '""') + '"').join(',') + '\n';
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'attendance-' + (eventIdInput?.value || 'event') + '.csv';
        a.click();
        URL.revokeObjectURL(url);
    });

    // --- Export PDF — STUBBED (PDF library not yet in this project) ---
    document.getElementById('amExportPdfBtn')?.addEventListener('click', () => {
        // TODO: PDF export not yet implemented — flagged in AttendanceManagement_AllInOne.php
        alert('PDF export not yet implemented.\n\nThis feature requires a PDF generation library to be agreed on. Flagged for a future sprint.');
    });

})();
