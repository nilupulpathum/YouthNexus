/**
 * Settings Interaction — CRUD-03
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
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            alert('Settings updated successfully!');
        });
    });

    const btnVerify = document.querySelector('.btn-verify-code');
    if (btnVerify) {
        btnVerify.addEventListener('click', () => {
            alert('Verification code sent to your email!');
        });
    }
});
