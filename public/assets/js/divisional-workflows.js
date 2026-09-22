(function () {
  'use strict';

  var activeModal = null;

  function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('dw-modal-open');
    activeModal = null;
  }

  function openModal(modal) {
    if (!modal) return;
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('dw-modal-open');
    activeModal = modal;
    var focusTarget = modal.querySelector('input:not([type="hidden"]), select, textarea, button');
    if (focusTarget) focusTarget.focus();
  }

  document.addEventListener('click', function (event) {
    var modalTrigger = event.target.closest('[data-modal-open]');
    if (modalTrigger) {
      openModal(document.getElementById(modalTrigger.getAttribute('data-modal-open')));
      return;
    }

    var closeTrigger = event.target.closest('[data-modal-close]');
    if (closeTrigger) {
      closeModal(closeTrigger.closest('.dw-modal'));
      return;
    }

    var filterTrigger = event.target.closest('[data-filter-toggle]');
    if (filterTrigger) {
      var panel = document.getElementById(filterTrigger.getAttribute('aria-controls'));
      if (panel) {
        panel.hidden = !panel.hidden;
        filterTrigger.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
      }
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && activeModal) closeModal(activeModal);
  });

  document.querySelectorAll('[data-file-input]').forEach(function (input) {
    input.addEventListener('change', function () {
      var label = document.querySelector('[data-file-name-for="' + input.id + '"]');
      if (label) label.textContent = input.files.length ? input.files[0].name : 'Choose a PDF, JPG, or PNG receipt';
    });
  });

  var financeChangeKey = 'youthnexus:divisional-finance-change';
  var refreshFinance = document.querySelector('[data-finance-refresh-on-change]');
  var changedFinance = document.querySelector('[data-finance-state-changed]');
  var loadedAt = Date.now();

  if (changedFinance) {
    try {
      window.localStorage.setItem(financeChangeKey, String(Date.now()));
    } catch (error) {
      // The server-side lock still protects the entry when storage is unavailable.
    }
  }

  if (refreshFinance) {
    window.addEventListener('storage', function (event) {
      if (event.key === financeChangeKey) window.location.reload();
    });
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) window.location.reload();
    });
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState !== 'visible') return;
      try {
        var changedAt = Number(window.localStorage.getItem(financeChangeKey) || 0);
        if (changedAt > loadedAt) window.location.reload();
      } catch (error) {
        // A normal navigation still loads the current server state.
      }
    });
  }
})();
