(function () {
    const modal        = document.getElementById('eaReviewModal');
    const modalBody     = document.getElementById('eaModalBody');
    const modalTitle    = document.getElementById('eaModalEventTitle');
    const closeBtn       = document.getElementById('eaModalClose');
    const cancelBtn        = document.getElementById('eaCancelReviewBtn');
    const resultSelect       = document.getElementById('eaReviewResultSelect');
    const remarksField          = document.getElementById('eaRemarks');
    const impactAlert              = document.getElementById('eaImpactAlert');
    const confirmBtn                  = document.getElementById('eaConfirmSubmitBtn');

    const grid = document.getElementById('eaPendingList');
    let pendingGridHtml = grid ? grid.innerHTML : null;

    let activeEventId = null;
    let approvedEventsCache = null;
    let rejectedEventsCache = null;

    // Filters elements
    const searchInput      = document.getElementById('eaSearchInput');
    const filterBtn        = document.getElementById('eaFilterBtn');
    const filterPanel      = document.getElementById('eaFilterPanel');
    const filterType       = document.getElementById('eaFilterType');
    const filterDateFrom   = document.getElementById('eaFilterDateFrom');
    const filterDateTo     = document.getElementById('eaFilterDateTo');
    const clearFilterBtn   = document.getElementById('eaClearFilterBtn');
    const addFilterBtn     = document.getElementById('eaAddFilterBtn');
    const filterCountEl    = document.getElementById('eaFilterCount');

    function escapeHtml(s) {
        return (s ?? '').toString()
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderTargetSummary(targetScope, targets) {
        if (targetScope === 'AllInScope') {
            return '<span style="display:inline-flex; align-items:center; gap:4px; font-weight:600; color:var(--db-sidebar-bg);">' +
                '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' +
                'All Clubs in Division</span>';
        }
        if (!targets || !targets.length) return '<em style="color:#9ca3af;">No clubs targeted</em>';
        const rows = targets.map(t =>
            '<li style="margin-bottom:4px; font-size:12.5px;">' +
            '<span>' + escapeHtml(t.target_club_name) + '</span>' +
            ' <small style="color:#9ca3af; font-family: monospace;">' + escapeHtml(t.target_club_code) + '</small>' +
            (t.max_attendance ? ' <span style="background:#f1f5f9; padding: 1px 6px; border-radius: 4px; font-size:11px; margin-left: 6px; font-weight:500; font-family:monospace;">Max: ' + t.max_attendance + '</span>' : '') +
            '</li>'
        ).join('');
        return '<ul style="margin: 0; padding-left: 16px;">' + rows + '</ul>';
    }

    function openReview(eventId) {
        activeEventId = eventId;
        modalTitle.textContent = 'Loading...';
        modalBody.innerHTML = '<p>Loading event details...</p>';
        modal.classList.add('open');
        
        fetch((window.ROOT || '') + '/eventapproval/review/' + eventId)
            .then(response => {
                if (!response.ok) throw new Error('Failed to load event details.');
                return response.json();
            })
            .then(data => {
                if (data.error) throw new Error(data.error);
                const ev = data.event;
                const targets = data.targets;
                
                modalTitle.textContent = ev.title;
                let bodyHtml =
                    '<div class="ea-info-groups">' +
                        
                        // Group 1: Schedule & Location
                        '<div class="ea-info-group">' +
                            '<h4 class="ea-info-group-title">' +
                                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--db-sidebar-bg);"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>' +
                                ' Schedule & Location' +
                            '</h4>' +
                            '<div class="ea-fields-grid">' +
                                '<div class="ea-field-item">' +
                                    '<span class="ea-field-label">' +
                                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>' +
                                        ' When' +
                                    '</span>' +
                                    '<span class="ea-field-value">' + escapeHtml(ev.start_datetime) + ' – ' + escapeHtml(ev.end_datetime) + '</span>' +
                                '</div>' +
                                '<div class="ea-field-item">' +
                                    '<span class="ea-field-label">' +
                                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
                                        ' Location / Venue' +
                                    '</span>' +
                                    '<span class="ea-field-value">' + escapeHtml(ev.location) + '</span>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +

                        // Group 2: Audience & Capacity
                        '<div class="ea-info-group">' +
                            '<h4 class="ea-info-group-title">' +
                                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--db-sidebar-bg);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' +
                                ' Audience & Capacity' +
                            '</h4>' +
                            '<div class="ea-fields-grid">' +
                                '<div class="ea-field-item">' +
                                    '<span class="ea-field-label">' +
                                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>' +
                                        ' Target Audience' +
                                    '</span>' +
                                    '<span class="ea-field-value">' + renderTargetSummary(ev.target_scope, targets) + '</span>' +
                                '</div>' +
                                '<div class="ea-field-item">' +
                                    '<span class="ea-field-label">' +
                                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m4.9 19.1 14.2-14.2"/><path d="m14.2 19.1 4.9-4.9"/></svg>' +
                                        ' Max Attendees' +
                                    '</span>' +
                                    '<span class="ea-field-value">' + escapeHtml(String(ev.max_attendance ?? 'Unlimited')) + '</span>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +

                        // Group 3: Submitting Club & Creator
                        '<div class="ea-info-group">' +
                            '<h4 class="ea-info-group-title">' +
                                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--db-sidebar-bg);"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16"/><path d="M12 11h.01"/><path d="M12 7h.01"/><path d="M8 11h.01"/><path d="M8 7h.01"/></svg>' +
                                ' Organizers & Submitter' +
                            '</h4>' +
                            '<div class="ea-fields-grid">' +
                                '<div class="ea-field-item">' +
                                    '<span class="ea-field-label">' +
                                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16"/><path d="M12 11h.01"/><path d="M12 7h.01"/></svg>' +
                                        ' Club' +
                                    '</span>' +
                                    '<span class="ea-field-value">' + escapeHtml(ev.organizer_club_name) + ' (<small style="color:#9ca3af; font-family: monospace;">' + escapeHtml(ev.organizer_club_code) + '</small>)</span>' +
                                '</div>' +
                                '<div class="ea-field-item">' +
                                    '<span class="ea-field-label">' +
                                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>' +
                                        ' Event Type' +
                                    '</span>' +
                                    '<span class="ea-field-value">' + escapeHtml(ev.event_type || 'General') + '</span>' +
                                '</div>' +
                                '<div class="ea-field-item">' +
                                    '<span class="ea-field-label">' +
                                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' +
                                        ' Submitted by' +
                                    '</span>' +
                                    '<span class="ea-field-value">' + escapeHtml(ev.creator_name) + ' (<small style="color:#6b7280;">' + escapeHtml(ev.creator_role) + '</small>)</span>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +

                        // Group 4: Description
                        '<div class="ea-info-group">' +
                            '<h4 class="ea-info-group-title">' +
                                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--db-sidebar-bg);"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>' +
                                ' Description & Objectives' +
                            '</h4>' +
                            '<div style="background:#fff; border:1px solid var(--db-border, #e7e9f0); border-radius:8px; padding:12px; font-size:13px; line-height:1.5; color:#334155;">' +
                                escapeHtml(ev.description).replace(/\n/g, '<br>') +
                            '</div>' +
                        '</div>' +

                    '</div>';

                // Hide decision panel if already Approved or Rejected
                const decisionPanel = document.querySelector('.ea-decision-panel');
                if (ev.status === 'Approved' || ev.status === 'Rejected') {
                    if (decisionPanel) decisionPanel.style.display = 'none';
                    
                    let statusHtml = '';
                    if (ev.status === 'Approved') {
                        statusHtml =
                            '<div class="ea-decision-impact-alert approve" style="margin-top: 16px; display: flex; align-items: flex-start; gap: 10px;">' +
                                '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' +
                                '<div>' +
                                    '<strong style="display:block; font-size:13px; margin-bottom:4px;">EVENT APPROVED</strong>' +
                                    '<span style="font-size:12.5px;">Approved by <strong>' + escapeHtml(ev.approver_name || 'Zonal Coordinator') + '</strong></span>' +
                                '</div>' +
                            '</div>';
                    } else if (ev.status === 'Rejected') {
                        statusHtml =
                            '<div class="ea-decision-impact-alert reject" style="margin-top: 16px; display: flex; align-items: flex-start; gap: 10px;">' +
                                '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' +
                                '<div>' +
                                    '<strong style="display:block; font-size:13px; margin-bottom:4px;">EVENT REJECTED</strong>' +
                                    '<span style="font-size:12.5px; display:block; margin-bottom:4px;">Rejected by <strong>' + escapeHtml(ev.approver_name || 'Zonal Coordinator') + '</strong></span>' +
                                    (ev.rejection_remarks ? '<div style="margin-top:6px; padding-top:6px; border-top:1px dashed rgba(153,27,27,0.2); font-size:12px;"><strong>Remarks:</strong> ' + escapeHtml(ev.rejection_remarks) + '</div>' : '') +
                                '</div>' +
                            '</div>';
                    }
                    bodyHtml += statusHtml;
                } else {
                    if (decisionPanel) decisionPanel.style.display = 'block';
                }
                modalBody.innerHTML = bodyHtml;

                resultSelect.value = 'approve';
                remarksField.value = '';
                updateImpactAlert();
            })
            .catch(err => {
                modalBody.innerHTML = '<p style="color:red;">Error: ' + escapeHtml(err.message) + '</p>';
            });
    }

    function closeReview() {
        modal.classList.remove('open');
        activeEventId = null;
    }

    function updateImpactAlert() {
        const isReject = resultSelect.value === 'reject';
        impactAlert.classList.toggle('approve', !isReject);
        impactAlert.classList.toggle('reject', isReject);
        impactAlert.querySelector('p').textContent = isReject
            ? 'Rejecting this event will notify the submitting club with your remarks. The event will not be published to the division calendar.'
            : "Approving this event will publish it to the division's event calendar and notify the submitting club. This event will then be visible to the Divisional Secretary and eligible for attendance tracking once it occurs.";
    }

    resultSelect.addEventListener('change', updateImpactAlert);

    // Initial binding for review buttons
    function bindReviewButtons() {
        if (!grid) return;
        grid.querySelectorAll('.ea-btn-review').forEach(btn => {
            btn.addEventListener('click', () => openReview(btn.dataset.eventId));
        });
    }
    bindReviewButtons();

    closeBtn.addEventListener('click', closeReview);
    cancelBtn.addEventListener('click', closeReview);

    // Notification Dropdown Interaction
    const notifBellBtn = document.getElementById('notifBellBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifDropdownLink = document.getElementById('notifDropdownLink');
    const statPending = document.getElementById('statPending');

    if (notifBellBtn && notifDropdown) {
        notifBellBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isHidden = notifDropdown.style.display === 'none';
            notifDropdown.style.display = isHidden ? 'block' : 'none';
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function () {
            notifDropdown.style.display = 'none';
        });
        notifDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    if (notifDropdownLink && statPending) {
        notifDropdownLink.addEventListener('click', function (e) {
            e.preventDefault();
            notifDropdown.style.display = 'none';
            statPending.click(); // Focus/filter pending events
        });
    }

    confirmBtn.addEventListener('click', function () {
        if (!activeEventId) return;
        
        const decision = resultSelect.value; // 'approve' | 'reject'
        const remarks   = remarksField.value.trim();

        if (decision === 'reject' && !remarks) {
            alert('Please provide remarks explaining the rejection.');
            return;
        }

        fetch((window.ROOT || '') + '/eventapproval/' + decision + '/' + activeEventId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ csrf_token: window.CSRF_TOKEN, remarks: remarks })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeReview();
                location.reload(); // matches ClubRegistrationApproval's reload-after-decision pattern
            } else {
                alert(data.error || 'Something went wrong. Please try again.');
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
        });
    });

    // ---------------------------------------------------------------
    // Client-side filtering logic
    // ---------------------------------------------------------------
    function filterCards() {
        if (!grid) return;
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const type  = filterType ? filterType.value : '';
        const dateFrom = filterDateFrom ? filterDateFrom.value : '';
        const dateTo   = filterDateTo ? filterDateTo.value : '';

        const cards = grid.querySelectorAll('.ea-card');
        let visibleCount = 0;
        let activeFilters = 0;

        if (query) activeFilters++;
        if (type) activeFilters++;
        if (dateFrom) activeFilters++;
        if (dateTo) activeFilters++;

        if (filterCountEl) {
            if (activeFilters > 0) {
                filterCountEl.textContent = activeFilters;
                filterCountEl.style.display = 'inline-flex';
            } else {
                filterCountEl.style.display = 'none';
            }
        }

        cards.forEach(function (card) {
            const textMatch = !query || 
                            (card.dataset.title || '').indexOf(query) !== -1 || 
                            (card.dataset.club || '').indexOf(query) !== -1;
            const typeMatch = !type || (card.dataset.type || '') === type;
            
            const eventDate = card.dataset.start ? card.dataset.start.substring(0, 10) : '';
            const dateFromMatch = !dateFrom || eventDate >= dateFrom;
            const dateToMatch = !dateTo || eventDate <= dateTo;

            const isVisible = textMatch && typeMatch && dateFromMatch && dateToMatch;
            card.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        // Feedback message if no cards match
        const noMatchEl = grid.querySelector('#eaNoFilterMatch');
        if (cards.length > 0) {
            if (visibleCount === 0) {
                if (!noMatchEl) {
                    const msg = document.createElement('div');
                    msg.id = 'eaNoFilterMatch';
                    msg.className = 'ea-empty-state ea-empty';
                    msg.style.gridColumn = '1 / -1';
                    msg.innerHTML =
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="40" height="40" style="margin-bottom:12px;opacity:0.4;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>' +
                        '<p>No events match your search/filters.</p>';
                    grid.appendChild(msg);
                }
            } else if (noMatchEl) {
                noMatchEl.remove();
            }
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterCards);

    if (filterBtn && filterPanel) {
        filterBtn.addEventListener('click', function () {
            const isOpen = filterPanel.classList.toggle('open');
            filterBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    if (addFilterBtn) {
        addFilterBtn.addEventListener('click', filterCards);
    }

    if (clearFilterBtn) {
        clearFilterBtn.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            if (filterType) filterType.value = '';
            if (filterDateFrom) filterDateFrom.value = '';
            if (filterDateTo) filterDateTo.value = '';
            filterCards();
        });
    }

    // ---------------------------------------------------------------
    // Tab switching and cache management
    // ---------------------------------------------------------------
    const statApproved = document.getElementById('statApproved');
    const statRejected = document.getElementById('statRejected');

    function setActiveStat(button) {
        [statPending, statApproved, statRejected].forEach(function (b) { if (b) b.classList.remove('is-active'); });
        if (button) button.classList.add('is-active');
    }

    function renderApprovedGrid(events) {
        if (!grid) return;
        if (!events || events.length === 0) {
            grid.innerHTML =
                '<div class="ea-empty-state" style="grid-column: 1 / -1;">' +
                    '<p>No approved club events found in this division.</p>' +
                '</div>';
            return;
        }

        let html = '';
        events.forEach(function (ev) {
            html +=
                '<div class="ea-card" data-event-id="' + ev.event_id + '" data-title="' + escapeHtml((ev.title || '').toLowerCase()) + '" data-club="' + escapeHtml((ev.club_name || '').toLowerCase()) + '" data-type="' + escapeHtml(ev.event_type || '') + '" data-start="' + escapeHtml(ev.start_datetime || '') + '">' +
                    '<div class="ea-card-top">' +
                        '<span class="ea-badge club">Club Event</span>' +
                        '<span class="ea-badge approved" style="background:#d1fae5; color:#065f46;">Approved</span>' +
                    '</div>' +
                    '<h3 class="ea-card-title">' + escapeHtml(ev.title) + '</h3>' +
                    '<p class="ea-card-club">' +
                        escapeHtml(ev.club_name ?? '') +
                        ' <small class="ea-club-code">' + escapeHtml(ev.club_code ?? '') + '</small>' +
                    '</p>' +
                    '<div class="ea-card-meta">' +
                        '<span>' + escapeHtml(new Date(ev.start_datetime).toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })) + '</span>' +
                        '<span>' + escapeHtml(ev.location ?? '—') + '</span>' +
                    '</div>' +
                    '<div class="ea-card-date" style="font-size: 11px; color: #16a34a; font-weight: 600; margin-top: 4px;">' +
                        'APPROVED' + (ev.approver_name ? ' BY ' + escapeHtml(ev.approver_name.toUpperCase()) : '') +
                    '</div>' +
                    '<div class="ea-card-footer">' +
                        '<span class="ea-card-submitter">Submitted by ' + escapeHtml(ev.creator_name ?? '—') + ' (' + escapeHtml(ev.creator_role ?? '—') + ')</span>' +
                        '<button type="button" class="ea-btn-view ea-btn-view-details" data-event-id="' + ev.event_id + '">View Details <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></button>' +
                    '</div>' +
                '</div>';
        });
        grid.innerHTML = html;
        
        grid.querySelectorAll('.ea-btn-view-details').forEach(btn => {
            btn.addEventListener('click', () => openReview(btn.dataset.eventId));
        });
    }

    function renderRejectedGrid(events) {
        if (!grid) return;
        if (!events || events.length === 0) {
            grid.innerHTML =
                '<div class="ea-empty-state" style="grid-column: 1 / -1;">' +
                    '<p>No rejected club events found in this division.</p>' +
                '</div>';
            return;
        }

        let html = '';
        events.forEach(function (ev) {
            html +=
                '<div class="ea-card" data-event-id="' + ev.event_id + '" data-title="' + escapeHtml((ev.title || '').toLowerCase()) + '" data-club="' + escapeHtml((ev.club_name || '').toLowerCase()) + '" data-type="' + escapeHtml(ev.event_type || '') + '" data-start="' + escapeHtml(ev.start_datetime || '') + '">' +
                    '<div class="ea-card-top">' +
                        '<span class="ea-badge club">Club Event</span>' +
                        '<span class="ea-badge rejected" style="background:#fee2e2; color:#b91c1c;">Rejected</span>' +
                    '</div>' +
                    '<h3 class="ea-card-title">' + escapeHtml(ev.title) + '</h3>' +
                    '<p class="ea-card-club">' +
                        escapeHtml(ev.club_name ?? '') +
                        ' <small class="ea-club-code">' + escapeHtml(ev.club_code ?? '') + '</small>' +
                    '</p>' +
                    '<div class="ea-card-meta">' +
                        '<span>' + escapeHtml(new Date(ev.start_datetime).toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })) + '</span>' +
                        '<span>' + escapeHtml(ev.location ?? '—') + '</span>' +
                    '</div>' +
                    '<div class="ea-card-date" style="font-size: 11px; color: #dc2626; font-weight: 600; margin-top: 4px;">' +
                        'REJECTED' + (ev.approver_name ? ' BY ' + escapeHtml(ev.approver_name.toUpperCase()) : '') + (ev.rejection_remarks ? ' — ' + escapeHtml(ev.rejection_remarks) : '') +
                    '</div>' +
                    '<div class="ea-card-footer">' +
                        '<span class="ea-card-submitter">Submitted by ' + escapeHtml(ev.creator_name ?? '—') + ' (' + escapeHtml(ev.creator_role ?? '—') + ')</span>' +
                        '<button type="button" class="ea-btn-view ea-btn-view-details" data-event-id="' + ev.event_id + '">View Details <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></button>' +
                    '</div>' +
                '</div>';
        });
        grid.innerHTML = html;

        grid.querySelectorAll('.ea-btn-view-details').forEach(btn => {
            btn.addEventListener('click', () => openReview(btn.dataset.eventId));
        });
    }

    if (statPending) {
        statPending.addEventListener('click', function () {
            setActiveStat(statPending);
            if (pendingGridHtml !== null && grid) {
                grid.innerHTML = pendingGridHtml;
                bindReviewButtons();
                filterCards();
            }
        });
    }

    if (statApproved) {
        statApproved.addEventListener('click', function () {
            setActiveStat(statApproved);
            if (pendingGridHtml === null && grid) {
                pendingGridHtml = grid.innerHTML;
            }

            if (approvedEventsCache !== null) {
                renderApprovedGrid(approvedEventsCache);
                filterCards();
                return;
            }

            grid.innerHTML = '<p style="grid-column: 1 / -1; padding: 20px; color: #6b7280; text-align: center;">Loading approved events…</p>';
            fetch((window.ROOT || '') + '/eventapproval/approved', { credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    approvedEventsCache = data.events || [];
                    renderApprovedGrid(approvedEventsCache);
                    filterCards();
                })
                .catch(function () {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; padding: 20px; color: #b91c1c; text-align: center;">Failed to load approved events.</p>';
                });
        });
    }

    if (statRejected) {
        statRejected.addEventListener('click', function () {
            setActiveStat(statRejected);
            if (pendingGridHtml === null && grid) {
                pendingGridHtml = grid.innerHTML;
            }

            if (rejectedEventsCache !== null) {
                renderRejectedGrid(rejectedEventsCache);
                filterCards();
                return;
            }

            grid.innerHTML = '<p style="grid-column: 1 / -1; padding: 20px; color: #6b7280; text-align: center;">Loading rejected events…</p>';
            fetch((window.ROOT || '') + '/eventapproval/rejected', { credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    rejectedEventsCache = data.events || [];
                    renderRejectedGrid(rejectedEventsCache);
                    filterCards();
                })
                .catch(function () {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; padding: 20px; color: #b91c1c; text-align: center;">Failed to load rejected events.</p>';
                });
        });
    }
})();
