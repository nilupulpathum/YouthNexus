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
      var visible = (!term || (row.dataset.search || '').indexOf(term) !== -1)
        && (!value(filters.club) || row.dataset.club === value(filters.club))
        && (!value(filters.category) || row.dataset.category === value(filters.category))
        && (!value(filters.status) || row.dataset.status === value(filters.status))
        && (min === null || amount >= min)
        && (max === null || amount <= max)
        && (!value(filters.from) || row.dataset.date >= value(filters.from))
        && (!value(filters.to) || row.dataset.date <= value(filters.to));
      row.dataset.filterMatch = visible ? 'true' : 'false';
      row.hidden = !visible;
    });

    sortRows();
    updateSections();

    if (window.ynPager && typeof window.ynPager.update === 'function') {
      window.ynPager.update();
    }

    ['pending', 'history'].forEach(function (section) {
      var sectionRows = rows.filter(function (row) { return row.dataset.section === section; });
      var matches = sectionRows.filter(function (row) { return row.dataset.filterMatch !== 'false'; });
      var targetId = section === 'pending' ? 'allocation-pending-tbody' : 'allocation-history-tbody';
      var pagerNav = document.querySelector('[data-yn-pagination][data-yn-page-target="' + targetId + '"]');
      if (pagerNav && matches.length === 0) {
        pagerNav.hidden = true;
      }
    });

    document.dispatchEvent(new CustomEvent('divisional:filtered'));
  }

  function sortRows() {
    var mode = value(filters.sort) || 'newest';
    ['pending', 'history'].forEach(function (section) {
      var body = document.querySelector('[data-allocation-rows="' + section + '"]');
      if (!body) return;
      var sectionRows = rows.filter(function (row) { return row.dataset.section === section; });
      sectionRows.sort(function (a, b) {
        if (mode === 'oldest') return (a.dataset.date || '').localeCompare(b.dataset.date || '');
        if (mode === 'amount-high') return Number(b.dataset.amount || 0) - Number(a.dataset.amount || 0);
        if (mode === 'amount-low') return Number(a.dataset.amount || 0) - Number(b.dataset.amount || 0);
        return (b.dataset.date || '').localeCompare(a.dataset.date || '');
      });
      sectionRows.forEach(function (row) { body.appendChild(row); });
    });
  }

  function updateSections() {
    ['pending', 'history'].forEach(function (name) {
      var section = document.querySelector('[data-allocation-section="' + name + '"]');
      if (!section) return;
      var sectionRows = rows.filter(function (row) { return row.dataset.section === name; });
      var visible = sectionRows.filter(function (row) { return row.dataset.filterMatch !== 'false'; }).length;
      var count = section.querySelector('[data-allocation-count]');
      var empty = section.querySelector('[data-empty-state]');
      if (count) count.textContent = visible + (name === 'pending' ? ' pending' : ' records');
      if (empty) empty.classList.toggle('is-visible', visible === 0);

      var tabBadge = document.querySelector('[data-tab-count="' + name + '"]');
      if (tabBadge) {
        tabBadge.textContent = String(visible);
        if (name === 'pending') {
          tabBadge.classList.toggle('dv-tab__badge--attention', visible > 0);
        }
      }
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

  // ── Number to English Words Helper ────────────────────────
  function numberToWords(num) {
    if (isNaN(num) || num <= 0) return '';
    var units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                 'Seventeen', 'Eighteen', 'Nineteen'];
    var tens  = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    function convert(n) {
      if (n === 0) return '';
      if (n < 20) return units[n] + ' ';
      if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + units[n % 10] : '') + ' ';
      if (n < 1000) return units[Math.floor(n / 100)] + ' Hundred ' + convert(n % 100);
      if (n < 100000) return convert(Math.floor(n / 1000)) + 'Thousand ' + convert(n % 1000);
      if (n < 10000000) return convert(Math.floor(n / 100000)) + 'Lakh ' + convert(n % 100000);
      return convert(Math.floor(n / 10000000)) + 'Crore ' + convert(n % 10000000);
    }

    return convert(Math.floor(num)).trim() + ' Sri Lankan Rupees';
  }

  // Amount input listener
  var allocAmount = document.getElementById('alloc_amount');
  var allocWords = document.getElementById('alloc_amount_words');
  if (allocAmount && allocWords) {
    allocAmount.addEventListener('input', function () {
      var clean = this.value.replace(/,/g, '').replace(/[^\d.]/g, '');
      var parsed = parseFloat(clean);
      if (!isNaN(parsed) && parsed > 0) {
        allocWords.textContent = numberToWords(parsed);
      } else {
        allocWords.textContent = 'Enter disbursement sum in Sri Lankan Rupees';
      }
    });
  }

  // Disbursement Method Toggle
  var methodInput = document.getElementById('selectedMethodInput');
  var btnRTGS = document.getElementById('btnMethodRTGS');
  var btnCheque = document.getElementById('btnMethodCheque');

  function setMethod(method) {
    if (methodInput) methodInput.value = method;
    if (btnRTGS) btnRTGS.classList.toggle('ft-method-selected', method === 'RTGS');
    if (btnCheque) btnCheque.classList.toggle('ft-method-selected', method === 'ChequeSLIPS');
  }

  if (btnRTGS) btnRTGS.addEventListener('click', function () { setMethod('RTGS'); });
  if (btnCheque) btnCheque.addEventListener('click', function () { setMethod('ChequeSLIPS'); });

  // ── Dynamic Recipient Bank Account Card (Club) ───────────
  var allocClubSelect        = document.getElementById('alloc_club_id');
  var recipientAccountBox    = document.getElementById('recipientAccountBox');
  var recipientEmptyState    = document.getElementById('recipientEmptyState');
  var recipientLoadedState   = document.getElementById('recipientLoadedState');
  var recipientWarningState  = document.getElementById('recipientWarningState');
  var recipientBankName      = document.getElementById('recipientBankName');
  var recipientAccountMasked = document.getElementById('recipientAccountMasked');
  var recipientAccountLabel  = document.getElementById('recipientAccountLabel');
  var recipientIdInput       = document.getElementById('alloc_recipient_bank_account_id');
  var btnSubmitAlloc         = document.getElementById('btnSubmitAlloc');

  function updateRecipientCard(clubId) {
    if (!recipientAccountBox) return;

    if (!clubId) {
      if (recipientEmptyState) recipientEmptyState.style.display = 'flex';
      if (recipientLoadedState) recipientLoadedState.style.display = 'none';
      if (recipientWarningState) recipientWarningState.style.display = 'none';
      recipientAccountBox.className = 'ft-bank-box ft-bank-box-recipient ft-state-empty';
      if (recipientIdInput) recipientIdInput.value = '';
      if (btnSubmitAlloc) {
        btnSubmitAlloc.disabled = true;
        btnSubmitAlloc.title = 'Please select a target club with a verified bank account.';
      }
      return;
    }

    function applyVerifiedAccount(acc) {
      if (recipientEmptyState) recipientEmptyState.style.display = 'none';
      if (recipientWarningState) recipientWarningState.style.display = 'none';
      if (recipientLoadedState) recipientLoadedState.style.display = 'flex';
      recipientAccountBox.className = 'ft-bank-box ft-bank-box-recipient ft-state-verified';

      if (recipientBankName) {
        recipientBankName.innerHTML = (acc.bank_name || 'Bank') + ' <span class="ft-bank-sub" id="recipientBranchName">(' + (acc.branch_name || 'Branch') + ')</span>';
      }
      if (recipientAccountMasked) {
        recipientAccountMasked.textContent = acc.masked_account || (acc.account_number ? ('****-' + String(acc.account_number).slice(-4)) : '—');
      }
      if (recipientAccountLabel) {
        recipientAccountLabel.textContent = acc.account_label || (acc.club_name ? acc.club_name + ' Fund' : 'Club Operational Fund');
      }
      if (recipientIdInput) {
        recipientIdInput.value = acc.bank_account_id || '';
      }
      if (btnSubmitAlloc) {
        btnSubmitAlloc.disabled = false;
        btnSubmitAlloc.removeAttribute('title');
      }
    }

    function applyWarningNoAccount() {
      if (recipientEmptyState) recipientEmptyState.style.display = 'none';
      if (recipientLoadedState) recipientLoadedState.style.display = 'none';
      if (recipientWarningState) recipientWarningState.style.display = 'flex';
      recipientAccountBox.className = 'ft-bank-box ft-bank-box-recipient ft-state-warning';

      if (recipientIdInput) recipientIdInput.value = '';
      if (btnSubmitAlloc) {
        btnSubmitAlloc.disabled = true;
        btnSubmitAlloc.title = 'Cannot transfer: No verified bank account registered for selected club.';
      }
    }

    // 1. Look up in preloaded config.clubAccounts
    var config = window.YouthNexusDivisionalAllocations || {};
    var accountsMap = config.clubAccounts || {};
    var acc = accountsMap[clubId] || null;

    if (acc) {
      applyVerifiedAccount(acc);
      return;
    }

    // 2. Fallback check via AJAX endpoint if not found in preloaded map
    var rootUrl = config.rootUrl || '';
    fetch(rootUrl + '/divisionalallocations/getclubaccount?club_id=' + encodeURIComponent(clubId), {
      headers: { 'Accept': 'application/json' }
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data && data.success && data.account) {
        applyVerifiedAccount(data.account);
      } else {
        applyWarningNoAccount();
      }
    })
    .catch(function () {
      applyWarningNoAccount();
    });
  }

  if (allocClubSelect) {
    allocClubSelect.addEventListener('change', function () {
      updateRecipientCard(this.value);
    });
  }

  // ── Review Fund Request Modal ─────────────────────────────
  document.querySelectorAll('[data-review-allocation]').forEach(function (button) {
    button.addEventListener('click', function () {
      var modal = document.getElementById('review-allocation');
      var form = document.querySelector('[data-review-allocation-form]');
      if (form) {
        form.action = button.dataset.action;
      }
      if (modal) {
        var setField = function (selector, text) {
          modal.querySelectorAll(selector).forEach(function (el) {
            el.textContent = text || '—';
          });
        };
        setField('[data-review-club]', button.dataset.clubName);
        setField('[data-review-reference]', button.dataset.reference);
        setField('[data-review-amount]', button.dataset.amountLabel);
        setField('[data-review-category]', button.dataset.categoryLabel);
        setField('[data-review-requested]', button.dataset.requestedLabel);
        setField('[data-review-purpose]', button.dataset.purpose);

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
      }
    });
  });

  // Reject confirmation
  document.querySelectorAll('[data-reject-allocation-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm('Reject this fund request?')) event.preventDefault();
    });
  });

  // Submit button lock
  document.querySelectorAll('[data-allocation-form], [data-review-allocation-form]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var submit = form.querySelector('[type="submit"]');
      if (submit) submit.disabled = true;
    });
  });

  // ── Transaction Details Modal ─────────────────────────────
  function openDetailsModal(button) {
    var modal = document.getElementById('transferDetailsModal');
    if (!modal) return;

    var id = button.dataset.id;
    var ref = button.dataset.ref || '—';
    var status = button.dataset.status || 'Completed';
    var amount = button.dataset.amount || '0.00';
    var datetime = button.dataset.datetime || '—';
    var date = button.dataset.date || '—';
    var club = button.dataset.club || '—';
    var category = button.dataset.category || 'General';
    var bank = button.dataset.bank || 'Bank of Ceylon';
    var branch = button.dataset.branch || '';
    var account = button.dataset.account || '—';
    var method = button.dataset.method || '—';
    var authorized = button.dataset.authorized || 'Divisional Treasurer';
    var purpose = button.dataset.purpose || '—';

    var setText = function (elemId, val) {
      var el = document.getElementById(elemId);
      if (el) el.textContent = val;
    };

    setText('dtModalRef', ref);
    setText('dtModalRef2', ref);
    setText('dtModalAmount', 'LKR ' + amount);
    setText('dtModalDatetime', datetime);
    setText('dtModalClub', club);
    setText('dtModalCategory', category);
    setText('dtModalDate', date);
    setText('dtModalMethod', method);
    setText('dtModalBank', bank + (branch ? ' (' + branch + ')' : ''));
    setText('dtModalAccount', account);
    setText('dtModalAuthorized', authorized);
    setText('dtModalPurpose', purpose);
    setText('dtModalSubtitle', 'Club fund disbursement for ' + club);

    var chip = document.getElementById('dtModalStatusChip');
    if (chip) {
      chip.textContent = status;
      chip.className = 'ft-status-chip ' + (status === 'Completed' ? 'ft-status-completed' : (status === 'Rejected' ? 'ft-status-failed' : 'ft-status-processing'));
    }

    var dlBtn = document.getElementById('btnReceiptDownload');
    if (dlBtn && id) {
      var rootUrl = (window.YouthNexusDivisionalAllocations && window.YouthNexusDivisionalAllocations.rootUrl) || '';
      dlBtn.href = rootUrl + '/divisionalallocations/receipt/' + id;
    }

    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    // Server-side details fetch verification
    if (id) {
      var root = (window.YouthNexusDivisionalAllocations && window.YouthNexusDivisionalAllocations.rootUrl) || '';
      fetch(root + '/divisionalallocations/details/' + id, {
        headers: { 'Accept': 'application/json' }
      })
      .then(function (res) { return res.ok ? res.json() : null; })
      .then(function (data) {
        if (!data) return;
        if (data.authorized_by_name) setText('dtModalAuthorized', data.authorized_by_name + ' (Divisional Treasurer)');
        if (data.bank_name) setText('dtModalBank', data.bank_name + (data.branch_name ? ' (' + data.branch_name + ')' : ''));
        if (data.account_number) setText('dtModalAccount', data.account_number);
      })
      .catch(function () {});
    }
  }

  document.addEventListener('click', function (e) {
    var viewBtn = e.target.closest('.ft-btn-view-details');
    if (viewBtn) {
      openDetailsModal(viewBtn);
      return;
    }

    // Modal open for create modal
    var openBtn = e.target.closest('[data-modal-open="new-allocation"], [data-modal-open="fundAllocationModal"]');
    if (openBtn) {
      var allocModal = document.getElementById('fundAllocationModal');
      if (allocModal) {
        allocModal.hidden = false;
        allocModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (allocClubSelect) updateRecipientCard(allocClubSelect.value);
      }
      return;
    }

    // Modal close
    var closeBtn = e.target.closest('[data-modal-close]');
    if (closeBtn) {
      var overlay = closeBtn.closest('.ft-overlay, .dw-modal');
      if (overlay) {
        overlay.hidden = true;
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      }
    }
  });

  // Close modals on Escape key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.ft-overlay:not([hidden])').forEach(function (overlay) {
        overlay.hidden = true;
        overlay.setAttribute('aria-hidden', 'true');
      });
      document.body.style.overflow = '';
    }
  });

  applyFilters();
})();
