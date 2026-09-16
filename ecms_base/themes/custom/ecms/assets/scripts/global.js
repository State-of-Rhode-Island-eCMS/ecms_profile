// Global helper functions
//
// Shared utilities used across the theme's behaviors and component scripts.
// These are intentionally global (not wrapped in a behavior) so that other
// scripts can call them. The behaviors that use them live in their own files
// (page-init.js, expand-collapse.js, responsive-tables.js) and are bound via
// Drupal.behaviors so they re-run for content added after initial page load
// (e.g. BigPipe placeholders streamed in for authenticated users, or AJAX).

// Missing forEach on NodeList for IE11
// SCRIPT438: Object does not support property or method forEach
if (window.NodeList && !NodeList.prototype.forEach) {
  NodeList.prototype.forEach = Array.prototype.forEach;
}

// Check for mouse clicks, enter keypress (13), or spacebar keypress (32)
// https://karlgroves.com/2014/11/24/ridiculously-easy-trick-for-keyboard-accessibility
function a11yClick(event){
  if(event.type === 'click') {
    return true;
  } else if(event.type === 'keypress') {
    var code = event.charCode || event.keyCode;
    if((code === 32) || (code === 13)) {
      return true;
    }
  } else {
    return false;
  }
}

// Menu closer function
function allMenuCloser() {

  // Close main nav
  var qh_toggle_btn = document.getElementById('js__toggle-nav');
  if (qh_toggle_btn !== null) {
    qh_toggle_btn.setAttribute('aria-expanded', 'false');
  }

  // Close any open primary-nav drop downs (RIGA-891 — these were previously
  // left open by this closer)
  document.querySelectorAll('.js__qh-dd-toggle[aria-expanded="true"]').forEach(function (dd_toggle) {
    dd_toggle.setAttribute('aria-expanded', 'false');
  });

  // Close sidebar nav
  var qh_nav_minor = document.getElementById('js__minor-menu');
  if (qh_nav_minor !== null) {
    qh_nav_minor.classList.remove('qh__nav-minor--expanded');
  }

  // Keep the sidebar nav toggle's ARIA state in sync (RIGA-891)
  var qh_minor_toggle = document.getElementById('js__minor-toggle');
  if (qh_minor_toggle !== null) {
    qh_minor_toggle.setAttribute('aria-expanded', 'false');
  }

  // Close settings nav
  var qh_usersettings_btn = document.getElementById('js__user-settings__toggle');
  if (qh_usersettings_btn !== null) {
    qh_usersettings_btn.setAttribute('aria-expanded', 'false');
  }

  // Close language nav
  var qh_userlanguage_btn = document.getElementById('js__user-language__toggle');
  if (qh_userlanguage_btn !== null) {
    qh_userlanguage_btn.setAttribute('aria-expanded', 'false');
  }

  // Close gtranslate nav
  var qh_gtranslate_btn = document.getElementById('js__gtranslate__toggle');
  if (qh_gtranslate_btn !== null) {
    qh_gtranslate_btn.setAttribute('aria-expanded', 'false');
  }
}

// Close a menu when keyboard focus leaves it, and on Escape (RIGA-891).
// Menus previously stayed open when a keyboard user tabbed past them, leaving
// an expanded panel floating over the page with no way to dismiss it.
//
// container - element wrapping BOTH the toggle and the panel (focus moving
//             between them must not count as leaving)
// toggle    - the button that opens the menu; receives focus again on Escape
// isOpen    - function returning whether the menu is currently open; nothing
//             happens while closed (so Escape elsewhere never steals focus)
// closeFn   - function that actually closes the menu
function qhMenuFocusDismiss(container, toggle, isOpen, closeFn) {
  if (container === null || container === undefined) {
    return;
  }

  container.addEventListener('focusout', function (event) {
    // relatedTarget is null when focus leaves the document (e.g. window blur);
    // keep the menu open in that case.
    if (isOpen() && event.relatedTarget !== null && !container.contains(event.relatedTarget)) {
      closeFn();
    }
  });

  container.addEventListener('keydown', function (event) {
    if ((event.key === 'Escape' || event.key === 'Esc') && isOpen()) {
      // Only close the innermost open menu (e.g. a drop down inside the
      // mobile drawer) — a second Escape closes the next level up.
      event.stopPropagation();
      closeFn();
      if (toggle !== null && toggle !== undefined) {
        toggle.focus();
      }
    }
  });
}

// Add screen overlay
function addPageOverlay() {
  var pageOverlay = document.createElement('div');
  var divContent = document.createTextNode(' ');
  pageOverlay.appendChild(divContent);
  pageOverlay.id = 'page_overlay';
  pageOverlay.classList.add('page-overlay');
  document.getElementsByTagName('body')[0].appendChild(pageOverlay);

  if (pageOverlay) {
    document.getElementsByTagName('html')[0].classList.add('touch-nav');

    pageOverlay.addEventListener('click', function(e) {
      e.preventDefault();
      allMenuCloser();
      deactivatePageOverlay();
    });
  }
}

function deactivatePageOverlay() {
  var pageOverlay = document.getElementById('page_overlay');
  pageOverlay.classList.remove('active');
}

function activatePageOverlay() {
  var pageOverlay = document.getElementById('page_overlay');
  pageOverlay.classList.add('active');
}

// forEach function from Todd Motto's  blog: https://toddmotto.com/ditch-the-array-foreach-call-nodelist-hack/
var _forEach = function (array, callback, scope) {
  for (var i = 0; i < array.length; i++) {
    callback.call(scope, i, array[i]); // passes back stuff we need
  }
};


// Cookie getter
function getCookie(name) {
  var value = '; ' + document.cookie;
  var parts = value.split('; ' + name + '=');
  if (parts.length == 2) return parts.pop().split(';').shift();
}
