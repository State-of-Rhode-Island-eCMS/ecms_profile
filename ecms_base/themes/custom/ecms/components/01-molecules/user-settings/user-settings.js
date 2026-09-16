/**
 * @file User settings (accessibility) panel behavior.
 *
 * Wires the settings + language toggles, light/dark mode, and the font-size /
 * line-height / word-spacing sliders.
 *
 * Bound via Drupal.behaviors + once instead of window.onload / a one-time
 * DOMContentLoaded listener, so it attaches whenever the settings panel is
 * present — including content delivered after initial page load (e.g. BigPipe
 * for authenticated users).
 *
 * a11yClick / allMenuCloser / activatePageOverlay / deactivatePageOverlay /
 * getCookie are global helpers defined in the theme's global.js
 * (ecms/global-styling).
 */

(function (Drupal, once) {
  'use strict';

  function handleFontSizeSliderUpdate(e) {
    document.documentElement.style.setProperty("--fontSizeModifier", this.value);
    document.cookie = "fontSizeModifier=" + this.value + "; max-age=31536000; path=/; samesite=strict; domain=.ri.gov";
  }

  function fontSizeSliderSet() {
    var fontSizeModifier = getComputedStyle(document.documentElement).getPropertyValue('--fontSizeModifier');
    var fontSizeElement = document.getElementById('font_size_modifier');
    if (fontSizeElement !== null && fontSizeElement !== undefined) {
      fontSizeElement.setAttribute('value', fontSizeModifier.trim());
    }
  }

  function handleLineHeightSliderUpdate(e) {
    document.documentElement.style.setProperty("--lineHeightModifier", this.value);
    document.cookie = "lineHeightModifier=" + this.value + "; max-age=31536000; path=/; samesite=strict; domain=.ri.gov";
  }

  function lineHeightSliderSet() {
    var lineHeightModifier = getComputedStyle(document.documentElement).getPropertyValue('--lineHeightModifier');
    var lineSpaceElement = document.getElementById('line_height_modifier');
    if (lineSpaceElement !== null && lineSpaceElement !== undefined) {
      lineSpaceElement.setAttribute('value', lineHeightModifier.trim());
    }
  }

  function handleWordSpaceSliderUpdate(e) {
    document.documentElement.style.setProperty("--wordSpaceModifier", this.value);
    document.cookie = "wordSpaceModifier=" + this.value + "; max-age=31536000; path=/; samesite=strict; domain=.ri.gov";
  }

  function wordSpaceSliderSet() {
    var wordSpaceModifier = getComputedStyle(document.documentElement).getPropertyValue('--wordSpaceModifier');
    var wordSpaceElement = document.getElementById('word_space_modifier');
    if (wordSpaceElement !== null && wordSpaceElement !== undefined) {
      wordSpaceElement.setAttribute('value', wordSpaceModifier.trim());
    }
  }

  Drupal.behaviors.ecmsUserSettings = {
    attach: function (context) {
      once('ecms-user-settings', '#js__user-settings__toggle', context).forEach(function (qh_usersettings_btn) {

        // Variable-font support: prep the sliders, or hide them if unsupported.
        var vfSupport = "CSS" in window && "supports" in CSS && CSS.supports("(font-variation-settings: normal)");
        if (vfSupport === true) {
          fontSizeSliderSet();
          lineHeightSliderSet();
          wordSpaceSliderSet();
        } else {
          ['qh-fontsize', 'qh-lineheight', 'qh-wordspace'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
              el.style.display = 'none';
            }
          });
        }

        // Settings toggle.
        qh_usersettings_btn.addEventListener('click', function (event) {
          // a11yClick function restricts keypress to spacebar or enter
          if (a11yClick(event) === true) {
            var expanded = qh_usersettings_btn.getAttribute('aria-expanded');
            if (expanded == 'true') {
              qh_usersettings_btn.setAttribute('aria-expanded', 'false');
              deactivatePageOverlay();
            } else {
              allMenuCloser();
              qh_usersettings_btn.setAttribute('aria-expanded', 'true');
              activatePageOverlay();
            }
          }
        });

        // Close the settings menu when keyboard focus leaves it or on Escape
        // (RIGA-891). The parent wrapper contains both the toggle and the
        // panel list.
        qhMenuFocusDismiss(
          qh_usersettings_btn.parentElement,
          qh_usersettings_btn,
          function () {
            return qh_usersettings_btn.getAttribute('aria-expanded') === 'true';
          },
          function () {
            qh_usersettings_btn.setAttribute('aria-expanded', 'false');
            deactivatePageOverlay();
          }
        );

        // Language toggle.
        var qh_userlanguage_btn = document.getElementById('js__user-language__toggle');
        if (qh_userlanguage_btn !== null && qh_userlanguage_btn !== undefined) {
          qh_userlanguage_btn.addEventListener('click', function (event) {
            // a11yClick function restricts keypress to spacebar or enter
            if (a11yClick(event) === true) {
              var expanded = qh_userlanguage_btn.getAttribute('aria-expanded');
              if (expanded == 'true') {
                qh_userlanguage_btn.setAttribute('aria-expanded', 'false');
                deactivatePageOverlay();
              } else {
                allMenuCloser();
                qh_userlanguage_btn.setAttribute('aria-expanded', 'true');
                activatePageOverlay();
              }
            }
          });

          // Close the language menu when keyboard focus leaves it or on
          // Escape (RIGA-891).
          qhMenuFocusDismiss(
            qh_userlanguage_btn.parentElement,
            qh_userlanguage_btn,
            function () {
              return qh_userlanguage_btn.getAttribute('aria-expanded') === 'true';
            },
            function () {
              qh_userlanguage_btn.setAttribute('aria-expanded', 'false');
              deactivatePageOverlay();
            }
          );
        }

        // Light mode settings
        const lightModeToggle = document.getElementById('light_mode_switch');
        const lightModeReset = document.getElementById('light_mode_reset');

        if (lightModeToggle !== null && lightModeToggle !== undefined) {
          lightModeToggle.addEventListener('click', function (e) {
            e.preventDefault();

            // Always check the value of the custom property before determining state.
            if (getComputedStyle(document.documentElement).getPropertyValue('--osLightMode').trim() == 'dark' && !document.getElementsByTagName("html")[0].classList.contains('light')) {
              document.cookie = "lightMode=light; max-age=31536000; path=/; samesite=strict; domain=.ri.gov";
              document.getElementsByTagName("html")[0].classList.remove('dark');
              document.getElementsByTagName("html")[0].classList.add('light');
            } else if (getComputedStyle(document.documentElement).getPropertyValue('--osLightMode').trim() == 'light' && document.getElementsByTagName("html")[0].classList.contains('dark')) {
              document.cookie = "lightMode=dark; max-age=31536000; path=/; samesite=strict; domain=.ri.gov";
              document.getElementsByTagName("html")[0].classList.remove('dark');
              document.getElementsByTagName("html")[0].classList.add('light');
            } else {
              // set a cookie to save the setting
              document.cookie = "lightMode=dark; max-age=31536000; path=/; samesite=strict; domain=.ri.gov";
              document.getElementsByTagName("html")[0].classList.remove('light');
              document.getElementsByTagName("html")[0].classList.add('dark');
            }
          });
        }

        if (lightModeReset !== null && lightModeReset !== undefined) {
          lightModeReset.addEventListener('click', function (e) {
            e.preventDefault();
            document.cookie = "lightMode=auto; max-age=31536000; path=/; samesite=strict; domain=.ri.gov";

            // Remove any current body classes.
            document.getElementsByTagName("html")[0].classList.remove('dark');
            document.getElementsByTagName("html")[0].classList.remove('light');
          });
        }

        // Font size settings
        var fontSizeSlider = document.getElementById('font_size_modifier');
        if (fontSizeSlider !== null && fontSizeSlider !== undefined) {
          fontSizeSlider.addEventListener('change', handleFontSizeSliderUpdate);
        }

        // Line-height settings
        var lineHeightSlider = document.getElementById('line_height_modifier');
        if (lineHeightSlider !== null && lineHeightSlider !== undefined) {
          lineHeightSlider.addEventListener('change', handleLineHeightSliderUpdate);
        }

        // Word space settings
        var wordSpaceSlider = document.getElementById('word_space_modifier');
        if (wordSpaceSlider !== null && wordSpaceSlider !== undefined) {
          wordSpaceSlider.addEventListener('change', handleWordSpaceSliderUpdate);
        }
      });
    }
  };
})(Drupal, once);
