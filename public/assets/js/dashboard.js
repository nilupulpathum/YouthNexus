(function () {
  'use strict';

  var body = document.body;
  var profileMenu = document.querySelector('[data-profile-menu]');
  var profileToggle = document.querySelector('[data-profile-toggle]');
  var profileDropdown = document.querySelector('[data-profile-dropdown]');
  var notifMenu = document.querySelector('[data-notif-menu]');
  var notifToggle = document.querySelector('[data-notif-toggle]');
  var notifDropdown = document.querySelector('[data-notif-dropdown]');
  var sidebar = document.getElementById('dashboard-sidebar');
  var sidebarToggle = document.querySelector('[data-sidebar-toggle]');
  var sidebarClose = document.querySelector('[data-sidebar-close]');
  var sidebarScrim = document.querySelector('[data-sidebar-scrim]');

  function finishNavigation() {
    document.documentElement.classList.remove('yn-nav-loading');
    try {
      sessionStorage.removeItem('yn-sidebar-navigation');
    } catch (error) { /* Storage is optional. */ }
  }

  // Lift the cover as soon as the page shell is parsed — do NOT wait for
  // the full `load` event, which is delayed by every image, iframe, and
  // external stylesheet (the Google Fonts @import can hang for ages on a
  // slow connection and left the cover stuck over a ready page).
  if (document.readyState !== 'loading') {
    finishNavigation();
  } else {
    document.addEventListener('DOMContentLoaded', finishNavigation, { once: true });
  }
  window.addEventListener('pageshow', function (event) {
    if (event.persisted) finishNavigation();
  });

  if (sidebar) {
    sidebar.addEventListener('click', function (event) {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      var link = event.target.closest('.dashboard-nav a[href]');
      if (!link || link.target || link.hasAttribute('download')) return;

      var destination = new URL(link.href, window.location.href);
      if (destination.origin !== window.location.origin || !/^https?:$/.test(destination.protocol)) return;
      // An in-page anchor or current route needs no full-page loading state.
      if (destination.pathname === window.location.pathname && destination.search === window.location.search) return;

      document.documentElement.classList.add('yn-nav-loading');
      try {
        sessionStorage.setItem('yn-sidebar-navigation', String(Date.now()));
      } catch (error) { /* The outgoing-page cover still works. */ }

      // If navigation was canceled or failed, return control to the user.
      window.setTimeout(finishNavigation, 30000);
    });
  }

  function setProfileOpen(isOpen) {
    if (!profileToggle || !profileDropdown) {
      return;
    }

    profileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    profileDropdown.hidden = !isOpen;
  }

  function setNotifOpen(isOpen) {
    if (!notifToggle || !notifDropdown) {
      return;
    }

    notifToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    notifDropdown.hidden = !isOpen;
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
      setNotifOpen(false);
      setProfileOpen(profileDropdown.hidden);
    });

    document.addEventListener('click', function (event) {
      if (profileMenu && !profileMenu.contains(event.target)) {
        setProfileOpen(false);
      }
      if (notifMenu && !notifMenu.contains(event.target)) {
        setNotifOpen(false);
      }
    });
  }

  if (notifToggle && notifDropdown) {
    notifToggle.addEventListener('click', function () {
      setProfileOpen(false);
      setNotifOpen(notifDropdown.hidden);
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
      setNotifOpen(false);
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
