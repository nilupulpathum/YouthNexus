(function () {
  'use strict';

  var search = document.querySelector('[data-allocation-search]');
  var rows = Array.prototype.slice.call(document.querySelectorAll('[data-allocation-row]'));
  var filters = {
    club: document.querySelector('[data-allocation-club-filter]'),
    category: document.querySelector('[data-allocation-category-filter]'),
    status: document.querySelector('[data-allocation-status-filter]'),
    sort: document.querySelector('[data-allocation-sort]'),
    min: document.querySelector('[data-allocation-min-amount]'),
    max: document.querySelector('[data-allocation-max-amount]'),
    from: document.querySelector('[data-allocation-date-from]'),
    to: document.querySelector('[data-allocation-date-to]')
  };

  function value(element) {
    return element ? element.value.trim().toLowerCase() : '';
  }

  function applyFilters() {
    var term = value(search);
    var min = filters.min && filters.min.value !== '' ? Number(filters.min.value) : null;
    var max = filters.max && filters.max.value !== '' ? Number(filters.max.value) : null;

    rows.forEach(function (row) {
      var amount = Number(row.dataset.amount || 0);
      var visible = (!term || row.dataset.search.indexOf(term) !== -1)
        && (!value(filters.club) || row.dataset.club === value(filters.club))
        && (!value(filters.category) || row.dataset.category === value(filters.category))
        && (!value(filters.status) || row.dataset.status === value(filters.status))
        && (min === null || amount >= min)
        && (max === null || amount <= max)
        && (!value(filters.from) || row.dataset.date >= value(filters.from))
        && (!value(filters.to) || row.dataset.date <= value(filters.to));
      row.hidden = !visible;
    });

    sortRows();
    updateSections();
  }

  function sortRows() {
    var mode = value(filters.sort) || 'newest';
    ['pending', 'history'].forEach(function (section) {
      var body = document.querySelector('[data-allocation-rows="' + section + '"]');
      if (!body) return;
      var sectionRows = rows.filter(function (row) { return row.dataset.section === section; });
      sectionRows.sort(function (a, b) {
        if (mode === 'oldest') return a.dataset.date.localeCompare(b.dataset.date);
        if (mode === 'amount-high') return Number(b.dataset.amount) - Number(a.dataset.amount);
        if (mode === 'amount-low') return Number(a.dataset.amount) - Number(b.dataset.amount);
        return b.dataset.date.localeCompare(a.dataset.date);
      });
      sectionRows.forEach(function (row) { body.appendChild(row); });
    });
  }

  function updateSections() {
    ['pending', 'history'].forEach(function (name) {
      var section = document.querySelector('[data-allocation-section="' + name + '"]');
      if (!section) return;
      var visible = rows.filter(function (row) {
        return row.dataset.section === name && !row.hidden;
      }).length;
      var count = section.querySelector('[data-allocation-count]');
      var empty = section.querySelector('[data-empty-state]');
      if (count) count.textContent = visible + (name === 'pending' ? ' pending' : ' records');
      if (empty) empty.classList.toggle('is-visible', visible === 0);
    });
  }

  if (search) search.addEventListener('input', applyFilters);
  var applyButton = document.querySelector('[data-allocation-filter-apply]');
  if (applyButton) applyButton.addEventListener('click', applyFilters);

  var resetButton = document.querySelector('[data-allocation-filter-reset]');
  if (resetButton) {
    resetButton.addEventListener('click', function () {
      Object.keys(filters).forEach(function (key) {
        if (filters[key]) filters[key].value = key === 'sort' ? 'newest' : '';
      });
      if (search) search.value = '';
      applyFilters();
    });
  }

  document.querySelectorAll('[data-review-allocation]').forEach(function (button) {
    button.addEventListener('click', function () {
      var form = document.querySelector('[data-review-allocation-form]');
      if (!form) return;
      form.action = button.dataset.action;
      form.querySelector('[data-review-club]').textContent = button.dataset.clubName;
      form.querySelector('[data-review-reference]').textContent = button.dataset.reference;
      form.querySelector('[data-review-amount]').textContent = button.dataset.amountLabel;
      form.querySelector('[data-review-category]').textContent = button.dataset.categoryLabel;
      form.querySelector('[data-review-requested]').textContent = button.dataset.requestedLabel;
      form.querySelector('[data-review-purpose]').textContent = button.dataset.purpose;
    });
  });

  document.querySelectorAll('[data-reject-allocation-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm('Reject this fund request?')) event.preventDefault();
    });
  });

  document.querySelectorAll('[data-review-allocation-form]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var submit = form.querySelector('[type="submit"]');
      if (submit) submit.disabled = true;
    });
  });

  var allocationForm = document.querySelector('[data-allocation-form]');
  var method = document.querySelector('[data-allocation-method]');
  var reference = document.querySelector('[data-allocation-reference]');
  var amountInput = document.getElementById('allocation-amount');
  var amountWords = document.querySelector('[data-allocation-amount-words]');
  var allocationError = document.querySelector('[data-allocation-error]');

  function numberToWords(number) {
    if (!Number.isFinite(number) || number <= 0) return '';
    var units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    var tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    function convert(value) {
      if (value < 20) return units[value];
      if (value < 100) return tens[Math.floor(value / 10)] + (value % 10 ? ' ' + units[value % 10] : '');
      if (value < 1000) return units[Math.floor(value / 100)] + ' Hundred' + (value % 100 ? ' ' + convert(value % 100) : '');
      if (value < 100000) return convert(Math.floor(value / 1000)) + ' Thousand' + (value % 1000 ? ' ' + convert(value % 1000) : '');
      if (value < 10000000) return convert(Math.floor(value / 100000)) + ' Lakh' + (value % 100000 ? ' ' + convert(value % 100000) : '');
      return convert(Math.floor(value / 10000000)) + ' Crore' + (value % 10000000 ? ' ' + convert(value % 10000000) : '');
    }
    return convert(Math.floor(number)) + ' Sri Lankan Rupees';
  }

  if (amountInput && amountWords) {
    amountInput.addEventListener('input', function () {
      var words = numberToWords(Number(amountInput.value));
      amountWords.textContent = words || 'Enter the disbursement amount in Sri Lankan Rupees.';
    });
  }

  if (allocationForm && method && reference) {
    method.addEventListener('change', function () {
      var endpoint = allocationForm.action.replace(/\/create\/?$/, '/getreference');
      fetch(endpoint + '?method=' + encodeURIComponent(method.value), { headers: { Accept: 'application/json' } })
        .then(function (response) { return response.json(); })
        .then(function (payload) { if (payload.success && payload.reference) reference.value = payload.reference; })
        .catch(function () {});
    });
  }

  if (allocationForm) {
    allocationForm.addEventListener('submit', function (event) {
      event.preventDefault();
      var submit = allocationForm.querySelector('[type="submit"]');
      if (submit) {
        submit.disabled = true;
        submit.textContent = 'Authorizing...';
      }
      if (allocationError) allocationError.hidden = true;

      fetch(allocationForm.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(allocationForm)
      })
        .then(function (response) {
          return response.json().then(function (payload) {
            if (!response.ok) throw new Error(payload.error || 'The allocation could not be completed.');
            return payload;
          });
        })
        .then(function (payload) {
          window.location.assign(payload.redirect || allocationForm.action.replace(/\/create\/?$/, ''));
        })
        .catch(function (error) {
          if (allocationError) {
            allocationError.textContent = error.message;
            allocationError.hidden = false;
          }
          if (submit) {
            submit.disabled = false;
            submit.textContent = 'Confirm Allocation';
          }
        });
    });
  }

  applyFilters();
})();
