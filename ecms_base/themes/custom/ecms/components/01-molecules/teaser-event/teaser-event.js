/**
 * @file Event teaser repeat-rule (RRULE) humanizer.
 *
 * Replaces each teaser's machine RRULE string with human-readable text.
 *
 * Bound via Drupal.behaviors + once so it also processes teasers delivered
 * after initial page load. Event teasers render in views/listings within main
 * content, which Drupal streams in via BigPipe for authenticated users after
 * DOMContentLoaded has fired — a one-time binding would leave the raw RRULE
 * string showing.
 *
 * window.rrule is provided by the ecms/rrule-js library.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ecmsTeaserEvent = {
    attach: function (context) {
      once('ecms-teaser-event-rrule', '.qh__teaser-event__time-rrule', context).forEach(function (value) {
        var rrule_string = value.dataset.rrule;
        if (window.rrule && rrule_string) {
          value.innerHTML = window.rrule.rrulestr(rrule_string).toText();
        }
      });
    }
  };
})(Drupal, once);
