/**
 * @file Canvas Tab Group — builds the tablist and wires keyboard navigation.
 *
 * Each Canvas Tab component renders a [data-canvas-tab-button] and a
 * [data-canvas-tab-panel] inside a [data-canvas-tab] wrapper. This script
 * collects those buttons into a shared tablist, assigns ARIA attributes, and
 * implements the W3C ARIA Tabs keyboard pattern.
 *
 * https://www.w3.org/WAI/ARIA/apg/patterns/tabs/
 *
 * The DOM manipulation (moving buttons into a shared tablist) is skipped when
 * running inside Canvas's preview iframe. Canvas renders pages in a same-origin
 * iframe whose element carries data-canvas-preview="true"; window.frameElement
 * exposes that attribute to the inner document. All Tab components remain
 * intact and individually selectable for editing in that context.
 */

(function (Drupal, once) {
  'use strict';

  let groupCounter = 0;

  /**
   * Activate a tab button and show its paired panel.
   *
   * @param {HTMLElement} button - The tab button to activate.
   * @param {HTMLElement[]} allButtons - Every button in this group.
   */
  function activateTab(button, allButtons) {
    allButtons.forEach((b) => {
      b.setAttribute('aria-selected', 'false');
      b.setAttribute('tabindex', '-1');
      b.classList.remove('canvas-tab__button--active');

      const panel = document.getElementById(b.getAttribute('aria-controls'));
      if (panel) {
        panel.setAttribute('hidden', '');
      }
    });

    button.setAttribute('aria-selected', 'true');
    button.setAttribute('tabindex', '0');
    button.classList.add('canvas-tab__button--active');

    const activePanel = document.getElementById(button.getAttribute('aria-controls'));
    if (activePanel) {
      activePanel.removeAttribute('hidden');
    }
  }

  /**
   * Initialise a single tab group widget.
   *
   * @param {HTMLElement} group
   */
  function initGroup(group) {
    const tabEls = Array.from(group.querySelectorAll(':scope > [data-canvas-tab]'));
    if (!tabEls.length) return;

    const id = ++groupCounter;

    // Build tablist element and prepend it to the group.
    const tablist = document.createElement('div');
    tablist.className = 'canvas-tab-group__tablist';
    tablist.setAttribute('role', 'tablist');
    group.insertBefore(tablist, group.firstChild);

    const buttons = [];

    tabEls.forEach((tabEl, i) => {
      const button = tabEl.querySelector('[data-canvas-tab-button]');
      const panel = tabEl.querySelector('[data-canvas-tab-panel]');
      if (!button || !panel) return;

      const tabId = `canvas-tab-${id}-${i + 1}`;
      const panelId = `canvas-panel-${id}-${i + 1}`;

      // Wire ARIA.
      button.id = tabId;
      button.setAttribute('role', 'tab');
      button.setAttribute('aria-controls', panelId);
      button.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
      button.setAttribute('tabindex', i === 0 ? '0' : '-1');
      if (i === 0) button.classList.add('canvas-tab__button--active');

      panel.id = panelId;
      panel.setAttribute('role', 'tabpanel');
      panel.setAttribute('aria-labelledby', tabId);
      panel.setAttribute('tabindex', '0');
      if (i !== 0) panel.setAttribute('hidden', '');

      // Move button from its canvas-tab wrapper into the shared tablist.
      tablist.appendChild(button);
      buttons.push(button);
    });

    // Click and keyboard handling.
    buttons.forEach((button, index) => {
      button.addEventListener('click', () => activateTab(button, buttons));

      button.addEventListener('keydown', (event) => {
        let newIndex = null;

        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
          newIndex = (index + 1) % buttons.length;
        } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
          newIndex = (index - 1 + buttons.length) % buttons.length;
        } else if (event.key === 'Home') {
          newIndex = 0;
        } else if (event.key === 'End') {
          newIndex = buttons.length - 1;
        }

        if (newIndex !== null) {
          event.preventDefault();
          activateTab(buttons[newIndex], buttons);
          buttons[newIndex].focus();
        }
      });
    });
  }

  Drupal.behaviors.canvasTabGroup = {
    attach(context) {
      // Canvas renders the page inside a same-origin iframe whose element has
      // data-canvas-preview="true". Skip DOM manipulation in that context so
      // every Tab component stays intact and is individually selectable for
      // editing.
      try {
        if (
          window.frameElement &&
          window.frameElement.getAttribute('data-canvas-preview') === 'true'
        ) {
          return;
        }
      } catch (e) {
        // Cross-origin frameElement access throws; not a Canvas preview frame.
      }

      once('canvas-tab-group', '[data-canvas-tab-group]', context).forEach(initGroup);
    },
  };
})(Drupal, once);
