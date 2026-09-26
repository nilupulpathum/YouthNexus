(function () {
  'use strict';

  var pagers = Array.prototype.slice.call(document.querySelectorAll('[data-yn-pagination]')).map(function (nav) {
    return {
      nav: nav,
      body: document.getElementById(nav.dataset.ynPageTarget),
      size: Math.max(1, Number(nav.dataset.ynPageSize) || 10),
      page: 1
    };
  }).filter(function (pager) { return pager.body; });

  function button(label, page, disabled, current) {
    var control = document.createElement('button');
    control.type = 'button';
    control.className = 'yn-pagination__link' + (current ? ' yn-pagination__link--active' : '');
    control.textContent = label;
    control.disabled = disabled;
    if (current) control.setAttribute('aria-current', 'page');
    if (!disabled) control.dataset.page = String(page);
    return control;
  }

  function render(pager) {
    // Filtering records membership separately from visibility, so paging never changes result counts.
    var rows = Array.prototype.slice.call(pager.body.querySelectorAll('tr[data-filter-match]'));
    var matches = rows.filter(function (row) { return row.dataset.filterMatch !== 'false'; });
    var pages = Math.max(1, Math.ceil(matches.length / pager.size));
    pager.page = Math.min(pager.page, pages);
    var start = (pager.page - 1) * pager.size;
    matches.forEach(function (row, index) {
      row.hidden = index < start || index >= start + pager.size;
    });
    rows.forEach(function (row) {
      if (row.dataset.filterMatch === 'false') row.hidden = true;
    });

    pager.nav.hidden = pages <= 1;
    pager.nav.replaceChildren();
    if (pages <= 1) return;
    pager.nav.appendChild(button('Previous', pager.page - 1, pager.page === 1, false));
    var first = Math.max(1, Math.min(pager.page - 2, pages - 4));
    for (var page = first; page <= Math.min(pages, first + 4); page += 1) {
      pager.nav.appendChild(button(String(page), page, false, page === pager.page));
    }
    pager.nav.appendChild(button('Next', pager.page + 1, pager.page === pages, false));
  }

  pagers.forEach(function (pager) {
    pager.nav.addEventListener('click', function (event) {
      var target = event.target.closest('button[data-page]');
      if (!target) return;
      pager.page = Number(target.dataset.page);
      render(pager);
      pager.nav.scrollIntoView({ block: 'nearest' });
    });
  });

  window.ynPager = {
    update: function () {
      pagers.forEach(function (pager) {
        pager.page = 1;
        render(pager);
      });
    }
  };
})();
