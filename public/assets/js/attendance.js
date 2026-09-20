/* =====================================================================
   attendance.js — Attendance Governance & Management
   Supports NYSC Administrator (Cascading Filters & National Scope)
   and Divisional Secretary.
   ===================================================================== */

(function () {
    'use strict';

    const ROOT       = window.ROOT || '';
    const CSRF_TOKEN = document.getElementById('csrfToken')?.value || '';
    const isNYSCAdmin = !!window.isNYSCAdmin;

    /* -----------------------------------------------------------------
       TOAST HELPER
       ----------------------------------------------------------------- */
    function showToast(message, type = 'success') {
        const t = document.getElementById('amToast');
        if (!t) return;
        t.textContent = message;
        t.className   = 'am-toast ' + type;
        void t.offsetWidth;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3500);
    }

    /* =================================================================
       SESSION-LIST PAGE (Filters & Cascading Dropdowns)
       ================================================================= */

    // --- Filter Panel Toggle -----------------------------------------
    const filterBtn   = document.getElementById('amFilterBtn');
    const filterPanel = document.getElementById('amFilterPanel');
    if (filterBtn && filterPanel) {
        filterBtn.addEventListener('click', () => {
            const open = filterPanel.classList.toggle('open');
            filterBtn.setAttribute('aria-expanded', open);
        });
    }

    // --- Cascading Dropdowns for NYSC Admin --------------------------
    const zoneSelect = document.getElementById('filterZone');
    const divSelect  = document.getElementById('filterDivision');
    const clubSelect = document.getElementById('filterClub');

    if (zoneSelect && divSelect) {
        zoneSelect.addEventListener('change', async function () {
            const zoneId = this.value;
            divSelect.innerHTML  = '<option value="">All Divisions</option>';
            if (clubSelect) clubSelect.innerHTML = '<option value="">All Clubs</option>';

            if (zoneId) {
                try {
                    const res  = await fetch(`${ROOT}/attendance/getdivisions?zone_id=${encodeURIComponent(zoneId)}`);
                    const data = await res.json();
                    if (data.success && data.divisions) {
                        data.divisions.forEach(d => {
                            const opt = document.createElement('option');
                            opt.value = d.division_id;
                            opt.textContent = d.division_name;
                            divSelect.appendChild(opt);
                        });
                    }
                } catch (e) {
                    console.error('Error fetching divisions:', e);
                }
            }
        });
    }

    if (divSelect && clubSelect) {
        divSelect.addEventListener('change', async function () {
            const divId = this.value;
            clubSelect.innerHTML = '<option value="">All Clubs</option>';

            if (divId) {
                try {
                    const res  = await fetch(`${ROOT}/attendance/getclubs?division_id=${encodeURIComponent(divId)}`);
                    const data = await res.json();
                    if (data.success && data.clubs) {
                        data.clubs.forEach(c => {
                            const opt = document.createElement('option');
                            opt.value = c.club_id;
                            opt.textContent = c.club_name;
                            clubSelect.appendChild(opt);
                        });
                    }
                } catch (e) {
                    console.error('Error fetching clubs:', e);
                }
            }
        });
    }

    // --- Client-side search for event cards --------------------------
    const searchInput = document.getElementById('amSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('#amCardGrid .am-card');
            let visible = 0;

            cards.forEach(card => {
                const title = card.dataset.title || '';
                const type  = card.dataset.type  || '';
                const match = !q || title.includes(q) || type.includes(q);
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });

            let emptyMsg = document.getElementById('amFilterEmpty');
            if (!emptyMsg) {
                emptyMsg = document.createElement('div');
                emptyMsg.id = 'amFilterEmpty';
                emptyMsg.className = 'am-empty-state';
                emptyMsg.style.gridColumn = '1 / -1';
                emptyMsg.innerHTML = '<p>No events match your search term.</p>';
                document.getElementById('amCardGrid')?.appendChild(emptyMsg);
            }
            emptyMsg.style.display = (visible === 0) ? '' : 'none';
        });
    }

    /* =================================================================
       ADD ATTENDANCE MODAL
       ================================================================= */
    const addBtn    = document.getElementById('amAddBtn');
    const modal     = document.getElementById('amModal');
    const closeBtn  = document.getElementById('amModalClose');
    const cancelBtn = document.getElementById('amModalCancelBtn');
    const saveBtn   = document.getElementById('amSaveBtn');

    function openModal() {
        if (!modal) return;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    if (addBtn)    addBtn.addEventListener('click', openModal);
    if (closeBtn)  closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    if (modal) {
        modal.addEventListener('click', e => {
            if (e.target === modal) closeModal();
        });
    }

    // Modal Tab Switching
    document.querySelectorAll('.am-modal-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.am-modal-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.am-tab-pane').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const targetPane = document.getElementById(this.dataset.tab === 'bulk' ? 'paneBulk' : 'paneSingle');
            if (targetPane) targetPane.classList.add('active');
        });
    });

    // Dynamic Member Fetching on Event Selection
    const sEventSelect  = document.getElementById('sEventSelect');
    const sMemberSelect = document.getElementById('sMemberSelect');

    if (sEventSelect && sMemberSelect) {
        sEventSelect.addEventListener('change', async function () {
            const eventId = this.value;
            sMemberSelect.innerHTML = '<option value="">Loading members…</option>';
            sMemberSelect.disabled  = true;

            if (!eventId) {
                sMemberSelect.innerHTML = '<option value="">— Select Event first —</option>';
                return;
            }

            try {
                const res  = await fetch(`${ROOT}/attendance/getmembers?event_id=${encodeURIComponent(eventId)}`);
                const data = await res.json();

                if (data.success && data.members && data.members.length > 0) {
                    sMemberSelect.innerHTML = '<option value="">— Select Member —</option>';
                    data.members.forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m.user_id || m.member_id;
                        const clubInfo = m.club_name ? ` (${m.club_name})` : '';
                        opt.textContent = `${m.member_name}${clubInfo}`;
                        sMemberSelect.appendChild(opt);
                    });
                    sMemberSelect.disabled = false;
                } else {
                    sMemberSelect.innerHTML = '<option value="">No members found in scope</option>';
                }
            } catch (err) {
                sMemberSelect.innerHTML = '<option value="">Failed to load members</option>';
            }
        });
    }

    // Modal Submission (Single & Bulk)
    if (saveBtn) {
        saveBtn.addEventListener('click', async function () {
            const isSingle = document.getElementById('tabSingle')?.classList.contains('active');
            const formData = new FormData();
            formData.append('csrf_token', CSRF_TOKEN);

            if (isSingle) {
                const eventId     = sEventSelect?.value;
                const memberId    = sMemberSelect?.value;
                const status      = document.getElementById('sStatus')?.value;
                const checkIn     = document.getElementById('sCheckIn')?.value;
                const remark      = document.getElementById('sRemark')?.value;

                if (!eventId || !memberId) {
                    alert('Please select both an Event and a Member.');
                    return;
                }

                formData.append('mode', 'single');
                formData.append('event_id', eventId);
                formData.append('member_id', memberId);
                formData.append('status', status);
                if (checkIn) formData.append('check_in_time', checkIn);
                if (remark)  formData.append('remark', remark);

            } else {
                const eventId = document.getElementById('bEventSelect')?.value;
                const file    = document.getElementById('bCsvFile')?.files[0];

                if (!eventId || !file) {
                    alert('Please select an Event and attach a CSV file.');
                    return;
                }

                formData.append('mode', 'bulk');
                formData.append('event_id', eventId);
                formData.append('csv_file', file);
            }

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';

            try {
                const res  = await fetch(`${ROOT}/attendance/save`, {
                    method: 'POST',
                    body: formData,
                });
                const data = await res.json();

                if (data.success) {
                    showToast(data.message || 'Attendance saved successfully.', 'success');
                    closeModal();
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    alert(data.error || 'Failed to save attendance.');
                }
            } catch (err) {
                alert('An error occurred during submission. Please try again.');
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Attendance';
            }
        });
    }

    /* =================================================================
       SESSION DETAIL PAGE (Roster Search, Filter & Quick Update)
       ================================================================= */
    const tableSearch = document.getElementById('amTableSearch');
    const statusFilter = document.getElementById('amTableStatusFilter');

    function filterRosterRows() {
        const query  = (tableSearch?.value || '').toLowerCase().trim();
        const status = statusFilter?.value || '';
        const rows   = document.querySelectorAll('.am-roster-row');

        rows.forEach(row => {
            const name   = row.dataset.name  || '';
            const email  = row.dataset.email || '';
            const club   = row.dataset.club  || '';
            const rStat  = row.dataset.status|| '';

            const matchQuery  = !query  || name.includes(query) || email.includes(query) || club.includes(query);
            const matchStatus = !status || rStat === status;

            row.style.display = (matchQuery && matchStatus) ? '' : 'none';
        });
    }

    if (tableSearch)  tableSearch.addEventListener('input', filterRosterRows);
    if (statusFilter) statusFilter.addEventListener('change', filterRosterRows);

    // Quick Update Modal on Detail Page
    const quickModal = document.getElementById('amQuickUpdateModal');
    const quickClose = document.getElementById('amQuickClose');
    const quickCancel= document.getElementById('amQuickCancel');
    const quickSave  = document.getElementById('amQuickSaveBtn');

    function closeQuickModal() {
        if (!quickModal) return;
        quickModal.classList.remove('open');
    }

    if (quickClose)  quickClose.addEventListener('click', closeQuickModal);
    if (quickCancel) quickCancel.addEventListener('click', closeQuickModal);

    document.querySelectorAll('.am-btn-quick-update').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!quickModal) return;
            const mId    = this.dataset.memberId;
            const mName  = this.dataset.memberName;
            const status = this.dataset.currentStatus || 'Present';
            const checkin= this.dataset.currentCheckin || '';
            const remark = this.dataset.currentRemark || '';

            document.getElementById('quMemberId').value = mId;
            document.getElementById('quMemberName').textContent = mName;
            document.getElementById('quStatus').value = (status === 'unmarked' || !status) ? 'Present' : status;
            document.getElementById('quCheckIn').value = checkin;
            document.getElementById('quRemark').value = remark;

            quickModal.classList.add('open');
        });
    });

    if (quickSave) {
        quickSave.addEventListener('click', async function () {
            const eventId  = document.getElementById('quEventId')?.value;
            const memberId = document.getElementById('quMemberId')?.value;
            const status   = document.getElementById('quStatus')?.value;
            const checkIn  = document.getElementById('quCheckIn')?.value;
            const remark   = document.getElementById('quRemark')?.value;

            const formData = new FormData();
            formData.append('csrf_token', CSRF_TOKEN);
            formData.append('mode', 'single');
            formData.append('event_id', eventId);
            formData.append('member_id', memberId);
            formData.append('status', status);
            if (checkIn) formData.append('check_in_time', checkIn);
            if (remark)  formData.append('remark', remark);

            quickSave.disabled = true;
            quickSave.textContent = 'Updating…';

            try {
                const res  = await fetch(`${ROOT}/attendance/save`, {
                    method: 'POST',
                    body: formData,
                });
                const data = await res.json();

                if (data.success) {
                    showToast('Member attendance updated successfully.', 'success');
                    closeQuickModal();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    alert(data.error || 'Failed to update attendance.');
                }
            } catch (err) {
                alert('Network error. Please try again.');
            } finally {
                quickSave.disabled = false;
                quickSave.textContent = 'Save Update';
            }
        });
    }

    // Close modals on Escape key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeModal();
            closeQuickModal();
        }
    });

})();
