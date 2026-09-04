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

    let activeEventId = null;

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
    closeBtn.addEventListener('click', closeReview);
    cancelBtn.addEventListener('click', closeReview);

    const statPending       = document.getElementById('statPending');
    const statApproved      = document.getElementById('statApproved');
    const statRejected      = document.getElementById('statRejected');
    const eaList            = document.getElementById('eaEventList');
    const searchInput       = document.getElementById('eaSearchInput');
    const filterBtn         = document.getElementById('eaFilterBtn');
    const filterPanel       = document.getElementById('eaFilterPanel');
    const filterType        = document.getElementById('eaFilterType');
    const filterSchedule    = document.getElementById('eaFilterSchedule');
    const filterCount       = document.getElementById('eaFilterCount');
    const clearFiltersBtn   = document.getElementById('eaClearFiltersBtn');

    function setActiveStat(targetCard) {
        [statPending, statApproved, statRejected].forEach(card => {
            if (!card) return;
            const isActive = card === targetCard;
            card.classList.toggle('is-active', isActive);
            card.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function formatDateStr(dateStr) {
        if (!dateStr) return '—';
        try {
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' at ' +
                   d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        } catch (e) {
            return dateStr;
        }
    }

    function statusConfig(type) {
        if (type === 'Pending') {
            return { apiStatus: 'PendingApproval', badgeClass: 'pending', badgeLabel: 'Pending Approval' };
        }
        if (type === 'Rejected') {
            return { apiStatus: 'Rejected', badgeClass: 'rejected', badgeLabel: 'Rejected' };
        }
        return { apiStatus: 'Approved', badgeClass: 'approved', badgeLabel: 'Approved' };
    }

    function renderEventGrid(events, type) {
        if (!eaList) return;
        const config = statusConfig(type);

        if (!events || !events.length) {
            eaList.innerHTML = '<div class="ea-empty-state"><p>No ' + type.toLowerCase() + ' club events found in this division.</p></div>';
            populateEventTypes([]);
            return;
        }

        eaList.innerHTML = events.map(ev => {
            const dateDisplay = formatDateStr(ev.start_datetime);
            const eventId = Number.parseInt(ev.event_id, 10);
            const detailUrl = (window.ROOT || '') + '/eventapproval/detail/' + eventId;
            const clubName = ev.club_name || ev.organizer_club_name || '';
            const clubCode = ev.club_code || ev.organizer_club_code || '';
            let extraNote = '';

            if (type === 'Approved') {
                extraNote = ev.approver_name ? 'Approved by ' + escapeHtml(ev.approver_name) : 'Approved';
            } else if (type === 'Rejected') {
                extraNote = ev.rejection_remarks ? 'Reason: ' + escapeHtml(ev.rejection_remarks) : 'Rejected';
            } else {
                extraNote = 'Awaiting your review';
            }

            const reviewButton = type === 'Pending'
                ? '<button type="button" class="ea-btn ea-btn-primary ea-btn-review" data-event-id="' + eventId + '">Review</button>'
                : '';

            return '<div class="ea-card" data-event-id="' + eventId + '"' +
                   ' data-detail-url="' + escapeHtml(detailUrl) + '"' +
                   ' data-title="' + escapeHtml((ev.title || '').toLowerCase()) + '"' +
                   ' data-club="' + escapeHtml(clubName.toLowerCase()) + '"' +
                   ' data-location="' + escapeHtml((ev.location || '').toLowerCase()) + '"' +
                   ' data-type="' + escapeHtml((ev.event_type || '').toLowerCase()) + '"' +
                   ' data-start="' + escapeHtml(ev.start_datetime || '') + '" role="link" tabindex="0">' +
                   '  <div class="ea-card-top">' +
                   '    <span class="ea-badge club">Club Event</span>' +
                   '    <span class="ea-badge ' + config.badgeClass + '">' + config.badgeLabel + '</span>' +
                   '  </div>' +
                   '  <h3 class="ea-card-title">' + escapeHtml(ev.title) + '</h3>' +
                   '  <p class="ea-card-club">' + escapeHtml(clubName) +
                   '    <small class="ea-club-code">' + escapeHtml(clubCode) + '</small>' +
                   '  </p>' +
                   '  <div class="ea-card-meta">' +
                   '    <span>' + dateDisplay + '</span>' +
                   '    <span>' + escapeHtml(ev.location || '—') + '</span>' +
                   '  </div>' +
                   '  <div class="ea-card-footer">' +
                   '    <span class="ea-card-submitter">Submitted by ' + escapeHtml(ev.creator_name || '—') + ' • <strong style="color:#4b5563;">' + extraNote + '</strong></span>' +
                   '    <div class="ea-card-actions">' +
                   '      <a href="' + escapeHtml(detailUrl) + '" class="ea-btn ea-btn-secondary">View Details</a>' +
                          reviewButton +
                   '    </div>' +
                   '  </div>' +
                   '</div>';
        }).join('') + '<div class="ea-empty-state" id="eaNoMatches" hidden><p>No events match your current search and filters.</p></div>';

        eaList.dataset.status = type;
        populateEventTypes(events);
        applyClientFilters();
    }

    function populateEventTypes(events) {
        if (!filterType) return;

        const previousValue = filterType.value;
        const types = [...new Set(events
            .map(event => (event.event_type || '').trim())
            .filter(Boolean))]
            .sort((a, b) => a.localeCompare(b));

        filterType.innerHTML = '<option value="">All Types</option>' + types.map(type =>
            '<option value="' + escapeHtml(type.toLowerCase()) + '">' + escapeHtml(type) + '</option>'
        ).join('');

        filterType.value = types.some(type => type.toLowerCase() === previousValue) ? previousValue : '';
        updateFilterCount();
    }

    function populateEventTypesFromCards() {
        if (!eaList) return;
        const types = [...eaList.querySelectorAll('.ea-card')].map(card => ({
            event_type: card.dataset.type || ''
        }));
        populateEventTypes(types);
    }

    function updateFilterCount() {
        if (!filterCount) return;
        const count = Number(Boolean(filterType && filterType.value)) +
                      Number(Boolean(filterSchedule && filterSchedule.value));
        filterCount.textContent = String(count);
        filterCount.hidden = count === 0;
    }

    function applyClientFilters() {
        if (!eaList) return;

        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const type = filterType ? filterType.value : '';
        const schedule = filterSchedule ? filterSchedule.value : '';
        const now = Date.now();
        const cards = [...eaList.querySelectorAll('.ea-card')];
        let visibleCount = 0;

        cards.forEach(card => {
            const haystack = [card.dataset.title, card.dataset.club, card.dataset.location, card.dataset.type].join(' ');
            const startTime = Date.parse(card.dataset.start || '');
            const matchesQuery = !query || haystack.includes(query);
            const matchesType = !type || card.dataset.type === type;
            const matchesSchedule = !schedule ||
                (schedule === 'upcoming' && !Number.isNaN(startTime) && startTime >= now) ||
                (schedule === 'past' && !Number.isNaN(startTime) && startTime < now);
            const visible = matchesQuery && matchesType && matchesSchedule;

            card.hidden = !visible;
            if (visible) visibleCount += 1;
        });

        const noMatches = document.getElementById('eaNoMatches');
        if (noMatches) noMatches.hidden = cards.length === 0 || visibleCount > 0;
        updateFilterCount();
    }

    function loadStatus(type, targetCard) {
        if (!eaList) return;
        const endpoints = {
            Pending: '/eventapproval/pending',
            Approved: '/eventapproval/approved',
            Rejected: '/eventapproval/rejected'
        };

        setActiveStat(targetCard);
        eaList.innerHTML = '<div class="ea-empty-state"><p>Loading ' + type.toLowerCase() + ' events...</p></div>';

        fetch((window.ROOT || '') + endpoints[type])
            .then(response => {
                if (!response.ok) throw new Error('Unable to load events.');
                return response.json();
            })
            .then(data => renderEventGrid(data.events || [], type))
            .catch(() => {
                eaList.innerHTML = '<div class="ea-empty-state" style="color:#b91c1c;"><p>Failed to load ' + type.toLowerCase() + ' events.</p></div>';
            });
    }

    if (statPending) statPending.addEventListener('click', () => loadStatus('Pending', statPending));
    if (statApproved) statApproved.addEventListener('click', () => loadStatus('Approved', statApproved));
    if (statRejected) statRejected.addEventListener('click', () => loadStatus('Rejected', statRejected));

    if (filterBtn && filterPanel) {
        filterBtn.addEventListener('click', () => {
            const isOpen = filterPanel.classList.toggle('open');
            filterBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    if (searchInput) searchInput.addEventListener('input', applyClientFilters);
    if (filterType) filterType.addEventListener('change', applyClientFilters);
    if (filterSchedule) filterSchedule.addEventListener('change', applyClientFilters);
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (filterType) filterType.value = '';
            if (filterSchedule) filterSchedule.value = '';
            applyClientFilters();
        });
    }

    if (eaList) {
        eaList.addEventListener('click', event => {
            const reviewButton = event.target.closest('.ea-btn-review');
            if (reviewButton) {
                openReview(reviewButton.dataset.eventId);
                return;
            }

            if (event.target.closest('a, button, input, select, textarea')) return;
            const card = event.target.closest('.ea-card');
            if (card && card.dataset.detailUrl) window.location.href = card.dataset.detailUrl;
        });

        eaList.addEventListener('keydown', event => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            if (event.target.closest('a, button, input, select, textarea')) return;
            const card = event.target.closest('.ea-card');
            if (!card || !card.dataset.detailUrl) return;
            event.preventDefault();
            window.location.href = card.dataset.detailUrl;
        });
    }

    if (!filterType || filterType.options.length <= 1) populateEventTypesFromCards();
    applyClientFilters();

    // Existing notification dropdown behavior is left unchanged.
    const notifBellBtn = document.getElementById('notifBellBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifDropdownLink = document.getElementById('notifDropdownLink');

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
})();
