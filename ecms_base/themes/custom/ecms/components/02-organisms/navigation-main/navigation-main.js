/**
 * @file Main (primary) navigation behavior.
 *
 * Handles the responsive relocation of search/social into the mobile drawer,
 * the mobile menu toggle, and the desktop dropdown toggles.
 *
 * Bound via Drupal.behaviors + once so the handlers also attach when the
 * navigation is delivered after initial page load. For authenticated users
 * Drupal streams the primary navigation in via BigPipe after DOMContentLoaded
 * has fired, so a one-time DOMContentLoaded binding never wires up the dropdown
 * toggles — leaving their real hrefs in place, so clicking just navigates.
 *
 * a11yClick / allMenuCloser / activatePageOverlay / deactivatePageOverlay are
 * global helpers defined in the theme's global.js (ecms/global-styling).
 */

// There are custom properties that track what breakpoint the site is using
function getQhNavState() {
  // NOTE: Strings from CSS get returned exactly as written
  // To check them in JS for true/false, they need to be written exactly
  //
  // Example:
  //   --nav-state: 'mobile';
  // Can be checked with ' \"mobile\"';
  //   --nav-state:mobile;
  // Can be checked with 'mobile';
  //
  // The strings are VERY literal
  //
  var qh_body = document.getElementsByTagName('body')[0];
  return getComputedStyle(qh_body).getPropertyValue("--nav-state");
}


// Mobile: Move the Search component into the Navigation drawer
// Benefit of insertBefore is that it moves the element, not a copy of the element
function moveSearchIntoNav(direction) {
  var qh_search = document.getElementById('js__site-search');
  var qh_header = document.getElementById('js__search-social');
  var qh_mainnav = document.getElementById('js__primary-menu');

  //console.log('moveSearchIntoNav fired');

  if (qh_search !== null && qh_search !== undefined) {
    // Which direction is this going?
    if (direction === 'toNav') {
      // Move from header to nav drawer
      if (qh_mainnav !== null && qh_mainnav !== undefined) {
        // Create a new <li>
        var qh_new_li = document.createElement('li');
        qh_new_li.classList.add('qh__nav-main__item__search');
        // Add the HTML of the search inside of it
        qh_new_li.appendChild(qh_search);
        // insert before the first child
        qh_mainnav.insertBefore(qh_new_li, qh_mainnav.firstChild);
      }
    } else {
      // Move from nav drawer to header
      if (qh_header !== null && qh_mainnav !== undefined) {
        // Append search to the header container
        qh_header.appendChild(qh_search);
        // Grab the previous search LI we created
        var qh_prev_li = document.getElementsByClassName('qh__nav-main__item__search');
        if (qh_prev_li[0] !== null && qh_prev_li[0] !== undefined) {
          qh_prev_li[0].parentNode.removeChild(qh_prev_li[0]);
        }
      }
    }
  }
}

// Mobile: Move the Social List component into the Navigation drawer
function moveSocialIntoNav(direction) {
  var qh_social = document.getElementById('js__social-links');
  var qh_header = document.getElementById('js__search-social');
  var qh_mainnav = document.getElementById('js__primary-menu');

  //console.log('moveSocialIntoNav fired');

  if (qh_social !== null && qh_social !== undefined) {
    // Which direction is this going?
    if (direction === 'toNav') {
      // Move from header to nav drawer
      if (qh_mainnav !== null && qh_mainnav !== undefined) {
        // Create a new <li>
        var qh_new_li = document.createElement('li');
        qh_new_li.classList.add('qh__nav-main__item__social');
        // Add the HTML of the social list inside of it
        qh_new_li.appendChild(qh_social);
        // insert after the last child
        qh_mainnav.insertBefore(qh_new_li, qh_mainnav.lastChild.nextSibling);
      }
    } else {
      // Move from nav drawer to header
      if (qh_header !== null && qh_header !== undefined) {
        // Append search to the header container
        qh_header.appendChild(qh_social);
        // Grab the previous search LI we created
        var qh_prev_li = document.getElementsByClassName('qh__nav-main__item__social');
        if (qh_prev_li[0] !== null && qh_prev_li[0] !== undefined) {
          qh_prev_li[0].parentNode.removeChild(qh_prev_li[0]);
        }
      }
    }
  }
}

function moveSearchAndSocial() {
  var qh_viewport_again = getQhNavState();
  if (qh_viewport_again.trim() !== qh_viewport.trim()) {
    if (qh_viewport_again.trim() === 'js-mobile') {
      //console.log('toNav – qh_viewport= ' + qh_viewport);
      //console.log('qh_viewport_again= ' + qh_viewport_again);
      moveSearchIntoNav('toNav');
      moveSocialIntoNav('toNav');
    } else {
      //console.log('fromNav – qh_viewport= ' + qh_viewport);
      //console.log('qh_viewport_again= ' + qh_viewport_again);
      moveSocialIntoNav('fromNav');
      moveSearchIntoNav('fromNav');
    }
    // The two are different, reset the global variable
    window.qh_viewport = qh_viewport_again;
  }
}


(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ecmsNavigationMain = {
    attach: function (context) {

      // Responsive search/social relocation. Keyed on the primary menu so it
      // sets up once when the nav is present (initial load or BigPipe stream).
      once('qh-nav-responsive', '#js__primary-menu', context).forEach(function () {
        // Declare the viewport state globally
        window.qh_viewport = '';

        // Call on page ready
        moveSearchAndSocial();

        // Call again on resize or orientation change
        // With a debounced window resize handler
        var qh_window_timeout = false;
        window.addEventListener('resize', function () {
          clearTimeout(qh_window_timeout);
          qh_window_timeout = setTimeout(moveSearchAndSocial, 250);
        });
        window.addEventListener('orientationchange', moveSearchAndSocial);
      });


      // Toggle button for mobile menu
      // Activates/deactivates page overlay
      // Also open/close off canvas menu
      // NOT globalized because of page overlay actions
      once('qh-toggle-nav', '#js__toggle-nav', context).forEach(function (qh_toggle_btn) {
        qh_toggle_btn.addEventListener('click', function (event) {
          // a11yClick function restricts keypress to spacebar or enter
          if (a11yClick(event) === true) {
            var expanded = qh_toggle_btn.getAttribute('aria-expanded');
            if (expanded == 'true') {
              qh_toggle_btn.setAttribute('aria-expanded', 'false');
              qh_toggle_btn.parentElement.classList.remove('open');
              deactivatePageOverlay();
            } else {
              allMenuCloser();
              qh_toggle_btn.setAttribute('aria-expanded', 'true');
              qh_toggle_btn.parentElement.classList.add('open');
              activatePageOverlay();
            }
          }
        });

        // Close the drawer when keyboard focus leaves it or on Escape
        // (RIGA-891). The parent wrapper contains both the toggle and the
        // drawer list.
        qhMenuFocusDismiss(
          qh_toggle_btn.parentElement,
          qh_toggle_btn,
          function () {
            return qh_toggle_btn.getAttribute('aria-expanded') === 'true';
          },
          function () {
            qh_toggle_btn.setAttribute('aria-expanded', 'false');
            qh_toggle_btn.parentElement.classList.remove('open');
            deactivatePageOverlay();
          }
        );
      });


      // Listen to mouse press, spacebar key press, and enter key press on Drop Down menu parents
      // Toggle the value of aria-expanded but also remove the content of href on parent
      once('qh-dd-toggle', '.js__qh-dd-toggle', context).forEach(function (toggle_element) {
        // Remove the contents of the href from this parent button
        toggle_element.setAttribute('href', '#');
        toggle_element.addEventListener('click', function (event) {
          // a11yClick function restricts keypress to spacebar or enter
          if (a11yClick(event) === true) {
            event.preventDefault();
            var expanded = toggle_element.getAttribute('aria-expanded');
            // Close all
            document.querySelectorAll('.js__qh-dd-toggle').forEach(function (btn) {
              btn.setAttribute('aria-expanded', 'false');
            });
            // Open the one that was pressed
            if (expanded == 'false') {
              toggle_element.setAttribute('aria-expanded', 'true');
            }
          }
        });

        // Close the drop down when keyboard focus leaves it or on Escape
        // (RIGA-891). The parent <li> contains both the toggle link and its
        // sub menu / mega menu panel.
        qhMenuFocusDismiss(
          toggle_element.parentElement,
          toggle_element,
          function () {
            return toggle_element.getAttribute('aria-expanded') === 'true';
          },
          function () {
            toggle_element.setAttribute('aria-expanded', 'false');
          }
        );
      });

    }
  };
})(Drupal, once);
