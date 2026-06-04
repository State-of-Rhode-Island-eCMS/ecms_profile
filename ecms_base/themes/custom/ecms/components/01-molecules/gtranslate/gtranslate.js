/**
 * @file Google Translate language selector behavior.
 *
 * Toggles the language popup and closes it when a language is chosen.
 *
 * Bound via Drupal.behaviors + once instead of a one-time DOMContentLoaded
 * listener, so it attaches whenever the selector is present — including content
 * delivered after initial page load (e.g. BigPipe for authenticated users).
 *
 * a11yClick / allMenuCloser / activatePageOverlay / deactivatePageOverlay are
 * global helpers defined in the theme's global.js (ecms/global-styling).
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ecmsGtranslate = {
    attach: function (context) {
      once('ecms-gtranslate', '#js__gtranslate__toggle', context).forEach(function (qh_gtranslate_btn) {
        var qh_gtranslate_list = document.getElementById("js__gtranslate__list");

        qh_gtranslate_btn.addEventListener("click", function (event) {
          // a11yClick function restricts keypress to spacebar or enter
          if (a11yClick(event) === true) {
            var expanded = qh_gtranslate_btn.getAttribute("aria-expanded");
            if (expanded == "true") {
              qh_gtranslate_btn.setAttribute("aria-expanded", "false");
              deactivatePageOverlay();
            } else {
              allMenuCloser();
              qh_gtranslate_btn.setAttribute("aria-expanded", "true");
              activatePageOverlay();
            }
          }
        });

        // Close popup when a language is selected.
        if (qh_gtranslate_list !== null && qh_gtranslate_list !== undefined) {
          // Listen for clicks on quick language links
          qh_gtranslate_list.addEventListener("click", function (event) {
            if (event.target.classList.contains("glink")) {
              // Close the popup
              qh_gtranslate_btn.setAttribute("aria-expanded", "false");
              deactivatePageOverlay();
            }
          });
        }
      });
    }
  };
})(Drupal, once);
