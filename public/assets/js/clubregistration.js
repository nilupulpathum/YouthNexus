(function () {
    'use strict';

    var grid          = document.getElementById('crGrid');
    var searchInput    = document.getElementById('crSearchInput');
    var statPending      = document.getElementById('statPending');
    var statApproved       = document.getElementById('statApproved');
    var statRejected       = document.getElementById('statRejected');
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
    // Filter panel & Search (client-side, over cards already rendered)
    // ---------------------------------------------------------------
    var filterBtn      = document.getElementById('crFilterBtn');
    var filterPanel    = document.getElementById('crFilterPanel');
    var filterStatus   = document.getElementById('crFilterStatus');
    var filterDocs     = document.getElementById('crFilterDocs');
    var addFilterBtn   = document.getElementById('crAddFilterBtn');
    var clearFilterBtn = document.getElementById('crClearFilterBtn');

    function filterCards() {
        if (!grid) return;
        var query  = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var status = filterStatus ? filterStatus.value : '';
        var docs   = filterDocs ? filterDocs.value : '';
        var cards  = grid.querySelectorAll('.cr-card');
        var visibleCount = 0;

        cards.forEach(function (card) {
            var textMatch   = !query  || card.dataset.name.indexOf(query) !== -1 || (card.dataset.proposer || '').toLowerCase().indexOf(query) !== -1;
            var statusMatch = !status || card.dataset.status === status;
            var docsMatch   = !docs   || card.dataset.docstatus === docs;
            var isVisible   = (textMatch && statusMatch && docsMatch);
            card.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        // If cards exist but none match the search/filter criteria, show feedback message
        var noMatchEl = grid.querySelector('#crNoFilterMatch');
        if (cards.length > 0) {
            if (visibleCount === 0) {
                if (!noMatchEl) {
                    var msg = document.createElement('div');
                    msg.id = 'crNoFilterMatch';
                    msg.className = 'cr-empty';
                    msg.style.gridColumn = '1 / -1';
                    msg.innerHTML =
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="40" height="40" style="margin-bottom:12px;opacity:0.4;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>' +
                        '<p>No applications match your search/filters.</p>';
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
            var isOpen = filterPanel.classList.toggle('open');
            filterBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    if (addFilterBtn) {
        addFilterBtn.addEventListener('click', filterCards);
    }
    if (clearFilterBtn) {
        clearFilterBtn.addEventListener('click', function () {
            if (filterStatus) filterStatus.value = '';
            if (filterDocs)   filterDocs.value   = '';
            filterCards();
        });
    }

    // ---------------------------------------------------------------
    // Stat cards as filters / views
    // ---------------------------------------------------------------
    var pendingGridHtml = null; // Cache initial pending cards HTML

    function renderApprovedGrid(apps) {
        if (!grid) return;
        if (!apps || apps.length === 0) {
            grid.innerHTML =
                '<div class="cr-empty" style="grid-column: 1 / -1;">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="40" height="40" style="margin-bottom:12px;opacity:0.4;"><path d="M20 6 9 17l-5-5"/></svg>' +
                    '<p>No approved applications found in this division.</p>' +
                '</div>';
            return;
        }

        var html = '';
        apps.forEach(function (app) {
            var dateStr = app.reviewed_at ? formatDOB(app.reviewed_at) : (app.submitted_at ? formatDOB(app.submitted_at) : '—');
            var reviewerText = app.reviewed_by_name ? ' BY ' + escapeHtml(app.reviewed_by_name.toUpperCase()) : '';

            html +=
                '<div class="cr-card" data-name="' + escapeHtml((app.club_name || '').toLowerCase()) + '" data-status="Approved" data-proposer="' + escapeHtml((app.proposer_name || '').toLowerCase()) + '" data-docstatus="complete">' +
                    '<div class="cr-card-top">' +
                        '<div class="cr-card-icon complete" title="Approved">' +
                            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>' +
                        '</div>' +
                        '<span class="cr-badge approved">Approved</span>' +
                    '</div>' +
                    '<div class="cr-card-date">' +
                        'APPROVED ' + escapeHtml(dateStr) + reviewerText +
                    '</div>' +
                    '<div class="cr-card-name">' + escapeHtml(app.club_name) + '</div>' +
                    '<div class="cr-card-proposer">' +
                        'Proposer: ' + escapeHtml(app.proposer_name || '—') +
                    '</div>' +
                    '<div class="cr-card-footer">' +
                        '<button type="button" class="cr-btn cr-review-btn" data-id="' + escapeHtml(app.application_id) + '">View</button>' +
                    '</div>' +
                '</div>';
        });
        grid.innerHTML = html;
    }

    function setActiveStat(button) {
        [statPending, statApproved, statRejected].forEach(function (b) { if (b) b.classList.remove('is-active'); });
        if (button) button.classList.add('is-active');
    }

    function renderRejectedGrid(apps) {
        if (!grid) return;
        if (!apps || apps.length === 0) {
            grid.innerHTML =
                '<div class="cr-empty" style="grid-column: 1 / -1;">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="40" height="40" style="margin-bottom:12px;opacity:0.4;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                    '<p>No rejected applications found in this division.</p>' +
                '</div>';
            return;
        }

        var html = '';
        apps.forEach(function (app) {
            var dateStr = app.reviewed_at ? formatDOB(app.reviewed_at) : (app.submitted_at ? formatDOB(app.submitted_at) : '—');
            var reviewerText = app.reviewed_by_name ? ' BY ' + escapeHtml(app.reviewed_by_name.toUpperCase()) : '';

            html +=
                '<div class="cr-card" data-name="' + escapeHtml((app.club_name || '').toLowerCase()) + '" data-status="Rejected" data-proposer="' + escapeHtml((app.proposer_name || '').toLowerCase()) + '" data-docstatus="complete">' +
                    '<div class="cr-card-top">' +
                        '<div class="cr-card-icon incomplete" title="Rejected">' +
                            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                        '</div>' +
                        '<span class="cr-badge rejected">Rejected</span>' +
                    '</div>' +
                    '<div class="cr-card-date">' +
                        'REJECTED ' + escapeHtml(dateStr) + reviewerText +
                    '</div>' +
                    '<div class="cr-card-name">' + escapeHtml(app.club_name) + '</div>' +
                    '<div class="cr-card-proposer">' +
                        'Proposer: ' + escapeHtml(app.proposer_name || '—') +
                    '</div>' +
                    '<div class="cr-card-footer">' +
                        '<button type="button" class="cr-btn cr-review-btn" data-id="' + escapeHtml(app.application_id) + '">View</button>' +
                    '</div>' +
                '</div>';
        });
        grid.innerHTML = html;
    }

    if (statPending) {
        statPending.addEventListener('click', function () {
            setActiveStat(statPending);
            if (pendingGridHtml !== null && grid) {
                grid.innerHTML = pendingGridHtml;
                filterCards();
            }
        });
    }

    if (statApproved) {
        statApproved.addEventListener('click', function () {
            setActiveStat(statApproved);
            // Cache current pending HTML if not yet cached
            if (pendingGridHtml === null && grid) {
                pendingGridHtml = grid.innerHTML;
            }
            grid.innerHTML = '<p style="grid-column: 1 / -1; padding: 20px; color: #6b7280; text-align: center;">Loading approved applications…</p>';
            fetch(ROOT_URL + '/clubregistration/approved', { credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    renderApprovedGrid(data.applications || []);
                    filterCards();
                })
                .catch(function () {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; padding: 20px; color: #b91c1c; text-align: center;">Failed to load approved applications.</p>';
                });
        });
    }

    if (statRejected) {
        statRejected.addEventListener('click', function () {
            setActiveStat(statRejected);
            // Cache current pending HTML if not yet cached
            if (pendingGridHtml === null && grid) {
                pendingGridHtml = grid.innerHTML;
            }
            grid.innerHTML = '<p style="grid-column: 1 / -1; padding: 20px; color: #6b7280; text-align: center;">Loading rejected applications…</p>';
            fetch(ROOT_URL + '/clubregistration/rejected', { credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    renderRejectedGrid(data.applications || []);
                    filterCards();
                })
                .catch(function () {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; padding: 20px; color: #b91c1c; text-align: center;">Failed to load rejected applications.</p>';
                });
        });
    }

    // ---------------------------------------------------------------
    // Review modal
    // ---------------------------------------------------------------
    function closeModal() {
        modalBackdrop.classList.remove('open');
        modalContent.innerHTML = '';
    }

    var mousedownTarget = null;
    modalBackdrop.addEventListener('mousedown', function (e) {
        mousedownTarget = e.target;
    });

    modalBackdrop.addEventListener('click', function (e) {
        if (e.target === modalBackdrop && mousedownTarget === modalBackdrop) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalBackdrop.classList.contains('open')) {
            closeModal();
        }
    });

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function maskAccountNumber(num) {
        if (!num) return '—';
        var clean = String(num).replace(/\s+/g, '');
        if (clean.length <= 4) return clean;
        var masked = '';
        var len = clean.length;
        for (var i = 0; i < len - 4; i++) {
            masked += '•';
            if ((i + 1) % 4 === 0) masked += ' ';
        }
        if (masked.charAt(masked.length - 1) !== ' ') masked += ' ';
        masked += clean.slice(-4);
        return masked;
    }

    function renderDocumentCard(title, subtitle, tagText, tagClass, path) {
        if (!path) {
            return '<div class="cr-doc-big-card empty">' +
                '<div class="cr-doc-card-header">' +
                    '<h4>' + escapeHtml(title) + '</h4>' +
                    '<span class="cr-doc-req-tag ' + tagClass + '">' + tagText + '</span>' +
                '</div>' +
                '<p class="cr-doc-card-desc">' + escapeHtml(subtitle) + '</p>' +
                '<div class="cr-doc-dashed-box empty">' +
                    '<span>No file uploaded</span>' +
                '</div>' +
            '</div>';
        }
        var fileName = path.split('/').pop();
        var ext = fileName.split('.').pop().toUpperCase();
        var iconSvg = ext === 'PDF'
            ? '<svg class="cr-doc-type-icon pdf" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8m8 4H8m4-8H8"/></svg>'
            : '<svg class="cr-doc-type-icon img" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';

        var displayMeta = '1.2 MB &bull; Uploaded Oct 25';
        if (title.indexOf('Venue') >= 0) {
            displayMeta = '840 KB &bull; Uploaded Oct 26';
        }

        return '<div class="cr-doc-big-card">' +
            '<div class="cr-doc-card-header">' +
                '<h4>' + escapeHtml(title) + '</h4>' +
                '<span class="cr-doc-req-tag ' + tagClass + '">' + tagText + '</span>' +
            '</div>' +
            '<p class="cr-doc-card-desc">' + escapeHtml(subtitle) + '</p>' +
            '<div class="cr-doc-dashed-box">' +
                iconSvg +
                '<span class="cr-doc-filename-large">' + escapeHtml(fileName) + '</span>' +
                '<span class="cr-doc-meta-large">' + displayMeta + '</span>' +
                '<a href="' + escapeHtml(ROOT_URL + path) + '" target="_blank" class="cr-btn cr-btn-view-doc">VIEW DOCUMENT</a>' +
            '</div>' +
        '</div>';
    }

    function renderNicCopyCard(roleLabel, path) {
        var btnHtml = path
            ? '<a href="' + escapeHtml(ROOT_URL + path) + '" target="_blank" class="cr-btn cr-btn-view-nic">VIEW NIC</a>'
            : '<button type="button" class="cr-btn cr-btn-view-nic disabled" disabled>VIEW NIC</button>';
        return '<div class="cr-nic-copy-card">' +
            '<div class="cr-nic-placeholder-box">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" width="28" height="28"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 13h4m-4 3h4m-10-1a3 3 0 0 1 6 0"/></svg>' +
            '</div>' +
            '<div class="cr-nic-copy-footer">' +
                '<span class="cr-nic-role-label">' + roleLabel + '</span>' +
                btnHtml +
            '</div>' +
        '</div>';
    }

    function renderAvatar(n, size) {
        if (n && n.photo_path) {
            return '<div class="cr-avatar-circle size-' + size + '"><img src="' + escapeHtml(ROOT_URL + n.photo_path) + '" alt="' + escapeHtml(n.name) + '"></div>';
        }
        return '<div class="cr-avatar-circle-placeholder size-' + size + '">' + (n ? escapeHtml(n.name.charAt(0)) : '?') + '</div>';
    }

    function formatDOB(dobStr) {
        if (!dobStr) return '—';
        var d = new Date(dobStr);
        if (isNaN(d.getTime())) return escapeHtml(dobStr);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function renderModal(data) {
        var app = data.application;
        var nominees = data.nominees || [];
        var assets = data.assets || [];

        var estDate = formatDOB(app.date_establishment);
        var submittedDate = formatDOB(app.submitted_at);

        var president = nominees.filter(function(n) { return n.role_type === 'President'; })[0];
        var secretary = nominees.filter(function(n) { return n.role_type === 'Secretary'; })[0];
        var treasurer = nominees.filter(function(n) { return n.role_type === 'Treasurer'; })[0];

        modalContent.innerHTML =
            '<div class="cr-modal-header">' +
                '<div class="cr-header-left">' +
                    '<div class="cr-header-title-row">' +
                        '<span class="cr-header-phase-tag">REGISTRATION PHASE 1-7</span>' +
                        '<h2>Review Full Club Application</h2>' +
                    '</div>' +
                    '<p>' + escapeHtml(app.club_name) + ' &bull; Application ID: ' + escapeHtml(app.application_ref || ('APP-' + app.application_id)) + ' &bull; Submitted ' + escapeHtml(submittedDate) + '</p>' +
                '</div>' +
                '<button type="button" class="cr-modal-close" id="crModalCloseBtn">&times;</button>' +
            '</div>' +

            // Section 1: Basic Information
            '<div class="cr-modal-section">' +
                '<div class="cr-section-header">' +
                    '<span class="cr-section-number">1</span>' +
                    '<h3 class="cr-section-title">BASIC INFORMATION</h3>' +
                '</div>' +
                '<div class="cr-basic-info-layout">' +
                    '<div class="cr-logo-section">' +
                        '<label class="cr-section-field-label">CLUB LOGO</label>' +
                        '<div class="cr-dashed-logo-box">' +
                            (app.club_logo_path 
                                ? '<img src="' + escapeHtml(ROOT_URL + app.club_logo_path) + '" alt="Club Logo" class="cr-logo-preview">' +
                                  '<span class="cr-logo-filename">' + escapeHtml(app.club_logo_path.split('/').pop()) + '</span>'
                                : '<svg class="cr-logo-placeholder-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>' +
                                  '<span class="cr-logo-filename">No logo uploaded</span>') +
                        '</div>' +
                    '</div>' +
                    '<div class="cr-basic-details-section">' +
                        '<div class="cr-detail-row">' +
                            '<div class="cr-field">' +
                                '<label>CLUB NAME</label>' +
                                '<span class="cr-club-name-title">' + escapeHtml(app.club_name) + '</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="cr-detail-cols">' +
                            '<div class="cr-field">' +
                                '<label>REGISTRATION NUMBER</label>' +
                                '<span class="cr-field-val-bold">' + escapeHtml(app.application_ref || 'Not assigned') + '</span>' +
                            '</div>' +
                            '<div class="cr-field">' +
                                '<label>DATE OF ESTABLISHMENT</label>' +
                                '<span class="cr-field-val-bold">' + escapeHtml(estDate) + '</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="cr-detail-row" style="margin-top: 12px;">' +
                            '<div class="cr-field">' +
                                '<label>CLUB CATEGORY</label>' +
                                '<div><span class="cr-category-tag">' + escapeHtml(app.category || 'Uncategorized') + '</span></div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                (app.description ? 
                '<div class="cr-mission-section">' +
                    '<label class="cr-section-field-label">CLUB MISSION STATEMENT</label>' +
                    '<div class="cr-mission-box">' +
                        '"' + escapeHtml(app.description) + '"' +
                    '</div>' +
                '</div>' : '') +
            '</div>' +

            // Section 2: Location Details
            '<div class="cr-modal-section">' +
                '<div class="cr-section-header">' +
                    '<span class="cr-section-number">2</span>' +
                    '<h3 class="cr-section-title">LOCATION DETAILS</h3>' +
                '</div>' +
                '<div class="cr-location-grid">' +
                    '<div class="cr-field"><label>PROVINCE</label><span class="cr-field-val-bold">' + escapeHtml(app.state_province || '—') + '</span></div>' +
                    '<div class="cr-field"><label>DISTRICT</label><span class="cr-field-val-bold">' + escapeHtml(app.district || '—') + '</span></div>' +
                    '<div class="cr-field"><label>DIVISION</label><span class="cr-field-val-bold">' + escapeHtml(app.division_name || '—') + (app.zone_name ? ' — ' + escapeHtml(app.zone_name) : '') + '</span></div>' +
                '</div>' +
                '<div class="cr-venue-section">' +
                    '<label class="cr-section-field-label">MEETING VENUE / OFFICIAL ADDRESS</label>' +
                    '<div class="cr-venue-box">' +
                        '<div class="cr-venue-address-row">' +
                            '<svg class="cr-pin-icon" viewBox="0 0 24 24" fill="none" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
                            '<span class="cr-venue-address-text">' + escapeHtml(app.street_address || '—') + ', ' + escapeHtml(app.city || '—') + ', ' + escapeHtml(app.postal_code || '—') + '.</span>' +
                        '</div>' +
                        (app.venue_established ? 
                        '<div class="cr-venue-info-tag">' +
                            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>' +
                            '<span>PERMANENT MEETING LOCATION ESTABLISHED</span>' +
                        '</div>' : '') +
                    '</div>' +
                '</div>' +
            '</div>' +

            // Section 3: Executive Committee Details
            '<div class="cr-modal-section">' +
                '<div class="cr-section-header">' +
                    '<span class="cr-section-number">3</span>' +
                    '<h3 class="cr-section-title">EXECUTIVE COMMITTEE DETAILS</h3>' +
                '</div>' +
                '<div class="cr-nominees-container">' +
                    (president ? 
                    '<div class="cr-nominee-horizontal-card">' +
                        '<div class="cr-nominee-horizontal-header">' +
                            '<span class="cr-role-title">' +
                                '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-right:4px;"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>' +
                                'PRESIDENT / PRIMARY OFFICER' +
                            '</span>' +
                        '</div>' +
                        '<div class="cr-nominee-horizontal-content">' +
                            '<div class="cr-nominee-photo-block">' +
                                renderAvatar(president, 'large') +
                                '<label>PROFILE PHOTO</label>' +
                            '</div>' +
                            '<div class="cr-nominee-fields-block">' +
                                '<div class="cr-nominee-field"><label>FULL NAME (AS PER NIC)</label><span class="cr-nominee-val-bold">' + escapeHtml(president.name) + '</span></div>' +
                                '<div class="cr-nominee-field"><label>NIC NUMBER</label><span class="cr-nominee-val-bold">' + escapeHtml(president.NIC || '—') + '</span></div>' +
                                '<div class="cr-nominee-field"><label>DATE OF BIRTH</label><span class="cr-nominee-val-bold">' + formatDOB(president.date_of_birth) + '</span></div>' +
                                '<div class="cr-nominee-field"><label>PHONE NUMBER</label><span class="cr-nominee-val-bold">' + escapeHtml(president.phone_number || '—') + '</span></div>' +
                                '<div class="cr-nominee-field-full"><label>EMAIL ADDRESS</label><span class="cr-nominee-val-bold">' + escapeHtml(president.email || '—') + '</span></div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' : '<p class="cr-empty-placeholder">No President nominee on file.</p>') +

                    '<div class="cr-nominees-subgrid">' +
                        // Secretary
                        '<div class="cr-nominee-subcard">' +
                            '<div class="cr-nominee-subcard-header">' +
                                '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>' +
                                'SECRETARY' +
                            '</div>' +
                            '<div class="cr-nominee-subcard-content">' +
                                '<div class="cr-nominee-photo-block">' +
                                    renderAvatar(secretary, 'small') +
                                    '<label>PROFILE PHOTO</label>' +
                                '</div>' +
                                '<div class="cr-nominee-subfields">' +
                                    '<div class="cr-subfield"><label>NAME</label><span class="cr-subfield-val">' + (secretary ? escapeHtml(secretary.name) : '—') + '</span></div>' +
                                    '<div class="cr-subfield"><label>NIC</label><span class="cr-subfield-val">' + (secretary ? escapeHtml(secretary.NIC || '—') : '—') + '</span></div>' +
                                    '<div class="cr-subfield"><label>DOB</label><span class="cr-subfield-val">' + (secretary ? formatDOB(secretary.date_of_birth) : '—') + '</span></div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +

                        // Treasurer
                        '<div class="cr-nominee-subcard">' +
                            '<div class="cr-nominee-subcard-header">' +
                                '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>' +
                                'TREASURER' +
                            '</div>' +
                            '<div class="cr-nominee-subcard-content">' +
                                '<div class="cr-nominee-photo-block">' +
                                    renderAvatar(treasurer, 'small') +
                                    '<label>PROFILE PHOTO</label>' +
                                '</div>' +
                                '<div class="cr-nominee-subfields">' +
                                    '<div class="cr-subfield"><label>NAME</label><span class="cr-subfield-val">' + (treasurer ? escapeHtml(treasurer.name) : '—') + '</span></div>' +
                                    '<div class="cr-subfield"><label>NIC</label><span class="cr-subfield-val">' + (treasurer ? escapeHtml(treasurer.NIC || '—') : '—') + '</span></div>' +
                                    '<div class="cr-subfield"><label>DOB</label><span class="cr-subfield-val">' + (treasurer ? formatDOB(treasurer.date_of_birth) : '—') + '</span></div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            // Section 4: Initial Club Assets
            '<div class="cr-modal-section">' +
                '<div class="cr-section-header">' +
                    '<span class="cr-section-number">4</span>' +
                    '<h3 class="cr-section-title">INITIAL CLUB ASSETS</h3>' +
                '</div>' +
                '<div class="cr-assets-two-columns">' +
                    '<div class="cr-assets-inventory-col">' +
                        '<label class="cr-section-field-label">ASSET INVENTORY</label>' +
                        '<table class="cr-assets-table">' +
                            '<thead>' +
                                '<tr>' +
                                    '<th>ASSET NAME</th>' +
                                    '<th>QTY</th>' +
                                    '<th>CONDITION</th>' +
                                '</tr>' +
                            '</thead>' +
                            '<tbody>' +
                                (assets.length > 0 ? assets.map(function(ast) {
                                    var padQty = String(ast.quantity).padStart(2, '0');
                                    return '<tr>' +
                                        '<td class="cr-asset-table-name">' + escapeHtml(ast.asset_name) + '</td>' +
                                        '<td class="cr-asset-table-qty">' + escapeHtml(padQty) + '</td>' +
                                        '<td><span class="cr-asset-table-condition-pill ' + escapeHtml(ast.condition.toLowerCase()) + '">' + escapeHtml(ast.condition) + '</span></td>' +
                                        '</tr>';
                                }).join('') : '<tr><td colspan="3" class="cr-table-empty">No assets listed</td></tr>') +
                            '</tbody>' +
                        '</table>' +
                    '</div>' +
                    '<div class="cr-assets-photos-col">' +
                        '<label class="cr-section-field-label">ASSET PHOTOS</label>' +
                        '<div class="cr-assets-photos-grid">' +
                            [0, 1, 2, 3].map(function(idx) {
                                var asset = assets[idx];
                                if (asset && asset.photo_path) {
                                    return '<div class="cr-asset-photo-box"><img src="' + escapeHtml(ROOT_URL + asset.photo_path) + '" alt="Asset Photo" class="cr-asset-photo-img"></div>';
                                }
                                return '<div class="cr-asset-photo-box empty">' +
                                       '<svg class="cr-photo-icon" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>' +
                                       '</div>';
                            }).join('') +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            // Section 5: Verification & Disbursements
            '<div class="cr-modal-section">' +
                '<div class="cr-section-header">' +
                    '<span class="cr-section-number">5</span>' +
                    '<h3 class="cr-section-title">VERIFICATION & DISBURSEMENTS</h3>' +
                '</div>' +
                '<div class="cr-verification-layout">' +
                    '<div class="cr-bank-info-card">' +
                        '<h4 class="cr-card-section-title">' +
                            '<svg class="cr-card-header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="22" width="18" height="2"/><line x1="6" y1="18" x2="6" y2="11"/><line x1="10" y1="18" x2="10" y2="11"/><line x1="14" y1="18" x2="14" y2="11"/><line x1="18" y1="18" x2="18" y2="11"/><polygon points="12 2 2 7 22 7 12 2"/></svg>' +
                            'OFFICIAL BANK INFORMATION' +
                        '</h4>' +
                        '<div class="cr-bank-fields-grid">' +
                            '<div class="cr-field"><label>BANK NAME</label><span class="cr-field-val-bold">' + escapeHtml(app.bank_name || '—') + '</span></div>' +
                            '<div class="cr-field"><label>BRANCH</label><span class="cr-field-val-bold">' + escapeHtml(app.bank_branch || '—') + '</span></div>' +
                            '<div class="cr-field-full"><label>ACCOUNT HOLDER NAME</label><span class="cr-field-val-bold">' + escapeHtml(app.account_holder || '—') + '</span></div>' +
                            '<div class="cr-field-full"><label>ACCOUNT NUMBER</label><span class="cr-field-val-bold cr-account-number-display">' + escapeHtml(maskAccountNumber(app.account_number)) + '</span></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="cr-processing-card">' +
                        '<div class="cr-processing-lock-circle">' +
                            '<svg viewBox="0 0 24 24" fill="none" stroke="#1e40af" stroke-width="2" width="18" height="18"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>' +
                        '</div>' +
                        '<div class="cr-processing-title">Secure Processing</div>' +
                        '<div class="cr-processing-subtitle">Financial details are verified for disbursement purposes.</div>' +
                        '<div class="cr-processing-checklist">' +
                            '<div class="cr-checklist-item"><svg class="cr-check-icon" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3" width="12" height="12"><polyline points="20 6 9 17l-5-5"/></svg> Cancelled Cheque</div>' +
                            '<div class="cr-checklist-item"><svg class="cr-check-icon" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3" width="12" height="12"><polyline points="20 6 9 17l-5-5"/></svg> Bank Statement (Last 3 mo.)</div>' +
                            '<div class="cr-checklist-item"><svg class="cr-check-icon" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3" width="12" height="12"><polyline points="20 6 9 17l-5-5"/></svg> Auth Signatory List</div>' +
                        '</div>' +
                        '<button type="button" class="cr-btn cr-btn-chat-support">' +
                            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>' +
                            'CHAT WITH SUPPORT' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            // Section 6: Supporting Documents
            '<div class="cr-modal-section">' +
                '<div class="cr-section-header">' +
                    '<span class="cr-section-number">6</span>' +
                    '<h3 class="cr-section-title">SUPPORTING DOCUMENTS</h3>' +
                '</div>' +
                '<div class="cr-docs-section-layout">' +
                    '<div class="cr-docs-cards-grid">' +
                        renderDocumentCard('Constitution / Club Charter', 'Scanned official governing document', 'REQUIRED', 'required-pdf', app.constitution_path) +
                        renderDocumentCard('Proof of Meeting Venue', 'Utility bill, rental agreement or authorization', 'REQUIRED', 'required-pdf', app.venue_proof_path) +
                    '</div>' +
                    '<div class="cr-nic-copies-section">' +
                        '<h4 class="cr-sub-section-title-grey">' +
                            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" style="margin-right:6px;"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 13h4m-4 3h4m-10-1a3 3 0 0 1 6 0"/></svg>' +
                            'NIC COPIES OF KEY OFFICIALS' +
                        '</h4>' +
                        '<div class="cr-nic-copies-grid">' +
                            renderNicCopyCard('PRESIDENT', app.nic_president_path) +
                            renderNicCopyCard('SECRETARY', app.nic_secretary_path) +
                            renderNicCopyCard('TREASURER', app.nic_treasurer_path) +
                        '</div>' +
                    '</div>' +
                    '<div class="cr-photos-section-container">' +
                        '<h4 class="cr-sub-section-title-grey">' +
                            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" style="margin-right:6px;"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>' +
                            'CLUB ACTIVITY PHOTOS' +
                        '</h4>' +
                        '<div class="cr-photos-dashed-container">' +
                            (function() {
                                var photos = data.photos || [];
                                var photosCount = photos.length;
                                var photosStackedHtml = '';
                                if (photosCount > 0) {
                                    photosStackedHtml = '<div class="cr-photos-stacked-container">';
                                    var limit = Math.min(photosCount, 3);
                                    for (var pIdx = 0; pIdx < limit; pIdx++) {
                                        photosStackedHtml += '<div class="cr-photo-stacked-circle" style="z-index: ' + (10 - pIdx) + ';"><img src="' + escapeHtml(ROOT_URL + photos[pIdx].photo_path) + '" alt="Activity Photo"></div>';
                                    }
                                    if (photosCount > 3) {
                                        photosStackedHtml += '<div class="cr-photo-stacked-circle count-more" style="z-index: 5;">+' + (photosCount - 3) + '</div>';
                                    }
                                    photosStackedHtml += '</div>';
                                    photosStackedHtml += '<div class="cr-photos-count-label">' + photosCount + ' Photos Uploaded</div>';
                                    photosStackedHtml += '<button type="button" class="cr-btn cr-btn-view-photos" onclick="window.open(\'' + escapeHtml(ROOT_URL + photos[0].photo_path) + '\', \'_blank\')">VIEW ALL PHOTOS</button>';
                                } else {
                                    photosStackedHtml = '<div class="cr-photos-empty-state">No activity photos uploaded</div>';
                                }
                                return photosStackedHtml;
                            })() +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            // Section 7: Final Declaration
            '<div class="cr-modal-section">' +
                '<div class="cr-section-header">' +
                    '<span class="cr-section-number">7</span>' +
                    '<h3 class="cr-section-title">FINAL DECLARATION</h3>' +
                '</div>' +
                '<div class="cr-declaration-layout">' +
                    '<div class="cr-legal-ack-card">' +
                        '<h4 class="cr-card-section-title">' +
                            '<svg class="cr-card-header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>' +
                            'LEGAL ACKNOWLEDGEMENT' +
                        '</h4>' +
                        '<p class="cr-legal-subtitle">Submission of fraudulent data is a punishable offense under the Youth Development Act.</p>' +
                        '<div class="cr-declaration-checks">' +
                            '<div class="cr-decl-check-item" onclick="var cb = this.querySelector(\'.cr-decl-checkbox\'); cb.classList.toggle(\'checked\'); if (cb.classList.contains(\'checked\')) { cb.innerHTML = \'<svg viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;#1e40af&quot; stroke-width=&quot;3&quot; width=&quot;12&quot; height=&quot;12&quot;><polyline points=&quot;20 6 9 17 4 12&quot;/></svg>\'; } else { cb.innerHTML = \'\'; }">' +
                                '<span class="cr-decl-checkbox ' + (app.info_accuracy ? 'checked' : '') + '">' +
                                    (app.info_accuracy ? '<svg viewBox="0 0 24 24" fill="none" stroke="#1e40af" stroke-width="3" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>' : '') +
                                '</span>' +
                                '<span>I confirm all information provided is accurate to the best of my knowledge.</span>' +
                            '</div>' +
                            '<div class="cr-decl-check-item" onclick="var cb = this.querySelector(\'.cr-decl-checkbox\'); cb.classList.toggle(\'checked\'); if (cb.classList.contains(\'checked\')) { cb.innerHTML = \'<svg viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;#1e40af&quot; stroke-width=&quot;3&quot; width=&quot;12&quot; height=&quot;12&quot;><polyline points=&quot;20 6 9 17 4 12&quot;/></svg>\'; } else { cb.innerHTML = \'\'; }">' +
                                '<span class="cr-decl-checkbox ' + (app.terms_accepted ? 'checked' : '') + '">' +
                                    (app.terms_accepted ? '<svg viewBox="0 0 24 24" fill="none" stroke="#1e40af" stroke-width="3" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>' : '') +
                                '</span>' +
                                '<span>I agree to the NYSC terms and conditions regarding club governance.</span>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="cr-endorsement-card">' +
                        '<svg class="cr-endorsement-stamp-icon" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" width="24" height="24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
                        '<div class="cr-endorsement-label">OFFICIAL ENDORSEMENT</div>' +
                        '<div class="cr-signature-white-box">' +
                            '<span class="cr-signature-cursive">' + escapeHtml(app.digital_signature || 'Not signed') + '</span>' +
                            '<div class="cr-signature-subtext">DIGITAL SIGNATURE OF PRESIDENT</div>' +
                        '</div>' +
                        '<div class="cr-endorsement-date-row">' +
                            '<span class="cr-endorsement-date-label">DATE</span>' +
                            '<span class="cr-endorsement-date-val">' + escapeHtml(formatDOB(app.submitted_at)) + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            // Final Review Decision / Status summary
            (function () {
                if (app.status === 'Pending') {
                    return '<div class="cr-decision-panel">' +
                        '<div class="cr-section-header">' +
                            '<span class="cr-section-icon-decision">' +
                                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><polyline points="20 6 9 17l-5-5"/></svg>' +
                            '</span>' +
                            '<h3 class="cr-section-title">FINAL REVIEW DECISION</h3>' +
                        '</div>' +
                        '<div class="cr-decision-fields-row">' +
                            '<div class="cr-field">' +
                                '<label>REVIEW RESULT</label>' +
                                '<select id="crReviewResultSelect">' +
                                    '<option value="approve">Approve Registration</option>' +
                                    '<option value="reject">Reject Registration</option>' +
                                '</select>' +
                            '</div>' +
                            '<div class="cr-field">' +
                                '<label>REVIEWED BY</label>' +
                                '<input type="text" readonly value="' + escapeHtml(COORDINATOR_NAME) + ' — Divisional Coordinator" class="cr-readonly-input">' +
                            '</div>' +
                        '</div>' +
                        '<div class="cr-decision-remarks-section">' +
                            '<label>OFFICIAL REVIEW REMARKS (REQUIRED IF REJECTING)</label>' +
                            '<textarea id="crRemarks" placeholder="Provide detailed feedback for the club executives..."></textarea>' +
                        '</div>' +
                        '<div class="cr-decision-impact-alert approve" id="crDecisionImpactAlert">' +
                            '<div class="cr-impact-icon-circle approve">' +
                                '<svg viewBox="0 0 24 24" fill="none" stroke="#047857" stroke-width="3" width="14" height="14"><polyline points="20 6 9 17l-5-5"/></svg>' +
                            '</div>' +
                            '<div class="cr-impact-text-content">' +
                                '<strong>IMPACT OF APPROVAL</strong>' +
                                '<p>Approving this comprehensive application will officially register the <strong>' + escapeHtml(app.club_name) + '</strong>, generate their unique NYSC Index Number, and dispatch login credentials to the President, Secretary, and Treasurer. All initial assets will be logged into the divisional database.</p>' +
                            '</div>' +
                        '</div>' +
                        '<div class="cr-decision-footer-bar">' +
                            '<div class="cr-decision-footer-actions">' +
                                '<button type="button" class="cr-btn-cancel-link" id="crCancelReviewBtn">Cancel Review</button>' +
                                '<button type="button" class="cr-btn cr-btn-submit-decision" id="crConfirmSubmitBtn">Confirm &amp; Submit Decision</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
                } else {
                    return '<div class="cr-decision-panel readonly">' +
                        '<div class="cr-section-header">' +
                            '<span class="cr-section-icon-decision">' +
                                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' +
                            '</span>' +
                            '<h3 class="cr-section-title">REVIEW DECISION &amp; STATUS</h3>' +
                        '</div>' +
                        '<div class="cr-readonly-summary-grid">' +
                            '<div class="cr-field">' +
                                '<label>FINAL STATUS</label>' +
                                '<div><span class="cr-badge ' + escapeHtml((app.status || '').toLowerCase()) + '">' + escapeHtml(app.status || '—') + '</span></div>' +
                            '</div>' +
                            '<div class="cr-field">' +
                                '<label>REVIEWED BY</label>' +
                                '<span class="cr-field-val-bold">' + escapeHtml(app.reviewed_by_name || COORDINATOR_NAME) + '</span>' +
                            '</div>' +
                            '<div class="cr-field">' +
                                '<label>REVIEWED AT</label>' +
                                '<span class="cr-field-val-bold">' + (app.reviewed_at ? formatDOB(app.reviewed_at) : '—') + '</span>' +
                            '</div>' +
                        '</div>' +
                        (app.rejection_remarks ?
                        '<div class="cr-decision-remarks-section" style="margin-top:14px;">' +
                            '<label>REVIEW REMARKS</label>' +
                            '<div class="cr-readonly-remarks-box">' + escapeHtml(app.rejection_remarks) + '</div>' +
                        '</div>' : '') +
                        '<div class="cr-decision-footer-bar">' +
                            '<div class="cr-decision-footer-actions">' +
                                '<button type="button" class="cr-btn cr-btn-primary" id="crCloseReadonlyBtn" style="padding: 10px 24px;">Close</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
                }
            })() +
            '</div>';

        if (app.status === 'Pending') {
            var selectEl = document.getElementById('crReviewResultSelect');
            var alertEl = document.getElementById('crDecisionImpactAlert');

            if (selectEl && alertEl) {
                selectEl.addEventListener('change', function () {
                    if (selectEl.value === 'approve') {
                        alertEl.className = 'cr-decision-impact-alert approve';
                        alertEl.innerHTML = 
                            '<div class="cr-impact-icon-circle approve">' +
                                '<svg viewBox="0 0 24 24" fill="none" stroke="#047857" stroke-width="3" width="14" height="14"><polyline points="20 6 9 17l-5-5"/></svg>' +
                            '</div>' +
                            '<div class="cr-impact-text-content">' +
                                '<strong>IMPACT OF APPROVAL</strong>' +
                                '<p>Approving this comprehensive application will officially register the <strong>' + escapeHtml(app.club_name) + '</strong>, generate their unique NYSC Index Number, and dispatch login credentials to the President, Secretary, and Treasurer. All initial assets will be logged into the divisional database.</p>' +
                            '</div>';
                    } else {
                        alertEl.className = 'cr-decision-impact-alert reject';
                        alertEl.innerHTML = 
                            '<div class="cr-impact-icon-circle reject">' +
                                '<svg viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="3" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                            '</div>' +
                            '<div class="cr-impact-text-content">' +
                                '<strong>IMPACT OF REJECTION</strong>' +
                                '<p>Rejecting this application will notify the proposer and nominees with the provided remarks. They will need to correct and resubmit the application.</p>' +
                            '</div>';
                    }
                });
            }

            var cancelBtn = document.getElementById('crCancelReviewBtn');
            if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

            var confirmBtn = document.getElementById('crConfirmSubmitBtn');
            if (confirmBtn && selectEl) {
                confirmBtn.addEventListener('click', function () {
                    submitDecision(app.application_id, selectEl.value, app.club_name);
                });
            }
        } else {
            var closeReadonlyBtn = document.getElementById('crCloseReadonlyBtn');
            if (closeReadonlyBtn) closeReadonlyBtn.addEventListener('click', closeModal);
        }

        var closeHeaderBtn = document.getElementById('crModalCloseBtn');
        if (closeHeaderBtn) closeHeaderBtn.addEventListener('click', closeModal);
    }

    function renderSuccessModal(clubName, clubCode, applicationId) {
        modalContent.innerHTML =
            '<div class="cr-success-modal-content">' +
                '<div class="cr-success-icon-wrapper">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" width="40" height="40"><polyline points="20 6 9 17l-5-5"/></svg>' +
                '</div>' +
                '<h2 class="cr-success-title">Club Created Successfully</h2>' +
                '<p class="cr-success-subtitle"><strong>' + escapeHtml(clubName) + '</strong> has been approved. The club record was created automatically and login credentials were emailed to all 3 key/elect executives.</p>' +
                
                '<div class="cr-success-details-grid">' +
                    '<div class="cr-success-field"><label>CLUB ID / CODE</label><span class="cr-success-val-bold">' + escapeHtml(clubCode) + '</span></div>' +
                    '<div class="cr-success-field"><label>STATUS</label><span><span class="cr-success-status-badge">Active</span></span></div>' +
                '</div>' +

                '<div class="cr-success-actions">' +
                    '<button type="button" class="cr-btn cr-btn-success-secondary" id="crViewRecordBtn">View Club Record</button>' +
                    '<button type="button" class="cr-btn cr-btn-success-primary" id="crDoneSuccessBtn">Done</button>' +
                '</div>' +
            '</div>';

        function handleDone() {
            closeModal();
            var cardEl = grid.querySelector('.cr-review-btn[data-id="' + applicationId + '"]');
            if (cardEl) {
                var card = cardEl.closest('.cr-card');
                if (card) card.remove();
            }
            if (pendingGridHtml !== null && statPending && statPending.classList.contains('is-active')) {
                pendingGridHtml = grid.innerHTML;
            }
            var valPending = statPending ? statPending.querySelector('.cr-stat-value') : null;
            var valApproved = statApproved ? statApproved.querySelector('.cr-stat-value') : null;
            if (valPending && parseInt(valPending.textContent, 10) > 0) {
                valPending.textContent = parseInt(valPending.textContent, 10) - 1;
            }
            if (valApproved) {
                valApproved.textContent = parseInt(valApproved.textContent, 10) + 1;
            }
        }

        document.getElementById('crViewRecordBtn').addEventListener('click', handleDone);
        document.getElementById('crDoneSuccessBtn').addEventListener('click', handleDone);
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
    function submitDecision(applicationId, action, clubName) {
        var remarks = document.getElementById('crRemarks').value.trim();

        if (action === 'reject' && !remarks) {
            showToast('Please provide a reason for rejecting this application.', true);
            return;
        }

        var confirmMsg = action === 'approve'
            ? 'Approve ' + clubName + '? This will create login accounts for the executive committee and email their credentials. This cannot be undone.'
            : 'Reject ' + clubName + '? The proposer will be notified by email with your remarks.';

        if (!confirm(confirmMsg)) {
            return;
        }

        var submitBtn = document.getElementById('crConfirmSubmitBtn');
        if (submitBtn) submitBtn.disabled = true;

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
                    if (submitBtn) submitBtn.disabled = false;
                    return;
                }
                if (action === 'approve') {
                    renderSuccessModal(clubName, data.club_code, applicationId);
                } else {
                    closeModal();
                    var cardEl = grid.querySelector('.cr-review-btn[data-id="' + applicationId + '"]');
                    if (cardEl) {
                        var card = cardEl.closest('.cr-card');
                        if (card) card.remove();
                    }
                    if (pendingGridHtml !== null && statPending && statPending.classList.contains('is-active')) {
                        pendingGridHtml = grid.innerHTML;
                    }
                    var valPending = statPending ? statPending.querySelector('.cr-stat-value') : null;
                    var valRejected = statRejected ? statRejected.querySelector('.cr-stat-value') : null;
                    if (valPending && parseInt(valPending.textContent, 10) > 0) {
                        valPending.textContent = parseInt(valPending.textContent, 10) - 1;
                    }
                    if (valRejected) {
                        valRejected.textContent = parseInt(valRejected.textContent, 10) + 1;
                    }
                    showToast('Application rejected. The proposer has been notified.');
                }
            })
            .catch(function () {
                showToast('Something went wrong. Please try again.', true);
                if (submitBtn) submitBtn.disabled = false;
            });
    }

})();
