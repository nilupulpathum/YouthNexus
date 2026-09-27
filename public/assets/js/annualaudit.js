/**
 * YouthNexus — Annual Financial Audit Interactive Script (annualaudit.js)
 */

(function () {
    'use strict';

    const clarifyModal = document.getElementById('clarifyModal');
    const approveModal = document.getElementById('approveModal');

    // Open Modal Helpers
    window.openClarifyModal = function (flagId = null, description = null) {
        if (!clarifyModal) return;

        // If a specific flag is targeted, we can pre-select it or update the description
        const flagCheckboxes = clarifyModal.querySelectorAll('input[name="flag_ids[]"]');
        if (flagId && flagCheckboxes.length > 0) {
            flagCheckboxes.forEach(cb => {
                cb.checked = (cb.value == flagId);
            });
        }

        clarifyModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.closeClarifyModal = function () {
        if (!clarifyModal) return;
        clarifyModal.classList.remove('show');
        document.body.style.overflow = '';
    };

    window.openApproveModal = function () {
        if (!approveModal) return;
        approveModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.closeApproveModal = function () {
        if (!approveModal) return;
        approveModal.classList.remove('show');
        document.body.style.overflow = '';
    };

    // Close on backdrop click
    if (clarifyModal) {
        clarifyModal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeClarifyModal();
            }
        });
    }

    if (approveModal) {
        approveModal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeApproveModal();
            }
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeClarifyModal();
            closeApproveModal();
        }
    });

    // Handle scope selector change
    const scopeSelect = document.getElementById('auditScopeSelect');
    if (scopeSelect) {
        scopeSelect.addEventListener('change', function () {
            const val = this.value;
            if (!val) return;
            const parts = val.split(':');
            const level = parts[0];
            const id = parts[1] || 0;
            const currentYear = document.getElementById('auditCurrentYear') ? document.getElementById('auditCurrentYear').value : 2026;
            window.location.href = `${window.YouthNexusAudit?.rootUrl || ''}/audit?scope_level=${encodeURIComponent(level)}&scope_id=${encodeURIComponent(id)}&year=${encodeURIComponent(currentYear)}`;
        });
    }

    // Submit button state indicators
    const forms = document.querySelectorAll('.audit-modal form');
    forms.forEach(form => {
        form.addEventListener('submit', function () {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.7';
                submitBtn.innerHTML = '<span>Processing...</span>';
            }
        });
    });

})();
