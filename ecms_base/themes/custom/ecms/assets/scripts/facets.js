/**
 * @file Facet accordion behaviors.
 *
 * The checkbox facet widget is rendered server-side as a .qh__accordion by
 * templates/form/facets-item-list--checkbox.html.twig, so all markup — the
 * toggle and (for long facets) the search input + no-results message — is
 * present on initial load and after every Facets AJAX re-render. JS only adds
 * behavior: flip aria-expanded on the toggle, and filter the list as the user
 * types. No markup is injected here.
 *
 * Both behaviors bind via Drupal.behaviors + once() rather than the global
 * `js__expand-collapse` hook (global.js): that handler binds once on
 * DOMContentLoaded and would not re-bind on the markup Facets swaps in via AJAX.
 */

/* global Drupal, once */

(function (Drupal, once) {
  'use strict';

  // Toggle: the accordion open/close CSS keys off aria-expanded on the button.
  Drupal.behaviors.ecmsFacetAccordion = {
    attach: function (context) {
      once(
        'ecms-facet-accordion',
        '.facets-widget-checkbox .qh__accordion__button',
        context
      ).forEach(function (button) {
        button.addEventListener('click', function () {
          const expanded = button.getAttribute('aria-expanded') === 'true';
          button.setAttribute('aria-expanded', String(!expanded));
        });
      });
    },
  };

  // Client-side filter for long facets. The input is server-rendered (above the
  // threshold) by the Twig template; this only wires up the typing behavior.
  function initSearch(input) {
    const widget = input.closest('.facets-widget-checkbox');
    const list = widget && widget.querySelector('.item-list__checkbox');
    if (!list) return;

    const empty = widget.querySelector('.facets-widget__no-results');
    // Read the item value from the server-rendered <a> sibling rather than the
    // <label>: the contrib widget injects the checkbox + label pair at attach
    // time, so labels may not exist yet, but the <a data-drupal-facet-item-value>
    // is always present. Fall back to the item's text content.
    const indexed = Array.from(list.querySelectorAll('.facet-item')).map(function (li) {
      const link = li.querySelector('a[data-drupal-facet-item-value]');
      const text = link
        ? link.getAttribute('data-drupal-facet-item-value')
        : li.textContent;
      return { el: li, text: (text || '').toLowerCase() };
    });

    input.addEventListener('input', function () {
      const q = input.value.trim().toLowerCase();
      let visible = 0;
      indexed.forEach(function (entry) {
        // Always keep checked items visible so users can see and uncheck active
        // filters without first clearing the search.
        const checkbox = entry.el.querySelector('.facets-checkbox');
        const isChecked = checkbox && checkbox.checked;
        const show = isChecked || !q || entry.text.indexOf(q) !== -1;
        entry.el.hidden = !show;
        if (show) visible++;
      });
      if (empty) empty.hidden = visible !== 0;
      list.hidden = visible === 0;
    });
  }

  Drupal.behaviors.ecmsFacetSearch = {
    attach: function (context) {
      once(
        'ecms-facet-search',
        '.facets-widget__search input[type="search"]',
        context
      ).forEach(initSearch);
    },
  };
})(Drupal, once);
