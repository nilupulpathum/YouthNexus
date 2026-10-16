/**
 * Settings Interaction — CRUD-03
 *
 * Every pane is real: Edit Profile saves to the User row and uploads the
 * picture, Notifications persists the toggles, and the Security pane
 * emails a 6-digit code that a modal confirms before the password change
 * is applied (see Settings.php). All calls are same-origin fetch POST,
 * JSON in/out.
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

    const securityForm = document.getElementById('security-form');
    const profileForm = document.getElementById('profile-form');
    if (!securityForm && !profileForm) {
        return;
    }
    const base = (profileForm || securityForm).dataset.actionBase || '';
    const csrfInput = document.querySelector('input[name="csrf_token"]');

    function post(action, params) {
        const body = new URLSearchParams(params);
        if (csrfInput) {
            body.append('csrf_token', csrfInput.value);
        }
        return fetch(base + '/' + action, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body,
        }).then(response => response.json().catch(() => ({ ok: false, error: 'Unexpected response. Please try again.' })));
    }

    function showNote(el, message, ok) {
        if (!el) {
            return;
        }
        el.classList.toggle('is-ok', !!ok);
        if (!message) {
            el.hidden = true;
            el.textContent = '';
            return;
        }
        el.hidden = false;
        el.textContent = message;
    }

    /* ---------------------------------------------------------------
     * Edit Profile — picture, fields, save/cancel
     * --------------------------------------------------------------- */

    const uploadBtn = document.getElementById('btn-upload');
    const avatarInput = document.getElementById('avatar-input');
    const deletePictureBtn = document.getElementById('btn-delete-picture');
    const avatarMessage = document.getElementById('avatar-message');
    const preview = document.getElementById('avatar-preview');
    let previewImg = document.getElementById('avatar-preview-img');
    const previewInitials = document.getElementById('avatar-preview-initials');

    function showAvatarPicture(url) {
        if (url) {
            if (previewImg) {
                previewImg.src = url;
                previewImg.hidden = false;
            } else {
                previewImg = document.createElement('img');
                previewImg.id = 'avatar-preview-img';
                previewImg.alt = 'Profile picture';
                previewImg.src = url;
                preview.prepend(previewImg);
            }
            if (previewInitials) {
                previewInitials.hidden = true;
            }
        } else {
            if (previewImg) {
                previewImg.hidden = true;
            }
            if (previewInitials) {
                previewInitials.hidden = false;
            }
        }
    }

    if (uploadBtn && avatarInput) {
        uploadBtn.addEventListener('click', () => avatarInput.click());
        avatarInput.addEventListener('change', () => {
            const file = avatarInput.files && avatarInput.files[0];
            avatarInput.value = '';
            if (!file) {
                return;
            }
            if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
                showNote(avatarMessage, 'Use a JPG, PNG or WEBP picture.');
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                showNote(avatarMessage, 'The picture must be 2 MB or smaller.');
                return;
            }
            showNote(avatarMessage, '');
            uploadBtn.disabled = true;
            if (deletePictureBtn) {
                deletePictureBtn.disabled = true;
            }
            const body = new FormData();
            body.append('picture', file);
            if (csrfInput) {
                body.append('csrf_token', csrfInput.value);
            }
            fetch(base + '/uploadPicture', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body,
            }).then(response => response.json().catch(() => ({ ok: false, error: 'Unexpected response. Please try again.' })))
                .then(data => {
                    uploadBtn.disabled = false;
                    if (deletePictureBtn) {
                        deletePictureBtn.disabled = false;
                    }
                    if (data && data.ok) {
                        showAvatarPicture(data.url);
                        showNote(avatarMessage, 'Picture updated.', true);
                        return;
                    }
                    showNote(avatarMessage, (data && data.error) || 'Upload failed. Please try again.');
                })
                .catch(() => {
                    uploadBtn.disabled = false;
                    if (deletePictureBtn) {
                        deletePictureBtn.disabled = false;
                    }
                    showNote(avatarMessage, 'Network error. Please try again.');
                });
        });
    }

    if (deletePictureBtn) {
        deletePictureBtn.addEventListener('click', () => {
            uploadBtn.disabled = true;
            deletePictureBtn.disabled = true;
            post('deletePicture', {})
                .then(data => {
                    uploadBtn.disabled = false;
                    deletePictureBtn.disabled = false;
                    if (data && data.ok) {
                        showAvatarPicture('');
                        showNote(avatarMessage, 'Picture removed.', true);
                        return;
                    }
                    showNote(avatarMessage, (data && data.error) || 'Could not remove the picture. Please try again.');
                })
                .catch(() => {
                    uploadBtn.disabled = false;
                    deletePictureBtn.disabled = false;
                    showNote(avatarMessage, 'Network error. Please try again.');
                });
        });
    }

    if (profileForm) {
        const profileMessage = document.getElementById('profile-form-message');
        const original = {};
        ['first_name', 'last_name', 'email', 'phone', 'address'].forEach(name => {
            const input = profileForm.querySelector('[name="' + name + '"]');
            if (input) {
                original[name] = input.value;
            }
        });

        profileForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const payload = {};
            Object.keys(original).forEach(name => {
                payload[name] = profileForm.querySelector('[name="' + name + '"]').value.trim();
            });
            if (!payload.first_name || !payload.last_name) {
                showNote(profileMessage, 'First name and last name are required.');
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(payload.email)) {
                showNote(profileMessage, 'Enter a valid email address.');
                return;
            }
            showNote(profileMessage, '');
            const saveBtn = profileForm.querySelector('button[type="submit"]');
            saveBtn.disabled = true;
            post('updateProfile', payload)
                .then(data => {
                    saveBtn.disabled = false;
                    if (data && data.ok) {
                        Object.keys(original).forEach(name => {
                            original[name] = payload[name];
                        });
                        showNote(profileMessage, 'Profile updated successfully.', true);
                        return;
                    }
                    showNote(profileMessage, (data && data.error) || 'Could not save your profile. Please try again.');
                })
                .catch(() => {
                    saveBtn.disabled = false;
                    showNote(profileMessage, 'Network error. Please try again.');
                });
        });

        const cancelBtn = document.getElementById('btn-cancel-profile');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                Object.keys(original).forEach(name => {
                    const input = profileForm.querySelector('[name="' + name + '"]');
                    if (input) {
                        input.value = original[name];
                    }
                });
                showNote(profileMessage, '');
            });
        }
    }

    /* ---------------------------------------------------------------
     * Notifications — persist the toggles
     * --------------------------------------------------------------- */

    const saveNotifsBtn = document.getElementById('btn-save-notifs');
    const notifMessage = document.getElementById('notif-form-message');
    if (saveNotifsBtn) {
        saveNotifsBtn.addEventListener('click', () => {
            saveNotifsBtn.disabled = true;
            post('updateNotificationPrefs', {
                email_enabled: document.getElementById('pref-email-enabled').checked ? '1' : '0',
                announcements_email: document.getElementById('pref-announcements-email').checked ? '1' : '0',
            })
                .then(data => {
                    saveNotifsBtn.disabled = false;
                    if (data && data.ok) {
                        showNote(notifMessage, 'Preferences saved.', true);
                        return;
                    }
                    showNote(notifMessage, (data && data.error) || 'Could not save preferences. Please try again.');
                })
                .catch(() => {
                    saveNotifsBtn.disabled = false;
                    showNote(notifMessage, 'Network error. Please try again.');
                });
        });
    }

    /* ---------------------------------------------------------------
     * Security — password change confirmed by an emailed 6-digit code
     * --------------------------------------------------------------- */

    if (!securityForm) {
        return;
    }

    const modal = document.getElementById('pw-code-modal');
    const modalBody = document.getElementById('pw-code-body');
    const modalSuccess = document.getElementById('pw-code-success');
    const modalFooter = document.getElementById('pw-code-footer');
    const codeBoxes = Array.from(document.querySelectorAll('.pw-code-box'));
    const confirmBtn = document.getElementById('pw-code-confirm');
    const resendBtn = document.getElementById('pw-code-resend');
    const cooldownNote = document.getElementById('pw-code-cooldown');
    const formError = document.getElementById('pw-form-error');
    const modalError = document.getElementById('pw-code-error');

    let cooldownTimer = null;

    function codeValue() {
        return codeBoxes.map(box => box.value.trim()).join('');
    }

    function clearCode() {
        codeBoxes.forEach(box => { box.value = ''; });
        if (codeBoxes.length) {
            codeBoxes[0].focus();
        }
    }

    function openModal() {
        showNote(modalError, '');
        clearCode();
        modalBody.hidden = false;
        modalSuccess.hidden = true;
        modalFooter.hidden = false;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        if (codeBoxes.length) {
            codeBoxes[0].focus();
        }
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

    // Same six-box behaviour as the sign-in verification page: auto-advance,
    // Backspace returns, pasting a code fills the boxes.
    codeBoxes.forEach((box, i) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/\D/g, '').slice(0, 1);
            if (box.value.length === 1 && i < codeBoxes.length - 1) {
                codeBoxes[i + 1].focus();
            }
        });
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && box.value === '' && i > 0) {
                codeBoxes[i - 1].focus();
            }
        });
        box.addEventListener('paste', (e) => {
            e.preventDefault();
            const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
            digits.split('').forEach((digit, j) => {
                if (codeBoxes[j]) {
                    codeBoxes[j].value = digit;
                }
            });
            (codeBoxes[Math.min(digits.length, 5)] || codeBoxes[5]).focus();
        });
    });

    securityForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const current = document.getElementById('current-password').value;
        const next = document.getElementById('new-password').value;
        const confirm = document.getElementById('confirm-password').value;
        if (!current || !next || !confirm) {
            showNote(formError, 'Fill in all three password fields.');
            return;
        }
        if (next.length < 8) {
            showNote(formError, 'New password must be at least 8 characters.');
            return;
        }
        if (next !== confirm) {
            showNote(formError, 'New passwords do not match.');
            return;
        }
        showNote(formError, '');
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
                showNote(formError, (data && data.error) || 'Could not start the change. Please try again.');
            })
            .catch(() => {
                submitBtn.disabled = false;
                showNote(formError, 'Network error. Please try again.');
            });
    });

    confirmBtn.addEventListener('click', () => {
        const code = codeValue();
        if (!/^\d{6}$/.test(code)) {
            showNote(modalError, 'Enter the 6-digit code.');
            return;
        }
        showNote(modalError, '');
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
                showNote(modalError, (data && data.error) || 'Could not confirm. Please try again.');
            })
            .catch(() => {
                confirmBtn.disabled = false;
                showNote(modalError, 'Network error. Please try again.');
            });
    });

    resendBtn.addEventListener('click', () => {
        showNote(modalError, '');
        resendBtn.disabled = true;
        post('resendCode', {})
            .then(data => {
                if (data && data.ok) {
                    startCooldown(60);
                    return;
                }
                resendBtn.disabled = false;
                showNote(modalError, (data && data.error) || 'Could not resend. Please try again.');
            })
            .catch(() => {
                resendBtn.disabled = false;
                showNote(modalError, 'Network error. Please try again.');
            });
    });
});
