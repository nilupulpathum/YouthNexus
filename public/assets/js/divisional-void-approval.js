(function () {
  'use strict';

  var search = document.querySelector('[data-approval-search]');
  if (!search) return;

  var rows = Array.prototype.slice.call(document.querySelectorAll('[data-approval-row]'));
  var pendingBody = document.querySelector('[data-pending-body]');
  var decidedBody = document.querySelector('[data-decided-body]');
  var club = document.querySelector('[data-approval-club-filter]');
  var status = document.querySelector('[data-approval-status-filter]');
  var type = document.querySelector('[data-approval-type-filter]');
  var reason = document.querySelector('[data-approval-reason-filter]');
  var minimum = document.querySelector('[data-approval-min-amount]');
  var maximum = document.querySelector('[data-approval-max-amount]');
  var dateFrom = document.querySelector('[data-approval-date-from]');
  var dateTo = document.querySelector('[data-approval-date-to]');
  var sort = document.querySelector('[data-approval-sort]');
  var pendingCount = document.querySelector('[data-pending-count]');
  var decidedCount = document.querySelector('[data-decided-count]');
  var pendingEmpty = document.querySelector('[data-pending-empty]');
  var decidedEmpty = document.querySelector('[data-decided-empty]');

  var panelControls = [club, status, type, reason, minimum, maximum, dateFrom, dateTo, sort];
  var appliedValues = new Map();
  function captureFilters() {
    panelControls.forEach(function (control) {
      if (control) appliedValues.set(control, control.value);
    });
  }
  function selected(control) {
    return control ? String(appliedValues.get(control) || '') : '';
  }
  captureFilters();

  function value(control) {
    return control ? String(control.value || '').trim().toLowerCase() : '';
  }

  function numberValue(control) {
    var current = selected(control);
    if (current === '') return null;
    var parsed = Number(current);
    return Number.isFinite(parsed) ? parsed : null;
  }

  function selectedValue(control) { return selected(control).trim().toLowerCase(); }

  function sortRows(sectionRows, body) {
    if (!body) return;
    var selected = selectedValue(sort) || 'newest';
    sectionRows.sort(function (left, right) {
      if (selected === 'oldest') return left.dataset.date.localeCompare(right.dataset.date);
      if (selected === 'amount-high') return Number(right.dataset.amount) - Number(left.dataset.amount);
      if (selected === 'amount-low') return Number(left.dataset.amount) - Number(right.dataset.amount);
      return right.dataset.date.localeCompare(left.dataset.date);
    });
    sectionRows.forEach(function (row) { body.appendChild(row); });
  }

  function applyFilters() {
    var query = value(search);
    var selectedClub = selectedValue(club);
    var selectedStatus = selectedValue(status);
    var selectedType = selectedValue(type);
    var reasonText = selectedValue(reason);
    var min = numberValue(minimum);
    var max = numberValue(maximum);
    var from = selected(dateFrom);
    var to = selected(dateTo);
    var pendingVisible = 0;
    var decidedVisible = 0;

    rows.forEach(function (row) {
      var amount = Number(row.dataset.amount);
      var matches = (!query || row.dataset.search.indexOf(query) !== -1)
        && (!selectedClub || row.dataset.club === selectedClub)
        && (!selectedStatus || row.dataset.status === selectedStatus)
        && (!selectedType || row.dataset.type === selectedType)
        && (!reasonText || row.dataset.reason.indexOf(reasonText) !== -1)
        && (min === null || amount >= min)
        && (max === null || amount <= max)
        && (!from || row.dataset.date >= from)
        && (!to || row.dataset.date <= to);
      row.hidden = !matches;
      row.dataset.filterMatch = matches ? 'true' : 'false';
      if (matches && row.dataset.section === 'pending') pendingVisible += 1;
      if (matches && row.dataset.section === 'decided') decidedVisible += 1;
    });

    sortRows(rows.filter(function (row) { return row.dataset.section === 'pending'; }), pendingBody);
    sortRows(rows.filter(function (row) { return row.dataset.section === 'decided'; }), decidedBody);
    if (pendingCount) pendingCount.textContent = pendingVisible + (pendingVisible === 1 ? ' request' : ' requests');
    if (decidedCount) decidedCount.textContent = decidedVisible + (decidedVisible === 1 ? ' request' : ' requests');
    if (pendingEmpty) pendingEmpty.classList.toggle('is-visible', pendingVisible === 0);
    if (decidedEmpty) decidedEmpty.classList.toggle('is-visible', decidedVisible === 0);
    if (window.ynPager) window.ynPager.update();
  }

  search.addEventListener('input', applyFilters);
  document.querySelector('[data-approval-filter-apply]')?.addEventListener('click', function () { captureFilters(); applyFilters(); });
  document.querySelector('[data-approval-filter-reset]')?.addEventListener('click', function () {
    [search, club, status, type, reason, minimum, maximum, dateFrom, dateTo].forEach(function (control) {
      if (control) control.value = '';
    });
    if (sort) sort.value = 'newest';
    captureFilters();
    applyFilters();
  });

  function setText(selector, text) {
    var target = document.querySelector(selector);
    if (target) target.textContent = text || '';
  }

  var decisionForm = document.querySelector('[data-decision-form]');
  var decisionSelect = document.querySelector('[data-decision-select]');
  var remarks = document.querySelector('[data-decision-remarks]');
  var remarksRequired = document.querySelector('[data-remarks-required]');
  var evidence = {};
  var evidenceSource = document.getElementById('void-review-evidence');
  if (evidenceSource) {
    try {
      evidence = JSON.parse(evidenceSource.textContent || '{}');
    } catch (error) {
      evidence = {};
    }
  }

  function money(amount, signed) {
    var number = Number(amount || 0);
    var prefix = signed && number > 0 ? '+' : '';
    return prefix + 'Rs. ' + number.toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function displayDate(value) {
    if (!value) return 'Not recorded';
    var parsed = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(parsed.getTime())) return value;
    return String(value).length === 10 ? parsed.toLocaleDateString('en-LK') : parsed.toLocaleString('en-LK');
  }

  function appendCell(row, text, className) {
    var cell = document.createElement('td');
    cell.textContent = text;
    if (className) cell.className = className;
    row.appendChild(cell);
  }

  function renderEvidence(requestId) {
    var item = evidence[String(requestId)] || {};
    setText('[data-evidence-category]', item.category || 'Uncategorised');
    setText('[data-evidence-entry-date]', displayDate(item.entry_date));
    setText('[data-evidence-creator]', item.entry_creator || 'Not recorded');
    setText('[data-evidence-created-at]', displayDate(item.entry_created_at));
    setText('[data-evidence-reconciled]', item.reconciled ? 'Yes' : 'No');
    setText('[data-evidence-allocation]', item.allocation_linked ? 'Linked' : 'Not linked');
    setText('[data-evidence-receipt-status]', item.receipt_url ? 'Attached' : 'Not attached');
    setText('[data-current-balance]', money(item.current_balance));
    setText('[data-balance-change]', money(item.balance_change, true));
    setText('[data-projected-balance]', money(item.projected_balance));

    var receipt = document.querySelector('[data-review-receipt]');
    if (receipt) {
      receipt.hidden = !item.receipt_url;
      receipt.href = item.receipt_url || '#';
    }

    var warnings = Array.isArray(item.warnings) ? item.warnings : [];
    var warningSection = document.querySelector('[data-warning-section]');
    var warningList = document.querySelector('[data-review-warnings]');
    if (warningSection) warningSection.hidden = warnings.length === 0;
    if (warningList) {
      warningList.replaceChildren();
      warnings.forEach(function (warning) {
        var listItem = document.createElement('li');
        listItem.textContent = warning;
        warningList.appendChild(listItem);
      });
    }

    var nearby = Array.isArray(item.nearby_entries) ? item.nearby_entries : [];
    var nearbyBody = document.querySelector('[data-nearby-entries]');
    var nearbyEmpty = document.querySelector('[data-nearby-empty]');
    if (nearbyEmpty) nearbyEmpty.hidden = nearby.length > 0;
    if (nearbyBody) {
      nearbyBody.replaceChildren();
      nearby.forEach(function (entry) {
        var row = document.createElement('tr');
        appendCell(row, entry.reference, 'dw-table__reference');
        appendCell(row, displayDate(entry.date));
        appendCell(row, entry.description, 'dw-table__description');
        appendCell(row, money(entry.amount), 'dw-money');
        appendCell(row, entry.type);
        var check = 'No match';
        if (entry.same_receipt) check = 'Same receipt';
        else if (entry.possible_duplicate) check = 'Possible duplicate';
        appendCell(row, check);
        nearbyBody.appendChild(row);
      });
    }
  }

  function updateDecisionRequirements() {
    var remarksNeeded = decisionSelect && decisionSelect.value === 'reject';
    var submit = decisionForm && decisionForm.querySelector('[data-submit-decision]');
    if (submit) {
      submit.classList.toggle('yn-btn--approve', !remarksNeeded);
      submit.classList.toggle('yn-btn--reject', !!remarksNeeded);
      submit.textContent = remarksNeeded ? 'Reject Request' : 'Approve Request';
    }
    if (remarks) {
      remarks.required = remarksNeeded;
      remarks.minLength = remarksNeeded ? 5 : 0;
    }
    if (remarksRequired) remarksRequired.hidden = !remarksNeeded;
  }

  if (decisionSelect) decisionSelect.addEventListener('change', updateDecisionRequirements);

  document.querySelectorAll('[data-review-request]').forEach(function (button) {
    button.addEventListener('click', function () {
      if (!decisionForm) return;
      decisionForm.action = decisionForm.dataset.decisionBase + button.dataset.requestId;
      setText('[data-review-reference]', button.dataset.requestReference);
      setText('[data-review-club]', button.dataset.clubName);
      setText('[data-review-entry]', button.dataset.entryReference + ' - ' + button.dataset.amountLabel);
      setText('[data-review-description]', button.dataset.entryDescription);
      setText('[data-review-type]', button.dataset.typeLabel);
      setText('[data-review-reason]', button.dataset.reasonLabel);
      setText('[data-review-requester]', button.dataset.requester);
      setText('[data-review-date]', button.dataset.requestedAt);
      renderEvidence(button.dataset.requestId);
      if (decisionSelect) decisionSelect.value = 'approve';
      if (remarks) remarks.value = '';
      updateDecisionRequirements();
    });
  });

  if (decisionForm) {
    decisionForm.addEventListener('submit', function (event) {
      updateDecisionRequirements();
      if (!decisionForm.checkValidity()) {
        event.preventDefault();
        decisionForm.reportValidity();
        return;
      }
      var message = 'Approve this void request and update the club ledger balance?';
      if (decisionSelect && decisionSelect.value === 'reject') {
        message = 'Reject this void request?';
      }
      if (!window.confirm(message)) event.preventDefault();
    });
  }

  applyFilters();
})();
