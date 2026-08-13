(function () {
    'use strict';

    var grid          = document.getElementById('crGrid');
    var searchInput    = document.getElementById('crSearchInput');
    var statPending      = document.getElementById('statPending');
    var statApproved       = document.getElementById('statApproved');
    var modalBackdrop         = document.getElementById('crModalBackdrop');
    var modalContent            = document.getElementById('crModalContent');
    var toast                     = document.getElementById('crToast');
    var csrfToken                    = document.getElementById('csrfToken') ? document.getElementById('csrfToken').value : '';

    // ---------------------------------------------------------------
    // Toast helper
    // ---------------------------------------------------------------
    function showToast(message, isError) {
        toast.textContent = message;
        toast.className = 'cr-toast show' + (isError ? ' error' : '');
        setTimeout(function () { toast.className = 'cr-toast'; }, 3500);
    }

    // ---------------------------------------------------------------
    // Search filter (client-side, over the cards already rendered)
    // ---------------------------------------------------------------
    if (searchInput && grid) {
        searchInput.addEventListener('input', function () {
            var query = searchInput.value.trim().toLowerCase();
            var cards = grid.querySelectorAll('.cr-card');
            cards.forEach(function (card) {
                var matches = card.dataset.name.indexOf(query) !== -1 || card.dataset.proposer.indexOf(query) !== -1;
                card.style.display = matches ? '' : 'none';
            });
        });
    }

    // ---------------------------------------------------------------
    // Stat cards as filters (improvement #2)
    // ---------------------------------------------------------------
    function setActiveStat(button) {
        [statPending, statApproved].forEach(function (b) { if (b) b.classList.remove('is-active'); });
        if (button) button.classList.add('is-active');
    }

    if (statPending) {
        statPending.addEventListener('click', function () { setActiveStat(statPending); });
    }
    if (statApproved) {
        statApproved.addEventListener('click', function () {
            setActiveStat(statApproved);
            showToast('Approved applications view is on the way — this queue only shows Pending for now.');
        });
    }

    // ---------------------------------------------------------------
    // Review modal
    // ---------------------------------------------------------------
    function closeModal() {
        modalBackdrop.classList.remove('open');
        modalContent.innerHTML = '';
    }

    modalBackdrop.addEventListener('click', function (e) {
        if (e.target === modalBackdrop) closeModal();
    });

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function renderModal(data) {
        var app = data.application;
        var nominees = data.nominees || [];

        var nomineesHtml = nominees.map(function (n) {
            return '<div class="cr-nominee-card">' +
                '<div class="role">' + escapeHtml(n.role_type) + '</div>' +
                '<div class="name">' + escapeHtml(n.name) + '</div>' +
                '<div class="meta">' + escapeHtml(n.email) + ' &middot; NIC: ' + escapeHtml(n.NIC) + '</div>' +
                '</div>';
        }).join('');

        var missingDocs = [];
        if (!app.constitution_path)  missingDocs.push('Constitution');
        if (!app.venue_proof_path)   missingDocs.push('Proof of Venue');
        if (!app.nic_president_path) missingDocs.push('President NIC');
        if (!app.nic_secretary_path) missingDocs.push('Secretary NIC');
        if (!app.nic_treasurer_path) missingDocs.push('Treasurer NIC');
        var docsHtml = missingDocs.length === 0
            ? '<span class="cr-doc-status complete">All required documents uploaded</span>'
            : '<span class="cr-doc-status incomplete">Missing: ' + escapeHtml(missingDocs.join(', ')) + '</span>';

        modalContent.innerHTML =
            '<div class="cr-modal-header">' +
                '<div>' +
                    '<h2>' + escapeHtml(app.club_name) + '</h2>' +
                    '<p>Application #' + escapeHtml(app.application_id) + ' &middot; Proposed by ' + escapeHtml(app.proposer_name) + '</p>' +
                '</div>' +
                '<button type="button" class="cr-modal-close" id="crModalCloseBtn">&times;</button>' +
            '</div>' +

            '<div class="cr-modal-section">' +
                '<h3>Basic Information</h3>' +
                '<div class="cr-field-grid">' +
                    '<div class="cr-field"><label>Category</label><span>' + escapeHtml(app.category || '—') + '</span></div>' +
                    '<div class="cr-field"><label>Established</label><span>' + escapeHtml(app.date_establishment || '—') + '</span></div>' +
                    '<div class="cr-field"><label>Members</label><span>' + escapeHtml(app.no_of_members) + '</span></div>' +
                '</div>' +
                (app.description ? '<p style="font-size:13px;color:#4b5563;margin-top:10px;">' + escapeHtml(app.description) + '</p>' : '') +
            '</div>' +

            '<div class="cr-modal-section">' +
                '<h3>Location</h3>' +
                '<div class="cr-field-grid">' +
                    '<div class="cr-field"><label>Address</label><span>' + escapeHtml(app.street_address || '—') + '</span></div>' +
                    '<div class="cr-field"><label>City</label><span>' + escapeHtml(app.city || '—') + '</span></div>' +
                    '<div class="cr-field"><label>Province</label><span>' + escapeHtml(app.state_province || '—') + '</span></div>' +
                '</div>' +
            '</div>' +

            '<div class="cr-modal-section">' +
                '<h3>Executive Committee</h3>' +
                (nomineesHtml || '<p style="font-size:13px;color:#6b7280;">No nominees on file.</p>') +
            '</div>' +

            '<div class="cr-modal-section">' +
                '<h3>Documents</h3>' +
                docsHtml +
            '</div>' +

            '<div class="cr-decision-panel">' +
                '<h3 style="font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;margin:0 0 10px;">Decision</h3>' +
                '<textarea id="crRemarks" placeholder="Remarks (required if rejecting)"></textarea>' +
                '<div class="cr-decision-actions">' +
                    '<button type="button" class="cr-btn cr-btn-danger" id="crRejectBtn">Reject</button>' +
                    '<button type="button" class="cr-btn cr-btn-primary" id="crApproveBtn">Approve</button>' +
                '</div>' +
            '</div>';

        document.getElementById('crModalCloseBtn').addEventListener('click', closeModal);
        document.getElementById('crApproveBtn').addEventListener('click', function () { submitDecision(app.application_id, 'approve'); });
        document.getElementById('crRejectBtn').addEventListener('click', function () { submitDecision(app.application_id, 'reject'); });
    }

    function openReview(applicationId) {
        modalBackdrop.classList.add('open');
        modalContent.innerHTML = '<p style="padding:20px;font-size:13px;color:#6b7280;">Loading application…</p>';

        fetch(ROOT_URL + '/clubregistration/review/' + applicationId, { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.error) {
                    modalContent.innerHTML = '<p style="padding:20px;color:#b91c1c;">' + escapeHtml(data.error) + '</p>';
                    return;
                }
                renderModal(data);
            })
            .catch(function () {
                modalContent.innerHTML = '<p style="padding:20px;color:#b91c1c;">Something went wrong loading this application.</p>';
            });
    }

    if (grid) {
        grid.addEventListener('click', function (e) {
            var btn = e.target.closest('.cr-review-btn');
            if (!btn) return;
            openReview(btn.dataset.id);
        });
    }

    // ---------------------------------------------------------------
    // Approve / Reject submission
    // ---------------------------------------------------------------
    function submitDecision(applicationId, action) {
        var remarks = document.getElementById('crRemarks').value.trim();

        if (action === 'reject' && !remarks) {
            showToast('Please provide a reason for rejecting this application.', true);
            return;
        }

        var approveBtn = document.getElementById('crApproveBtn');
        var rejectBtn = document.getElementById('crRejectBtn');
        approveBtn.disabled = true;
        rejectBtn.disabled = true;

        var body = new URLSearchParams();
        body.set('csrf_token', csrfToken);
        body.set('remarks', remarks);

        fetch(ROOT_URL + '/clubregistration/' + action + '/' + applicationId, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.error) {
                    showToast(data.error, true);
                    approveBtn.disabled = false;
                    rejectBtn.disabled = false;
                    return;
                }
                closeModal();
                var cardEl = grid.querySelector('.cr-review-btn[data-id="' + applicationId + '"]');
                if (cardEl) {
                    var card = cardEl.closest('.cr-card');
                    if (card) card.remove();
                }
                showToast(action === 'approve'
                    ? ('Application approved. Club code: ' + (data.club_code || ''))
                    : 'Application rejected. The proposer has been notified.');
            })
            .catch(function () {
                showToast('Something went wrong. Please try again.', true);
                approveBtn.disabled = false;
                rejectBtn.disabled = false;
            });
    }

})();
