(function () {
  'use strict';

  var tableBody = document.querySelector('[data-ledger-rows]');
  if (!tableBody) return;

  var rows = Array.prototype.slice.call(tableBody.querySelectorAll('[data-ledger-row]'));
  var search = document.querySelector('[data-ledger-search]');
  var quickType = document.querySelector('[data-ledger-quick-type]');
  var status = document.querySelector('[data-filter-status]');
  var reconciliation = document.querySelector('[data-filter-reconciliation]');
  var category = document.querySelector('[data-filter-category]');
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
    var categoryValue = valueOf(category);
    var receiptValue = valueOf(receipt);
    var fromValue = dateFrom ? dateFrom.value : '';
    var toValue = dateTo ? dateTo.value : '';
    var visible = 0;

    rows.forEach(function (row) {
      var matches = (!query || row.dataset.search.indexOf(query) !== -1)
        && (!typeValue || row.dataset.type === typeValue)
        && (!statusValue || row.dataset.status === statusValue)
        && (!reconciliationValue || row.dataset.reconciled === reconciliationValue)
        && (!categoryValue || row.dataset.category === categoryValue)
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
    [search, quickType, status, reconciliation, category, receipt, dateFrom, dateTo].forEach(function (control) {
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

  var editForm = document.querySelector('[data-edit-transaction-form]');
  document.querySelectorAll('[data-edit-transaction]').forEach(function (button) {
    button.addEventListener('click', function () {
      if (!editForm) return;
      var row = button.closest('[data-ledger-row]');
      editForm.action = editForm.dataset.updateBase + row.dataset.entryId;
      editForm.elements.type.value = row.dataset.typeLabel;
      editForm.elements.amount.value = row.dataset.amount;
      editForm.elements.category.value = row.dataset.categoryLabel;
      editForm.elements.date.value = row.dataset.date;
      editForm.elements.description.value = row.dataset.description;
      editForm.elements.receipt.value = '';
      if (editForm.elements.remove_receipt) {
        editForm.elements.remove_receipt.checked = false;
      }
      var fileLabel = document.querySelector('[data-file-name-for="edit-transaction-receipt"]');
      if (fileLabel) fileLabel.textContent = 'Choose a PDF, JPG, or PNG receipt';
      var removeRow = editForm.querySelector('[data-remove-receipt-row]');
      if (removeRow) removeRow.hidden = row.dataset.receipt !== 'attached';
    });
  });

  document.querySelectorAll('[data-transaction-form], [data-edit-transaction-form], [data-ledger-create-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      var amount = Number(form.elements.amount.value);
      if (!Number.isFinite(amount) || amount <= 0) {
        event.preventDefault();
        form.elements.amount.focus();
      }
    });
  });

  applyFilters();
})();
