(function () {
  'use strict';

  var body = document.body;
  var profileMenu = document.querySelector('[data-profile-menu]');
  var profileToggle = document.querySelector('[data-profile-toggle]');
  var profileDropdown = document.querySelector('[data-profile-dropdown]');
  var sidebar = document.getElementById('dashboard-sidebar');
  var sidebarToggle = document.querySelector('[data-sidebar-toggle]');
  var sidebarClose = document.querySelector('[data-sidebar-close]');
  var sidebarScrim = document.querySelector('[data-sidebar-scrim]');

  function setProfileOpen(isOpen) {
    if (!profileToggle || !profileDropdown) {
      return;
    }

    profileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    profileDropdown.hidden = !isOpen;
  }

  function setSidebarOpen(isOpen) {
    if (!sidebar || !sidebarToggle) {
      return;
    }

    body.classList.toggle('dashboard-sidebar-open', isOpen);
    sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    sidebarToggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');

    if (sidebarScrim) {
      sidebarScrim.hidden = !isOpen;
    }
  }

  if (profileToggle && profileDropdown) {
    profileToggle.addEventListener('click', function () {
      setProfileOpen(profileDropdown.hidden);
    });

    document.addEventListener('click', function (event) {
      if (profileMenu && !profileMenu.contains(event.target)) {
        setProfileOpen(false);
      }
    });
  }

  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function () {
      setSidebarOpen(!body.classList.contains('dashboard-sidebar-open'));
    });
  }

  if (sidebarClose) {
    sidebarClose.addEventListener('click', function () {
      setSidebarOpen(false);
    });
  }

  if (sidebarScrim) {
    sidebarScrim.addEventListener('click', function () {
      setSidebarOpen(false);
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      setProfileOpen(false);
      setSidebarOpen(false);
      if (profileToggle) {
        profileToggle.focus();
      }
    }
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth > 900) {
      setSidebarOpen(false);
    }
  });
}());
