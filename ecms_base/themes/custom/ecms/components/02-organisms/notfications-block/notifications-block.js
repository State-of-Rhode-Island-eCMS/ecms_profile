/**
 * @file Site notifications block — collapse persistence.
 *
 * Remembers (via sessionStorage) when a visitor has collapsed the site
 * notifications, and restores that collapsed state on subsequent views. The
 * actual aria-expanded toggle is handled by the global expand-collapse behavior;
 * this only layers the persistence on top.
 *
 * The persistence listens for the `ecms:expand-collapse` event the global
 * behavior dispatches after it toggles, NOT for `click`. Two click handlers on
 * the same trigger race: whichever registered first sees the pre-toggle value of
 * aria-expanded, so reading the attribute here would flip meaning depending on
 * script load order.
 *
 * Note that `.notifications-hidden` on <html> (set by the inline head script in
 * html.html.twig) is an anti-flash measure only. It applies `display: none`,
 * which outranks the `.js__aria-expanded` visibility toggle, so it must be
 * cleared as soon as the collapsed state has been applied to the elements
 * themselves — otherwise re-expanding the block leaves it invisible.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ecmsNotificationsBlock = {
    attach: function (context) {
      once('ecms-notifications-block', '#summary-notifications', context).forEach(function (notificationsToggle) {
        var detailsElement = document.getElementById('details-notifications');

        // Restore collapsed state if it was hidden in a previous view.
        if (sessionStorage.getItem('siteNotificationsHidden')) {
          notificationsToggle.setAttribute('aria-expanded', 'false');
          if (detailsElement !== null && detailsElement !== undefined) {
            detailsElement.classList.remove('js__aria-expanded');
          }
        }

        // The collapsed state now lives on the elements, so hand visibility back
        // to the .js__aria-expanded styling.
        document.documentElement.classList.remove('notifications-hidden');

        // Persist the state when the visitor toggles the block.
        notificationsToggle.addEventListener('ecms:expand-collapse', function (event) {
          if (event.detail.expanded) {
            sessionStorage.removeItem('siteNotificationsHidden');
          } else {
            sessionStorage.setItem('siteNotificationsHidden', true);
          }
        });
      });
    }
  };
})(Drupal, once);
