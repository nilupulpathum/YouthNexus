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
            return '<p><strong>Target Audience:</strong> All clubs in the division</p>';
        }
        if (!targets || !targets.length) return '<p><strong>Target Audience:</strong> —</p>';
        const rows = targets.map(t =>
            '<li>' + escapeHtml(t.target_club_name) +
            (t.max_attendance ? ' (max ' + t.max_attendance + ')' : '') +
            '</li>'
        ).join('');
        return '<p><strong>Target Audience:</strong></p><ul>' + rows + '</ul>';
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
                modalBody.innerHTML =
                    '<p><strong>Club:</strong> ' + escapeHtml(ev.organizer_club_name) + ' (' + escapeHtml(ev.organizer_club_code) + ')</p>' +
                    '<p><strong>Event Type:</strong> ' + escapeHtml(ev.event_type) + '</p>' +
                    '<p><strong>When:</strong> ' + escapeHtml(ev.start_datetime) + ' – ' + escapeHtml(ev.end_datetime) + '</p>' +
                    '<p><strong>Location:</strong> ' + escapeHtml(ev.location) + '</p>' +
                    '<p><strong>Max Attendees:</strong> ' + escapeHtml(String(ev.max_attendance ?? '—')) + '</p>' +
                    renderTargetSummary(ev.target_scope, targets) +
                    '<p><strong>Description:</strong><br>' + escapeHtml(ev.description) + '</p>' +
                    '<p><strong>Submitted by:</strong> ' + escapeHtml(ev.creator_name) + ' (' + escapeHtml(ev.creator_role) + ')</p>';

                // Hide decision panel if already Approved or Rejected
                const decisionPanel = document.querySelector('.ea-decision-panel');
                if (ev.status === 'Approved' || ev.status === 'Rejected') {
                    if (decisionPanel) decisionPanel.style.display = 'none';
                    modalBody.innerHTML += '<p><strong>Status:</strong> ' + escapeHtml(ev.status) + '</p>';
                    if (ev.status === 'Approved' && ev.approver_name) {
                        modalBody.innerHTML += '<p><strong>Approved by:</strong> ' + escapeHtml(ev.approver_name) + '</p>';
                    }
                    if (ev.status === 'Rejected') {
                        if (ev.approver_name) {
                            modalBody.innerHTML += '<p><strong>Rejected by:</strong> ' + escapeHtml(ev.approver_name) + '</p>';
                        }
                        if (ev.rejection_remarks) {
                            modalBody.innerHTML += '<p><strong>Rejection Remarks:</strong><br>' + escapeHtml(ev.rejection_remarks) + '</p>';
                        }
                    }
                } else {
                    if (decisionPanel) decisionPanel.style.display = 'block';
                }

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
                        '<button type="button" class="ea-btn ea-btn-primary ea-btn-view-details" data-event-id="' + ev.event_id + '">View Details</button>' +
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
                        '<button type="button" class="ea-btn ea-btn-primary ea-btn-view-details" data-event-id="' + ev.event_id + '">View Details</button>' +
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
