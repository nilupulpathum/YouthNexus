(function () {
  'use strict';

  var cards = Array.prototype.slice.call(document.querySelectorAll('[data-audit-card]'));
  var search = document.querySelector('[data-audit-search]');
  var typeFilter = document.querySelector('[data-audit-type-filter]');
  var statusFilter = document.querySelector('[data-audit-status-filter]');
  var flagFilter = document.querySelector('[data-audit-flag-filter]');
  var sortFilter = document.querySelector('[data-audit-sort]');

  function value(element) {
    return element ? element.value.trim().toLowerCase() : '';
  }

  var panelControls = [typeFilter, statusFilter, flagFilter, sortFilter];
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

  function applyQueueFilters() {
    var term = value(search);
    cards.forEach(function (card) {
      card.hidden = !((!term || card.dataset.search.indexOf(term) !== -1)
        && (!selected(typeFilter) || card.dataset.type === selected(typeFilter).trim().toLowerCase())
        && (!selected(statusFilter) || card.dataset.status === selected(statusFilter).trim().toLowerCase())
        && (!selected(flagFilter) || card.dataset.flags === selected(flagFilter).trim().toLowerCase()));
    });

    var grid = document.querySelector('[data-audit-grid]');
    if (grid) {
      cards.sort(function (a, b) {
        if (selected(sortFilter) === 'recent') return (b.dataset.date || '').localeCompare(a.dataset.date || '');
        if (selected(sortFilter) === 'oldest') return (a.dataset.date || '').localeCompare(b.dataset.date || '');
        return a.dataset.search.localeCompare(b.dataset.search);
      });
      cards.forEach(function (card) { grid.appendChild(card); });
    }

    var visible = cards.filter(function (card) { return !card.hidden; }).length;
    var count = document.querySelector('[data-audit-count]');
    var empty = document.querySelector('.dw-page > [data-empty-state]');
    if (count) count.textContent = visible + (visible === 1 ? ' club' : ' clubs');
    if (empty) empty.classList.toggle('is-visible', visible === 0);
  }

  if (search) search.addEventListener('input', applyQueueFilters);
  var applyButton = document.querySelector('[data-audit-filter-apply]');
  if (applyButton) applyButton.addEventListener('click', function () { captureFilters(); applyQueueFilters(); });
  var resetButton = document.querySelector('[data-audit-filter-reset]');
  if (resetButton) {
    resetButton.addEventListener('click', function () {
      [search, typeFilter, statusFilter, flagFilter].forEach(function (field) { if (field) field.value = ''; });
      if (sortFilter) sortFilter.value = 'club';
      captureFilters();
      applyQueueFilters();
    });
  }

  document.querySelectorAll('[data-start-club]').forEach(function (button) {
    button.addEventListener('click', function () {
      var club = document.getElementById('audit-club');
      if (club) club.value = button.dataset.startClub;
    });
  });

  var auditType = document.getElementById('audit-type');
  var periodStart = document.getElementById('audit-period-start');
  var periodEnd = document.getElementById('audit-period-end');
  function updatePeriodStart() {
    if (!auditType || !periodStart || !periodEnd || !periodEnd.value) return;
    var end = new Date(periodEnd.value + 'T00:00:00');
    var days = auditType.value === 'Weekly' ? 6 : (auditType.value === 'BiWeekly' ? 13 : null);
    if (days === null) {
      end.setDate(1);
    } else {
      end.setDate(end.getDate() - days);
    }
    var year = end.getFullYear();
    var month = String(end.getMonth() + 1).padStart(2, '0');
    var day = String(end.getDate()).padStart(2, '0');
    periodStart.value = year + '-' + month + '-' + day;
  }
  if (auditType) auditType.addEventListener('change', updatePeriodStart);
  if (periodEnd) periodEnd.addEventListener('change', updatePeriodStart);

  var entryTabs = document.querySelectorAll('[data-audit-entry-tab]');
  var entries = Array.prototype.slice.call(document.querySelectorAll('[data-audit-entry]'));
  var ledgerPanel = document.querySelector('[data-audit-ledger-panel]');
  var findingsPanel = document.querySelector('[data-audit-findings-panel]');
  entryTabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      entryTabs.forEach(function (item) { item.classList.remove('is-active'); });
      tab.classList.add('is-active');
      var selected = tab.dataset.auditEntryTab;
      if (ledgerPanel) ledgerPanel.hidden = selected === 'flags';
      if (findingsPanel) findingsPanel.hidden = selected !== 'flags';
      var visible = 0;
      entries.forEach(function (row) {
        row.hidden = selected !== 'all' && selected !== 'flags' && row.dataset.entryType !== selected;
        if (!row.hidden) visible += 1;
      });
      var count = document.querySelector('[data-audit-entry-count]');
      if (count && selected !== 'flags') count.textContent = visible + (visible === 1 ? ' entry' : ' entries');
    });
  });

  document.querySelectorAll('[data-request-receipt]').forEach(function (button) {
    button.addEventListener('click', function () {
      var reason = document.getElementById('audit-note-reason');
      var notes = document.getElementById('audit-note-text');
      if (reason) reason.value = 'Missing Receipt';
      if (notes) notes.value = 'Please attach the receipt for: ' + button.dataset.entryDescription;
    });
  });

  document.querySelectorAll('[data-start-audit-form], [data-audit-note-form]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var submit = form.querySelector('[type="submit"]');
      if (submit) submit.disabled = true;
    });
  });

  applyQueueFilters();
})();
