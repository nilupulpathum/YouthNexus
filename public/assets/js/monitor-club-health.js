/**
 * monitorclubhealth.js
 * Client-side search, collapsible filter panel, sort toggle, and modal interactivity for Monitor Club Health
 */

document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const searchInput        = document.getElementById('mchSearchInput');
    const topSearchInput     = document.getElementById('mchTopSearch');
    const searchClearBtn     = document.getElementById('mchSearchClear');
    const filterBtn          = document.getElementById('mchFilterBtn');
    const filterPanel        = document.getElementById('mchFilterPanel');
    const filterStatus       = document.getElementById('mchFilterStatus');
    const addFilterBtn       = document.getElementById('mchAddFilterBtn');
    const clearFilterBtn     = document.getElementById('mchClearFilterBtn');
    const sortToggleBtn      = document.getElementById('mchSortToggleBtn');
    const statCards          = document.querySelectorAll('.mch-stat-card');
    const gridContainer      = document.getElementById('mchClubGrid');
    const noFilterMatch      = document.getElementById('mchNoFilterMatch');
    const resetFilterBtn     = document.getElementById('mchResetFilters');
    const exportBtn          = document.getElementById('mchExportBtn');
    const toastEl            = document.getElementById('mchToast');

    // Detail Modal Elements
    const detailModal        = document.getElementById('mchDetailModal');
    const detailCloseBtn     = document.getElementById('mchDetailCloseBtn');
    const modalTitle         = document.getElementById('mchModalTitle');
    const modalCode          = document.getElementById('mchModalCode');
    const modalStatusPill    = document.getElementById('mchModalStatusPill');
    const modalDesc          = document.getElementById('mchModalDesc');
    const modalLocation      = document.getElementById('mchModalLocation');
    const modalEstDate       = document.getElementById('mchModalEstDate');
    const modalActiveMembers = document.getElementById('mchModalActiveMembers');
    const modalExecList      = document.getElementById('mchModalExecList');
    const modalOverallScore  = document.getElementById('mchModalOverallScore');
    const openFlagModalBtn   = document.getElementById('mchOpenFlagModalBtn');
    const editDetailsBtn     = document.getElementById('mchEditDetailsBtn');
    const downloadReportBtn  = document.getElementById('mchDownloadReportBtn');

    // Flag Modal Elements
    const flagModal          = document.getElementById('mchFlagModal');
    const flagCloseBtn       = document.getElementById('mchFlagCloseBtn');
    const flagCancelBtn      = document.getElementById('mchFlagCancelBtn');
    const flagSubmitBtn      = document.getElementById('mchFlagSubmitBtn');
    const flagClubName       = document.getElementById('mchFlagClubName');
    const flagScore          = document.getElementById('mchFlagScore');
    const flagComment        = document.getElementById('mchFlagComment');
    const flagSeverity       = document.getElementById('mchFlagSeverity');

    if (!gridContainer) return;

    let currentSortOrder = 'desc'; // 'desc' = Highest Score First, 'asc' = Lowest Score First
    let currentClubData = null;

    // Get all initial card elements
    const cards = Array.from(gridContainer.querySelectorAll('.mch-card'));
    const totalCount = cards.length;

    /**
     * Show toast message
     */
    function showToast(message) {
        if (!toastEl) return;
        toastEl.textContent = message;
        toastEl.classList.add('show');
        setTimeout(() => {
            toastEl.classList.remove('show');
        }, 3600);
    }

    /**
     * Filter cards based on search query and health status filter
     */
    function filterCards() {
        if (!gridContainer) return;
        const query  = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const status = filterStatus ? filterStatus.value : '';
        let visibleCount = 0;

        cards.forEach(card => {
            const clubName   = (card.dataset.name || '').toLowerCase();
            const clubCode   = (card.dataset.code || '').toLowerCase();
            const cardStatus = card.dataset.status || '';

            const textMatch   = !query || clubName.includes(query) || clubCode.includes(query);
            const statusMatch = !status || cardStatus === status;

            if (textMatch && statusMatch) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Sync stat card active visual state with current status filter
        statCards.forEach(stat => {
            const filterVal = stat.dataset.filter;
            const mappedStatus = filterVal === 'Healthy' ? 'Green' : (filterVal === 'At Risk' ? 'Yellow' : (filterVal === 'Dormant' ? 'Red' : ''));
            if (status && mappedStatus === status) {
                stat.classList.add('is-active');
            } else {
                stat.classList.remove('is-active');
            }
        });

        // Toggle clear search button
        if (searchClearBtn) {
            searchClearBtn.style.display = query.length > 0 ? 'inline-block' : 'none';
        }

        // Empty state feedback
        if (noFilterMatch) {
            if (visibleCount === 0 && totalCount > 0) {
                noFilterMatch.style.display = 'flex';
            } else {
                noFilterMatch.style.display = 'none';
            }
        }
    }

    /**
     * Sort cards by overall health score
     */
    function sortCards(order) {
        currentSortOrder = order;
        const sortedCards = [...cards].sort((a, b) => {
            const scoreA = parseFloat(a.dataset.score) || 0;
            const scoreB = parseFloat(b.dataset.score) || 0;
            return order === 'asc' ? scoreA - scoreB : scoreB - scoreA;
        });

        sortedCards.forEach(card => {
            gridContainer.appendChild(card);
        });
    }

    // --- Search input listener ---
    function handleSearch(val) {
        if (searchInput && searchInput.value !== val) searchInput.value = val;
        if (topSearchInput && topSearchInput.value !== val) topSearchInput.value = val;
        filterCards();
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => handleSearch(e.target.value));
    }
    if (topSearchInput) {
        topSearchInput.addEventListener('input', (e) => handleSearch(e.target.value));
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', () => {
            handleSearch('');
            if (searchInput) searchInput.focus();
        });
    }

    // --- Filter panel toggle button ---
    if (filterBtn && filterPanel) {
        filterBtn.addEventListener('click', () => {
            const isExpanded = filterBtn.getAttribute('aria-expanded') === 'true';
            filterBtn.setAttribute('aria-expanded', !isExpanded);
            filterPanel.classList.toggle('open', !isExpanded);
        });
    }

    // --- Filter panel action buttons ---
    if (addFilterBtn) {
        addFilterBtn.addEventListener('click', () => {
            filterCards();
        });
    }

    if (filterStatus) {
        filterStatus.addEventListener('change', () => {
            filterCards();
        });
    }

    if (clearFilterBtn) {
        clearFilterBtn.addEventListener('click', () => {
            if (filterStatus) filterStatus.value = '';
            if (searchInput) searchInput.value = '';
            if (topSearchInput) topSearchInput.value = '';
            filterCards();
        });
    }

    // --- Sort toggle button ---
    if (sortToggleBtn) {
        sortToggleBtn.addEventListener('click', () => {
            if (currentSortOrder === 'desc') {
                currentSortOrder = 'asc';
                sortToggleBtn.textContent = 'Sort: Lowest Score First ▾';
                sortToggleBtn.setAttribute('data-sort', 'asc');
            } else {
                currentSortOrder = 'desc';
                sortToggleBtn.textContent = 'Sort: Highest Score First ▾';
                sortToggleBtn.setAttribute('data-sort', 'desc');
            }
            sortCards(currentSortOrder);
        });
    }

    // --- Stat cards click listener (bridges to Health Status filter) ---
    statCards.forEach(stat => {
        stat.addEventListener('click', () => {
            const filterVal = stat.dataset.filter;
            const mappedStatus = filterVal === 'Healthy' ? 'Green' : (filterVal === 'At Risk' ? 'Yellow' : (filterVal === 'Dormant' ? 'Red' : ''));

            if (filterStatus) {
                if (filterStatus.value === mappedStatus) {
                    filterStatus.value = '';
                } else {
                    filterStatus.value = mappedStatus;
                }
            }
            filterCards();
        });
    });

    // --- Reset filters button in empty state ---
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', () => {
            if (filterStatus) filterStatus.value = '';
            if (searchInput) searchInput.value = '';
            if (topSearchInput) topSearchInput.value = '';
            filterCards();
        });
    }

    // --- Export button handler ---
    if (exportBtn) {
        exportBtn.addEventListener('click', (e) => {
            e.preventDefault();
            showToast('Exporting club health overview...');
        });
    }

    // =========================================================================
    // MODALS LOGIC
    // =========================================================================

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function openDetailModal(card) {
        if (!detailModal) return;

        const id       = card.dataset.id;
        const name     = card.dataset.name || 'Club';
        const code     = card.dataset.code || 'CLB-000000';
        const status   = card.dataset.status || 'Green';
        const score    = parseFloat(card.dataset.score) || 0;
        const members  = card.dataset.members || '0';
        const desc     = card.dataset.desc || 'No description provided.';
        const division = card.dataset.division || 'Colombo';
        const estDate  = card.dataset.date || 'Not available';

        let committee = [];
        try {
            committee = JSON.parse(card.dataset.committee || '[]');
        } catch (e) {
            committee = [];
        }

        currentClubData = { club_id: id, name, code, status, score, members, division, estDate, committee };

        if (modalTitle) modalTitle.textContent = name;
        if (modalCode) modalCode.textContent = code;
        if (modalDesc) modalDesc.textContent = desc;
        if (modalLocation) modalLocation.textContent = division;
        if (modalEstDate) modalEstDate.textContent = estDate;
        if (modalActiveMembers) modalActiveMembers.textContent = members;

        // Health Status Pill
        if (modalStatusPill) {
            const isNeverScored = (parseFloat(score) === 0 && status === 'Green') || status === 'NotScored';
            if (isNeverScored) {
                modalStatusPill.className = 'mch-modal-status-pill not-scored';
                modalStatusPill.textContent = '• Not Yet Scored';
            } else {
                modalStatusPill.className = 'mch-modal-status-pill ' + status.toLowerCase();
                let label = '• Healthy (Green)';
                if (status === 'Yellow') label = '• At Risk (Yellow)';
                if (status === 'Red') label = '• Dormant (Red)';
                modalStatusPill.textContent = label;
            }
        }

        // Issue #5 Fixed: Render real Executive Committee dynamically
        if (modalExecList) {
            if (Array.isArray(committee) && committee.length > 0) {
                let html = '';
                committee.forEach(member => {
                    const initials = member.initials || 'U';
                    const memberName = member.name || 'Member';
                    const roleType = member.role_type || 'Officer';
                    html += `
                        <div class="mch-exec-member">
                            <div class="mch-exec-avatar">${escapeHtml(initials)}</div>
                            <div class="mch-exec-info">
                                <b>${escapeHtml(memberName)}</b>
                                <span>${escapeHtml(roleType)}</span>
                            </div>
                        </div>
                    `;
                });
                modalExecList.innerHTML = html;
            } else {
                modalExecList.innerHTML = '<p class="mch-empty-subtext">No committee members recorded</p>';
            }
        }

        // Issue #7 Fixed: Real Overall Health Score (no fake component formula)
        if (modalOverallScore) {
            modalOverallScore.textContent = `${Math.round(score)} / 100`;
        }

        // Keep administrative actions (Flagging / Editing Details) exclusive to allowed roles
        const userRole = document.body.dataset.userRole;
        if (userRole === 'DivisionalCoordinator' || userRole === 'DivisionalSecretary') {
            if (openFlagModalBtn) openFlagModalBtn.style.display = '';
        } else {
            if (openFlagModalBtn) openFlagModalBtn.style.display = 'none';
        }
        
        if (userRole === 'DivisionalCoordinator') {
            if (editDetailsBtn) editDetailsBtn.style.display = '';
        } else {
            if (editDetailsBtn) editDetailsBtn.style.display = 'none';
        }

        detailModal.classList.add('is-open');

        const pendingBox = document.getElementById('mchEventsPendingBox');
        const eventList = document.getElementById('mchModalEventList');
        const summaryStrip = document.getElementById('mchModalEventSummaryStrip');
        const viewAllBtn = document.getElementById('mchViewAllEventsBtn');

        if (pendingBox) {
            pendingBox.style.display = 'flex';
            pendingBox.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><p>Loading events...</p>';
        }
        if (eventList) {
            eventList.style.display = 'none';
            eventList.innerHTML = '';
        }
        if (summaryStrip) {
            summaryStrip.style.display = 'none';
        }
        if (viewAllBtn) {
            viewAllBtn.style.display = 'none';
        }

        if (id) {
            // Usually ROOT is provided by a global script tag. If not, fallback to /YouthNexus/YouthNexus/public
            const rootUrl = (typeof ROOT !== 'undefined') ? ROOT : '/YouthNexus/YouthNexus/public';
            fetch(rootUrl + '/monitorclubhealth/details/' + id)
                .then(res => {
                    if (!res.ok) throw new Error('Failed to fetch club details.');
                    return res.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    
                    if (currentClubData) {
                        currentClubData.apiDetails = data;
                    }

                    if (pendingBox) pendingBox.style.display = 'none';

                    // Issue A: Populate summary strip
                    if (summaryStrip) {
                        const summary = data.summary || {};
                        const conductedCount = summary.conducted_count || 0;
                        const avgRate = summary.avg_attendance_rate;

                        const summaryConductedEl = document.getElementById('mchModalSummaryEventsConducted');
                        const summaryAvgEl = document.getElementById('mchModalSummaryAvgAttendance');

                        if (data.events && data.events.length > 0) {
                            summaryStrip.style.display = 'flex';
                            if (summaryConductedEl) {
                                summaryConductedEl.textContent = conductedCount;
                            }
                            if (summaryAvgEl) {
                                summaryAvgEl.textContent = (avgRate !== null && avgRate !== undefined) ? `${avgRate}%` : 'No events in this period';
                            }
                        } else {
                            summaryStrip.style.display = 'none';
                        }
                    }

                    if (eventList) {
                        eventList.style.display = 'flex';
                        if (data.events && data.events.length > 0) {
                            let html = '';
                            data.events.forEach((ev, index) => {
                                const title = escapeHtml(ev.title || 'Event');
                                const date = ev.start_datetime ? new Date(ev.start_datetime).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '';
                                const type = ev.event_type ? escapeHtml(ev.event_type) : 'Event';
                                const attendanceCount = parseInt(ev.attendance_recorded_count || 0);
                                const presentCount = parseInt(ev.present_count || 0);
                                const rate = attendanceCount > 0 ? Math.round((presentCount / attendanceCount) * 100) : 0;
                                
                                let badgeClass = 'red';
                                if (rate >= 75) {
                                    badgeClass = 'green';
                                } else if (rate >= 50) {
                                    badgeClass = 'yellow';
                                }
                                
                                // Issue C: Hide rows after the first 5 by default
                                const hiddenClass = index >= 5 ? 'mch-event-card mch-event-hidden' : 'mch-event-card';
                                const hiddenStyle = index >= 5 ? 'style="display: none;"' : '';
                                
                                html += `
                                    <div class="${hiddenClass}" ${hiddenStyle}>
                                        <div class="mch-event-info">
                                            <h4 class="mch-event-title">${title}</h4>
                                            <div class="mch-event-meta">
                                                <span class="mch-event-type-badge">${type}</span>
                                                <span>${date}</span>
                                            </div>
                                        </div>
                                        <span class="mch-event-attendance-badge ${badgeClass}">
                                            ${presentCount}/${attendanceCount} (${rate}%)
                                        </span>
                                    </div>
                                `;
                            });
                            eventList.innerHTML = html;

                            // Issue C: Setup view all button if more than 5 events exist
                            if (data.events.length > 5 && viewAllBtn) {
                                viewAllBtn.style.display = 'block';
                                viewAllBtn.textContent = `View all ${data.events.length} events`;
                                
                                // Re-clone button to strip any old listeners cleanly
                                const newBtn = viewAllBtn.cloneNode(true);
                                viewAllBtn.parentNode.replaceChild(newBtn, viewAllBtn);
                                
                                newBtn.addEventListener('click', () => {
                                    const hiddenCards = eventList.querySelectorAll('.mch-event-hidden');
                                    hiddenCards.forEach(c => {
                                        c.style.display = 'flex';
                                    });
                                    newBtn.style.display = 'none';
                                });
                            }
                        } else {
                            if (pendingBox) {
                                pendingBox.style.display = 'flex';
                                pendingBox.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><p>No events recorded for this club yet</p>';
                            }
                            eventList.style.display = 'none';
                        }
                    }
                })
                .catch(err => {
                    if (pendingBox) {
                        pendingBox.style.display = 'flex';
                        pendingBox.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><p style="color:#dc2626;">Error loading events.</p>';
                    }
                    console.error(err);
                });
        }
    }

    function closeDetailModal() {
        if (detailModal) detailModal.classList.remove('is-open');
    }

    function openFlagModal() {
        if (!flagModal || !currentClubData) return;
        closeDetailModal();

        if (flagClubName) flagClubName.textContent = currentClubData.name;
        if (flagScore) flagScore.textContent = `${Math.round(currentClubData.score)} / 100`;
        if (flagComment) flagComment.value = '';

        if (currentClubData.apiDetails) {
            const summary = currentClubData.apiDetails.summary || {};
            const eventsConducted = summary.conducted_count || 0;
            const avgRate = summary.avg_attendance_rate;

            const elEvents = document.getElementById('mchFlagEventsConducted');
            if (elEvents) elEvents.textContent = eventsConducted;
            
            const elRate = document.getElementById('mchFlagAvgRate');
            if (elRate) elRate.textContent = (avgRate !== null && avgRate !== undefined) ? `${avgRate}%` : '—';
            
            const elPriorFlags = document.getElementById('mchFlagPriorFlagsList');
            const elPriorFlagsContainer = document.getElementById('mchFlagPriorFlagsContainer');
            if (elPriorFlags && elPriorFlagsContainer) {
                elPriorFlags.innerHTML = '';
                const priorFlags = currentClubData.apiDetails.priorFlags || [];
                if (priorFlags.length > 0) {
                    elPriorFlagsContainer.style.display = 'block';
                    priorFlags.forEach(pf => {
                        const sevClass = pf.severity.toLowerCase();
                        elPriorFlags.innerHTML += `
                            <div class="mch-flag-prior-flag-item">
                                <span class="mch-flag-prior-badge ${sevClass}">${escapeHtml(pf.severity)}</span>
                                <span>${escapeHtml(pf.flagged_by_role)} (${escapeHtml(pf.flagged_by_name)}) &mdash; ${escapeHtml(pf.comment)}</span>
                            </div>
                        `;
                    });
                } else {
                    elPriorFlagsContainer.style.display = 'none';
                }
            }
        }

        flagModal.classList.add('is-open');
    }

    function closeFlagModal() {
        if (flagModal) flagModal.classList.remove('is-open');
    }

    // Attach click listeners to cards
    cards.forEach(card => {
        card.addEventListener('click', () => {
            openDetailModal(card);
        });
    });

    if (detailCloseBtn) {
        detailCloseBtn.addEventListener('click', closeDetailModal);
    }
    if (detailModal) {
        detailModal.addEventListener('click', (e) => {
            if (e.target === detailModal) closeDetailModal();
        });
    }

    if (openFlagModalBtn) {
        openFlagModalBtn.addEventListener('click', openFlagModal);
    }
    if (flagCloseBtn) {
        flagCloseBtn.addEventListener('click', closeFlagModal);
    }
    if (flagCancelBtn) {
        flagCancelBtn.addEventListener('click', closeFlagModal);
    }
    if (flagModal) {
        flagModal.addEventListener('click', (e) => {
            if (e.target === flagModal) closeFlagModal();
        });
    }

    // Issue #8 Fixed: Honest confirmation copy on flag submission preview
    if (flagSubmitBtn) {
        flagSubmitBtn.addEventListener('click', () => {
            const comment = (flagComment ? flagComment.value : '').trim();
            const severity = document.getElementById('mchFlagSeverity') ? document.getElementById('mchFlagSeverity').value : 'Medium';

            if (!comment) {
                alert('Please provide a reason / comment for the NYSC Admin.');
                if (flagComment) flagComment.focus();
                return;
            }

            const rootUrl = (typeof ROOT !== 'undefined') ? ROOT : '/YouthNexus/YouthNexus/public';
            const csrfToken = (typeof CSRF_TOKEN !== 'undefined') ? CSRF_TOKEN : '';

            fetch(rootUrl + '/monitorclubhealth/flag/' + currentClubData.club_id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    csrf_token: csrfToken,
                    severity: severity,
                    comment: comment,
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeFlagModal();
                    showToast('Flag submitted to NYSC Admin.');
                    // Optionally, trigger a refresh here if needed
                } else {
                    alert(data.error || 'Could not submit flag. Please try again.');
                }
            })
            .catch(() => alert('Could not submit flag. Please try again.'));
        });
    }

    if (downloadReportBtn) {
        downloadReportBtn.addEventListener('click', () => {
            showToast('Generating club health report PDF...');
        });
    }

    if (editDetailsBtn) {
        editDetailsBtn.addEventListener('click', () => {
            showToast('Editing club details will be enabled in Phase 2.');
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeDetailModal();
            closeFlagModal();
        }
    });

    // Initial sort
    sortCards('desc');
});
