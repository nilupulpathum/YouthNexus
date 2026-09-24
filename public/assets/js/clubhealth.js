/* ============================================================
   YouthNexus — Monitor Club Health (NYSC Administration)
   Cascading zone→division filter, health profile modal,
   disband warning and execute disband controls.
   ============================================================ */
(function () {
    'use strict';

    // ── DOM References ────────────────────────────────────────────────────────
    var zoneSel        = document.getElementById('chZone');
    var divisionSel    = document.getElementById('chDivision');
    var dataScript     = document.getElementById('club-health-data');
    var disbandModal   = document.getElementById('club-health-disband');
    var disbandConfirm = document.getElementById('chDisbandConfirm');
    var disbandAck     = document.getElementById('chDisbandAck');
    var disbandReason  = document.getElementById('chDisbandReason');

    var ROOT = '/YouthNexus/public';
    var CSRF = '';
    var CLUB_DATA = {};
    var currentClubId = null;

    if (dataScript) {
        ROOT = dataScript.getAttribute('data-root') || ROOT;
        CSRF = dataScript.getAttribute('data-csrf') || '';
        try {
            CLUB_DATA = JSON.parse(dataScript.textContent || '{}');
        } catch (e) {
            console.error('Failed to parse club health JSON data', e);
        }
    }

    // ── Utility Helpers ───────────────────────────────────────────────────────
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function money(n) {
        return Number(n || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fmtDate(d) {
        if (!d) return '—';
        var dt = new Date(String(d).replace(' ', 'T'));
        return isNaN(dt.getTime()) ? String(d) : dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function toast(msg, ok) {
        var t = document.createElement('div');
        t.className = 'ch-toast ' + (ok ? 'ok' : 'err');
        t.textContent = msg;
        document.body.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('show'); });
        setTimeout(function () {
            t.classList.remove('show');
            setTimeout(function () { t.remove(); }, 300);
        }, 4500);
    }

    function post(url, data) {
        var params = new URLSearchParams();
        Object.keys(data).forEach(function (k) { params.append(k, data[k]); });
        params.append('csrf_token', CSRF);

        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            credentials: 'same-origin',
            body: params.toString()
        }).then(function (res) {
            return res.json().catch(function () { throw new Error('Server returned non-JSON response'); });
        });
    }

    // ── 1. Modal Controller ───────────────────────────────────────────────────
    function openModal(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.removeAttribute('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modal) {
        if (typeof modal === 'string') modal = document.getElementById(modal);
        if (modal) {
            modal.setAttribute('hidden', '');
            modal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.dw-modal:not([hidden])')) {
                document.body.style.overflow = '';
            }
        }
    }

    document.addEventListener('click', function (e) {
        var openBtn = e.target.closest('[data-modal-open]');
        if (openBtn) {
            var targetId = openBtn.getAttribute('data-modal-open');
            openModal(targetId);
            return;
        }

        var closeBtn = e.target.closest('[data-modal-close]');
        if (closeBtn) {
            var modal = closeBtn.closest('.dw-modal');
            closeModal(modal);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var activeModals = document.querySelectorAll('.dw-modal:not([hidden])');
            activeModals.forEach(function (m) { closeModal(m); });
        }
    });

    // ── 2. Cascading Zone → Division Filter ──────────────────────────────────
    if (zoneSel && divisionSel) {
        zoneSel.addEventListener('change', function () {
            var zid = zoneSel.value;
            divisionSel.innerHTML = '<option value="">All divisions</option>';
            if (!zid) return;

            divisionSel.disabled = true;
            fetch(ROOT + '/clubhealth/divisions?zone_id=' + encodeURIComponent(zid), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.success && Array.isArray(data.divisions)) {
                    data.divisions.forEach(function (d) {
                        var opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.name;
                        divisionSel.appendChild(opt);
                    });
                }
                divisionSel.disabled = false;
            })
            .catch(function () {
                divisionSel.disabled = false;
                toast('Failed to load divisions for selected zone.', false);
            });
        });
    }

    // ── 3. Populate Profile Modal Data ───────────────────────────────────────
    document.addEventListener('click', function (e) {
        var detailBtn = e.target.closest('[data-club-details]');
        if (!detailBtn) return;

        var clubId = detailBtn.getAttribute('data-club-details');
        currentClubId = clubId;
        var detail = CLUB_DATA[clubId];
        if (!detail) {
            toast('Club details not available.', false);
            return;
        }

        var modal = document.getElementById('club-health-details');
        if (!modal) return;

        var club = detail.club || {};
        var score = detail.score || {};
        var events = detail.events || [];
        var history = detail.history || [];
        var flags = detail.flags || [];
        var execs = detail.executives || [];
        var finance = detail.finance || { summary: {}, entries: [] };
        var audits = detail.audits || [];

        var statusKey = (score.health_status || club.health_status || 'Yellow').toLowerCase();

        // Header Avatar & Identity
        var avatarEl = modal.querySelector('[data-detail-avatar]');
        if (avatarEl) {
            var initials = (club.club_name || '').split(/\s+/).map(function(w){ return w[0]; }).slice(0, 2).join('').toUpperCase() || 'YC';
            avatarEl.textContent = initials;
            avatarEl.className = 'dch-modal__avatar dch-card__avatar--' + esc(statusKey);
        }

        var nameEl = modal.querySelector('[data-detail-club-name]');
        if (nameEl) nameEl.textContent = club.club_name || 'Club Details';

        var codeEl = modal.querySelector('[data-detail-club-code]');
        if (codeEl) codeEl.textContent = club.club_code ? '#' + club.club_code : 'N/A';

        var statusEl = modal.querySelector('[data-detail-status]');
        if (statusEl) {
            statusEl.textContent = score.health_status || 'Yellow';
            statusEl.className = 'dw-status dw-status--' + esc(statusKey);
        }

        // Hero Score
        var overallEl = modal.querySelector('[data-detail-overall]');
        if (overallEl) overallEl.textContent = Math.round(Number(score.overall_score || 0));

        var windowEl = modal.querySelector('[data-detail-window]');
        if (windowEl) {
            windowEl.textContent = 'Calculated over 6-month rolling window (' + fmtDate(detail.window_start) + ' to ' + fmtDate(detail.window_end) + ')';
        }

        // About & Meta
        var descEl = modal.querySelector('[data-detail-description]');
        if (descEl) descEl.textContent = club.description || 'No detailed description recorded for this youth club.';

        var infoEl = modal.querySelector('[data-detail-club-info]');
        if (infoEl) {
            infoEl.innerHTML =
                '<dt>Zone</dt><dd>' + esc(club.zonal_name || '—') + '</dd>' +
                '<dt>Division</dt><dd>' + esc(club.division_name || '—') + '</dd>' +
                '<dt>Registered</dt><dd>' + fmtDate(club.registration_date) + '</dd>' +
                '<dt>Active Members</dt><dd>' + esc(club.no_of_members || club.active_members || 0) + ' members</dd>' +
                '<dt>Status</dt><dd>' + esc(club.status || 'Active') + '</dd>';
        }

        // Executives
        var execEl = modal.querySelector('[data-detail-executives]');
        if (execEl) {
            if (execs.length === 0) {
                execEl.innerHTML = '<p class="dw-muted-copy">No executive committee registered.</p>';
            } else {
                execEl.innerHTML = execs.map(function (x) {
                    return '<div class="dch-executive-item">' +
                        '<strong>' + esc(x.name) + '</strong>' +
                        '<span>' + esc(x.role) + (x.contact_no ? ' · ' + esc(x.contact_no) : '') + '</span>' +
                        '</div>';
                }).join('');
            }
        }

        // Performance Grid
        var perfEl = modal.querySelector('[data-detail-performance]');
        if (perfEl) {
            perfEl.innerHTML =
                '<div class="dch-perf-item"><strong>' + Math.round(Number(score.event_score || 0)) + '</strong><span>Events (40%)</span></div>' +
                '<div class="dch-perf-item"><strong>' + Math.round(Number(score.finance_score || 0)) + '</strong><span>Finance (30%)</span></div>' +
                '<div class="dch-perf-item"><strong>' + Math.round(Number(score.attendance_score || 0)) + '</strong><span>Attendance (30%)</span></div>';
        }

        // Events Table
        var eventsTbody = modal.querySelector('[data-detail-events]');
        var eventsEmpty = modal.querySelector('[data-detail-events-empty]');
        if (eventsTbody) {
            if (events.length === 0) {
                eventsTbody.innerHTML = '';
                if (eventsEmpty) eventsEmpty.style.display = 'block';
            } else {
                if (eventsEmpty) eventsEmpty.style.display = 'none';
                eventsTbody.innerHTML = events.map(function (ev) {
                    var pct = ev.total_registered > 0 ? Math.round((ev.present_count / ev.total_registered) * 100) : 0;
                    return '<tr>' +
                        '<td><strong>' + esc(ev.event_title) + '</strong></td>' +
                        '<td>' + fmtDate(ev.event_date) + '</td>' +
                        '<td><span class="dw-status dw-status--green">' + esc(ev.status || 'Completed') + '</span></td>' +
                        '<td>' + esc(ev.present_count || 0) + ' / ' + esc(ev.total_registered || 0) + '</td>' +
                        '<td>' + pct + '%</td>' +
                        '</tr>';
                }).join('');
            }
        }

        // Breakdown Cards
        var breakdownEl = modal.querySelector('[data-detail-breakdown]');
        if (breakdownEl) {
            breakdownEl.innerHTML =
                '<div class="dch-breakdown-card">' +
                    '<h4>Events Component</h4>' +
                    '<p>' + esc(score.events_completed || 0) + ' completed / 6 target in window</p>' +
                    '<strong>' + Math.round(Number(score.event_score || 0)) + ' / 100</strong>' +
                '</div>' +
                '<div class="dch-breakdown-card">' +
                    '<h4>Attendance Component</h4>' +
                    '<p>' + esc(score.total_present || 0) + ' present / ' + esc(score.total_attendance_records || 0) + ' recorded</p>' +
                    '<strong>' + Math.round(Number(score.attendance_score || 0)) + ' / 100</strong>' +
                '</div>' +
                '<div class="dch-breakdown-card">' +
                    '<h4>Finance Component</h4>' +
                    '<p>Activity 40% + Receipts 30% + Ledger 30%</p>' +
                    '<strong>' + Math.round(Number(score.finance_score || 0)) + ' / 100</strong>' +
                '</div>';
        }

        // Financial Summary & Entries
        var finSumEl = modal.querySelector('[data-detail-finance-summary]');
        if (finSumEl) {
            var sum = finance.summary || {};
            finSumEl.innerHTML =
                '<div class="dw-detail-box"><strong>LKR ' + money(sum.total_in) + '</strong><span>Total Income</span></div>' +
                '<div class="dw-detail-box"><strong>LKR ' + money(sum.total_out) + '</strong><span>Total Expenses</span></div>' +
                '<div class="dw-detail-box"><strong>LKR ' + money(sum.balance) + '</strong><span>Current Balance</span></div>' +
                '<div class="dw-detail-box"><strong>' + Math.round(Number(sum.receipt_coverage_pct || 0)) + '%</strong><span>Receipt Coverage</span></div>';
        }

        var finTbody = modal.querySelector('[data-detail-finance-entries]');
        var finEmpty = modal.querySelector('[data-detail-finance-empty]');
        if (finTbody) {
            var entries = finance.entries || [];
            if (entries.length === 0) {
                finTbody.innerHTML = '';
                if (finEmpty) finEmpty.style.display = 'block';
            } else {
                if (finEmpty) finEmpty.style.display = 'none';
                finTbody.innerHTML = entries.map(function (e) {
                    var isInc = (e.entry_type === 'Income' || e.entry_type === 'Grant');
                    return '<tr>' +
                        '<td>' + fmtDate(e.transaction_date) + '</td>' +
                        '<td>' + esc(e.entry_type) + '</td>' +
                        '<td>' + esc(e.description || '—') + '</td>' +
                        '<td style="font-weight:700; color:' + (isInc ? '#16a34a' : '#dc2626') + ';">' + (isInc ? '+' : '-') + ' LKR ' + money(e.amount) + '</td>' +
                        '<td>' + (e.receipt_url ? '<span style="color:#16a34a;">✓ Yes</span>' : '<span style="color:#dc2626;">✕ Missing</span>') + '</td>' +
                        '<td>' + (e.is_reconciled ? '✓ Reconciled' : 'Pending') + '</td>' +
                        '<td><span class="dw-status dw-status--' + (e.status === 'Approved' ? 'green' : 'yellow') + '">' + esc(e.status || 'Pending') + '</span></td>' +
                        '</tr>';
                }).join('');
            }
        }

        // Audits & Flags
        var auditEl = modal.querySelector('[data-detail-audits]');
        if (auditEl) {
            if (audits.length === 0) {
                auditEl.innerHTML = '<p class="dw-muted-copy">No recent financial audits on record.</p>';
            } else {
                auditEl.innerHTML = audits.map(function (a) {
                    return '<div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:10px; margin-bottom:8px; font-size:12.5px;">' +
                        '<strong>Audit Year ' + esc(a.audit_year) + '</strong> · Status: <span class="dw-status dw-status--' + (a.status === 'Passed' ? 'green' : 'red') + '">' + esc(a.status) + '</span>' +
                        '</div>';
                }).join('');
            }
        }

        // 6-Month History Table
        var histTbody = modal.querySelector('[data-detail-history]');
        if (histTbody) {
            if (history.length === 0) {
                histTbody.innerHTML = '<tr><td colspan="6" class="dw-muted-copy">No history records found.</td></tr>';
            } else {
                histTbody.innerHTML = history.map(function (h) {
                    var hKey = (h.health_status || 'Yellow').toLowerCase();
                    return '<tr>' +
                        '<td><strong>' + esc(h.month_year) + '</strong></td>' +
                        '<td>' + Math.round(Number(h.event_score || 0)) + '</td>' +
                        '<td>' + Math.round(Number(h.finance_score || 0)) + '</td>' +
                        '<td>' + Math.round(Number(h.attendance_score || 0)) + '</td>' +
                        '<td><strong>' + Math.round(Number(h.overall_score || 0)) + '</strong></td>' +
                        '<td><span class="dw-status dw-status--' + esc(hKey) + '">' + esc(h.health_status || 'Yellow') + '</span></td>' +
                        '</tr>';
                }).join('');
            }
        }

        // Health Concerns / Flags
        var flagsEl = modal.querySelector('[data-detail-flags]');
        if (flagsEl) {
            if (flags.length === 0) {
                flagsEl.innerHTML = '<p class="dw-muted-copy">No open health concerns recorded for this club.</p>';
            } else {
                flagsEl.innerHTML = flags.map(function (fl) {
                    return '<div style="background:#fff7ed; border:1px solid #ffedd5; border-radius:10px; padding:12px; margin-bottom:10px; font-size:12.5px;">' +
                        '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">' +
                        '<strong style="color:#c2410c;">' + esc(fl.flag_type || 'Concern') + '</strong>' +
                        '<span class="dw-status dw-status--yellow">' + esc(fl.severity || 'Warning') + '</span>' +
                        '</div>' +
                        '<p style="margin:0 0 4px 0; color:#475569;">' + esc(fl.description || fl.concern_reason || '') + '</p>' +
                        '<small style="color:#94a3b8;">Raised on ' + fmtDate(fl.created_at) + '</small>' +
                        '</div>';
                }).join('');
            }
        }

        // ── Disbandment Panel (NYSC Admin Only) ──────────────────────────────
        var disbandPanel = modal.querySelector('[data-disband-panel]');
        if (disbandPanel) {
            var isDormant = (score.health_status === 'Red');
            var isDisbanded = (club.status === 'Disbanded');

            if (isDisbanded) {
                disbandPanel.style.display = 'block';
                var headerTitle = disbandPanel.querySelector('h3');
                if (headerTitle) headerTitle.textContent = '❌ Club Status: Disbanded';
                var headerSub = disbandPanel.querySelector('[data-disband-subtitle]');
                if (headerSub) headerSub.textContent = 'Disbanded on ' + fmtDate(club.disbanded_at) + ' · Reason: ' + (club.disband_reason || 'Administrative disbandment');
                var actionsDiv = disbandPanel.querySelector('.dch-disband-panel__actions');
                if (actionsDiv) actionsDiv.style.display = 'none';
            } else if (isDormant) {
                disbandPanel.style.display = 'block';
                var headerTitle = disbandPanel.querySelector('h3');
                if (headerTitle) headerTitle.textContent = '⚠ Dormant Club — Disbandment Controls';
                var headerSub = disbandPanel.querySelector('[data-disband-subtitle]');
                if (headerSub) headerSub.textContent = 'This club is in the Red (Dormant) band (Score: ' + Math.round(Number(score.overall_score || 0)) + '/100).';
                var actionsDiv = disbandPanel.querySelector('.dch-disband-panel__actions');
                if (actionsDiv) actionsDiv.style.display = 'flex';

                var balNote = disbandPanel.querySelector('[data-disband-balance-note]');
                if (balNote) {
                    var curBal = Number(finance.summary ? finance.summary.balance : 0);
                    if (curBal > 0) {
                        balNote.style.display = 'block';
                        balNote.textContent = 'Note: Club has a remaining balance of LKR ' + money(curBal) + '. Executing disband will generate an automatic Fund Transfer Out entry clearing this balance.';
                    } else {
                        balNote.style.display = 'none';
                    }
                }
            } else {
                disbandPanel.style.display = 'none';
            }
        }
    });

    // ── 4. Warn Action ────────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var warnBtn = e.target.closest('[data-warn-btn]');
        if (!warnBtn || !currentClubId) return;

        if (!confirm('Send formal dormancy/disband warning to the division coordinator and club leadership?')) {
            return;
        }

        warnBtn.disabled = true;
        warnBtn.textContent = 'Sending warning…';

        post(ROOT + '/clubhealth/warn/' + encodeURIComponent(currentClubId), {})
            .then(function (res) {
                if (res && res.success) {
                    toast(res.message || 'Warning notification sent to divisional coordinator.', true);
                    warnBtn.textContent = 'Warning Sent ✓';
                } else {
                    toast((res && res.message) ? res.message : 'Could not send warning.', false);
                    warnBtn.disabled = false;
                    warnBtn.textContent = 'Issue Disband Warning';
                }
            })
            .catch(function (err) {
                toast('Network error while issuing warning: ' + err.message, false);
                warnBtn.disabled = false;
                warnBtn.textContent = 'Issue Disband Warning';
            });
    });

    // ── 5. Execute Disband Modal Triggers ─────────────────────────────────────
    document.addEventListener('click', function (e) {
        var execBtn = e.target.closest('[data-exec-disband-btn]');
        if (!execBtn || !currentClubId) return;

        // Close profile modal
        closeModal('club-health-details');

        // Reset disband form
        if (disbandAck) disbandAck.checked = false;
        if (disbandReason) disbandReason.value = '';
        if (disbandConfirm) {
            disbandConfirm.disabled = true;
            disbandConfirm.setAttribute('data-club', currentClubId);
        }

        // Open confirm disband modal
        openModal('club-health-disband');
    });

    function validateDisbandForm() {
        if (!disbandReason || !disbandAck || !disbandConfirm) return;
        var reasonVal = (disbandReason.value || '').trim();
        var isValid = (reasonVal.length >= 10 && disbandAck.checked);
        disbandConfirm.disabled = !isValid;
    }

    if (disbandReason) {
        disbandReason.addEventListener('input', validateDisbandForm);
        disbandReason.addEventListener('keyup', validateDisbandForm);
    }
    if (disbandAck) {
        disbandAck.addEventListener('change', validateDisbandForm);
    }

    if (disbandConfirm) {
        disbandConfirm.addEventListener('click', function () {
            var clubId = disbandConfirm.getAttribute('data-club') || currentClubId;
            var reasonVal = disbandReason ? (disbandReason.value || '').trim() : '';

            if (!clubId || reasonVal.length < 10 || !disbandAck.checked) {
                toast('Please provide a reason of at least 10 characters and accept the acknowledgement.', false);
                return;
            }

            disbandConfirm.disabled = true;
            disbandConfirm.textContent = 'Disbanding…';

            post(ROOT + '/clubhealth/disband/' + encodeURIComponent(clubId), { reason: reasonVal })
                .then(function (res) {
                    if (res && res.success) {
                        toast(res.message || 'Club disbanded successfully.', true);
                        closeModal('club-health-disband');
                        setTimeout(function () {
                            window.location.reload();
                        }, 1200);
                    } else {
                        toast((res && res.message) ? res.message : 'Disband action failed.', false);
                        disbandConfirm.disabled = false;
                        disbandConfirm.textContent = 'Execute Disband';
                    }
                })
                .catch(function (err) {
                    toast('Error executing disband: ' + err.message, false);
                    disbandConfirm.disabled = false;
                    disbandConfirm.textContent = 'Execute Disband';
                });
        });
    }

})();
