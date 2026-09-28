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

      // Publish half the scrollbar width as a custom property. vw units
      // include the scrollbar but the page grid centers inside the
      // scrollbar-less width, so any CSS that mirrors the page gutter with
      // 3.5vw (e.g. the section-menu edge tab in _navigation-minor.scss)
      // overshoots by this much when classic scrollbars are on. 0 for
      // overlay scrollbars, so the CSS fallback of 0px matches no-JS.
      once('ecms-scrollbar-comp', 'html', context).forEach(function (html) {
        function setScrollbarComp() {
          var comp = (window.innerWidth - html.clientWidth) / 2;
          html.style.setProperty('--qh-scrollbar-comp', comp + 'px');
        }
        setScrollbarComp();
        window.addEventListener('resize', setScrollbarComp);
      });
    }
  };
})(Drupal, once);
