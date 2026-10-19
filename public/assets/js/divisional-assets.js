(function () {
  'use strict';

  var rows = Array.prototype.slice.call(document.querySelectorAll('[data-asset-row]'));
  var search = document.querySelector('[data-asset-search]');
  var category = document.querySelector('[data-asset-category]');
  var status = document.querySelector('[data-asset-status]');
  var quantity = document.querySelector('[data-asset-quantity]');
  var sort = document.querySelector('[data-asset-sort]');

  var TAB_MAP = {
    'club-request': 'pending',
    inventory: 'inventory',
    'transfer-history': 'transfers',
    'zonal-request': 'zonal'
  };

  var SECTION_MAP = {
    'club-request': ['[data-club-request-count]', '[data-club-request-empty]', 'club-request-tbody'],
    inventory: ['[data-inventory-count]', '[data-inventory-empty]', 'inventory-tbody'],
    'transfer-history': ['[data-transfer-count]', '[data-transfer-empty]', 'transfer-tbody'],
    'zonal-request': ['[data-zonal-count]', '[data-zonal-empty]', 'zonal-tbody']
  };

  function applyFilters() {
    var query = search ? search.value.trim().toLowerCase() : '';
    var categoryValue = category ? category.value : '';
    var statusValue = status ? status.value : '';
    var minimum = quantity && quantity.value !== '' ? Number(quantity.value) : null;
    rows.forEach(function (row) {
      var visible = (!query || (row.dataset.search || '').indexOf(query) !== -1)
        && (!categoryValue || row.dataset.category === categoryValue)
        && (!statusValue || row.dataset.status === statusValue)
        && (minimum === null || Number(row.dataset.quantity || 0) >= minimum);
      row.dataset.filterMatch = visible ? 'true' : 'false';
      row.hidden = !visible;
    });

    sortRows();

    ['club-request', 'inventory', 'transfer-history', 'zonal-request'].forEach(updateSection);

    if (window.ynPager && typeof window.ynPager.update === 'function') {
      window.ynPager.update();
    }

    ['club-request', 'inventory', 'transfer-history', 'zonal-request'].forEach(function (section) {
      var sectionRows = rows.filter(function (row) { return row.dataset.section === section; });
      var matches = sectionRows.filter(function (row) { return row.dataset.filterMatch !== 'false'; });
      var pagerNav = document.querySelector('[data-yn-pagination][data-yn-page-target="' + SECTION_MAP[section][2] + '"]');
      if (pagerNav && matches.length === 0) {
        pagerNav.hidden = true;
      }
    });

    document.dispatchEvent(new CustomEvent('divisional:filtered'));
  }

  function updateSection(section) {
    var sectionRows = rows.filter(function (row) { return row.dataset.section === section; });
    var visible = sectionRows.filter(function (row) { return row.dataset.filterMatch !== 'false'; }).length;
    var count = document.querySelector(SECTION_MAP[section][0]);
    var empty = document.querySelector(SECTION_MAP[section][1]);
    if (count) {
      var label = section === 'inventory' ? ' items' : (section === 'transfer-history' ? ' transfers' : ' requests');
      count.textContent = visible + label;
    }
    if (empty) empty.classList.toggle('is-visible', visible === 0);

    var tabKey = TAB_MAP[section];
    if (tabKey) {
      var tabBadge = document.querySelector('[data-tab-count="' + tabKey + '"]');
      if (tabBadge) {
        tabBadge.textContent = String(visible);
        if (tabKey === 'pending') {
          tabBadge.classList.toggle('dv-tab__badge--attention', visible > 0);
        }
      }
    }
  }

  function sortRows() {
    var mode = sort ? sort.value : 'name';
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
  if (apply) apply.addEventListener('click', applyFilters);
  var reset = document.querySelector('[data-asset-reset]');
  if (reset) reset.addEventListener('click', function () {
    if (category) category.value = '';
    if (status) status.value = '';
    if (quantity) quantity.value = '';
    if (sort) sort.value = 'name';
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
  }
  if (decision) decision.addEventListener('change', updateDecision);
  updateDecision();
  applyFilters();
})();
