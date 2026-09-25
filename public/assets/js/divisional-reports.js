(function () {
  'use strict';

  var rows = Array.prototype.slice.call(document.querySelectorAll('[data-report-row]'));
  var search = document.querySelector('[data-report-search]');
  var category = document.querySelector('[data-report-category]');
  var format = document.querySelector('[data-report-format]');
  var status = document.querySelector('[data-report-status]');
  var from = document.querySelector('[data-report-from]');
  var sort = document.querySelector('[data-report-sort]');
  var body = document.querySelector('[data-report-body]');
  var count = document.querySelector('[data-report-count]');
  var empty = document.querySelector('[data-report-empty]');
  var libraryTitle = document.querySelector('[data-report-library-title]');

  function applyFilters() {
    var term = search ? search.value.trim().toLowerCase() : '';
    var categoryValue = category ? category.value : '';
    var formatValue = format ? format.value : '';
    var statusValue = status ? status.value.trim().toLowerCase() : '';
    var fromValue = from ? from.value : '';
    var visible = rows.filter(function (row) {
      return (!term || row.dataset.search.indexOf(term) !== -1) &&
        (!categoryValue || row.dataset.category === categoryValue) &&
        (!formatValue || row.dataset.format === formatValue) &&
        (!statusValue || (row.dataset.status || 'active').trim().toLowerCase() === statusValue) &&
        (!fromValue || row.dataset.date >= fromValue);
    });
    rows.forEach(function (row) { row.hidden = visible.indexOf(row) === -1; });
    if (body && sort) {
      visible.sort(function (a, b) {
        if (sort.value === 'oldest') return a.dataset.date.localeCompare(b.dataset.date);
        if (sort.value === 'type') return a.dataset.type.localeCompare(b.dataset.type);
        return b.dataset.date.localeCompare(a.dataset.date);
      }).forEach(function (row) { body.appendChild(row); });
    }
    if (count) count.textContent = visible.length + (visible.length === 1 ? ' report' : ' reports');
    if (empty) empty.classList.toggle('is-visible', visible.length === 0);
    if (libraryTitle) {
      libraryTitle.textContent = statusValue === 'archived' ? 'Archived Reports'
        : (statusValue === 'active' ? 'Recent Reports' : 'All Reports');
    }
  }

  if (search) search.addEventListener('input', applyFilters);
  if (status) status.addEventListener('change', applyFilters);
  var apply = document.querySelector('[data-report-apply]');
  if (apply) apply.addEventListener('click', applyFilters);
  var reset = document.querySelector('[data-report-reset]');
  if (reset) reset.addEventListener('click', function () {
    [category, format, from].forEach(function (field) { if (field) field.value = ''; });
    if (status) status.value = 'active';
    if (sort) sort.value = 'newest';
    if (search) search.value = '';
    applyFilters();
  });

  applyFilters();
}());
