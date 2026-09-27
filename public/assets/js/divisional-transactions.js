(function () {
  'use strict';

  var grid = document.querySelector('[data-transaction-grid]');
  if (!grid) return;

  var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-transaction-card]'));
  var search = document.querySelector('[data-transaction-search]');
  var type = document.querySelector('[data-transaction-type-filter]');
  var category = document.querySelector('[data-transaction-category-filter]');
  var receipt = document.querySelector('[data-transaction-receipt-filter]');
  var sort = document.querySelector('[data-transaction-sort]');
  var minimum = document.querySelector('[data-transaction-min-amount]');
  var maximum = document.querySelector('[data-transaction-max-amount]');
  var dateFrom = document.querySelector('[data-transaction-date-from]');
  var dateTo = document.querySelector('[data-transaction-date-to]');
  var count = document.querySelector('[data-transaction-count]');
  var empty = document.querySelector('[data-empty-state]');

  function textValue(control) {
    return control ? String(control.value || '').trim().toLowerCase() : '';
  }

  function numericValue(control) {
    if (!control || control.value === '') return null;
    var parsed = Number(control.value);
    return Number.isFinite(parsed) ? parsed : null;
  }

  function sortCards() {
    var selected = textValue(sort) || 'newest';
    cards.sort(function (left, right) {
      if (selected === 'oldest') return left.dataset.date.localeCompare(right.dataset.date);
      if (selected === 'amount-high') return Number(right.dataset.amount) - Number(left.dataset.amount);
      if (selected === 'amount-low') return Number(left.dataset.amount) - Number(right.dataset.amount);
      return right.dataset.date.localeCompare(left.dataset.date);
    });
    cards.forEach(function (card) { grid.appendChild(card); });
  }

  function applyFilters() {
    var query = textValue(search);
    var selectedType = textValue(type);
    var selectedCategory = textValue(category);
    var selectedReceipt = textValue(receipt);
    var minimumAmount = numericValue(minimum);
    var maximumAmount = numericValue(maximum);
    var from = dateFrom ? dateFrom.value : '';
    var to = dateTo ? dateTo.value : '';
    var visible = 0;

    cards.forEach(function (card) {
      var amount = Number(card.dataset.amount);
      var matches = (!query || card.dataset.search.indexOf(query) !== -1)
        && (!selectedType || card.dataset.type === selectedType)
        && (!selectedCategory || card.dataset.category === selectedCategory)
        && (!selectedReceipt || card.dataset.receipt === selectedReceipt)
        && (minimumAmount === null || amount >= minimumAmount)
        && (maximumAmount === null || amount <= maximumAmount)
        && (!from || card.dataset.date >= from)
        && (!to || card.dataset.date <= to);
      card.hidden = !matches;
      if (matches) visible += 1;
    });

    sortCards();
    if (count) count.textContent = visible + (visible === 1 ? ' transaction' : ' transactions');
    if (empty) empty.classList.toggle('is-visible', visible === 0);
  }

  if (search) search.addEventListener('input', applyFilters);
  document.querySelector('[data-transaction-filter-apply]')?.addEventListener('click', applyFilters);
  document.querySelector('[data-transaction-filter-reset]')?.addEventListener('click', function () {
    [search, type, category, receipt, minimum, maximum, dateFrom, dateTo].forEach(function (control) {
      if (control) control.value = '';
    });
    if (sort) sort.value = 'newest';
    applyFilters();
  });

  var editForm = document.querySelector('[data-edit-transaction-form]');
  document.querySelectorAll('[data-edit-transaction]').forEach(function (button) {
    button.addEventListener('click', function () {
      if (!editForm) return;
      var card = button.closest('[data-transaction-card]');
      editForm.action = editForm.dataset.updateBase + card.dataset.entryId;
      editForm.elements.type.value = card.dataset.typeLabel;
      editForm.elements.amount.value = card.dataset.amount;
      editForm.elements.category.value = card.dataset.categoryLabel;
      editForm.elements.date.value = card.dataset.date;
      editForm.elements.description.value = card.dataset.description;
      editForm.elements.receipt.value = '';
      editForm.elements.remove_receipt.checked = false;
      var fileLabel = document.querySelector('[data-file-name-for="edit-transaction-receipt"]');
      if (fileLabel) fileLabel.textContent = 'Choose a PDF, JPG, or PNG receipt';
      var removeRow = editForm.querySelector('[data-remove-receipt-row]');
      if (removeRow) removeRow.hidden = card.dataset.receipt !== 'attached';
    });
  });

  document.querySelectorAll('[data-transaction-form], [data-edit-transaction-form]').forEach(function (form) {
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
