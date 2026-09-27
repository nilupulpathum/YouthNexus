(function () {
  'use strict';

  var cards = Array.prototype.slice.call(document.querySelectorAll('[data-health-card]'));
  var search = document.querySelector('[data-health-search]');
  var statusFilter = document.querySelector('[data-health-status]');
  var flagFilter = document.querySelector('[data-health-flag]');
  var minScore = document.querySelector('[data-health-min]');
  var maxScore = document.querySelector('[data-health-max]');
  var sort = document.querySelector('[data-health-sort]');
  var dataNode = document.getElementById('club-health-data');
  var details = dataNode ? JSON.parse(dataNode.textContent || '{}') : {};
  var rootUrl = dataNode ? String(dataNode.dataset.root || '').replace(/\/$/, '') : '';
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

  function applyFilters() {
    var query = search ? search.value.trim().toLowerCase() : '';
    var selectedStatus = statusFilter ? statusFilter.value : '';
    var selectedFlag = flagFilter ? flagFilter.value : '';
    var minimum = minScore && minScore.value !== '' ? Number(minScore.value) : null;
    var maximum = maxScore && maxScore.value !== '' ? Number(maxScore.value) : null;
    var visible = 0;
    cards.forEach(function (card) {
      var score = Number(card.dataset.score || 0);
      var flags = Number(card.dataset.flags || 0);
      var show = (!query || (card.dataset.search || '').indexOf(query) !== -1)
        && (!selectedStatus || card.dataset.status === selectedStatus)
        && (!selectedFlag || (selectedFlag === 'flagged' ? flags > 0 : flags === 0))
        && (minimum === null || score >= minimum)
        && (maximum === null || score <= maximum);
      card.hidden = !show;
      if (show) visible++;
    });
    var count = document.querySelector('[data-health-count]');
    var empty = document.querySelector('[data-health-empty]');
    if (count) count.textContent = visible + ' clubs';
    if (empty) empty.classList.toggle('is-visible', visible === 0);
  }

  function sortCards() {
    var grid = document.querySelector('[data-health-grid]');
    var mode = sort ? sort.value : 'score-high';
    cards.sort(function (a, b) {
      if (mode === 'score-low') return Number(a.dataset.score) - Number(b.dataset.score);
      if (mode === 'name') return (a.dataset.name || '').localeCompare(b.dataset.name || '');
      if (mode === 'flags') return Number(b.dataset.flags) - Number(a.dataset.flags);
      return Number(b.dataset.score) - Number(a.dataset.score);
    }).forEach(function (card) { grid.appendChild(card); });
  }

  if (search) search.addEventListener('input', applyFilters);
  document.querySelector('[data-health-apply]').addEventListener('click', function () { sortCards(); applyFilters(); });
  document.querySelector('[data-health-reset]').addEventListener('click', function () {
    statusFilter.value = '';
    flagFilter.value = '';
    minScore.value = '';
    maxScore.value = '';
    sort.value = 'score-high';
    sortCards();
    applyFilters();
  });

  function populateClubInfo(record) {
    var target = document.querySelector('[data-detail-club-info]');
    target.replaceChildren();
    document.querySelector('[data-detail-description]').textContent = record.club.description || 'No club description has been recorded.';
    [
      ['Category', record.club.club_category || 'Not recorded'],
      ['Location', record.club.club_city || record.club.division_name],
      ['Division and zone', record.club.division_name + ', ' + record.club.zonal_name],
      ['Established', formatDate(record.club.date_establishment || record.club.registration_date)],
      ['Registration status', record.club.status]
    ].forEach(function (item) {
      var row = node('div');
      row.append(node('dt', item[0]), node('dd', item[1]));
      target.appendChild(row);
    });

    var executives = document.querySelector('[data-detail-executives]');
    executives.replaceChildren();
    if (!record.executives.length) {
      executives.appendChild(node('p', 'No active executive records found.', 'dw-muted-copy'));
      return;
    }
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

  function populatePerformance(record) {
    var target = document.querySelector('[data-detail-performance]');
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
    empty.hidden = record.events.length > 0;
  }

  function populateHistory(record) {
    var body = document.querySelector('[data-detail-history]');
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

    var body = document.querySelector('[data-detail-finance-entries]');
    var empty = document.querySelector('[data-detail-finance-empty]');
    body.replaceChildren();
    finance.entries.forEach(function (entry) {
      var row = node('tr');
      [formatDate(entry.date), entry.type, entry.description || entry.category || 'No description', formatMoney(entry.amount),
        entry.attachment_url ? 'Attached' : 'Not attached', Number(entry.reconciled) === 1 ? 'Yes' : 'No', entry.status]
        .forEach(function (value) { row.appendChild(node('td', value)); });
      body.appendChild(row);
    });
    empty.hidden = finance.entries.length > 0;

    var audits = document.querySelector('[data-detail-audits]');
    audits.replaceChildren();
    if (!finance.audits.length && !finance.red_flags.length) {
      audits.appendChild(node('p', 'No club audits or financial flags found.', 'dw-muted-copy'));
      return;
    }
    finance.audits.forEach(function (audit) {
      var item = node('div', null, 'dch-review-row');
      item.append(node('strong', audit.audit_type + ' audit - ' + audit.audit_status), node('span', formatDate(audit.period_start) + ' to ' + formatDate(audit.period_end)));
      audits.appendChild(item);
    });
    finance.red_flags.forEach(function (flag) {
      var item = node('div', null, 'dch-review-row dch-review-row--warning');
      item.append(node('strong', flag.flag_type + ' - ' + flag.status), node('span', flag.description));
      audits.appendChild(item);
    });
  }

  function populateFlags(record) {
    var target = document.querySelector('[data-detail-flags]');
    target.replaceChildren();
    if (!record.flags.length) {
      target.appendChild(node('p', 'No health concerns have been recorded.', 'dw-muted-copy'));
      return;
    }
    record.flags.forEach(function (flag) {
      var item = node('div', null, 'dch-review-row' + (flag.status === 'Open' ? ' dch-review-row--warning' : ''));
      var source = flag.source === 'System' ? 'System generated' : ((flag.raised_by_name || 'Divisional officer') + ' - ' + (flag.raised_by_role || ''));
      item.append(node('strong', readableLabel(flag.flag_category) + ' - ' + readableLabel(flag.status)), node('span', flag.reason), node('small', source + ' on ' + formatDate(flag.raised_at)));
      target.appendChild(item);
    });
  }

  function openDetails(clubId) {
    var record = details[String(clubId)];
    if (!record) return;
    activeClubId = String(clubId);
    document.querySelector('[data-detail-club-name]').textContent = record.club.club_name;
    document.querySelector('[data-detail-club-code]').textContent = record.club.club_code;
    var detailAvatar = document.querySelector('[data-detail-avatar]');
    var nextAvatar = avatar(record.club.club_name, record.club.club_logo_path, 'dch-modal__avatar');
    nextAvatar.setAttribute('data-detail-avatar', '');
    detailAvatar.replaceWith(nextAvatar);
    document.querySelector('[data-detail-overall]').textContent = Number(record.score.overall_score).toFixed(0);
    document.querySelector('[data-detail-window]').textContent = 'Scoring window: ' + formatDate(record.score.window_start) + ' to ' + formatDate(record.score.window_end);
    var pill = document.querySelector('[data-detail-status]');
    pill.textContent = healthLabel(record.score.health_status);
    pill.className = 'dw-status dw-status--' + record.score.health_status.toLowerCase();
    populateBreakdown(record);
    populateHistory(record);
    populateClubInfo(record);
    populatePerformance(record);
    populateEvents(record);
    populateFinance(record);
    populateFlags(record);
  }

  document.querySelectorAll('[data-club-details]').forEach(function (button) {
    button.addEventListener('click', function () { openDetails(button.dataset.clubDetails); });
  });

  var flagForm = document.querySelector('[data-health-flag-form]');
  document.querySelector('[data-open-health-flag]').addEventListener('click', function () {
    var record = details[activeClubId];
    if (!record) return;
    flagForm.action = flagForm.dataset.actionBase + activeClubId;
    document.querySelector('[data-flag-club-name]').textContent = record.club.club_name;
    var detailModal = document.getElementById('club-health-details');
    detailModal.hidden = true;
    detailModal.setAttribute('aria-hidden', 'true');
  });
})();
