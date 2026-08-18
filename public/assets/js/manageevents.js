/**
 * manageevents.js — Divisional Event Management frontend logic
 * Handles modal workflows, event type quick chips, real-time datetime validation,
 * and AJAX form submission.
 */

document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------------------------
    // 1. Modal Helper Functions
    // -------------------------------------------------------------
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            // Set min datetime for start_datetime to current time
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            const minIso = now.toISOString().slice(0, 16);
            const startInput = modal.querySelector('input[name="start_datetime"]');
            if (startInput && !startInput.value) {
                startInput.min = minIso;
            }
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('is-open');
            document.body.style.overflow = '';
        }
    }

    // Modal Trigger Buttons
    const btnOpenCreateModal = document.getElementById('btnOpenCreateModal');
    if (btnOpenCreateModal) {
        btnOpenCreateModal.addEventListener('click', (e) => {
            e.preventDefault();
            openModal('createEventModal');
        });
    }

    const btnOpenEditModal = document.getElementById('btnOpenEditModal');
    if (btnOpenEditModal) {
        btnOpenEditModal.addEventListener('click', (e) => {
            e.preventDefault();
            openModal('editEventModal');
        });
    }

    // Modal Close Buttons & Backdrop Clicks
    document.querySelectorAll('.me-modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                closeModal(backdrop.id);
            }
        });

        backdrop.querySelectorAll('.me-modal-close, .me-btn-cancel').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                closeModal(backdrop.id);
            });
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.me-modal-backdrop.is-open').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });

    // -------------------------------------------------------------
    // 2. Event Type Quick Chips
    // -------------------------------------------------------------
    function setupChips(form) {
        const typeInput = form.querySelector('input[name="event_type"]');
        const chips = form.querySelectorAll('.me-chip');

        if (typeInput && chips.length > 0) {
            chips.forEach(chip => {
                chip.addEventListener('click', () => {
                    const value = chip.getAttribute('data-value');
                    typeInput.value = value;
                    chips.forEach(c => c.classList.remove('is-active'));
                    chip.classList.add('is-active');
                    typeInput.dispatchEvent(new Event('input'));
                });
            });

            typeInput.addEventListener('input', () => {
                const val = typeInput.value.trim().toLowerCase();
                chips.forEach(chip => {
                    if (chip.getAttribute('data-value').toLowerCase() === val) {
                        chip.classList.add('is-active');
                    } else {
                        chip.classList.remove('is-active');
                    }
                });
            });
        }
    }

    // -------------------------------------------------------------
    // 3. Datetime Real-Time Validation
    // -------------------------------------------------------------
    function validateDateRange(form) {
        const startInput = form.querySelector('input[name="start_datetime"]');
        const endInput   = form.querySelector('input[name="end_datetime"]');
        const alertBox   = form.querySelector('.me-validation-alert');
        const alertMsg   = form.querySelector('.me-validation-msg');

        if (!startInput || !endInput) return true;

        const startVal = startInput.value;
        const endVal   = endInput.value;

        if (!startVal && !endVal) {
            if (alertBox) alertBox.classList.remove('is-visible');
            return true;
        }

        const now = new Date();
        const startDate = startVal ? new Date(startVal) : null;
        const endDate   = endVal ? new Date(endVal) : null;

        let hasError = false;
        let errorMessage = 'Event start must be after now, and end must be after start';

        if (startDate && startDate <= now) {
            hasError = true;
        } else if (startDate && endDate && endDate <= startDate) {
            hasError = true;
        }

        if (hasError) {
            if (alertBox && alertMsg) {
                alertMsg.textContent = errorMessage;
                alertBox.classList.add('is-visible');
            }
            return false;
        } else {
            if (alertBox) {
                alertBox.classList.remove('is-visible');
            }
            return true;
        }
    }

    // -------------------------------------------------------------
    // 4. Form Submission & AJAX Handling
    // -------------------------------------------------------------
    function setupForm(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        setupChips(form);

        const startInput = form.querySelector('input[name="start_datetime"]');
        const endInput   = form.querySelector('input[name="end_datetime"]');

        if (startInput) startInput.addEventListener('change', () => validateDateRange(form));
        if (endInput)   endInput.addEventListener('change', () => validateDateRange(form));

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Client-side date check
            if (!validateDateRange(form)) {
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Submitting...';
            }

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    // Show errors
                    const alertBox = form.querySelector('.me-validation-alert');
                    const alertMsg = form.querySelector('.me-validation-msg');
                    let errMsg = data.error || 'Please correct the errors in the form.';
                    if (data.errors) {
                        errMsg = Object.values(data.errors).join('<br>');
                    }
                    if (alertBox && alertMsg) {
                        alertMsg.innerHTML = errMsg;
                        alertBox.classList.add('is-visible');
                    } else {
                        alert(errMsg);
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                }
            } catch (err) {
                // Fallback standard submit if network or json issue
                console.error('AJAX submit failed, falling back to standard submit:', err);
                form.submit();
            }
        });
    }

    setupForm('createEventForm');
    setupForm('editEventForm');

    // -------------------------------------------------------------
    // 5. Search & Filters on List Page
    // -------------------------------------------------------------
    const filterForm = document.getElementById('eventFilterForm');
    if (filterForm) {
        const autoSubmitFields = filterForm.querySelectorAll('select, input[type="date"]');
        autoSubmitFields.forEach(field => {
            field.addEventListener('change', () => {
                filterForm.submit();
            });
        });
    }
});
