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

  var type = document.getElementById('generate-report-type');
  var description = document.querySelector('[data-report-description]');
  if (type && description) type.addEventListener('change', function () {
    var selected = type.options[type.selectedIndex];
    description.textContent = selected && selected.dataset.description
      ? selected.dataset.description : 'Select a report to see what it contains.';
  });

  var form = document.querySelector('[data-report-form]');
  if (form) form.addEventListener('submit', function (event) {
    var start = document.getElementById('report-start');
    var end = document.getElementById('report-end');
    var output = document.getElementById('generate-format');
    if (start && end && start.value > end.value) {
      event.preventDefault();
      end.setCustomValidity('The period end must be on or after the period start.');
      end.reportValidity();
      return;
    }
    if (end) end.setCustomValidity('');
    if (!output || output.value === 'OnScreen') return;

    event.preventDefault();
    var submit = form.querySelector('[type="submit"]');
    if (submit) submit.disabled = true;

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      credentials: 'same-origin',
      redirect: 'follow'
    }).then(function (response) {
      var contentType = response.headers.get('Content-Type') || '';
      if (!response.ok || (contentType.indexOf('application/pdf') === -1 && contentType.indexOf('text/csv') === -1)) {
        throw new Error('The report download could not be prepared.');
      }
      var disposition = response.headers.get('Content-Disposition') || '';
      var filenameMatch = disposition.match(/filename="?([^";]+)"?/i);
      var fallback = output.value === 'PDF' ? 'division-report.pdf' : 'division-report.csv';
      return response.blob().then(function (blob) {
        return {blob: blob, filename: filenameMatch ? filenameMatch[1] : fallback};
      });
    }).then(function (download) {
      var url = URL.createObjectURL(download.blob);
      var link = document.createElement('a');
      link.href = url;
      link.download = download.filename;
      link.hidden = true;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);

      var close = form.querySelector('[data-modal-close]');
      if (close) close.click();
      window.setTimeout(function () { window.location.reload(); }, 250);
    }).catch(function () {
      window.location.reload();
    });
  });

  applyFilters();
}());
