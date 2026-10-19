(function () {
  'use strict';

  var container = document.querySelector('[data-tab-container]');
  if (!container) return;

  var tablist = container.querySelector('[role="tablist"]');
  var tabs = Array.prototype.slice.call(container.querySelectorAll('[role="tab"]'));
  var panels = Array.prototype.slice.call(container.querySelectorAll('[role="tabpanel"]'));

  if (!tabs.length || !panels.length) return;

  var TAB_WHITELIST = ['pending', 'inventory', 'transfers', 'zonal'];
  var DEFAULT_TAB = 'pending';

  // Progressive enhancement: only hide inactive panels when JS initializes
  container.classList.add('js-tabs');

  function getUrlTab() {
    try {
      var params = new URLSearchParams(window.location.search);
      var tab = params.get('tab');
      if (tab && TAB_WHITELIST.indexOf(tab) !== -1) {
        return tab;
      }
    } catch (e) {}
    return DEFAULT_TAB;
  }

  function updateUrl(tabKey) {
    try {
      var url = new URL(window.location.href);
      if (url.searchParams.get('tab') !== tabKey) {
        url.searchParams.set('tab', tabKey);
        window.history.replaceState(null, '', url.toString());
      }
    } catch (e) {}
  }

  var currentActiveTab = getUrlTab();

  function updateAttentionBadge() {
    var pendingBadge = document.querySelector('[data-tab-count="pending"]');
    if (pendingBadge) {
      var count = Number(pendingBadge.textContent || 0);
      pendingBadge.classList.toggle('dv-tab__badge--attention', count > 0);
    }
  }

  function updateFilterHints() {
    updateAttentionBadge();

    var activeTabKey = currentActiveTab;
    var activeBadge = document.querySelector('[data-tab-count="' + activeTabKey + '"]');
    var activeCount = activeBadge ? Number(activeBadge.textContent || 0) : 0;

    var otherMatches = 0;
    TAB_WHITELIST.forEach(function (key) {
      if (key !== activeTabKey) {
        var badge = document.querySelector('[data-tab-count="' + key + '"]');
        if (badge) {
          otherMatches += Number(badge.textContent || 0);
        }
      }
    });

    panels.forEach(function (panel) {
      var hint = panel.querySelector('[data-tab-hint]');
      if (!hint) return;

      var isThisPanelActive = panel.dataset.tab === activeTabKey;
      if (isThisPanelActive && activeCount === 0 && otherMatches > 0) {
        var label = otherMatches === 1 ? 'result' : 'results';
        hint.textContent = otherMatches + ' ' + label + ' in other tabs';
        hint.hidden = false;
      } else {
        hint.hidden = true;
        hint.textContent = '';
      }
    });
  }

  function setActiveTab(tabKey, moveFocus) {
    if (TAB_WHITELIST.indexOf(tabKey) === -1) {
      tabKey = DEFAULT_TAB;
    }
    currentActiveTab = tabKey;

    var activeTabButton = null;

    tabs.forEach(function (tab) {
      var isActive = tab.dataset.tab === tabKey;
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      tab.tabIndex = isActive ? 0 : -1;
      if (isActive) {
        activeTabButton = tab;
      }
    });

    panels.forEach(function (panel) {
      var isActive = panel.dataset.tab === tabKey;
      panel.hidden = !isActive;
    });

    updateUrl(tabKey);
    updateFilterHints();

    if (moveFocus && activeTabButton) {
      activeTabButton.focus();
    }
  }

  // Keyboard navigation on tablist
  tabs.forEach(function (tab, index) {
    tab.addEventListener('click', function () {
      setActiveTab(tab.dataset.tab, false);
    });

    tab.addEventListener('keydown', function (event) {
      var targetIndex = -1;
      switch (event.key) {
        case 'ArrowRight':
        case 'ArrowDown':
          event.preventDefault();
          targetIndex = (index + 1) % tabs.length;
          break;
        case 'ArrowLeft':
        case 'ArrowUp':
          event.preventDefault();
          targetIndex = (index - 1 + tabs.length) % tabs.length;
          break;
        case 'Home':
          event.preventDefault();
          targetIndex = 0;
          break;
        case 'End':
          event.preventDefault();
          targetIndex = tabs.length - 1;
          break;
        case 'Enter':
        case ' ':
          event.preventDefault();
          setActiveTab(tab.dataset.tab, false);
          return;
        default:
          return;
      }

      if (targetIndex !== -1 && tabs[targetIndex]) {
        setActiveTab(tabs[targetIndex].dataset.tab, true);
      }
    });
  });

  // Summary cards interactive binding
  var CARD_LABEL_MAP = {
    'units in division store': 'inventory',
    'asset types': 'inventory',
    'pending club requests': 'pending',
    'requests to zonal': 'zonal'
  };

  var summaryCards = Array.prototype.slice.call(document.querySelectorAll('.dw-summary-card'));
  summaryCards.forEach(function (card) {
    var labelEl = card.querySelector('.dw-summary-card__label');
    if (!labelEl) return;
    var normalized = labelEl.textContent.trim().toLowerCase();
    var targetTab = CARD_LABEL_MAP[normalized];
    if (!targetTab) return;

    card.setAttribute('tabindex', '0');
    card.setAttribute('role', 'button');
    card.setAttribute('aria-label', 'Show ' + labelEl.textContent.trim());

    card.addEventListener('click', function () {
      setActiveTab(targetTab, false);
      var targetPanel = container.querySelector('[role="tabpanel"][data-tab="' + targetTab + '"]');
      if (targetPanel) {
        targetPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });

    card.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        setActiveTab(targetTab, false);
        var targetPanel = container.querySelector('[role="tabpanel"][data-tab="' + targetTab + '"]');
        if (targetPanel) {
          targetPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }
    });
  });

  // Listen for filter events from divisional-assets.js
  document.addEventListener('divisional:filtered', function () {
    updateFilterHints();
  });

  // Initialize active tab state on load
  setActiveTab(currentActiveTab, false);
  updateAttentionBadge();
})();
