/**
 * @file Gallery slider behavior.
 *
 * Initializes a tiny-slider (tns) instance for each gallery paragraph.
 *
 * Bound via Drupal.behaviors + once so it also initializes galleries delivered
 * after initial page load. Galleries render in node body content, which Drupal
 * streams in via BigPipe for authenticated users after DOMContentLoaded has
 * fired — a one-time DOMContentLoaded binding would leave them uninitialized.
 *
 * tns is provided by the ecms/tiny-slider library.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ecmsGallery = {
    attach: function (context) {
      once('ecms-gallery', '.qh__gallery__slider', context).forEach(function (value) {
        var pid = value.dataset.pid;
        tns({
          container: value,
          items: 1,
          controlsContainer: "#qh__gallery__controls-" + pid,
          navContainer: "#qh__gallery__nav-" + pid,
          mouseDrag: true,
          loop: true,
        });
      });
    }
  };
})(Drupal, once);
