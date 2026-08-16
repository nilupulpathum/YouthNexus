/**
 * monitorclubhealth.js
 * Client-side search, filtering, sorting, and modal interactivity for Monitor Club Health
 */

document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const searchInput        = document.getElementById('mchSearchInput');
    const topSearchInput     = document.getElementById('mchTopSearch');
    const searchClearBtn     = document.getElementById('mchSearchClear');
    const filterChips        = document.querySelectorAll('.mch-chip');
    const statCards          = document.querySelectorAll('.mch-stat-card');
    const sortSelect         = document.getElementById('mchSortSelect');
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
    const modalEventScore    = document.getElementById('mchModalEventScore');
    const modalFinanceScore  = document.getElementById('mchModalFinanceScore');
    const modalAttendanceScore = document.getElementById('mchModalAttendanceScore');
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

    let currentFilter = 'All';
    let currentSearch = '';
    let currentSort   = 'score-desc';
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
        }, 3200);
    }

    /**
     * Filter and Sort cards
     */
    function applyFiltersAndSort() {
        let visibleCount = 0;
        const searchTerms = currentSearch.toLowerCase().trim();

        // 1. Filter
        cards.forEach(card => {
            const clubName   = (card.dataset.name || '').toLowerCase();
            const clubCode   = (card.dataset.code || '').toLowerCase();
            const status     = card.dataset.status || '';
            const isFlagged  = card.dataset.flagged === '1';

            // Check Search
            const matchesSearch = !searchTerms || clubName.includes(searchTerms) || clubCode.includes(searchTerms);

            // Check Status Filter
            let matchesFilter = false;
            if (currentFilter === 'All') {
                matchesFilter = true;
            } else if (currentFilter === 'Healthy' && status === 'Green') {
                matchesFilter = true;
            } else if (currentFilter === 'At Risk' && status === 'Yellow') {
                matchesFilter = true;
            } else if (currentFilter === 'Dormant' && status === 'Red') {
                matchesFilter = true;
            }

            if (matchesSearch && matchesFilter) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // 2. Sort visible cards
        const sortedCards = [...cards].sort((a, b) => {
            const scoreA   = parseFloat(a.dataset.score) || 0;
            const scoreB   = parseFloat(b.dataset.score) || 0;
            const nameA    = (a.dataset.name || '').toLowerCase();
            const nameB    = (b.dataset.name || '').toLowerCase();
            const membersA = parseInt(a.dataset.members, 10) || 0;
            const membersB = parseInt(b.dataset.members, 10) || 0;

            switch (currentSort) {
                case 'score-asc':
                    return scoreA - scoreB;
                case 'name-asc':
                    return nameA.localeCompare(nameB);
                case 'name-desc':
                    return nameB.localeCompare(nameA);
                case 'members-desc':
                    return membersB - membersA;
                case 'score-desc':
                default:
                    return scoreB - scoreA;
            }
        });

        // Re-append sorted cards to the DOM
        sortedCards.forEach(card => {
            gridContainer.appendChild(card);
        });

        // 3. Update empty state
        if (noFilterMatch) {
            if (visibleCount === 0 && totalCount > 0) {
                noFilterMatch.style.display = 'flex';
            } else {
                noFilterMatch.style.display = 'none';
            }
        }
    }

    /**
     * Set active filter chip and sync stat card active states
     */
    function setActiveFilter(filterName) {
        currentFilter = filterName;

        // Update chips
        filterChips.forEach(chip => {
            if (chip.dataset.filter === filterName) {
                chip.classList.add('is-active');
            } else {
                chip.classList.remove('is-active');
            }
        });

        // Update stat cards
        statCards.forEach(stat => {
            if (stat.dataset.filter === filterName) {
                stat.classList.add('is-active');
            } else {
                stat.classList.remove('is-active');
            }
        });

        applyFiltersAndSort();
    }

    // --- Search input listener ---
    function handleSearch(val) {
        currentSearch = val;
        if (searchInput && searchInput.value !== val) searchInput.value = val;
        if (topSearchInput && topSearchInput.value !== val) topSearchInput.value = val;

        if (searchClearBtn) {
            searchClearBtn.style.display = currentSearch.length > 0 ? 'block' : 'none';
        }
        applyFiltersAndSort();
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

    // --- Filter chip click listener ---
    filterChips.forEach(chip => {
        chip.addEventListener('click', () => {
            const filterVal = chip.dataset.filter || 'All';
            setActiveFilter(filterVal);
        });
    });

    // --- Stat card click listener ---
    statCards.forEach(stat => {
        stat.addEventListener('click', () => {
            const filterVal = stat.dataset.filter;
            if (currentFilter === filterVal) {
                setActiveFilter('All');
            } else {
                setActiveFilter(filterVal);
            }
        });
    });

    // --- Sort dropdown listener ---
    if (sortSelect) {
        sortSelect.addEventListener('change', (e) => {
            currentSort = e.target.value;
            applyFiltersAndSort();
        });
    }

    // --- Reset filters button ---
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', () => {
            handleSearch('');
            if (sortSelect) {
                sortSelect.value = 'score-desc';
                currentSort = 'score-desc';
            }
            setActiveFilter('All');
        });
    }

    // --- Export button stub handler ---
    if (exportBtn) {
        exportBtn.addEventListener('click', (e) => {
            e.preventDefault();
            showToast('Exporting club health metrics summary...');
        });
    }

    // =========================================================================
    // MODALS LOGIC
    // =========================================================================

    function openDetailModal(card) {
        if (!detailModal) return;

        const name    = card.dataset.name || 'Club';
        const code    = card.dataset.code || 'CLB-000000';
        const status  = card.dataset.status || 'Green';
        const score   = parseFloat(card.dataset.score) || 0;
        const members = card.dataset.members || '0';
        const desc    = card.dataset.desc || 'Empowering local youth through community service and skill development.';
        const division = card.dataset.division || 'Colombo';
        const estDate = card.dataset.date || 'March 2021';

        currentClubData = { name, code, status, score, members, division, estDate };

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

        // Proportional component scores derived for detail view
        const eventScore = Math.round(score * 0.35 * 30 / 35);
        const financeScore = Math.round(score * 0.35 * 30 / 35);
        const attendanceScore = Math.max(0, Math.round(score - eventScore - financeScore));

        if (modalEventScore) modalEventScore.textContent = `${Math.min(30, Math.max(0, eventScore))} / 30`;
        if (modalFinanceScore) modalFinanceScore.textContent = `${Math.min(30, Math.max(0, financeScore))} / 30`;
        if (modalAttendanceScore) modalAttendanceScore.textContent = `${Math.min(30, Math.max(0, attendanceScore))} / 30`;
        if (modalOverallScore) modalOverallScore.textContent = `${Math.round(score)} / 100`;

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

    // Attach click listeners to cards and arrow buttons
    cards.forEach(card => {
        card.addEventListener('click', (e) => {
            // Prevent if clicking on something else if needed
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

    if (flagSubmitBtn) {
        flagSubmitBtn.addEventListener('click', () => {
            const comment = (flagComment ? flagComment.value : '').trim();
            if (!comment) {
                alert('Please provide a reason / comment for the NYSC Admin.');
                if (flagComment) flagComment.focus();
                return;
            }
            closeFlagModal();
            showToast(`Flag report submitted to NYSC Admin for ${currentClubData.name}.`);
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

    // Initial run
    applyFiltersAndSort();
});
