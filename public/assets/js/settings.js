/**
 * Settings Interaction — CRUD-03
 *
 * Tabs + presentation-only forms keep their existing behaviour. The Security
 * pane is real: Update Password validates, emails a 6-digit code, and a
 * modal confirms the code before the change is applied (see Settings.php).
 * All calls are same-origin fetch POST, JSON in/out.
 */

document.addEventListener('DOMContentLoaded', () => {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const panes = document.querySelectorAll('.settings-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-target');

            // Update buttons
            tabBtns.forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');

            // Update panes
            panes.forEach(p => p.classList.remove('is-active'));
            document.getElementById(target).classList.add('is-active');
        });
    });

    const forms = document.querySelectorAll('.settings-form');
    forms.forEach(form => {
        // The security form has its own real handler below.
        if (form.id === 'security-form') {
            return;
        }
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            alert('Settings updated successfully!');
        });
    });

    const securityForm = document.getElementById('security-form');
    const base = securityForm ? (securityForm.dataset.actionBase || '') : '';

    if (!securityForm) {
        return;
    }

    const modal = document.getElementById('pw-code-modal');
    const modalBody = document.getElementById('pw-code-body');
    const modalSuccess = document.getElementById('pw-code-success');
    const modalFooter = document.getElementById('pw-code-footer');
    const codeInput = document.getElementById('pw-code-input');
    const confirmBtn = document.getElementById('pw-code-confirm');
    const resendBtn = document.getElementById('pw-code-resend');
    const cooldownNote = document.getElementById('pw-code-cooldown');
    const formError = document.getElementById('pw-form-error');
    const modalError = document.getElementById('pw-code-error');
    const csrfToken = securityForm.querySelector('input[name="csrf_token"]');

    let cooldownTimer = null;

    function post(action, params) {
        const body = new URLSearchParams(params);
        if (csrfToken) {
            body.append('csrf_token', csrfToken.value);
        }
        return fetch(base + '/settings/' + action, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body,
        }).then(response => response.json().catch(() => ({ ok: false, error: 'Unexpected response. Please try again.' })));
    }

    function showError(el, message) {
        if (!el) {
            return;
        }
        if (!message) {
            el.hidden = true;
            el.textContent = '';
            return;
        }
        el.hidden = false;
        el.textContent = message;
    }

    function openModal() {
        showError(modalError, '');
        codeInput.value = '';
        modalBody.hidden = false;
        modalSuccess.hidden = true;
        modalFooter.hidden = false;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        codeInput.focus();
    }

    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
        if (cooldownTimer) {
            window.clearInterval(cooldownTimer);
            cooldownTimer = null;
        }
    }

    function startCooldown(seconds) {
        if (cooldownTimer) {
            window.clearInterval(cooldownTimer);
            cooldownTimer = null;
        }
        let left = seconds;
        resendBtn.disabled = true;
        const tick = () => {
            if (left <= 0) {
                resendBtn.disabled = false;
                cooldownNote.textContent = '';
                return;
            }
            cooldownNote.textContent = ' (' + left + 's)';
            left -= 1;
            cooldownTimer = window.setTimeout(tick, 1000);
        };
        tick();
    }

    modal.querySelectorAll('[data-modal-close]').forEach(el => {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    securityForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const current = document.getElementById('current-password').value;
        const next = document.getElementById('new-password').value;
        const confirm = document.getElementById('confirm-password').value;
        if (!current || !next || !confirm) {
            showError(formError, 'Fill in all three password fields.');
            return;
        }
        if (next.length < 8) {
            showError(formError, 'New password must be at least 8 characters.');
            return;
        }
        if (next !== confirm) {
            showError(formError, 'New passwords do not match.');
            return;
        }
        showError(formError, '');
        const submitBtn = securityForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        post('updatePassword', { current, new: next, confirm })
            .then(data => {
                submitBtn.disabled = false;
                if (data && data.ok) {
                    openModal();
                    startCooldown(60);
                    return;
                }
                showError(formError, (data && data.error) || 'Could not start the change. Please try again.');
            })
            .catch(() => {
                submitBtn.disabled = false;
                showError(formError, 'Network error. Please try again.');
            });
    });

    confirmBtn.addEventListener('click', () => {
        const code = codeInput.value.trim();
        if (!/^\d{6}$/.test(code)) {
            showError(modalError, 'Enter the 6-digit code.');
            return;
        }
        showError(modalError, '');
        confirmBtn.disabled = true;
        post('confirmPassword', { code })
            .then(data => {
                confirmBtn.disabled = false;
                if (data && data.ok) {
                    modalBody.hidden = true;
                    modalFooter.hidden = true;
                    modalSuccess.hidden = false;
                    document.getElementById('current-password').value = '';
                    document.getElementById('new-password').value = '';
                    document.getElementById('confirm-password').value = '';
                    return;
                }
                showError(modalError, (data && data.error) || 'Could not confirm. Please try again.');
            })
            .catch(() => {
                confirmBtn.disabled = false;
                showError(modalError, 'Network error. Please try again.');
            });
    });

    resendBtn.addEventListener('click', () => {
        showError(modalError, '');
        resendBtn.disabled = true;
        post('resendCode', {})
            .then(data => {
                if (data && data.ok) {
                    startCooldown(60);
                    return;
                }
                resendBtn.disabled = false;
                showError(modalError, (data && data.error) || 'Could not resend. Please try again.');
            })
            .catch(() => {
                resendBtn.disabled = false;
                showError(modalError, 'Network error. Please try again.');
            });
    });
});
