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

    document.querySelectorAll('.ea-btn-review').forEach(btn => {
        btn.addEventListener('click', () => openReview(btn.dataset.eventId));
    });
    closeBtn.addEventListener('click', closeReview);
    cancelBtn.addEventListener('click', closeReview);

    // Notification Dropdown Interaction
    const notifBellBtn = document.getElementById('notifBellBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifDropdownLink = document.getElementById('notifDropdownLink');
    const statPending = document.getElementById('statPending');
    const statApproved = document.getElementById('statApproved');
    const statRejected = document.getElementById('statRejected');
    const eaList = document.getElementById('eaPendingList');
    let pendingListHtml = null;

    function setActiveStat(targetCard) {
        [statPending, statApproved, statRejected].forEach(card => {
            if (card) card.classList.remove('is-active');
        });
        if (targetCard) targetCard.classList.add('is-active');
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

    function renderEventGrid(events, type) {
        if (!eaList) return;
        if (!events || !events.length) {
            eaList.innerHTML = '<div class="ea-empty-state"><p>No ' + type.toLowerCase() + ' events found in this division.</p></div>';
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

            return '<div class="ea-card" data-event-id="' + ev.event_id + '">' +
                   '  <div class="ea-card-top">' +
                   '    <span class="ea-badge club">Club Event</span>' +
                   '    <span class="ea-badge ' + badgeClass + '">' + badgeLabel + '</span>' +
                   '  </div>' +
                   '  <h3 class="ea-card-title">' + escapeHtml(ev.title) + '</h3>' +
                   '  <p class="ea-card-club">' + escapeHtml(ev.club_name || '') +
                   '    <small class="ea-club-code">' + escapeHtml(ev.club_code || '') + '</small>' +
                   '  </p>' +
                   '  <div class="ea-card-meta">' +
                   '    <span>' + dateDisplay + '</span>' +
                   '    <span>' + escapeHtml(ev.location || '—') + '</span>' +
                   '  </div>' +
                   '  <div class="ea-card-footer">' +
                   '    <span class="ea-card-submitter">Submitted by ' + escapeHtml(ev.creator_name || '—') + ' • <strong style="color:#4b5563;">' + extraNote + '</strong></span>' +
                   '  </div>' +
                   '</div>';
        }).join('');
    }

    if (statPending) {
        statPending.addEventListener('click', function () {
            setActiveStat(statPending);
            if (pendingListHtml !== null && eaList) {
                eaList.innerHTML = pendingListHtml;
                document.querySelectorAll('.ea-btn-review').forEach(btn => {
                    btn.addEventListener('click', () => openReview(btn.dataset.eventId));
                });
            }
        });
    }

    if (statApproved) {
        statApproved.addEventListener('click', function () {
            setActiveStat(statApproved);
            if (pendingListHtml === null && eaList) {
                pendingListHtml = eaList.innerHTML;
            }
            if (eaList) eaList.innerHTML = '<div class="ea-empty-state"><p>Loading approved events...</p></div>';
            fetch((window.ROOT || '') + '/eventapproval/approved')
                .then(r => r.json())
                .then(data => {
                    renderEventGrid(data.events || [], 'Approved');
                })
                .catch(err => {
                    if (eaList) eaList.innerHTML = '<div class="ea-empty-state" style="color:red;"><p>Failed to load approved events.</p></div>';
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
            fetch((window.ROOT || '') + '/eventapproval/rejected')
                .then(r => r.json())
                .then(data => {
                    renderEventGrid(data.events || [], 'Rejected');
                })
                .catch(err => {
                    if (eaList) eaList.innerHTML = '<div class="ea-empty-state" style="color:red;"><p>Failed to load rejected events.</p></div>';
                });
        });
    }

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
