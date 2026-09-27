/**
 * zonal.js - client behaviour for zonal pages (filters, modals, validation).
 * Modal open/close + filter-panel toggle come from divisional-workflows.js
 * via [data-modal-open], [data-modal-close] and [data-filter-toggle].
 */

/* ---- zonalcoordinator/events ---- */
if (document.getElementById('coordinator-event-search')) {

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('coordinator-event-list');
    const search = document.getElementById('coordinator-event-search');
    const status = document.getElementById('coordinator-event-status');
    const empty = document.getElementById('coordinator-event-empty');
    const applyFilters = () => {
        if (!list) return;
        let visible = 0;
        list.querySelectorAll('.dw-record-card').forEach((item) => {
            const matchesSearch = !search || !search.value.trim() || (item.dataset.search || '').includes(search.value.trim().toLowerCase());
            const matchesStatus = !status || !status.value || (item.dataset.status || '') === status.value;
            item.hidden = !(matchesSearch && matchesStatus);
            if (!item.hidden) visible += 1;
        });
        if (empty) empty.hidden = visible !== 0;
    };
    [search, status].forEach((element) => {
        if (!element) return;
        element.addEventListener('input', applyFilters);
        element.addEventListener('change', applyFilters);
    });

    const modal = document.getElementById('event-review');
    const form = document.getElementById('event-decision');
    document.querySelectorAll('[data-review]').forEach((button) => button.addEventListener('click', () => {
        document.getElementById('event-id').value = button.dataset.review;
        document.getElementById('event-review-name').textContent = button.dataset.title;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
    }));
    modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', () => {
        const host = button.closest('.dw-modal');
        if (host) { host.hidden = true; host.setAttribute('aria-hidden', 'true'); }
    }));
    modal.addEventListener('click', (event) => { if (event.target === modal || event.target.classList.contains('dw-modal__backdrop')) { modal.hidden = true; modal.setAttribute('aria-hidden', 'true'); } });
    modal.querySelectorAll('[data-decision]').forEach((button) => button.addEventListener('click', () => { form.action = (form.dataset.baseAction || '') + button.dataset.decision; }));
});

}

/* ---- zonalsecretary/events ---- */
if (document.getElementById('zonal-event-open')) {

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('zonal-event-list');
    const search = document.getElementById('zonal-event-search');
    const status = document.getElementById('zonal-event-status');
    const type = document.getElementById('zonal-event-type');
    const empty = document.getElementById('zonal-event-empty');
    const applyFilters = () => {
        let visible = 0;
        list.querySelectorAll('.dw-record-card').forEach((item) => {
            const matchesSearch = !search.value.trim() || item.dataset.search.includes(search.value.trim().toLowerCase());
            const matchesStatus = !status.value || item.dataset.status === status.value;
            const matchesType = !type.value || item.dataset.type.toLowerCase() === type.value.toLowerCase();
            item.hidden = !(matchesSearch && matchesStatus && matchesType);
            if (!item.hidden) visible += 1;
        });
        empty.hidden = visible !== 0;
    };
    [search, status, type].forEach((element) => {
        element.addEventListener('input', applyFilters);
        element.addEventListener('change', applyFilters);
    });

    const modal = document.getElementById('zonal-event-modal');
    const open = document.getElementById('zonal-event-open');
    const form = document.getElementById('zonal-event-form');
    const date = document.getElementById('zonal-event-date');
    const time = document.getElementById('zonal-event-time');
    const dateBanner = document.getElementById('zonal-event-date-banner');
    const dateError = document.getElementById('zonal-event-date-error');
    const formError = document.getElementById('zonal-event-error');
    const close = () => { modal.hidden = true; document.body.style.overflow = ''; };
    const clearDateError = () => {
        dateBanner.hidden = true;
        dateError.hidden = true;
        date.classList.remove('is-invalid');
    };
    open.addEventListener('click', () => { form.reset(); formError.hidden = true; clearDateError(); modal.hidden = false; document.body.style.overflow = 'hidden'; });
    modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    date.addEventListener('input', clearDateError);
    time.addEventListener('input', clearDateError);
    form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) {
            event.preventDefault();
            formError.textContent = 'Complete all required fields.';
            formError.hidden = false;
            return;
        }
        const eventAt = new Date(date.value + 'T' + time.value);
        if (Number.isNaN(eventAt.getTime()) || eventAt <= new Date()) {
            event.preventDefault();
            dateBanner.hidden = false;
            dateError.hidden = false;
            date.classList.add('is-invalid');
            date.focus();
        }
    });

    const editModal = document.getElementById('zonal-event-edit-modal');
    const editForm = document.getElementById('zonal-event-edit-form');
    const editError = document.getElementById('zonal-event-edit-error');
    const closeEdit = () => { editModal.hidden = true; editModal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
    document.querySelectorAll('[data-edit-event]').forEach((button) => button.addEventListener('click', () => {
        document.getElementById('zonal-event-edit-id').value = button.dataset.editEvent;
        document.getElementById('zonal-event-edit-title-input').value = button.dataset.title || '';
        document.getElementById('zonal-event-edit-type').value = button.dataset.type || '';
        document.getElementById('zonal-event-edit-date').value = button.dataset.date || '';
        document.getElementById('zonal-event-edit-time').value = button.dataset.time || '';
        document.getElementById('zonal-event-edit-location').value = button.dataset.location || '';
        document.getElementById('zonal-event-edit-audience').value = button.dataset.audience || 'All divisions';
        editError.hidden = true;
        editModal.hidden = false;
        editModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }));
    editModal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', closeEdit));
    editModal.addEventListener('click', (event) => { if (event.target === editModal) closeEdit(); });
    editForm.addEventListener('submit', (event) => {
        if (!editForm.checkValidity()) {
            event.preventDefault();
            editError.textContent = 'Complete all required fields.';
            editError.hidden = false;
            return;
        }
        const eventAt = new Date(
            document.getElementById('zonal-event-edit-date').value + 'T' + document.getElementById('zonal-event-edit-time').value
        );
        if (Number.isNaN(eventAt.getTime()) || eventAt <= new Date()) {
            event.preventDefault();
            editError.textContent = 'Choose a date and time in the future.';
            editError.hidden = false;
        }
    });

    const deleteModal = document.getElementById('zonal-event-delete-modal');
    const closeDelete = () => { deleteModal.hidden = true; deleteModal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
    document.querySelectorAll('[data-delete-event]').forEach((button) => button.addEventListener('click', () => {
        document.getElementById('zonal-event-delete-id').value = button.dataset.deleteEvent;
        document.getElementById('zonal-event-delete-name').textContent = 'Delete "' + (button.dataset.title || '') + '"? Recorded attendance blocks deletion.';
        deleteModal.hidden = false;
        deleteModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }));
    deleteModal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', closeDelete));
    deleteModal.addEventListener('click', (event) => { if (event.target === deleteModal) closeDelete(); });
});

}

/* ---- zonaltreasurer/assets ---- */
if (document.getElementById('asset-register')) {
document.querySelectorAll('[data-modal-open]').forEach(button=>button.addEventListener('click',()=>{document.getElementById(button.getAttribute('data-modal-open')).hidden=false;}));document.querySelectorAll('[data-modal-close]').forEach(button=>button.addEventListener('click',()=>{var modal=button.closest('.dw-modal');if(modal)modal.hidden=true;}));document.querySelectorAll('[data-transfer]').forEach(button=>button.addEventListener('click',()=>{document.getElementById('transfer-asset-id').value=button.dataset.transfer;document.getElementById('transfer-asset-name').textContent=button.dataset.name;document.getElementById('asset-transfer').hidden=false;}));
}

/* ---- zonaltreasurer/audit ---- */
if (document.getElementById('zonal-audit')) {

document.addEventListener('DOMContentLoaded', () => {
    const close = (modal) => { modal.classList.remove('show'); document.body.style.overflow = ''; };
    document.querySelectorAll('[data-modal-open]').forEach((button) => button.addEventListener('click', () => { const modal = document.getElementById(button.dataset.modalOpen); modal.querySelectorAll('input[name="flag_id"]').forEach((input) => { input.value = button.dataset.flagId || ''; }); modal.classList.add('show'); document.body.style.overflow = 'hidden'; }));
    document.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', () => close(button.closest('.audit-overlay'))));
    document.querySelectorAll('.audit-overlay').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) close(modal); }));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') document.querySelectorAll('.audit-overlay.show').forEach(close); });
});

}

/* ---- zonaltreasurer/ledger ---- */
if (document.getElementById('log-transaction')) {
document.querySelector('[data-modal-open="log-transaction"]').addEventListener('click',()=>{document.getElementById('log-transaction').hidden=false;});document.querySelectorAll('[data-modal-close]').forEach(button=>button.addEventListener('click',()=>{var modal=button.closest('.dw-modal');if(modal)modal.hidden=true;}));
}

/* ---- zonaltreasurer/voids ---- */
if (document.getElementById('void-review')) {
var voidModal=document.getElementById('void-review'),voidForm=document.getElementById('void-decision-form');document.querySelectorAll('[data-review]').forEach(button=>button.addEventListener('click',()=>{document.getElementById('void-review-id').value=button.dataset.review;document.getElementById('void-review-reference').textContent=button.dataset.division+' - '+button.dataset.reference;document.getElementById('void-remark').value='';voidModal.hidden=false;}));document.querySelectorAll('[data-modal-close]').forEach(button=>button.addEventListener('click',()=>{var modal=button.closest('.dw-modal');if(modal)modal.hidden=true;}));voidForm.querySelectorAll('[data-decision]').forEach(button=>button.addEventListener('click',()=>{voidForm.action=(voidForm.dataset.baseAction || '')+button.dataset.decision;}));
}
