(function () {
    'use strict';

    // ---- Config ----
    // The view owns user/session data; the external script only reads its JSON payload.
    const CONFIG = JSON.parse(document.getElementById('mu-page-config').textContent);
    const ROOT = CONFIG.root;
    const CSRF = CONFIG.csrf;
    const ZONES = CONFIG.zones;
    const LEVEL_ROLES = CONFIG.levelRoles;

    // ----------------------------------------------------------------
    // TOAST
    // ----------------------------------------------------------------
    const toast = document.getElementById('mu-toast');
    let toastTimer;
    function showToast(msg, type = 'success') {
        clearTimeout(toastTimer);
        toast.textContent = msg;
        toast.className = 'mu-toast mu-toast-' + type + ' show';
        toastTimer = setTimeout(() => toast.classList.remove('show'), 4000);
    }

    // ----------------------------------------------------------------
    // MODAL OPEN / CLOSE
    // ----------------------------------------------------------------
    function openModal(id) {
        document.getElementById(id).classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('show');
        document.body.style.overflow = '';
    }

    // Close buttons
    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.close));
    });
    // Click outside modal
    ['mu-add-modal', 'mu-edit-modal'].forEach(id => {
        document.getElementById(id).addEventListener('click', function(e) {
            if (e.target === this) closeModal(id);
        });
    });
    // ESC key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeModal('mu-add-modal');
            closeModal('mu-edit-modal');
        }
    });

    // ----------------------------------------------------------------
    // OPEN ADD MODAL
    // ----------------------------------------------------------------
    document.getElementById('mu-add-user-btn').addEventListener('click', () => {
        resetForm('mu-add-form');
        document.getElementById('mu-add-success').classList.remove('show');
        openModal('mu-add-modal');
    });

    // ----------------------------------------------------------------
    // DYNAMIC ROLE + JURISDICTION DROPDOWNS
    // ----------------------------------------------------------------

    function populatePositions(levelSel, posSel, currentVal = '') {
        const level  = levelSel.value;
        const roles  = LEVEL_ROLES[level] || [];
        posSel.innerHTML = '<option value="">Select position…</option>';
        roles.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.value;
            opt.textContent = r.label;
            if (r.value === currentVal) opt.selected = true;
            posSel.appendChild(opt);
        });
    }

    function populateJurisdiction(levelSel, jurisSel, zonalHid, divHid, currentZonal = null, currentDiv = null) {
        const level = levelSel.value;
        jurisSel.innerHTML = '<option value="">Loading…</option>';
        zonalHid.value = '';
        divHid.value   = '';

        if (level === 'Zonal Level') {
            jurisSel.innerHTML = '<option value="">Select zone…</option>';
            ZONES.forEach(z => {
                const opt = document.createElement('option');
                opt.value = z.id;
                opt.textContent = z.name;
                if (z.id == currentZonal) opt.selected = true;
                jurisSel.appendChild(opt);
            });
            jurisSel.onchange = () => {
                zonalHid.value = jurisSel.value;
                divHid.value   = '';
            };
            if (currentZonal) zonalHid.value = currentZonal;

        } else if (level === 'Divisional Level') {
            // Fetch divisions via AJAX
            fetch(ROOT + '/manageuser/getdivisions', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                jurisSel.innerHTML = '<option value="">Select division…</option>';
                (data.divisions || []).forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.division_id;
                    opt.textContent = d.division_name;
                    if (d.division_id == currentDiv) opt.selected = true;
                    jurisSel.appendChild(opt);
                });
                jurisSel.onchange = () => {
                    divHid.value   = jurisSel.value;
                    zonalHid.value = '';
                };
                if (currentDiv) divHid.value = currentDiv;
            })
            .catch(() => { jurisSel.innerHTML = '<option value="">Error loading divisions</option>'; });
        } else {
            jurisSel.innerHTML = '<option value="">Select level first…</option>';
        }
    }

    // Add modal level/position wiring
    const addLevel = document.getElementById('add-level');
    const addPos   = document.getElementById('add-position');
    const addJuris = document.getElementById('add-jurisdiction');
    const addZon   = document.getElementById('add-zonal-id');
    const addDiv   = document.getElementById('add-division-id');

    addLevel.addEventListener('change', () => {
        populatePositions(addLevel, addPos);
        populateJurisdiction(addLevel, addJuris, addZon, addDiv);
    });

    // Edit modal level/position wiring
    const editLevel = document.getElementById('edit-level');
    const editPos   = document.getElementById('edit-position');
    const editJuris = document.getElementById('edit-jurisdiction');
    const editZon   = document.getElementById('edit-zonal-id');
    const editDiv   = document.getElementById('edit-division-id');

    editLevel.addEventListener('change', () => {
        populatePositions(editLevel, editPos);
        populateJurisdiction(editLevel, editJuris, editZon, editDiv);
    });

    // ----------------------------------------------------------------
    // FIELD ERROR HELPERS
    // ----------------------------------------------------------------
    function clearErrors(prefix) {
        document.querySelectorAll('[id^="err-' + prefix + '"]').forEach(el => {
            el.textContent = '';
            el.classList.remove('show');
        });
    }
    function showErrors(prefix, errors) {
        Object.entries(errors).forEach(([field, msg]) => {
            const el = document.getElementById('err-' + prefix + field);
            if (el) { el.textContent = msg; el.classList.add('show'); }
        });
    }
    function resetForm(formId) {
        const form = document.getElementById(formId);
        if (form) form.reset();
        const prefix = formId === 'mu-add-form' ? 'add-' : 'edit-';
        clearErrors(prefix);
    }

    // ----------------------------------------------------------------
    // FETCH WRAPPER
    // ----------------------------------------------------------------
    function postJSON(url, formData) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        }).then(r => r.json());
    }

    // ----------------------------------------------------------------
    // LOADING STATE
    // ----------------------------------------------------------------
    function setLoading(submitBtn, spinnerId, iconId, on) {
        const btn = document.getElementById(submitBtn);
        const sp  = document.getElementById(spinnerId);
        const ic  = document.getElementById(iconId);
        if (on) {
            btn.disabled = true;
            sp.style.display  = 'block';
            if (ic) ic.style.display = 'none';
        } else {
            btn.disabled = false;
            sp.style.display  = 'none';
            if (ic) ic.style.display = '';
        }
    }

    // ----------------------------------------------------------------
    // ADD USER FORM SUBMIT
    // ----------------------------------------------------------------
    document.getElementById('mu-add-form').addEventListener('submit', function(e) {
        e.preventDefault();
        clearErrors('add-');
        setLoading('mu-add-submit', 'mu-add-spinner', 'mu-add-icon', true);

        const fd = new FormData(this);
        postJSON(ROOT + '/manageuser/create', fd)
            .then(data => {
                setLoading('mu-add-submit', 'mu-add-spinner', 'mu-add-icon', false);
                if (data.success) {
                    // show temp password
                    const sb = document.getElementById('mu-add-success');
                    document.getElementById('mu-temp-pass-display').textContent = data.tempPassword || '—';
                    sb.classList.add('show');
                    // Reset the form
                    this.reset();
                    addPos.innerHTML   = '<option value="">Select position…</option>';
                    addJuris.innerHTML = '<option value="">Select level first…</option>';
                    showToast(data.message, 'success');
                    // Reload page after 3s to show new user in table
                    setTimeout(() => location.reload(), 3000);
                } else {
                    if (data.errors) showErrors('add-', data.errors);
                    else showToast(data.message || 'Failed to create user.', 'error');
                }
            })
            .catch(() => {
                setLoading('mu-add-submit', 'mu-add-spinner', 'mu-add-icon', false);
                showToast('Network error. Please try again.', 'error');
            });
    });

    // ----------------------------------------------------------------
    // EDIT — open and pre-fill
    // ----------------------------------------------------------------
    document.querySelectorAll('.mu-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const uid = btn.dataset.uid;
            resetForm('mu-edit-form');
            document.getElementById('edit-user-id').value = uid;

            // Fetch user data
            fetch(ROOT + '/manageuser/getuser/' + uid, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { showToast('Could not load user.', 'error'); return; }
                const u = data.user;
                document.getElementById('edit-first-name').value = u.first_name || '';
                document.getElementById('edit-last-name').value  = u.last_name  || '';
                document.getElementById('edit-nic').value        = u.NIC        || '';
                document.getElementById('edit-email').value      = u.email      || '';
                document.getElementById('edit-phone').value      = u.phone_number || '';

                // Set level, then populate positions, then set position
                editLevel.value = u.level;
                populatePositions(editLevel, editPos, u.role);
                populateJurisdiction(editLevel, editJuris, editZon, editDiv, u.zonal_id, u.division_id);

                openModal('mu-edit-modal');
            })
            .catch(() => showToast('Network error loading user.', 'error'));
        });
    });

    // ----------------------------------------------------------------
    // EDIT FORM SUBMIT
    // ----------------------------------------------------------------
    document.getElementById('mu-edit-form').addEventListener('submit', function(e) {
        e.preventDefault();
        clearErrors('edit-');
        const uid = document.getElementById('edit-user-id').value;
        setLoading('mu-edit-submit', 'mu-edit-spinner', 'mu-edit-icon', true);

        const fd = new FormData(this);
        postJSON(ROOT + '/manageuser/update/' + uid, fd)
            .then(data => {
                setLoading('mu-edit-submit', 'mu-edit-spinner', 'mu-edit-icon', false);
                if (data.success) {
                    closeModal('mu-edit-modal');
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    if (data.errors) showErrors('edit-', data.errors);
                    else showToast(data.message || 'Failed to update user.', 'error');
                }
            })
            .catch(() => {
                setLoading('mu-edit-submit', 'mu-edit-spinner', 'mu-edit-icon', false);
                showToast('Network error. Please try again.', 'error');
            });
    });

    // ----------------------------------------------------------------
    // STATUS TOGGLE (deactivate / reactivate)
    // ----------------------------------------------------------------
    document.querySelectorAll('.mu-status-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const uid       = btn.dataset.uid;
            const newStatus = btn.dataset.newStatus;
            const name      = btn.dataset.name;
            const verb      = newStatus === 'Active' ? 'reactivate' : 'deactivate';

            if (!confirm('Are you sure you want to ' + verb + ' the account for ' + name + '?')) return;

            const fd = new FormData();
            fd.append('csrf_token', CSRF);
            fd.append('new_status', newStatus);

            postJSON(ROOT + '/manageuser/setstatus/' + uid, fd)
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        showToast(data.message || 'Action failed.', 'error');
                    }
                })
                .catch(() => showToast('Network error.', 'error'));
        });
    });

    // Keep the permanent delete action introduced on dev when moving the
    // page script out of its PHP view. The server enforces history safeguards.
    document.querySelectorAll('.mu-delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const uid = btn.dataset.uid;
            const name = btn.dataset.name;
            const message = 'Permanently delete the account for ' + name + '?\n\n'
                + 'This removes the account forever and cannot be undone. '
                + 'Accounts with any financial, audit, or operational history are protected and cannot be deleted.';
            if (!confirm(message)) return;

            const form = new FormData();
            form.append('csrf_token', CSRF);
            postJSON(ROOT + '/manageuser/delete/' + uid, form)
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        showToast(data.message || 'Delete failed.', 'error');
                    }
                })
                .catch(() => showToast('Network error. Please try again.', 'error'));
        });
    });

    // ----------------------------------------------------------------
    // FILTER FORM — auto-submit on select change
    // ----------------------------------------------------------------
    ['mu-filter-level', 'mu-filter-position', 'mu-filter-status'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => document.getElementById('mu-filter-form').submit());
    });

    // Search on Enter
    const searchInput = document.getElementById('mu-search-input');
    if (searchInput) {
        let searchTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => document.getElementById('mu-filter-form').submit(), 500);
        });
    }

    // Reset filters
    document.getElementById('mu-reset-btn').addEventListener('click', () => {
        window.location.href = ROOT + '/manageuser';
    });

    // ----------------------------------------------------------------
    // EXPORT CSV (placeholder — triggers page with ?export=1)
    // ----------------------------------------------------------------
    document.getElementById('mu-export-btn').addEventListener('click', () => {
        window.location.href = ROOT + '/manageuser?export=1&' +
            new URLSearchParams(CONFIG.filters).toString();
    });

})();
