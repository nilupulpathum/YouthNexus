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
})();
