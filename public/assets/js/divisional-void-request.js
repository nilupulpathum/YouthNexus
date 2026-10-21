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

  function value(control) {
    return control ? String(control.value || '').trim().toLowerCase() : '';
  }

  function numberValue(control) {
    if (!control || control.value === '') return null;
    var parsed = Number(control.value);
    return Number.isFinite(parsed) ? parsed : null;
  }

  function rowMatches(row, includeStatus) {
    var query = value(search);
    var selectedType = value(typeFilter);
    var selectedStatus = value(statusFilter);
    var min = numberValue(minimum);
    var max = numberValue(maximum);
    var from = dateFrom ? dateFrom.value : '';
    var to = dateTo ? dateTo.value : '';
    var amount = Number(row.dataset.amount);

    return (!query || row.dataset.search.indexOf(query) !== -1)
      && (!selectedType || row.dataset.type === selectedType)
      && (!includeStatus || !selectedStatus || row.dataset.status === selectedStatus)
      && (min === null || amount >= min)
      && (max === null || amount <= max)
      && (!from || row.dataset.date >= from)
      && (!to || row.dataset.date <= to);
  }

  function sortRows(rows, body) {
    if (!body) return;
    var selected = value(sort) || 'newest';
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
      row.dataset.filterMatch = matches ? 'true' : 'false';
      row.hidden = !matches;
      if (matches) eligibleVisible += 1;
    });
    requestRows.forEach(function (row) {
      var matches = rowMatches(row, true);
      row.dataset.filterMatch = matches ? 'true' : 'false';
      row.hidden = !matches;
      if (matches) requestVisible += 1;
    });

    sortRows(eligibleRows, eligibleBody);
    sortRows(requestRows, requestBody);

    if (eligibleCount) eligibleCount.textContent = eligibleVisible + (eligibleVisible === 1 ? ' entry' : ' entries');
    if (requestCount) requestCount.textContent = requestVisible + (requestVisible === 1 ? ' request' : ' requests');
    if (eligibleEmpty) eligibleEmpty.classList.toggle('is-visible', eligibleVisible === 0);
    if (requestEmpty) requestEmpty.classList.toggle('is-visible', requestVisible === 0);

    var eligibleBadge = document.querySelector('[data-tab-count="eligible"]');
    if (eligibleBadge) eligibleBadge.textContent = String(eligibleVisible);

    var requestBadge = document.querySelector('[data-tab-count="requests"]');
    if (requestBadge) requestBadge.textContent = String(requestVisible);

    if (window.ynPager && typeof window.ynPager.update === 'function') {
      window.ynPager.update();
    }

    var eligiblePager = document.querySelector('[data-yn-pagination][data-yn-page-target="void-eligible-tbody"]');
    if (eligiblePager && eligibleVisible === 0) {
      eligiblePager.hidden = true;
    }

    var requestPager = document.querySelector('[data-yn-pagination][data-yn-page-target="void-request-tbody"]');
    if (requestPager && requestVisible === 0) {
      requestPager.hidden = true;
    }

    document.dispatchEvent(new CustomEvent('divisional:filtered'));
  }

  search.addEventListener('input', applyFilters);
  var applyBtn = document.querySelector('[data-void-filter-apply]');
  if (applyBtn) applyBtn.addEventListener('click', applyFilters);

  var resetBtn = document.querySelector('[data-void-filter-reset]');
  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      [search, statusFilter, typeFilter, minimum, maximum, dateFrom, dateTo].forEach(function (control) {
        if (control) control.value = '';
      });
      if (sort) sort.value = 'newest';
      applyFilters();
    });
  }

  var entrySelect = document.querySelector('[data-void-entry-select]');

  function setText(selector, text) {
    var target = document.querySelector(selector);
    if (target) target.textContent = text || '';
  }

  function populateStatusModal(button) {
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
  }

  document.addEventListener('click', function (event) {
    var requestButton = event.target.closest('[data-request-entry]');
    if (requestButton) {
      if (entrySelect) entrySelect.value = requestButton.dataset.requestEntry;
      return;
    }

    var statusButton = event.target.closest('[data-request-status]');
    if (statusButton) {
      populateStatusModal(statusButton);
      return;
    }

    var confirmButton = event.target.closest('[data-confirm]');
    if (confirmButton) {
      if (!window.confirm(confirmButton.dataset.confirm)) event.preventDefault();
      return;
    }
  });

  applyFilters();
})();
