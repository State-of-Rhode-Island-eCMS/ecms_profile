/**
 * @file Facet widget enhancements.
 *
 * Wraps each .facets-widget-checkbox in a collapsible body and, for facets
 * above SEARCH_THRESHOLD items, injects a client-side search input that filters
 * the checkbox list by label text. Pin-selected-to-top is provided server-side
 * by the active_widget_order processor on each facet — JS does not reorder.
 *
 * Re-attach safe via once(): Facets re-renders the block via AJAX on each
 * selection, and this behavior runs again on the fresh markup.
 */

/* global Drupal, once */

(function (Drupal, once) {
  'use strict';

  const SEARCH_THRESHOLD = 10;
  let widgetCounter = 0;

  function initFacet(widget) {
    const heading = widget.querySelector('h3');
    const list = widget.querySelector('.item-list__checkbox');
    if (!heading || !list) return;

    const id = ++widgetCounter;
    const bodyId = `facet-body-${id}`;
    const headingText = heading.textContent.trim();
    const items = Array.from(list.querySelectorAll('.facet-item'));

    // --- Collapsible header ---
    const titleText = document.createElement('span');
    titleText.className = 'facets-widget__title-text';
    titleText.textContent = headingText;

    const icon = document.createElement('span');
    icon.className = 'facets-widget__toggle-icon';
    icon.setAttribute('aria-hidden', 'true');

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'facets-widget__toggle';
    button.setAttribute('aria-expanded', 'true');
    button.setAttribute('aria-controls', bodyId);
    button.appendChild(titleText);
    button.appendChild(icon);

    heading.textContent = '';
    heading.appendChild(button);

    // --- Wrap body (search + list) ---
    const body = document.createElement('div');
    body.id = bodyId;
    body.className = 'facets-widget__body';
    list.parentNode.insertBefore(body, list);
    body.appendChild(list);

    // --- Search input (only above the threshold) ---
    if (items.length > SEARCH_THRESHOLD) {
      const wrap = document.createElement('div');
      wrap.className = 'facets-widget__search';

      const input = document.createElement('input');
      input.type = 'search';
      input.placeholder = `Find a ${headingText}`;
      input.setAttribute('aria-label', `Filter ${headingText} options`);
      wrap.appendChild(input);

      const empty = document.createElement('p');
      empty.className = 'facets-widget__no-results';
      empty.textContent = 'No matches.';
      empty.hidden = true;

      body.insertBefore(wrap, list);
      body.insertBefore(empty, list);

      // Read the item value from the server-rendered <a> sibling rather than
      // the <label>. The contrib Facets widget injects the checkbox + label
      // pair at attach time, so labels may not exist yet when this behavior
      // runs. The <a data-drupal-facet-item-value="…"> is always present.
      // Read the item value from the server-rendered <a> sibling rather than
      // the <label>. The contrib Facets widget injects the checkbox + label
      // pair at attach time, so labels may not exist yet when this behavior
      // runs. The <a data-drupal-facet-item-value="…"> is always present.
      const indexed = items.map(function (li) {
        const link = li.querySelector('a[data-drupal-facet-item-value]');
        const text = link ? link.getAttribute('data-drupal-facet-item-value') : '';
        return {
          el: li,
          text: text.toLowerCase(),
        };
      });

      input.addEventListener('input', function () {
        const q = input.value.trim().toLowerCase();
        let visible = 0;
        indexed.forEach(function (entry) {
          // Always keep checked items visible — users need to be able to see
          // and uncheck active filters without first clearing the search.
          const checkbox = entry.el.querySelector('.facets-checkbox');
          const isChecked = checkbox && checkbox.checked;
          const show = isChecked || !q || entry.text.indexOf(q) !== -1;
          entry.el.hidden = !show;
          if (show) visible++;
        });
        empty.hidden = visible !== 0;
        list.hidden = visible === 0;
      });
    }

    // --- Collapse toggle ---
    button.addEventListener('click', function () {
      const expanded = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-expanded', String(!expanded));
      body.hidden = expanded;
    });
  }

  Drupal.behaviors.ecmsFacetWidget = {
    attach: function (context) {
      once('ecms-facet-widget', '.facets-widget-checkbox', context).forEach(initFacet);
    },
  };
})(Drupal, once);
