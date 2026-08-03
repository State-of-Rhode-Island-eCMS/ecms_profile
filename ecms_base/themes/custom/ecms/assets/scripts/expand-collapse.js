/**
 * @file Generic expand / collapse utility.
 *
 * Minimum expected markup:
 *   <div>
 *     <div>
 *       <button id="summaryId" class="js__expand-collapse" aria-expanded="false" aria-controls="targetId">See More</button>
 *     </div>
 *     <div id="targetId" aria-labelledby="summaryId" class="">Content to reveal here</div>
 *   </div>
 *
 * This ONLY toggles a show/hide class on the target and toggles aria-expanded.
 * Any other functionality (like swapping the text content if true/false) needs
 * to live in the relevant component JS.
 *
 * After each toggle the trigger dispatches a bubbling `ecms:expand-collapse`
 * event carrying the resulting state. Component JS that needs to react to a
 * toggle must listen for that event rather than adding its own `click` handler:
 * a second click handler reads whatever `aria-expanded` happens to be at the
 * moment it runs, which depends on listener registration order and silently
 * inverts if the load order of the two scripts ever changes.
 *
 * Bound via Drupal.behaviors + once so the handler also attaches to triggers
 * delivered after initial page load. For authenticated users Drupal streams
 * parts of the page (e.g. the minor/section navigation) via BigPipe after
 * DOMContentLoaded has already fired; a one-time DOMContentLoaded binding would
 * miss those triggers and the accordion would never open.
 *
 * a11yClick is a global helper defined in global.js.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ecmsExpandCollapse = {
    attach: function (context) {
      once('ecms-expand-collapse', '.js__expand-collapse', context).forEach(function (toggle_element) {
        toggle_element.addEventListener('click', function (event) {
          event.preventDefault();
          if (a11yClick(event) === true) {
            var expanded = toggle_element.getAttribute('aria-expanded');
            var target_id = toggle_element.getAttribute('aria-controls');
            var target_element = document.getElementById(target_id);

            if (expanded == 'true') {
              toggle_element.setAttribute('aria-expanded', 'false');
              target_element.classList.remove('js__aria-expanded');
            } else {
              toggle_element.setAttribute('aria-expanded', 'true');
              target_element.classList.add('js__aria-expanded');
            }

            toggle_element.dispatchEvent(new CustomEvent('ecms:expand-collapse', {
              bubbles: true,
              detail: {
                expanded: expanded != 'true',
                target: target_element
              }
            }));
          }
        });
      });
    }
  };
})(Drupal, once);
