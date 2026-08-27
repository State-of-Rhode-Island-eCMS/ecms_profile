/**
 * @file Mobile toggle for the minor (section) navigation.
 *
 * Different than the global expand/collapse in that we toggle a class on the
 * parent, and it hooks into the global page overlay actions.
 *
 * Bound via Drupal.behaviors + once so it also attaches when the navigation is
 * delivered after initial page load. For authenticated users Drupal streams the
 * minor navigation in via BigPipe after DOMContentLoaded has fired, so a
 * one-time DOMContentLoaded binding would never wire up the toggle.
 *
 * a11yClick / allMenuCloser / activatePageOverlay / deactivatePageOverlay are
 * global helpers defined in the theme's global.js (ecms/global-styling).
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ecmsNavigationMinor = {
    attach: function (context) {
      once('ecms-nav-minor-toggle', '#js__minor-toggle', context).forEach(function (qh_toggle_btn) {
        var qh_nav_minor = document.getElementById('js__minor-menu');

        qh_toggle_btn.addEventListener('click', function (event) {
          // a11yClick function restricts keypress to spacebar or enter
          if (a11yClick(event) === true) {
            event.preventDefault();
            if (qh_nav_minor.classList.contains('qh__nav-minor--expanded')) {
              qh_nav_minor.classList.remove('qh__nav-minor--expanded');
              // Keep ARIA state in sync — this was hardcoded "false" forever
              // (RIGA-891 callout 8)
              qh_toggle_btn.setAttribute('aria-expanded', 'false');
              deactivatePageOverlay();
            } else {
              allMenuCloser();
              activatePageOverlay();
              qh_nav_minor.classList.add('qh__nav-minor--expanded');
              qh_toggle_btn.setAttribute('aria-expanded', 'true');
            }
          }
        });

        // Close the drawer when keyboard focus leaves it or on Escape
        // (RIGA-891). The <nav> contains both the toggle and the list.
        qhMenuFocusDismiss(
          qh_nav_minor,
          qh_toggle_btn,
          function () {
            return qh_nav_minor.classList.contains('qh__nav-minor--expanded');
          },
          function () {
            qh_nav_minor.classList.remove('qh__nav-minor--expanded');
            qh_toggle_btn.setAttribute('aria-expanded', 'false');
            deactivatePageOverlay();
          }
        );
      });
    }
  };
})(Drupal, once);
