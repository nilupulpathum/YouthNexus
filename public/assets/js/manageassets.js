/**
 * manageassets.js — National Asset Management & Logistics Interactivity
 * YouthNexus · NYSC Administration
 */

document.addEventListener('DOMContentLoaded', function () {
    const config = window.YouthNexusAssets || { rootUrl: '', csrfToken: '' };

    // Elements - Modals & Overlays
    const addStockModal = document.getElementById('addStockModal');
    const distributeModal = document.getElementById('distributeModal');
    const toast = document.getElementById('assetToast');

    // Action Buttons
    const btnOpenAddStock = document.getElementById('btnOpenAddStock');
    const btnOpenDistribute = document.getElementById('btnOpenDistribute');
    const btnRunReconcile = document.getElementById('btnRunReconcile');
    const searchInput = document.getElementById('assetSearchInput');
    const filterCategory = document.getElementById('filterCategory');
    const filterZone = document.getElementById('filterZone');
    const filterForm = document.getElementById('filterForm');

    // Add Stock Form Elements
    const addStockCategory = document.getElementById('addStockCategory');
    const addStockItemSelect = document.getElementById('addStockItemSelect');
    const addStockCurrentBadge = document.getElementById('addStockCurrentBadge');
    const addStockQty = document.getElementById('addStockQty');
    const addStockInvoice = document.getElementById('addStockInvoice');

    // Distribute Form Elements
    const distCategory = document.getElementById('distCategory');
    const distItemSelect = document.getElementById('distItemSelect');
    const distZoneSelect = document.getElementById('distZoneSelect');
    const distAvailableBadge = document.getElementById('distAvailableBadge');
    const distMaxText = document.getElementById('distMaxText');
    const distQuantityInput = document.getElementById('distQuantityInput');
    const distRemainingText = document.getElementById('distRemainingText');
    const distNoteBox = document.getElementById('distNoteBox');
    const distNoteText = document.getElementById('distNoteText');

    // All catalog items data embedded from server
    const catalogData = window.YouthNexusAssetsCatalog || [];

    // Helper: Show Toast
    function showToast(message, isError = false) {
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'am-toast ' + (isError ? 'am-toast-error' : 'am-toast-success') + ' show';
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4500);
    }

    // Helper: Open Modal
    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Helper: Close Modal
    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Bind Close buttons
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-close-modal');
            const modal = document.getElementById(targetId);
            closeModal(modal);
        });
    });

    // Close on overlay backdrop click
    [addStockModal, distributeModal].forEach(modal => {
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeModal(this);
                }
            });
        }
    });

    // Escape key closes modals
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal(addStockModal);
            closeModal(distributeModal);
        }
    });

    // Open Add Stock Modal Button
    if (btnOpenAddStock) {
        btnOpenAddStock.addEventListener('click', function () {
            if (addStockCategory && addStockCategory.options.length > 0) {
                filterAddStockItems(addStockCategory.value);
            }
            openModal(addStockModal);
        });
    }

    // Open Distribute Modal Button
    if (btnOpenDistribute) {
        btnOpenDistribute.addEventListener('click', function () {
            if (distCategory && distCategory.options.length > 0) {
                filterDistributeItems(distCategory.value);
            }
            openModal(distributeModal);
        });
    }

    // ── ADD STOCK MODAL LOGIC ─────────────────────────────────
    function filterAddStockItems(category, selectedItemId = null) {
        if (!addStockItemSelect) return;
        addStockItemSelect.innerHTML = '';

        const items = catalogData.filter(it => !category || category === 'All Categories' || it.category === category);

        items.forEach(it => {
            const opt = document.createElement('option');
            opt.value = it.catalog_item_id;
            opt.textContent = `${it.item_name} (${it.sku})`;
            if (selectedItemId && it.catalog_item_id == selectedItemId) {
                opt.selected = true;
            }
            addStockItemSelect.appendChild(opt);
        });

        updateAddStockStockInfo();
    }

    function updateAddStockStockInfo() {
        if (!addStockItemSelect || !addStockCurrentBadge) return;
        const itemId = addStockItemSelect.value;
        if (!itemId) {
            addStockCurrentBadge.textContent = '0 units';
            return;
        }

        fetch(`${config.rootUrl}/manageassets/getiteminfo?item_id=${itemId}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    addStockCurrentBadge.textContent = `${res.data.national_stock} ${res.data.unit || 'units'}`;
                }
            })
            .catch(() => {});
    }

    if (addStockCategory) {
        addStockCategory.addEventListener('change', function () {
            filterAddStockItems(this.value);
        });
    }

    if (addStockItemSelect) {
        addStockItemSelect.addEventListener('change', updateAddStockStockInfo);
    }

    // ── DISTRIBUTE MODAL LOGIC ────────────────────────────────
    let currentAvailableStock = 0;

    function filterDistributeItems(category, selectedItemId = null) {
        if (!distItemSelect) return;
        distItemSelect.innerHTML = '';

        const items = catalogData.filter(it => !category || category === 'All Categories' || it.category === category);

        items.forEach(it => {
            const opt = document.createElement('option');
            opt.value = it.catalog_item_id;
            opt.textContent = `${it.item_name} (${it.sku})`;
            if (selectedItemId && it.catalog_item_id == selectedItemId) {
                opt.selected = true;
            }
            distItemSelect.appendChild(opt);
        });

        updateDistributeInfo();
    }

    function updateDistributeInfo() {
        if (!distItemSelect || !distZoneSelect) return;
        const itemId = distItemSelect.value;
        const zoneId = distZoneSelect.value;

        if (!itemId) return;

        fetch(`${config.rootUrl}/manageassets/getiteminfo?item_id=${itemId}&zone_id=${zoneId || ''}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    const d = res.data;
                    currentAvailableStock = d.national_stock;

                    if (distAvailableBadge) {
                        distAvailableBadge.textContent = `Available stock: ${d.national_stock} ${d.unit || 'units'}`;
                    }
                    if (distMaxText) {
                        distMaxText.textContent = `Maximum: ${d.national_stock}`;
                    }
                    if (distQuantityInput) {
                        distQuantityInput.max = d.national_stock;
                        if (parseInt(distQuantityInput.value) > d.national_stock) {
                            distQuantityInput.value = Math.max(1, d.national_stock);
                        }
                    }

                    // Update Deficit Note Box
                    if (distNoteBox && distNoteText) {
                        if (d.zone_current < d.zone_threshold) {
                            distNoteBox.style.display = 'flex';
                            distNoteText.textContent = `Note: ${d.zone_name} currently reports an asset deficit (${d.zone_current} recorded; regional threshold: ${d.zone_threshold}). Recommended allocation: ${d.recommended} ${d.unit || 'units'}.`;
                            if (distQuantityInput && d.recommended > 0 && d.national_stock >= d.recommended) {
                                distQuantityInput.value = d.recommended;
                            }
                        } else {
                            distNoteBox.style.display = 'none';
                        }
                    }

                    calcRemaining();
                }
            })
            .catch(() => {});
    }

    function calcRemaining() {
        if (!distQuantityInput || !distRemainingText) return;
        const qty = parseInt(distQuantityInput.value) || 0;
        const remaining = Math.max(0, currentAvailableStock - qty);
        distRemainingText.textContent = `${remaining} units`;
    }

    if (distCategory) {
        distCategory.addEventListener('change', function () {
            filterDistributeItems(this.value);
        });
    }

    if (distItemSelect) {
        distItemSelect.addEventListener('change', updateDistributeInfo);
    }

    if (distZoneSelect) {
        distZoneSelect.addEventListener('change', updateDistributeInfo);
    }

    if (distQuantityInput) {
        distQuantityInput.addEventListener('input', calcRemaining);
    }

    // ── ROW ACTION DIRECT TRIGGERS ────────────────────────────
    document.querySelectorAll('[data-action="add-stock-row"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const itemId = this.getAttribute('data-item-id');
            const category = this.getAttribute('data-category');

            if (addStockCategory && category) {
                addStockCategory.value = category;
            }
            filterAddStockItems(category, itemId);
            openModal(addStockModal);
        });
    });

    document.querySelectorAll('[data-action="distribute-row"]').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.classList.contains('disabled')) return;
            const itemId = this.getAttribute('data-item-id');
            const category = this.getAttribute('data-category');

            if (distCategory && category) {
                distCategory.value = category;
            }
            filterDistributeItems(category, itemId);
            openModal(distributeModal);
        });
    });

    // ── RECONCILIATION BUTTON ─────────────────────────────────
    if (btnRunReconcile) {
        btnRunReconcile.addEventListener('click', function (e) {
            e.preventDefault();
            btnRunReconcile.textContent = '⏳ Reconciling...';
            btnRunReconcile.style.pointerEvents = 'none';

            fetch(`${config.rootUrl}/manageassets/reconcile`)
                .then(res => res.json())
                .then(res => {
                    btnRunReconcile.innerHTML = '⇄ Run Zonal Reconciliation';
                    btnRunReconcile.style.pointerEvents = '';
                    if (res.success) {
                        showToast(res.message);
                    }
                })
                .catch(() => {
                    btnRunReconcile.innerHTML = '⇄ Run Zonal Reconciliation';
                    btnRunReconcile.style.pointerEvents = '';
                    showToast('Reconciliation check completed.', false);
                });
        });
    }

    // ── SEARCH DEBOUNCE / SUBMIT ──────────────────────────────
    let searchTimeout;
    if (searchInput && filterForm) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 600);
        });
    }

    // ── FORM SUBMISSIONS VIA AJAX ─────────────────────────────
    const addStockForm = document.getElementById('addStockForm');
    const distributeForm = document.getElementById('distributeForm');

    if (distributeForm) {
        distributeForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitBtn = distributeForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.textContent : 'Confirm Distribution';
            if (submitBtn) {
                submitBtn.textContent = 'Distributing...';
                submitBtn.disabled = true;
            }

            const formData = new FormData(distributeForm);

            fetch(`${config.rootUrl}/manageassets/distribute`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (submitBtn) {
                    submitBtn.textContent = originalBtnText;
                    submitBtn.disabled = false;
                }
                if (data.success) {
                    closeModal(distributeModal);
                    showToast(data.message, false);
                    setTimeout(() => {
                        window.location.reload();
                    }, 600);
                } else {
                    showToast(data.message || 'Distribution failed. Please try again.', true);
                }
            })
            .catch(err => {
                if (submitBtn) {
                    submitBtn.textContent = originalBtnText;
                    submitBtn.disabled = false;
                }
                showToast('An unexpected network error occurred.', true);
            });
        });
    }

    if (addStockForm) {
        addStockForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitBtn = addStockForm.querySelector('button[type="submit"]');
            const originalHtml = submitBtn ? submitBtn.innerHTML : 'Add to Inventory';
            if (submitBtn) {
                submitBtn.innerHTML = 'Adding Stock...';
                submitBtn.disabled = true;
            }

            const formData = new FormData(addStockForm);

            fetch(`${config.rootUrl}/manageassets/addstock`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (submitBtn) {
                    submitBtn.innerHTML = originalHtml;
                    submitBtn.disabled = false;
                }
                if (data.success) {
                    closeModal(addStockModal);
                    showToast(data.message, false);
                    setTimeout(() => {
                        window.location.reload();
                    }, 600);
                } else {
                    showToast(data.message || 'Failed to add stock.', true);
                }
            })
            .catch(err => {
                if (submitBtn) {
                    submitBtn.innerHTML = originalHtml;
                    submitBtn.disabled = false;
                }
                showToast('An unexpected network error occurred.', true);
            });
        });
    }

    // Check if flash messages exist on page load
    const flashSuccessEl = document.getElementById('initialFlashSuccess');
    const flashErrorEl = document.getElementById('initialFlashError');

    if (flashSuccessEl && flashSuccessEl.value) {
        showToast(flashSuccessEl.value, false);
    } else if (flashErrorEl && flashErrorEl.value) {
        showToast(flashErrorEl.value, true);
    }
});
