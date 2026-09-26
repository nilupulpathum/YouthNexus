(function () {
  'use strict';

  var search = document.querySelector('[data-void-search]');
  if (!search) return;

  var eligibleBody = document.querySelector('[data-eligible-body]');
  var requestBody = document.querySelector('[data-request-body]');
  var eligibleRows = Array.prototype.slice.call(document.querySelectorAll('[data-eligible-row]'));
  var requestRows = Array.prototype.slice.call(document.querySelectorAll('[data-request-row]'));
  var typeFilter = document.querySelector('[data-void-type-filter]');
  var statusFilter = document.querySelector('[data-void-status-filter]');
  var minimum = document.querySelector('[data-void-min-amount]');
  var maximum = document.querySelector('[data-void-max-amount]');
  var dateFrom = document.querySelector('[data-void-date-from]');
  var dateTo = document.querySelector('[data-void-date-to]');
  var sort = document.querySelector('[data-void-sort]');
  var eligibleCount = document.querySelector('[data-eligible-count]');
  var requestCount = document.querySelector('[data-request-count]');
  var eligibleEmpty = document.querySelector('[data-eligible-empty]');
  var requestEmpty = document.querySelector('[data-request-empty]');

  var panelControls = [typeFilter, statusFilter, minimum, maximum, dateFrom, dateTo, sort];
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

  function rowMatches(row, includeStatus) {
    var query = value(search);
    var selectedType = selectedValue(typeFilter);
    var selectedStatus = selectedValue(statusFilter);
    var min = numberValue(minimum);
    var max = numberValue(maximum);
    var from = selected(dateFrom);
    var to = selected(dateTo);
    var amount = Number(row.dataset.amount);

    return (!query || row.dataset.search.indexOf(query) !== -1)
      && (!selectedType || row.dataset.type === selectedType)
      && (!includeStatus || !selectedStatus || row.dataset.status === selectedStatus)
      && (min === null || amount >= min)
      && (max === null || amount <= max)
      && (!from || row.dataset.date >= from)
      && (!to || row.dataset.date <= to);
  }

  function selectedValue(control) { return selected(control).trim().toLowerCase(); }

  function sortRows(rows, body) {
    if (!body) return;
    var selected = selectedValue(sort) || 'newest';
    rows.sort(function (left, right) {
      if (selected === 'oldest') return left.dataset.date.localeCompare(right.dataset.date);
      if (selected === 'amount-high') return Number(right.dataset.amount) - Number(left.dataset.amount);
      if (selected === 'amount-low') return Number(left.dataset.amount) - Number(right.dataset.amount);
      return right.dataset.date.localeCompare(left.dataset.date);
    });
    rows.forEach(function (row) { body.appendChild(row); });
  }

  function applyFilters() {
    var eligibleVisible = 0;
    var requestVisible = 0;
    eligibleRows.forEach(function (row) {
      var matches = rowMatches(row, false);
      row.hidden = !matches;
      if (matches) eligibleVisible += 1;
    });
    requestRows.forEach(function (row) {
      var matches = rowMatches(row, true);
      row.hidden = !matches;
      if (matches) requestVisible += 1;
    });
    sortRows(eligibleRows, eligibleBody);
    sortRows(requestRows, requestBody);
    if (eligibleCount) eligibleCount.textContent = eligibleVisible + (eligibleVisible === 1 ? ' entry' : ' entries');
    if (requestCount) requestCount.textContent = requestVisible + (requestVisible === 1 ? ' request' : ' requests');
    if (eligibleEmpty) eligibleEmpty.classList.toggle('is-visible', eligibleVisible === 0);
    if (requestEmpty) requestEmpty.classList.toggle('is-visible', requestVisible === 0);
  }

  search.addEventListener('input', applyFilters);
  document.querySelector('[data-void-filter-apply]')?.addEventListener('click', function () { captureFilters(); applyFilters(); });
  document.querySelector('[data-void-filter-reset]')?.addEventListener('click', function () {
    [search, statusFilter, typeFilter, minimum, maximum, dateFrom, dateTo].forEach(function (control) {
      if (control) control.value = '';
    });
    if (sort) sort.value = 'newest';
    captureFilters();
    applyFilters();
  });

  var entrySelect = document.querySelector('[data-void-entry-select]');
  document.querySelectorAll('[data-request-entry]').forEach(function (button) {
    button.addEventListener('click', function () {
      if (entrySelect) entrySelect.value = button.dataset.requestEntry;
    });
  });

  function setText(selector, text) {
    var target = document.querySelector(selector);
    if (target) target.textContent = text || '';
  }

  document.querySelectorAll('[data-request-status]').forEach(function (button) {
    button.addEventListener('click', function () {
      var status = button.dataset.statusLabel;
      var pending = status === 'Pending';
      var canWithdraw = button.dataset.canWithdraw === '1';
      setText('[data-status-request-reference]', button.dataset.requestReference);
      setText('[data-status-entry]', button.dataset.entryReference + ' - ' + button.dataset.amountLabel);
      setText('[data-status-description]', button.dataset.entryDescription);
      setText('[data-status-reason]', button.dataset.reason);
      setText('[data-status-recipient]', button.dataset.recipient);
      setText('[data-status-state]', status);
      setText('[data-status-submitted-date]', button.dataset.requestedAt);
      setText('[data-status-review-copy]', pending ? 'Awaiting response from ' + button.dataset.recipient : 'Review completed');
      setText('[data-status-decision-copy]', pending ? 'Pending' : status + (button.dataset.decidedAt ? ' on ' + button.dataset.decidedAt : ''));

      var reviewStep = document.querySelector('[data-status-review-step]');
      var decisionStep = document.querySelector('[data-status-decision-step]');
      if (reviewStep) reviewStep.classList.toggle('is-complete', !pending);
      if (decisionStep) decisionStep.classList.toggle('is-complete', !pending);

      var remarksRow = document.querySelector('[data-status-remarks-row]');
      if (remarksRow) remarksRow.hidden = !button.dataset.remarks;
      setText('[data-status-remarks]', button.dataset.remarks);

      var withdrawForm = document.querySelector('[data-withdraw-form]');
      if (withdrawForm) {
        withdrawForm.hidden = !pending || !canWithdraw;
        withdrawForm.action = withdrawForm.dataset.withdrawBase + button.dataset.requestId;
      }
    });
  });

  document.querySelectorAll('[data-confirm]').forEach(function (button) {
    button.addEventListener('click', function (event) {
      if (!window.confirm(button.dataset.confirm)) event.preventDefault();
    });
  });

  applyFilters();
})();
