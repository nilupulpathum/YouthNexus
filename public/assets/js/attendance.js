/* =====================================================================
   attendance.js — Attendance Governance & Management
   Supports NYSC Administrator (Cascading Filters & National Scope)
   and Divisional Secretary.
   ===================================================================== */

(function () {
    'use strict';

    const pageConfig = document.getElementById('attendanceConfig');
    const ROOT       = pageConfig?.dataset.root || '';
    const CSRF_TOKEN = document.getElementById('csrfToken')?.value || '';
    const isNYSCAdmin = pageConfig?.dataset.nyscAdmin === 'true';

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
    const filterCount = document.getElementById('amFilterCount');
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

    // --- Client-side search and divisional filters -------------------
    const searchInput  = document.getElementById('amSearchInput');
    const typeFilter   = document.getElementById('amFilterType');
    const scopeFilter  = document.getElementById('amFilterScope');
    const applyFilters = document.getElementById('amApplyFilterBtn');
    const clearFilters = document.getElementById('amClearFilterBtn');
    const cardGrid     = document.getElementById('amCardGrid');

    function setFilterCount(count) {
        if (!filterCount) return;
        filterCount.textContent = String(count);
        filterCount.classList.toggle('hidden', count === 0);
    }

    function getClientFilterCount() {
        let count = 0;
        if (typeFilter?.value) count++;
        if (scopeFilter?.value) count++;
        return count;
    }

    function getEmptyMessage() {
        let emptyMessage = document.getElementById('amFilterEmpty');
        if (emptyMessage || !cardGrid) return emptyMessage;

        emptyMessage = document.createElement('div');
        emptyMessage.id = 'amFilterEmpty';
        emptyMessage.className = 'am-empty-state';

        const message = document.createElement('p');
        message.textContent = 'No events match the current search and filters.';
        emptyMessage.appendChild(message);
        cardGrid.appendChild(emptyMessage);

        return emptyMessage;
    }

    function filterEventCards() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const selectedType = isNYSCAdmin
            ? ''
            : (typeFilter?.value || '').toLowerCase();
        const selectedScope = isNYSCAdmin
            ? ''
            : (scopeFilter?.value || '').toLowerCase();
        const cards = document.querySelectorAll('#amCardGrid .am-card');
        let visible = 0;

        cards.forEach(card => {
            const searchableText = card.dataset.search || '';
            const eventType = card.dataset.type || '';
            const eventScope = card.dataset.scope || '';
            const matchesQuery = !query || searchableText.includes(query);
            const matchesType = !selectedType || eventType === selectedType;
            const matchesScope = !selectedScope || eventScope === selectedScope;
            const matches = matchesQuery && matchesType && matchesScope;

            card.style.display = matches ? '' : 'none';
            if (matches) visible++;
        });

        const emptyMessage = getEmptyMessage();
        if (emptyMessage) {
            emptyMessage.style.display = visible === 0 ? '' : 'none';
        }
    }

    searchInput?.addEventListener('input', filterEventCards);

    if (!isNYSCAdmin) {
        applyFilters?.addEventListener('click', () => {
            filterEventCards();
            setFilterCount(getClientFilterCount());
        });

        clearFilters?.addEventListener('click', () => {
            if (typeFilter) typeFilter.value = '';
            if (scopeFilter) scopeFilter.value = '';
            setFilterCount(0);
            filterEventCards();
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
        document.body.style.overflow = '';
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
            document.body.style.overflow = 'hidden';
        });
    });

    quickModal?.addEventListener('click', event => {
        if (event.target === quickModal) closeQuickModal();
    });

    function updateDetailSummary() {
        const rows = Array.from(document.querySelectorAll('.am-roster-row'));
        if (!rows.length) return;

        const present = rows.filter(row => (row.dataset.status || '').toLowerCase() === 'present').length;
        const absent = rows.filter(row => (row.dataset.status || '').toLowerCase() === 'absent').length;
        const rate = Math.round((present / rows.length) * 100);

        const presentCount = document.getElementById('amPresentCount');
        const absentCount = document.getElementById('amAbsentCount');
        const attendanceRate = document.getElementById('amAttendanceRate');

        if (presentCount) presentCount.textContent = String(present);
        if (absentCount) absentCount.textContent = String(absent);
        if (attendanceRate) attendanceRate.textContent = `${rate}%`;
    }

    function currentLocalDateTimeValue() {
        const now = new Date();
        const localTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
        return localTime.toISOString().slice(0, 16);
    }

    function updateRosterRow(memberId, status, checkIn, remark) {
        const row = Array.from(document.querySelectorAll('.am-roster-row'))
            .find(candidate => candidate.dataset.memberId === String(memberId));
        if (!row) return;

        row.dataset.status = status;

        const statusCell = row.cells[2];
        if (statusCell) {
            const badge = document.createElement('span');
            const normalizedStatus = status.toLowerCase();

            badge.className = `am-status-badge ${normalizedStatus}`;
            badge.textContent = normalizedStatus === 'present' ? 'Present' : 'Absent';
            statusCell.replaceChildren(badge);
        }

        const checkInCell = row.cells[3];
        if (checkInCell) {
            const normalizedCheckIn = status === 'Present' ? checkIn : '';
            checkInCell.textContent = normalizedCheckIn
                ? normalizedCheckIn.split('T')[1]?.slice(0, 5) || normalizedCheckIn.slice(0, 5)
                : '—';
        }

        const remarkCell = row.cells[4];
        if (remarkCell) remarkCell.textContent = remark.trim() || '—';

        const recordedByCell = row.cells[5];
        if (recordedByCell) {
            const user = { name: pageConfig?.dataset.userName, role: pageConfig?.dataset.userRole };
            const recorderName = document.createTextNode(user.name || 'Current user');
            recordedByCell.replaceChildren(recorderName);

            if (user.role) {
                const role = document.createElement('small');
                role.className = 'am-recorder-role';
                role.textContent = ` (${user.role})`;
                recordedByCell.appendChild(role);
            }

            const timestamp = document.createElement('div');
            timestamp.className = 'am-recorded-at';
            timestamp.textContent = 'Updated just now';
            recordedByCell.appendChild(timestamp);
        }

        const updateButton = row.querySelector('.am-btn-quick-update');
        if (updateButton) {
            updateButton.dataset.currentStatus = status;
            updateButton.dataset.currentCheckin = status === 'Present' ? checkIn : '';
            updateButton.dataset.currentRemark = remark;
        }

        updateDetailSummary();
        filterRosterRows();
    }

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
                    const effectiveCheckIn = status === 'Present'
                        ? (checkIn || currentLocalDateTimeValue())
                        : '';
                    updateRosterRow(memberId, status, effectiveCheckIn, remark);
                    showToast('Member attendance updated successfully.', 'success');
                    closeQuickModal();
                } else {
                    showToast(data.error || 'Failed to update attendance.', 'error');
                }
            } catch (err) {
                showToast('Network error. Please try again.', 'error');
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
