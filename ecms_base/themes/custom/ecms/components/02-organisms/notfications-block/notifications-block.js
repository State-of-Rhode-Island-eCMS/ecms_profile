/**
 * @file Site notifications block — collapse persistence.
 *
 * Remembers (via sessionStorage) when a visitor has collapsed the site
 * notifications, and restores that collapsed state on subsequent views. The
 * actual aria-expanded toggle is handled by the global expand-collapse behavior;
 * this only layers the persistence on top.
 *
 * Bound via Drupal.behaviors + once. Previously this ran at top-level script
 * evaluation, so it executed before its target existed (and never re-ran for
 * content streamed in via BigPipe for authenticated users), leaving the
 * persistence unbound.
 *
 * a11yClick is a global helper defined in the theme's global.js
 * (ecms/global-styling).
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ecmsNotificationsBlock = {
    attach: function (context) {
      once('ecms-notifications-block', '#summary-notifications', context).forEach(function (notificationsToggle) {

        // Restore collapsed state if it was hidden in a previous view.
        if (sessionStorage.getItem('siteNotificationsHidden')) {
          notificationsToggle.setAttribute('aria-expanded', 'false');
          var detailsElement = document.getElementById('details-notifications');
          if (detailsElement !== null && detailsElement !== undefined) {
            detailsElement.classList.remove('js__aria-expanded');
          }
        }

        // Persist the state when the visitor toggles the block.
        notificationsToggle.addEventListener('click', function (event) {
          if (a11yClick(event) === true) {
            var expanded = notificationsToggle.getAttribute('aria-expanded');

            if (expanded == 'true') {
              // Optimization for Repeat Views
              sessionStorage.setItem('siteNotificationsHidden', true);
              document.documentElement.classList.add("notifications-hidden");
            } else {
              // Optimization for Repeat Views
              sessionStorage.removeItem('siteNotificationsHidden');
              document.documentElement.classList.remove("notifications-hidden");
            }
          }
        });
      });
    }
  };
})(Drupal, once);
