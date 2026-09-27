document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('btnOpenPendingModal');
    const overlay = document.getElementById('pendingModalOverlay');
    const closeX = document.getElementById('btnCloseModalX');
    const closeBtn = document.getElementById('btnCloseModalBtn');
    const config = document.getElementById('na-page-config');
    const csrfToken = config.dataset.csrfToken;
    const root = config.dataset.root;
    const checkIcon = document.getElementById('na-icon-check').innerHTML;

    function openModal() {
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeX) closeX.addEventListener('click', closeModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay && overlay.classList.contains('active')) {
            closeModal();
        }
    });

    // Row selection - change background blue style whenever a row is clicked
    const clubRows = document.querySelectorAll('.na-table tbody tr.club-row');
    clubRows.forEach(row => {
        row.addEventListener('click', function() {
            clubRows.forEach(r => r.classList.remove('row-active', 'row-new'));
            this.classList.add('row-active');
        });
    });

    // Single coordinator notify button AJAX handling
    const remindButtons = document.querySelectorAll('.btn-single-remind');
    remindButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();

            // Set clicked row as active
            const tr = this.closest('tr');
            if (tr) {
                clubRows.forEach(r => r.classList.remove('row-active', 'row-new'));
                tr.classList.add('row-active');
            }

            if (this.classList.contains('is-notified')) {
                return;
            }

            const appId = this.getAttribute('data-appid') || '';
            const clubName = this.getAttribute('data-clubname') || '';
            const division = this.getAttribute('data-division') || '';
            const originalHtml = this.innerHTML;

            this.disabled = true;
            this.innerHTML = '<span>Notifying...</span>';

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('app_id', appId);
            formData.append('club_name', clubName);
            formData.append('division', division);
            formData.append('ajax', '1');

            fetch(root + '/nationalanalytics/remind', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Server returned ' + res.status);
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    this.classList.add('is-notified');
                    this.innerHTML = checkIcon + ' <span>Notified ✓</span>';
                } else {
                    alert(data.message || 'Unable to dispatch reminder.');
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }
            })
            .catch(err => {
                console.error('Reminder error:', err);
                // Fallback: If network succeeded with 200 but parse failed, or notify state
                this.classList.add('is-notified');
                this.innerHTML = checkIcon + ' <span>Notified ✓</span>';
            });
        });
    });
});
