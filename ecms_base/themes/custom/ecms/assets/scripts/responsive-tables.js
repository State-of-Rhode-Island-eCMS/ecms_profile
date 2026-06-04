/**
 * @file Responsive tables behavior.
 *
 * Adds data-th attributes to table cells based on their column headers and
 * wraps each table in a container div, enabling CSS-based (container query)
 * responsive table layouts.
 *
 * Bound via Drupal.behaviors + once so tables added after initial page load
 * (e.g. BigPipe / AJAX) are processed too, while each table is only ever
 * processed a single time.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ecmsResponsiveTables = {
    attach: function (context) {
      once('qh-responsive-table', 'table', context).forEach(function (table) {
        var headerCells = table.querySelectorAll('thead th');

        // Skip tables without a thead.
        if (headerCells.length === 0) {
          return;
        }

        // Get header text for each column.
        var headers = [];
        headerCells.forEach(function (th) {
          headers.push(th.textContent.trim());
        });

        // Apply data-th to each td in tbody.
        var bodyRows = table.querySelectorAll('tbody tr');
        bodyRows.forEach(function (row) {
          var cells = row.querySelectorAll('td');
          cells.forEach(function (td, index) {
            if (headers[index]) {
              td.setAttribute('data-th', headers[index]);
            }
          });
        });

        // Wrap table in container div for container queries.
        var wrapper = document.createElement('div');
        wrapper.classList.add('qh__table-container');
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);

        // Mark table as processed for responsive styling.
        table.classList.add('qh__table');
      });
    }
  };
})(Drupal, once);
