(function () {
  'use strict';

  var tableBody = document.querySelector('[data-ledger-rows]');
  if (!tableBody) return;

  var rows = Array.prototype.slice.call(tableBody.querySelectorAll('[data-ledger-row]'));
  var search = document.querySelector('[data-ledger-search]');
  var quickType = document.querySelector('[data-ledger-quick-type]');
  var status = document.querySelector('[data-filter-status]');
  var reconciliation = document.querySelector('[data-filter-reconciliation]');
  var receipt = document.querySelector('[data-filter-receipt]');
  var dateFrom = document.querySelector('[data-filter-date-from]');
  var dateTo = document.querySelector('[data-filter-date-to]');
  var count = document.querySelector('[data-entry-count]');
  var empty = document.querySelector('[data-empty-state]');

  function valueOf(element) {
    return element ? String(element.value || '').toLowerCase().trim() : '';
  }

  function applyFilters() {
    var query = valueOf(search);
    var typeValue = valueOf(quickType);
    var statusValue = valueOf(status);
    var reconciliationValue = valueOf(reconciliation);
    var receiptValue = valueOf(receipt);
    var fromValue = dateFrom ? dateFrom.value : '';
    var toValue = dateTo ? dateTo.value : '';
    var visible = 0;

    rows.forEach(function (row) {
      var matches = (!query || row.dataset.search.indexOf(query) !== -1)
        && (!typeValue || row.dataset.type === typeValue)
        && (!statusValue || row.dataset.status === statusValue)
        && (!reconciliationValue || row.dataset.reconciled === reconciliationValue)
        && (!receiptValue || row.dataset.receipt === receiptValue)
        && (!fromValue || row.dataset.date >= fromValue)
        && (!toValue || row.dataset.date <= toValue);
      row.hidden = !matches;
      if (matches) visible += 1;
    });

    if (count) count.textContent = visible + (visible === 1 ? ' entry' : ' entries');
    if (empty) empty.classList.toggle('is-visible', visible === 0);
  }

  [search, quickType].forEach(function (control) {
    if (control) control.addEventListener(control.tagName === 'INPUT' ? 'input' : 'change', applyFilters);
  });
  document.querySelector('[data-filter-apply]')?.addEventListener('click', applyFilters);
  document.querySelector('[data-filter-reset]')?.addEventListener('click', function () {
    [search, quickType, status, reconciliation, receipt, dateFrom, dateTo].forEach(function (control) {
      if (control) control.value = '';
    });
    applyFilters();
  });

  document.querySelectorAll('[data-reconcile-entry]').forEach(function (button) {
    button.addEventListener('click', function () {
      var nextValue = button.dataset.reconciled === '1' ? '0' : '1';
      var body = new URLSearchParams({
        csrf_token: button.dataset.csrf,
        reconciled: nextValue
      });
      button.disabled = true;
      fetch(button.dataset.endpoint, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json'},
        body: body.toString()
      }).then(function (response) {
        if (!response.ok) throw new Error('Request failed');
        return response.json();
      }).then(function (result) {
        if (!result.success) throw new Error('Update failed');
        window.location.reload();
      }).catch(function () {
        button.disabled = false;
        window.alert('The reconciliation status could not be updated. Please try again.');
      });
    });
  });

  var createForm = document.querySelector('[data-ledger-create-form]');
  if (createForm) {
    createForm.addEventListener('submit', function (event) {
      var amount = Number(createForm.elements.amount.value);
      if (!Number.isFinite(amount) || amount <= 0) {
        event.preventDefault();
        createForm.elements.amount.focus();
      }
    });
  }

  applyFilters();
})();
