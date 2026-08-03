/**
 * @file Page-level initialization behavior.
 *
 * Swaps the no-js class for js, and adds the screen overlay element used when
 * a menu is open. Bound via Drupal.behaviors (not a one-time DOMContentLoaded
 * listener) so it also runs when Drupal delivers content after initial load.
 * once() keys the html/body so this only happens a single time.
 *
 * a11yClick / addPageOverlay are global helpers defined in global.js.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ecmsPageInit = {
    attach: function (context) {

      // If JS is loaded, change the no-js class.
      once('ecms-js-class', 'html', context).forEach(function (html) {
        html.classList.remove('no-js');
        html.classList.add('js');
      });

      // Add an empty element that is styled when a menu is open.
      once('ecms-page-overlay', 'body', context).forEach(function () {
        addPageOverlay();
      });
    }
  };
})(Drupal, once);
