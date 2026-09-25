(function () {
  'use strict';

  var form = document.querySelector('[data-report-create-form]');
  if (!form) return;

  var category = document.querySelector('[data-create-category]');
  var type = document.querySelector('[data-create-type]');
  var description = document.querySelector('[data-create-description]');
  var start = document.getElementById('report-start');
  var end = document.getElementById('report-end');
  var periodStatus = document.querySelector('[data-period-status]');
  var validIcon = document.querySelector('[data-period-valid-icon]');
  var invalidIcon = document.querySelector('[data-period-invalid-icon]');

  function updateDescription() {
    if (!type || !description) return;
    var selected = type.options[type.selectedIndex];
    description.textContent = selected && selected.dataset.description
      ? selected.dataset.description
      : 'Select a report type.';
  }

  function filterReportTypes() {
    if (!category || !type) return;
    var firstAvailable = null;
    var selectedAvailable = false;
    Array.prototype.forEach.call(type.options, function (option) {
      var available = option.dataset.category === category.value;
      option.hidden = !available;
      option.disabled = !available;
      if (available && firstAvailable === null) firstAvailable = option;
      if (available && option.selected) selectedAvailable = true;
    });
    if (!selectedAvailable && firstAvailable) firstAvailable.selected = true;
    updateDescription();
  }

  function parseDate(value) {
    var parts = value.split('-');
    if (parts.length !== 3) return null;
    var parsed = new Date(Date.UTC(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2])));
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  function formatDate(value) {
    return value.toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC'});
  }

  function updatePeriodStatus() {
    if (!start || !end || !periodStatus) return true;
    var startDate = parseDate(start.value);
    var endDate = parseDate(end.value);
    var valid = startDate && endDate && startDate.getTime() <= endDate.getTime();
    periodStatus.classList.toggle('is-invalid', !valid);
    if (validIcon) validIcon.hidden = !valid;
    if (invalidIcon) invalidIcon.hidden = valid;

    if (!valid) {
      periodStatus.querySelector('span:last-child').textContent = 'The to date must be on or after the from date.';
      end.setCustomValidity('The to date must be on or after the from date.');
      return false;
    }

    var dayCount = Math.floor((endDate.getTime() - startDate.getTime()) / 86400000) + 1;
    periodStatus.querySelector('span:last-child').textContent =
      'Valid reporting period: ' + formatDate(startDate) + ' to ' + formatDate(endDate) + ' (' + dayCount + ' days)';
    end.setCustomValidity('');
    return true;
  }

  if (category) category.addEventListener('change', filterReportTypes);
  if (type) type.addEventListener('change', updateDescription);
  if (start) start.addEventListener('change', updatePeriodStatus);
  if (end) end.addEventListener('change', updatePeriodStatus);

  form.addEventListener('submit', function (event) {
    if (!updatePeriodStatus()) {
      event.preventDefault();
      if (end) end.reportValidity();
    }
  });

  filterReportTypes();
  updatePeriodStatus();
}());
