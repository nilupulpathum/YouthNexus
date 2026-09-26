(function () {
  'use strict';

  var rows = Array.prototype.slice.call(document.querySelectorAll('[data-asset-row]'));
  var search = document.querySelector('[data-asset-search]');
  var category = document.querySelector('[data-asset-category]');
  var status = document.querySelector('[data-asset-status]');
  var quantity = document.querySelector('[data-asset-quantity]');
  var sort = document.querySelector('[data-asset-sort]');

  var panelControls = [category, status, quantity, sort];
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

  function applyFilters() {
    var query = search ? search.value.trim().toLowerCase() : '';
    var categoryValue = selected(category);
    var statusValue = selected(status);
    var minimum = selected(quantity) !== '' ? Number(selected(quantity)) : null;
    rows.forEach(function (row) {
      var visible = (!query || (row.dataset.search || '').indexOf(query) !== -1)
        && (!categoryValue || row.dataset.category === categoryValue)
        && (!statusValue || row.dataset.status === statusValue)
        && (minimum === null || Number(row.dataset.quantity || 0) >= minimum);
      row.hidden = !visible;
      row.dataset.filterMatch = visible ? 'true' : 'false';
    });
    ['club-request', 'inventory', 'transfer-history', 'zonal-request'].forEach(updateSection);
    if (window.ynPager) window.ynPager.update();
  }

  function updateSection(section) {
    var sectionRows = rows.filter(function (row) { return row.dataset.section === section; });
    var visible = sectionRows.filter(function (row) { return !row.hidden; }).length;
    var map = {
      'club-request': ['[data-club-request-count]', '[data-club-request-empty]'],
      inventory: ['[data-inventory-count]', '[data-inventory-empty]'],
      'transfer-history': ['[data-transfer-count]', '[data-transfer-empty]'],
      'zonal-request': ['[data-zonal-count]', '[data-zonal-empty]']
    };
    var count = document.querySelector(map[section][0]);
    var empty = document.querySelector(map[section][1]);
    if (count) {
      var label = section === 'inventory' ? ' items' : (section === 'transfer-history' ? ' transfers' : ' requests');
      count.textContent = visible + label;
    }
    if (empty) empty.classList.toggle('is-visible', visible === 0);
  }

  function sortRows() {
    var mode = selected(sort) || 'name';
    ['club-request', 'inventory', 'transfer-history', 'zonal-request'].forEach(function (section) {
      var sectionRows = rows.filter(function (row) { return row.dataset.section === section; });
      if (!sectionRows.length) return;
      var body = sectionRows[0].parentElement;
      sectionRows.sort(function (a, b) {
        if (mode === 'quantity-high') return Number(b.dataset.quantity) - Number(a.dataset.quantity);
        if (mode === 'quantity-low') return Number(a.dataset.quantity) - Number(b.dataset.quantity);
        if (mode === 'newest') return (b.dataset.date || '').localeCompare(a.dataset.date || '');
        return (a.dataset.search || '').localeCompare(b.dataset.search || '');
      }).forEach(function (row) { body.appendChild(row); });
    });
  }

  if (search) search.addEventListener('input', applyFilters);
  var apply = document.querySelector('[data-asset-apply]');
  if (apply) apply.addEventListener('click', function () { captureFilters(); sortRows(); applyFilters(); });
  var reset = document.querySelector('[data-asset-reset]');
  if (reset) reset.addEventListener('click', function () {
    if (category) category.value = '';
    if (status) status.value = '';
    if (quantity) quantity.value = '';
    if (sort) sort.value = 'name';
    captureFilters();
    sortRows();
    applyFilters();
  });

  document.querySelectorAll('[data-adjust-asset]').forEach(function (button) {
    button.addEventListener('click', function () {
      document.querySelector('[data-adjust-id]').value = button.dataset.id;
      document.querySelector('[data-adjust-item]').textContent = button.dataset.item;
      document.querySelector('[data-adjust-quantity]').value = button.dataset.quantity;
    });
  });

  document.querySelectorAll('[data-transfer-asset]').forEach(function (button) {
    button.addEventListener('click', function () {
      document.querySelector('[data-transfer-id]').value = button.dataset.id;
      document.querySelector('[data-transfer-item]').textContent = button.dataset.item;
      document.querySelector('[data-transfer-available]').textContent = button.dataset.available + ' units available';
      var input = document.querySelector('[data-transfer-quantity]');
      input.value = '';
      input.max = button.dataset.available;
    });
  });

  document.querySelectorAll('[data-retire-asset]').forEach(function (button) {
    button.addEventListener('click', function () {
      document.querySelector('[data-retire-id]').value = button.dataset.id;
      document.querySelector('[data-retire-item]').textContent = button.dataset.item;
      document.querySelector('[data-retire-available]').textContent = button.dataset.available + ' units available';
      var input = document.querySelector('[data-retire-quantity]');
      input.value = '';
      input.max = button.dataset.available;
    });
  });

  var withdrawForm = document.querySelector('[data-withdraw-asset-form]');
  document.querySelectorAll('[data-withdraw-asset-request]').forEach(function (button) {
    button.addEventListener('click', function () {
      withdrawForm.action = withdrawForm.dataset.actionBase + button.dataset.id;
      document.querySelector('[data-withdraw-asset-ref]').textContent = button.dataset.ref;
    });
  });

  var reviewForm = document.querySelector('[data-review-form]');
  document.querySelectorAll('[data-review-request]').forEach(function (button) {
    button.addEventListener('click', function () {
      reviewForm.action = reviewForm.dataset.decisionBase + button.dataset.requestId;
      document.querySelector('[data-review-ref]').textContent = button.dataset.requestRef;
      document.querySelector('[data-review-club]').textContent = button.dataset.club;
      document.querySelector('[data-review-item]').textContent = button.dataset.item;
      document.querySelector('[data-review-quantity]').textContent = button.dataset.quantityLabel;
      document.querySelector('[data-review-available]').textContent = button.dataset.availableLabel;
      document.querySelector('[data-review-reason]').textContent = button.dataset.reason;
      document.querySelector('[data-review-requester]').textContent = button.dataset.requester || 'Club representative';
      document.querySelector('[data-review-date]').textContent = button.dataset.dateLabel;
    });
  });

  var decision = document.querySelector('[data-decision]');
  var remarks = document.querySelector('[data-remarks]');
  var required = document.querySelector('[data-remarks-required]');
  function updateDecision() {
    var rejecting = decision && decision.value === 'reject';
    if (remarks) remarks.required = rejecting;
    if (required) required.hidden = !rejecting;
    var submit = document.querySelector('[data-asset-submit-decision]');
    if (submit) {
      submit.classList.toggle('yn-btn--approve', !rejecting);
      submit.classList.toggle('yn-btn--reject', !!rejecting);
      submit.textContent = rejecting ? 'Reject Request' : 'Approve Request';
    }
  }
  if (decision) decision.addEventListener('change', updateDecision);
  updateDecision();
})();
