/**
 * Event Approval Javascript
 * Divisional Coordinator Dashboard
 */
(function () {
    const pageConfig = document.getElementById('eaPageConfig');
    const rootUrl = pageConfig?.dataset.root || '';
    const csrfToken = pageConfig?.dataset.csrfToken || '';
    const icons = {
        user: document.getElementById('eaIconUser')?.innerHTML || '',
        calendar: document.getElementById('eaIconCalendar')?.innerHTML || '',
        pin: document.getElementById('eaIconPin')?.innerHTML || ''
    };
    const modal            = document.getElementById('eaReviewModal');
    const modalBody        = document.getElementById('eaModalBody');
    const modalTitle       = document.getElementById('eaModalEventTitle');
    const closeBtn         = document.getElementById('eaModalClose');
    const backBtn          = document.getElementById('eaBackToEventsBtn');
    const cancelBtn        = document.getElementById('eaCancelReviewBtn');
    const resultSelect     = document.getElementById('eaReviewResultSelect');
    const remarksField     = document.getElementById('eaRemarks');
    const impactAlert      = document.getElementById('eaImpactAlert');
    const confirmBtn       = document.getElementById('eaConfirmSubmitBtn');
    const decisionPanel    = modal.querySelector('.ea-decision-panel');

    let activeEventId = null;
    let activeEventStatus = null;

    function escapeHtml(s) {
        return (s ?? '').toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatDateStr(dateStr) {
        if (!dateStr) return '—';
        try {
            const d = new Date(dateStr.replace(/-/g, '/'));
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' at ' +
                   d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        } catch (e) {
            return dateStr;
        }
    }

    function formatTimeRange(startStr, endStr) {
        if (!startStr) return '—';
        try {
            const dStart = new Date(startStr.replace(/-/g, '/'));
            const dEnd   = endStr ? new Date(endStr.replace(/-/g, '/')) : null;
            if (isNaN(dStart.getTime())) return startStr + (endStr ? ' to ' + endStr : '');

            const datePart = dStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const startTime = dStart.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

            if (dEnd && !isNaN(dEnd.getTime())) {
                const isSameDay = dStart.toDateString() === dEnd.toDateString();
                const endTime = dEnd.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                if (isSameDay) {
                    return datePart + ', ' + startTime + ' – ' + endTime;
                } else {
                    const endDatePart = dEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    return datePart + ' ' + startTime + ' – ' + endDatePart + ' ' + endTime;
                }
            }
            return datePart + ' at ' + startTime;
        } catch (e) {
            return startStr;
        }
    }

    function renderTargetSummary(targetScope, targets) {
        if (targetScope === 'AllInScope') {
            return 'All registered clubs in the division';
        }
        if (!targets || !targets.length) return 'Selected clubs only';
        return targets.map(t => escapeHtml(t.target_club_name) + (t.max_attendance ? ' (max ' + t.max_attendance + ')' : '')).join(', ');
    }

    function openReview(eventId) {
        activeEventId = eventId;
        modalTitle.textContent = 'Loading Event Details...';
        modalBody.innerHTML = '<div class="ea-modal-feedback"><p>Loading event information...</p></div>';
        decisionPanel.hidden = true;
        modal.classList.add('open');
        
        fetch(rootUrl + '/eventapproval/review/' + eventId)
            .then(response => {
                if (!response.ok) throw new Error('Failed to load event details.');
                return response.json();
            })
            .then(data => {
                if (data.error) throw new Error(data.error);
                const ev = data.event;
                const targets = data.targets;
                const isPending = ev.status === 'PendingApproval' || ev.status === 'CancellationPending';
                const isCancellation = ev.status === 'CancellationPending';
                activeEventStatus = ev.status;
                
                modalTitle.textContent = ev.title || 'Event Review';
                decisionPanel.hidden = !isPending;
                
                const isDivisionalEvent = Boolean(ev.organizer_division_id);
                const organizerLabel    = isDivisionalEvent ? 'Organizing Body' : 'Organizing Club';
                const organizerValue    = isDivisionalEvent 
                    ? `Divisional Secretariat <small class="ea-organizer-note">(${escapeHtml(ev.organizer_division_name || 'Division')})</small>`
                    : `${escapeHtml(ev.organizer_club_name || 'Club')} <small class="ea-organizer-note">(${escapeHtml(ev.organizer_club_code || '')})</small>`;

                const timeRangeDisplay = formatTimeRange(ev.start_datetime, ev.end_datetime);
                const targetDisplay    = renderTargetSummary(ev.target_scope, targets);
                const maxAttendees     = ev.max_attendance ? Number(ev.max_attendance).toLocaleString() + ' attendees' : 'No limit set';

                modalBody.innerHTML = `
                    <div class="ea-modal-section">
                        <h4 class="ea-modal-section-title">Event Overview</h4>
                        <div class="ea-detail-grid">
                            <div class="ea-detail-item">
                                <label>${organizerLabel}</label>
                                <span>${organizerValue}</span>
                            </div>
                            <div class="ea-detail-item">
                                <label>Event Category / Type</label>
                                <span>${escapeHtml(ev.event_type || 'General')}</span>
                            </div>
                            <div class="ea-detail-item full-width">
                                <label>Date &amp; Schedule</label>
                                <span>${escapeHtml(timeRangeDisplay)}</span>
                            </div>
                            <div class="ea-detail-item">
                                <label>Location / Venue</label>
                                <span>${escapeHtml(ev.location || 'Not specified')}</span>
                            </div>
                            <div class="ea-detail-item">
                                <label>Expected Attendance</label>
                                <span>${escapeHtml(maxAttendees)}</span>
                            </div>
                            <div class="ea-detail-item full-width">
                                <label>Target Scope</label>
                                <span>${escapeHtml(targetDisplay)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="ea-modal-section">
                        <h4 class="ea-modal-section-title">Event Description</h4>
                        <div class="ea-description-box">
                            ${escapeHtml(ev.description || 'No description provided by the club.').replace(/\n/g, '<br>')}
                        </div>
                    </div>

                    <div class="ea-modal-section">
                        <div class="ea-submitter-badge-bar">
                            ${icons.user}
                            <span>Submitted by <strong>${escapeHtml(ev.creator_name || 'Club Officer')}</strong>, role: <strong>${escapeHtml(ev.creator_role || 'Club Leader')}</strong></span>
                        </div>
                    </div>
                `;

                resultSelect.value = 'approve';
                resultSelect.options[0].textContent = isCancellation ? 'Approve Cancellation' : 'Approve Event';
                resultSelect.options[1].textContent = isCancellation ? 'Keep Event Approved' : 'Reject Event';
                remarksField.value = '';
                updateImpactAlert();
            })
            .catch(err => {
                modalBody.innerHTML = '<div class="ea-modal-feedback ea-modal-feedback--error"><p>Error: ' + escapeHtml(err.message) + '</p></div>';
            });
    }

    function closeReview() {
        modal.classList.remove('open');
        activeEventId = null;
        activeEventStatus = null;
    }

    function updateImpactAlert() {
        const isReject = resultSelect.value === 'reject';
        impactAlert.classList.toggle('approve', !isReject);
        impactAlert.classList.toggle('reject', isReject);
        
        const titleEl = impactAlert.querySelector('strong');
        const textEl  = impactAlert.querySelector('p');

        if (isReject) {
            titleEl.textContent = 'IMPACT OF REJECTION';
            textEl.textContent = 'Rejecting this event will notify the submitting club with your official remarks. The event will remain unapproved and will not be published to the division calendar.';
            confirmBtn.classList.add('is-reject');
            confirmBtn.textContent = 'Confirm & Reject Event';
        } else {
            titleEl.textContent = 'IMPACT OF APPROVAL';
            textEl.textContent = "Approving this event will publish it to the division's event calendar and notify the submitting club. This event will then be visible to the Divisional Secretary and eligible for attendance tracking once it occurs.";
            confirmBtn.classList.remove('is-reject');
            confirmBtn.textContent = 'Confirm & Approve Event';
        }
    }

    resultSelect.addEventListener('change', updateImpactAlert);

    function attachReviewButtons() {
        document.querySelectorAll('.ea-btn-review').forEach(btn => {
            btn.removeEventListener('click', handleReviewClick);
            btn.addEventListener('click', handleReviewClick);
        });
    }

    function handleReviewClick(e) {
        openReview(e.currentTarget.dataset.eventId);
    }

    attachReviewButtons();
    closeBtn.addEventListener('click', closeReview);
    backBtn.addEventListener('click', closeReview);
    cancelBtn.addEventListener('click', closeReview);

    // Filter Tabs
    const statPending  = document.getElementById('statPending');
    const statApproved = document.getElementById('statApproved');
    const statRejected = document.getElementById('statRejected');
    const eaList       = document.getElementById('eaPendingList');
    const searchInput  = document.getElementById('eaSearchInput');
    const filterBtn    = document.getElementById('eaFilterBtn');
    const filterPanel  = document.getElementById('eaFilterPanel');
    const filterLevel  = document.getElementById('eaFilterLevel');
    const dateFrom = document.getElementById('eaFilterDateFrom');
    const dateTo = document.getElementById('eaFilterDateTo');
    const clearFilters = document.getElementById('eaClearFilters');
    const applyFiltersButton = document.getElementById('eaApplyFilters');
    let appliedLevel = filterLevel.value;
    let appliedFrom = dateFrom.value;
    let appliedTo = dateTo.value;
    const filterEmpty  = document.getElementById('eaFilterEmpty');
    let pendingListHtml = null;

    function applyEventFilters() {
        const query = searchInput.value.trim().toLocaleLowerCase();
        const level = appliedLevel;
        const cards = [...eaList.querySelectorAll('.ea-card')];
        let visible = 0;
        cards.forEach(card => {
            const matchesText = card.textContent.toLocaleLowerCase().includes(query);
            const matchesLevel = level === 'all' || Boolean(card.querySelector('.ea-badge.' + level));
            const eventDate = card.dataset.eventDate || '';
            const matchesDate = (!appliedFrom || eventDate >= appliedFrom) && (!appliedTo || eventDate <= appliedTo);
            card.hidden = !(matchesText && matchesLevel && matchesDate);
            if (!card.hidden) visible++;
        });
        filterEmpty.hidden = cards.length === 0 || visible > 0;
        filterEmpty.classList.toggle('is-visible', !filterEmpty.hidden);
    }

    searchInput.addEventListener('input', applyEventFilters);
    applyFiltersButton.addEventListener('click', () => {
        appliedLevel = filterLevel.value;
        appliedFrom = dateFrom.value;
        appliedTo = dateTo.value;
        applyEventFilters();
    });
    filterBtn.addEventListener('click', () => {
        filterPanel.hidden = !filterPanel.hidden;
        filterBtn.setAttribute('aria-expanded', String(!filterPanel.hidden));
    });
    clearFilters.addEventListener('click', () => {
        searchInput.value = '';
        filterLevel.value = 'all';
        dateFrom.value = '';
        dateTo.value = '';
        appliedLevel = 'all';
        appliedFrom = '';
        appliedTo = '';
        applyEventFilters();
        searchInput.focus();
    });

    function setActiveStat(targetCard) {
        [statPending, statApproved, statRejected].forEach(card => {
            if (card) card.classList.remove('is-active');
        });
        if (targetCard) targetCard.classList.add('is-active');
    }

    function renderEventGrid(events, type) {
        if (!eaList) return;
        if (!events || !events.length) {
            eaList.innerHTML = '<div class="ea-empty-state"><p>No ' + type.toLowerCase() + ' events found in this division.</p></div>';
            applyEventFilters();
            return;
        }

        const isApproved = type.toLowerCase() === 'approved';
        const badgeClass = isApproved ? 'approved' : 'rejected';
        const badgeLabel = isApproved ? 'Approved' : 'Rejected';

        eaList.innerHTML = events.map(ev => {
            const dateDisplay = formatDateStr(ev.start_datetime);
            const extraNote   = isApproved 
                ? (ev.approver_name ? 'Approved by ' + escapeHtml(ev.approver_name) : 'Approved') 
                : (ev.rejection_remarks ? 'Reason: ' + escapeHtml(ev.rejection_remarks) : 'Rejected');

            const isDivisionalEvent = Boolean(ev.organizer_division_id);
            const badgeTypeClass    = isDivisionalEvent ? 'division' : 'club';
            const badgeTypeLabel    = isDivisionalEvent ? 'Divisional Event' : 'Club Event';
            const organizerTitle    = isDivisionalEvent 
                ? 'Divisional Secretariat (' + escapeHtml(ev.organizer_division_name || 'Division') + ')'
                : escapeHtml(ev.club_name || ev.organizer_club_name || '');
            const clubCodeDisplay   = (!isDivisionalEvent && (ev.club_code || ev.organizer_club_code)) 
                ? `<small class="ea-club-code">${escapeHtml(ev.club_code || ev.organizer_club_code)}</small>` 
                : '';

            return `
                <div class="ea-card" data-event-id="${ev.event_id}" data-event-date="${escapeHtml(String(ev.start_datetime || '').slice(0, 10))}">
                    <div class="ea-card-top">
                        <span class="ea-badge ${badgeTypeClass}">${badgeTypeLabel}</span>
                        <span class="ea-badge ${badgeClass}">${badgeLabel}</span>
                    </div>
                    <h3 class="ea-card-title">${escapeHtml(ev.title)}</h3>
                    <p class="ea-card-club">
                        ${organizerTitle}
                        ${clubCodeDisplay}
                    </p>
                    <div class="ea-card-meta">
                        <div class="ea-meta-item">
                            ${icons.calendar}
                            <span>${dateDisplay}</span>
                        </div>
                        <div class="ea-meta-item">
                            ${icons.pin}
                            <span>${escapeHtml(ev.location || '—')}</span>
                        </div>
                    </div>
                    <div class="ea-card-footer">
                        <span class="ea-card-submitter">Submitted by ${escapeHtml(ev.creator_name || '—')}, <strong class="ea-card-extra-note">${extraNote}</strong></span>
                        <button type="button" class="ea-btn ea-btn-review db-view-button" data-event-id="${ev.event_id}">View Details</button>
                    </div>
                </div>
            `;
        }).join('');
        applyEventFilters();
    }

    if (statPending) {
        statPending.addEventListener('click', function () {
            setActiveStat(statPending);
            if (pendingListHtml !== null && eaList) {
                eaList.innerHTML = pendingListHtml;
                attachReviewButtons();
            }
            applyEventFilters();
        });
    }

    if (statApproved) {
        statApproved.addEventListener('click', function () {
            setActiveStat(statApproved);
            if (pendingListHtml === null && eaList) {
                pendingListHtml = eaList.innerHTML;
            }
            if (eaList) eaList.innerHTML = '<div class="ea-empty-state"><p>Loading approved events...</p></div>';
            filterEmpty.hidden = true;
            filterEmpty.classList.remove('is-visible');
            fetch(rootUrl + '/eventapproval/approved')
                .then(r => r.json())
                .then(data => {
                    if (!statApproved.classList.contains('is-active')) return;
                    renderEventGrid(data.events || [], 'Approved');
                    attachReviewButtons();
                })
                .catch(err => {
                    if (!statApproved.classList.contains('is-active')) return;
                    if (eaList) eaList.innerHTML = '<div class="ea-empty-state ea-empty-state--error"><p>Failed to load approved events.</p></div>';
                });
        });
    }

    if (statRejected) {
        statRejected.addEventListener('click', function () {
            setActiveStat(statRejected);
            if (pendingListHtml === null && eaList) {
                pendingListHtml = eaList.innerHTML;
            }
            if (eaList) eaList.innerHTML = '<div class="ea-empty-state"><p>Loading rejected events...</p></div>';
            filterEmpty.hidden = true;
            filterEmpty.classList.remove('is-visible');
            fetch(rootUrl + '/eventapproval/rejected')
                .then(r => r.json())
                .then(data => {
                    if (!statRejected.classList.contains('is-active')) return;
                    renderEventGrid(data.events || [], 'Rejected');
                    attachReviewButtons();
                })
                .catch(err => {
                    if (!statRejected.classList.contains('is-active')) return;
                    if (eaList) eaList.innerHTML = '<div class="ea-empty-state ea-empty-state--error"><p>Failed to load rejected events.</p></div>';
                });
        });
    }

    // Modal Background Click-to-Close
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeReview();
        }
    });

    // Submit Decision
    confirmBtn.addEventListener('click', function () {
        if (!activeEventId) return;
        
        const decision = resultSelect.value; // 'approve' | 'reject'
        const remarks   = remarksField.value.trim();

        if ((decision === 'reject' || activeEventStatus === 'CancellationPending') && remarks.length < 5) {
            alert('Please provide remarks of at least 5 characters.');
            remarksField.focus();
            return;
        }

        confirmBtn.disabled = true;
        confirmBtn.style.opacity = '0.7';

        const endpoint = activeEventStatus === 'CancellationPending'
            ? '/eventapproval/cancellation/' + activeEventId
            : '/eventapproval/' + decision + '/' + activeEventId;
        const payload = activeEventStatus === 'CancellationPending'
            ? { csrf_token: csrfToken, remarks: remarks, decision: decision }
            : { csrf_token: csrfToken, remarks: remarks };
        fetch(rootUrl + endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(payload)
        })
        .then(r => r.json())
        .then(data => {
            confirmBtn.disabled = false;
            confirmBtn.style.opacity = '1';
            if (data.success) {
                closeReview();
                location.reload();
            } else {
                alert(data.error || 'Something went wrong. Please try again.');
            }
        })
        .catch(err => {
            confirmBtn.disabled = false;
            confirmBtn.style.opacity = '1';
            alert('Error: ' + err.message);
        });
    });

    // Approved events are the coordinator's default landing view.
    if (statApproved) statApproved.click();
})();
