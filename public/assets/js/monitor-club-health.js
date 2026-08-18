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

        currentClubData = { name, code, status, score, members, division, estDate, committee };

        if (modalTitle) modalTitle.textContent = name;
        if (modalCode) modalCode.textContent = code;
        if (modalDesc) modalDesc.textContent = desc;
        if (modalLocation) modalLocation.textContent = division;
        if (modalEstDate) modalEstDate.textContent = estDate;
        if (modalActiveMembers) modalActiveMembers.textContent = members;

        // Health Status Pill
        if (modalStatusPill) {
            modalStatusPill.className = 'mch-modal-status-pill ' + status.toLowerCase();
            let label = '• Healthy (Green)';
            if (status === 'Yellow') label = '• At Risk (Yellow)';
            if (status === 'Red') label = '• Dormant (Red)';
            modalStatusPill.textContent = label;
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

        detailModal.classList.add('is-open');
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
            if (!comment) {
                alert('Please provide a reason / comment for the NYSC Admin.');
                if (flagComment) flagComment.focus();
                return;
            }
            closeFlagModal();
            showToast('This flag was not saved. The review workflow will be enabled once the Club Health Flag system is built.');
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
