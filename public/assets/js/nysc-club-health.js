(function () {
  'use strict';

  var cards = Array.prototype.slice.call(document.querySelectorAll('[data-health-card]'));
  var search = document.querySelector('[data-health-search]');
  var zoneFilter = document.querySelector('[data-zone-filter]');    // now holds province
  var divisionFilter = document.querySelector('[data-division-filter]');
  var typeFilter = document.querySelector('[data-type-filter]');
  var applyBtn = document.querySelector('[data-health-apply]');
  var resetBtn = document.querySelector('[data-health-reset]');

  var dataNode = document.getElementById('club-health-data');
  var details = dataNode ? JSON.parse(dataNode.textContent || '{}') : {};
  var rootUrl = dataNode ? String(dataNode.dataset.root || '').replace(/\/$/, '') : '';

  var divisionDataNode = document.getElementById('division-data');
  var allDivisions = divisionDataNode ? JSON.parse(divisionDataNode.textContent || '[]') : [];

  var activeClubId = null;

  function node(tag, text, className) {
    var element = document.createElement(tag);
    if (text !== undefined && text !== null) element.textContent = String(text);
    if (className) element.className = className;
    return element;
  }

  function formatDate(value) {
    if (!value) return 'Not recorded';
    var date = new Date(String(value).replace(' ', 'T'));
    return isNaN(date.getTime()) ? String(value) : date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  function formatMoney(value) {
    return 'Rs. ' + Number(value || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function healthLabel(status) {
    return status === 'Green' ? 'Healthy' : (status === 'Yellow' ? 'At Risk' : 'Dormant');
  }

  function readableLabel(value) {
    return String(value || '').replace(/([a-z])([A-Z])/g, '$1 $2').replace(/_/g, ' ');
  }

  function initials(value) {
    return String(value || 'Club').trim().split(/\s+/).map(function (part) {
      return part.charAt(0).toUpperCase();
    }).join('').slice(0, 2) || 'CL';
  }

  function mediaUrl(value) {
    var path = String(value || '').trim();
    if (!path) return '';
    if (/^(https?:|data:)/i.test(path)) return path;
    return rootUrl + '/' + path.replace(/^\/+/, '');
  }

  function avatar(name, imagePath, className) {
    var holder = node('div', initials(name), className);
    var source = mediaUrl(imagePath);
    if (!source) return holder;
    var image = document.createElement('img');
    image.alt = String(name || 'Profile') + ' profile image';
    image.addEventListener('load', function () {
      holder.textContent = '';
      holder.appendChild(image);
    });
    image.src = source;
    return holder;
  }

  function statusPill(text, statusClass) {
    return node('span', text, 'dw-status dw-status--' + String(statusClass || text).toLowerCase().replace(/[^a-z0-9]+/g, '-'));
  }

  // 1. Dynamic Division dropdown: show only divisions matching the selected province
  function updateDivisionDropdown(selectedProvince) {
    if (!divisionFilter) return;
    var currentDiv = divisionFilter.value;
    var options = Array.prototype.slice.call(divisionFilter.querySelectorAll('option'));
    options.forEach(function (opt) {
      if (!opt.value) return; // keep "All Divisions"
      var optProvince = opt.getAttribute('data-province') || '';
      opt.hidden = selectedProvince ? (optProvince !== selectedProvince) : false;
    });
    // If current selection is now hidden, reset it
    var currentOpt = divisionFilter.querySelector('option[value="' + currentDiv + '"]');
    if (currentDiv && currentOpt && currentOpt.hidden) {
      divisionFilter.value = '';
    }
  }

  if (zoneFilter) {
    zoneFilter.addEventListener('change', function () {
      updateDivisionDropdown(this.value);
      applyFilters();
    });
  }

  if (divisionFilter) {
    divisionFilter.addEventListener('change', applyFilters);
  }

  if (typeFilter) {
    typeFilter.addEventListener('change', applyFilters);
  }

  if (search) {
    search.addEventListener('input', applyFilters);
  }

  function applyFilters() {
    var query = search ? search.value.trim().toLowerCase() : '';
    var selectedProvince = zoneFilter ? zoneFilter.value : '';   // province string
    var selectedDiv = divisionFilter ? divisionFilter.value : '';
    var selectedType = typeFilter ? typeFilter.value : '';

    var visible = 0;
    cards.forEach(function (card) {
      var cardProvince = card.dataset.province || '';
      var cardDiv = card.dataset.divisionId || '';
      var cardStatus = (card.dataset.status || '').toLowerCase(); // green, yellow, red
      var cardFlagged = card.dataset.flagged === '1' || Number(card.dataset.flags || 0) > 0;
      var searchContent = card.dataset.search || '';

      var matchQuery = !query || searchContent.indexOf(query) !== -1;
      var matchProvince = !selectedProvince || cardProvince === selectedProvince;
      var matchDiv = !selectedDiv || cardDiv === selectedDiv;

      var matchType = true;
      if (selectedType === 'active') {
        matchType = (card.dataset.clubStatus === 'Active') && cardStatus !== 'red';
      } else if (selectedType === 'flagged') {
        matchType = cardFlagged;
      } else if (selectedType === 'dormant') {
        matchType = (cardStatus === 'red' || Number(card.dataset.score || 0) < 30);
      }

      var show = matchQuery && matchProvince && matchDiv && matchType;
      card.hidden = !show;
      if (show) visible++;
    });

    var count = document.querySelector('[data-health-count]');
    var empty = document.querySelector('[data-health-empty]');
    if (count) count.textContent = visible + ' clubs';
    if (empty) empty.classList.toggle('is-visible', visible === 0);
  }

  if (applyBtn) {
    applyBtn.addEventListener('click', applyFilters);
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      if (search) search.value = '';
      if (zoneFilter) zoneFilter.value = '';
      if (typeFilter) typeFilter.value = '';
      updateDivisionDropdown('');
      if (divisionFilter) divisionFilter.value = '';
      applyFilters();
    });
  }

  // Populate Club Info & General Details
  function populateClubInfo(record) {
    var club = record.club;
    document.querySelector('[data-detail-club-name]').textContent = club.club_name;
    document.querySelector('[data-detail-club-code]').textContent = club.club_code;
    document.querySelector('[data-detail-description]').textContent = club.description || 'No club description has been recorded.';

    // Start date / Registration date
    var regDate = formatDate(club.registration_date || club.date_establishment);
    var startDateEl = document.querySelector('[data-detail-start-date]');
    if (startDateEl) startDateEl.textContent = regDate;

    // President Name
    var presEl = document.querySelector('[data-detail-president-name]');
    if (presEl) presEl.textContent = club.president_name ? club.president_name : 'Not Assigned';

    // Events conducted
    var evCountEl = document.querySelector('[data-detail-events-conducted]');
    if (evCountEl) evCountEl.textContent = (club.completed_events_count || record.score.completed_events || 0) + ' Events';

    // Balance in ledger
    var balance = record.finance.ledger ? Number(record.finance.ledger.current_balance || 0) : Number(club.ledger_balance || 0);
    var balEl = document.querySelector('[data-detail-ledger-balance]');
    if (balEl) balEl.textContent = formatMoney(balance);

    // Total asset count
    var assetCountEl = document.querySelector('[data-detail-asset-count]');
    if (assetCountEl) assetCountEl.textContent = (club.total_asset_count || 0) + ' Assets';

    // Meta list
    var target = document.querySelector('[data-detail-club-info]');
    if (target) {
      target.replaceChildren();
      [
        ['Zone', club.zonal_name],
        ['Division', club.division_name],
        ['Established', regDate],
        ['Active Members', club.active_members + ' youth members'],
        ['Registration Status', club.status]
      ].forEach(function (item) {
        var row = node('div');
        row.append(node('dt', item[0]), node('dd', item[1]));
        target.appendChild(row);
      });
    }

    // Executives
    var executives = document.querySelector('[data-detail-executives]');
    if (executives) {
      executives.replaceChildren();
      if (!record.executives.length) {
        executives.appendChild(node('p', 'No active executive records found.', 'dw-muted-copy'));
      } else {
        record.executives.forEach(function (person) {
          var item = node('div', null, 'dch-executive');
          var name = (person.first_name + ' ' + person.last_name).trim();
          var information = node('div', null, 'dch-executive__info');
          information.append(node('strong', name), node('span', person.role.replace('Club', '')));
          if (person.email) information.appendChild(node('small', person.email));
          item.append(avatar(name, person.profile_picture_url, 'dch-executive__avatar'), information);
          executives.appendChild(item);
        });
      }
    }

    // Handle Disband & Warning UI logic based on Dormancy & Flagged status
    var isFlagged = Number(club.flagged) === 1 || (record.flags && record.flags.length > 0);
    var isDormant = record.score.health_status === 'Red' || Number(record.score.overall_score) < 30;
    var disbandPanel = document.querySelector('[data-disband-panel]');
    var retrieveFundsBtn = document.querySelector('[data-btn-retrieve-funds]');
    var executeDisbandBtn = document.querySelector('[data-btn-execute-disband]');
    var executeLockMsg = document.querySelector('[data-disband-lock-msg]');

    if (disbandPanel) {
      if (isFlagged || isDormant) {
        disbandPanel.hidden = false;
        // Check balance
        if (balance > 0) {
          if (retrieveFundsBtn) {
            retrieveFundsBtn.hidden = false;
            retrieveFundsBtn.setAttribute('data-funds-amount', balance);
          }
          if (executeDisbandBtn) {
            executeDisbandBtn.disabled = true;
          }
          if (executeLockMsg) {
            executeLockMsg.hidden = false;
            executeLockMsg.textContent = 'Note: Club holds LKR ' + Number(balance).toLocaleString('en-LK', { minimumFractionDigits: 2 }) + ' in active funds. You must retrieve remaining funds to the National Treasury before executing disbandment.';
          }
        } else {
          if (retrieveFundsBtn) {
            retrieveFundsBtn.hidden = true;
          }
          if (executeDisbandBtn) {
            executeDisbandBtn.disabled = false;
          }
          if (executeLockMsg) {
            executeLockMsg.hidden = true;
          }
        }
      } else {
        disbandPanel.hidden = true;
      }
    }
  }

  function populatePerformance(record) {
    var target = document.querySelector('[data-detail-performance]');
    if (!target) return;
    target.replaceChildren();
    [
      ['Active members', record.club.active_members],
      ['Completed events', record.score.completed_events],
      ['Attendance rate', Number(record.score.attendance_score).toFixed(0) + '%'],
      ['Current balance', record.finance.ledger ? formatMoney(record.finance.ledger.current_balance) : 'No ledger']
    ].forEach(function (metric) {
      var item = node('div', null, 'dch-performance-card');
      item.append(node('span', metric[0]), node('strong', metric[1]));
      target.appendChild(item);
    });
  }

  function populateBreakdown(record) {
    var target = document.querySelector('[data-detail-breakdown]');
    if (!target) return;
    target.replaceChildren();
    [
      ['Events', record.score.event_score, '40%', record.score.completed_events + ' completed of 6 target events'],
      ['Finances', record.score.finance_score, '30%', record.score.approved_entries + ' approved entries; ' + record.score.receipted_expenses + ' of ' + record.score.expense_entries + ' expenses receipted; ' + record.score.reconciled_entries + ' entries reconciled'],
      ['Attendance', record.score.attendance_score, '30%', record.score.attendance_present + ' present of ' + record.score.attendance_recorded + ' recorded']
    ].forEach(function (metric) {
      var item = node('div', null, 'dch-breakdown__item');
      var heading = node('div');
      heading.append(node('strong', metric[0]), node('span', metric[2] + ' weight'));
      var bar = node('div', null, 'dch-meter');
      var fill = node('span');
      fill.style.width = Math.max(0, Math.min(100, Number(metric[1]))) + '%';
      bar.appendChild(fill);
      item.append(heading, node('b', Number(metric[1]).toFixed(1) + ' / 100'), bar, node('p', metric[3]));
      target.appendChild(item);
    });
  }

  function populateEvents(record) {
    var body = document.querySelector('[data-detail-events]');
    var empty = document.querySelector('[data-detail-events-empty]');
    if (!body) return;
    body.replaceChildren();
    record.events.forEach(function (event) {
      var recorded = Number(event.attendance_recorded || 0);
      var present = Number(event.present_count || 0);
      var rate = recorded ? Math.round((present / recorded) * 100) + '%' : 'Not recorded';
      var row = node('tr');
      [event.title, formatDate(event.start_datetime), event.status, present + ' of ' + recorded, rate]
        .forEach(function (value) { row.appendChild(node('td', value)); });
      body.appendChild(row);
    });
    if (empty) empty.hidden = record.events.length > 0;
  }

  function populateHistory(record) {
    var body = document.querySelector('[data-detail-history]');
    if (!body) return;
    body.replaceChildren();
    (record.history || []).forEach(function (snapshot) {
      var row = node('tr');
      [
        formatDate(snapshot.score_month),
        Number(snapshot.event_score).toFixed(1),
        Number(snapshot.finance_score).toFixed(1),
        Number(snapshot.attendance_score).toFixed(1),
        Number(snapshot.overall_score).toFixed(1)
      ].forEach(function (value) { row.appendChild(node('td', value)); });
      var statusCell = node('td');
      statusCell.appendChild(statusPill(healthLabel(snapshot.health_status), snapshot.health_status));
      row.appendChild(statusCell);
      body.appendChild(row);
    });
  }

  function populateFinance(record) {
    var finance = record.finance;
    var summary = document.querySelector('[data-detail-finance-summary]');
    if (summary) {
      summary.replaceChildren();
      var totals = finance.totals || {};
      [
        ['Current balance', finance.ledger ? formatMoney(finance.ledger.current_balance) : 'No club ledger'],
        ['Income in window', formatMoney(totals.income)],
        ['Expenses in window', formatMoney(totals.expenses)]
      ].forEach(function (metric) {
        var item = node('div');
        item.append(node('span', metric[0]), node('strong', metric[1]));
        summary.appendChild(item);
      });
    }

    var body = document.querySelector('[data-detail-finance-entries]');
    var empty = document.querySelector('[data-detail-finance-empty]');
    if (body) {
      body.replaceChildren();
      finance.entries.forEach(function (entry) {
        var row = node('tr');
        [formatDate(entry.date), entry.type, entry.description || entry.category || 'No description', formatMoney(entry.amount),
          entry.attachment_url ? 'Attached' : 'Not attached', Number(entry.reconciled) === 1 ? 'Yes' : 'No', entry.status]
          .forEach(function (value) { row.appendChild(node('td', value)); });
        body.appendChild(row);
      });
      if (empty) empty.hidden = finance.entries.length > 0;
    }

    var audits = document.querySelector('[data-detail-audits]');
    if (audits) {
      audits.replaceChildren();
      if (!finance.audits.length && !finance.red_flags.length) {
        audits.appendChild(node('p', 'No club audits or financial flags found.', 'dw-muted-copy'));
      } else {
        finance.audits.forEach(function (audit) {
          var item = node('div', null, 'dch-review-row');
          item.append(node('strong', 'FY ' + audit.financial_year + ' audit - ' + audit.audit_status), node('span', 'Math Check: ' + (audit.math_check_status || 'Pending')));
          audits.appendChild(item);
        });
        finance.red_flags.forEach(function (flag) {
          var item = node('div', null, 'dch-review-row dch-review-row--warning');
          item.append(node('strong', flag.flag_type + ' - ' + flag.status), node('span', flag.description || flag.reason || 'Flagged audit record'));
          audits.appendChild(item);
        });
      }
    }
  }

  function populateFlags(record) {
    var target = document.querySelector('[data-detail-flags]');
    if (!target) return;
    target.replaceChildren();
    if (!record.flags.length) {
      target.appendChild(node('p', 'No health concerns have been recorded.', 'dw-muted-copy'));
      return;
    }
    record.flags.forEach(function (flag) {
      var item = node('div', null, 'dch-review-row' + (flag.status === 'Open' ? ' dch-review-row--warning' : ''));
      var source = flag.source === 'System' ? 'System generated' : ((flag.raised_by_name || 'NYSC Administrator') + ' - ' + (flag.raised_by_role || ''));
      item.append(node('strong', readableLabel(flag.flag_category) + ' - ' + readableLabel(flag.status)), node('span', flag.reason), node('small', source + ' on ' + formatDate(flag.raised_at)));
      target.appendChild(item);
    });
  }

  function openDetails(clubId) {
    var record = details[String(clubId)];
    if (!record) return;
    activeClubId = String(clubId);

    var detailAvatar = document.querySelector('[data-detail-avatar]');
    if (detailAvatar) {
      var nextAvatar = avatar(record.club.club_name, record.club.club_logo_path, 'dch-modal__avatar');
      nextAvatar.setAttribute('data-detail-avatar', '');
      detailAvatar.replaceWith(nextAvatar);
    }

    var overallEl = document.querySelector('[data-detail-overall]');
    if (overallEl) overallEl.textContent = Number(record.score.overall_score).toFixed(0);

    var windowEl = document.querySelector('[data-detail-window]');
    if (windowEl) windowEl.textContent = 'Scoring window: ' + formatDate(record.score.window_start) + ' to ' + formatDate(record.score.window_end);

    var pill = document.querySelector('[data-detail-status]');
    if (pill) {
      pill.textContent = healthLabel(record.score.health_status);
      pill.className = 'dw-status dw-status--' + record.score.health_status.toLowerCase();
    }

    populateClubInfo(record);
    populateBreakdown(record);
    populateHistory(record);
    populatePerformance(record);
    populateEvents(record);
    populateFinance(record);
    populateFlags(record);

    var modal = document.getElementById('club-health-details');
    if (modal) {
      modal.hidden = false;
      modal.setAttribute('aria-modal', 'true');
    }
  }

  // Bind click on card or card buttons to open details
  cards.forEach(function (card) {
    card.addEventListener('click', function (e) {
      if (e.target.closest('button')) return;
      openDetails(card.dataset.clubId);
    });
  });

  document.querySelectorAll('[data-club-details]').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.stopPropagation();
      openDetails(button.dataset.clubDetails);
    });
  });

  // Modal Close Handling
  document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var modal = btn.closest('.dw-modal');
      if (modal) {
        modal.hidden = true;
      }
    });
  });

  // 1. Issue Disband Warning Modal & Form Setup
  var warningBtn = document.querySelector('[data-open-disband-warning]');
  var warningModal = document.getElementById('nysc-warning-modal');
  var warningForm = document.querySelector('[data-warning-form]');

  if (warningBtn) {
    warningBtn.addEventListener('click', function () {
      var record = details[activeClubId];
      if (!record || !warningModal) return;

      warningForm.action = warningForm.dataset.actionBase + activeClubId;
      document.querySelector('[data-warning-club-name]').textContent = record.club.club_name;

      var execNames = (record.executives || []).map(function (e) {
        return e.first_name + ' ' + e.last_name + ' (' + e.role.replace('Club', '') + ' - ' + e.email + ')';
      }).join(', ');

      document.querySelector('[data-warning-recipients]').textContent = execNames || 'President & Secretary';

      // Hide details modal, open warning modal
      var detailModal = document.getElementById('club-health-details');
      if (detailModal) detailModal.hidden = true;
      warningModal.hidden = false;
    });
  }

  // 2. Retrieve Remaining Funds Modal & Form Setup
  var retrieveBtn = document.querySelector('[data-btn-retrieve-funds]');
  var retrieveModal = document.getElementById('nysc-retrieve-funds-modal');
  var retrieveForm = document.querySelector('[data-retrieve-funds-form]');

  if (retrieveBtn) {
    retrieveBtn.addEventListener('click', function () {
      var record = details[activeClubId];
      if (!record || !retrieveModal) return;

      retrieveForm.action = retrieveForm.dataset.actionBase + activeClubId;
      document.querySelector('[data-retrieve-club-name]').textContent = record.club.club_name;
      var balance = record.finance.ledger ? Number(record.finance.ledger.current_balance || 0) : Number(record.club.ledger_balance || 0);
      document.querySelector('[data-retrieve-amount]').textContent = formatMoney(balance);

      var detailModal = document.getElementById('club-health-details');
      if (detailModal) detailModal.hidden = true;
      retrieveModal.hidden = false;
    });
  }

  // 3. Execute Disband Modal & Form Setup
  var disbandBtn = document.querySelector('[data-btn-execute-disband]');
  var disbandModal = document.getElementById('nysc-disband-modal');
  var disbandForm = document.querySelector('[data-disband-form]');

  if (disbandBtn) {
    disbandBtn.addEventListener('click', function () {
      var record = details[activeClubId];
      if (!record || !disbandModal) return;

      disbandForm.action = disbandForm.dataset.actionBase + activeClubId;
      document.querySelector('[data-disband-club-name]').textContent = record.club.club_name;

      var detailModal = document.getElementById('club-health-details');
      if (detailModal) detailModal.hidden = true;
      disbandModal.hidden = false;
    });
  }

  // Initial: show all divisions (no province selected)
  if (zoneFilter) {
    updateDivisionDropdown(zoneFilter.value);
  }
})();
