((Drupal, once) => {
  Drupal.behaviors.canvasHeroSearch = {
    attach(context) {
      once('canvas-hero-search', '.canvas-hero-search', context).forEach((root) => {
        const manifestEl = root.querySelector('.canvas-hero-search__manifest');
        if (!manifestEl) return;

        let entries;
        try {
          entries = JSON.parse(manifestEl.textContent);
        } catch (_) {
          return;
        }
        if (!Array.isArray(entries) || entries.length === 0) return;

        const pick = entries[Math.floor(Math.random() * entries.length)];
        if (!pick || !pick.file) return;

        const base = root.dataset.canvasHeroSearchImageBase || '';
        const src = base + pick.file;
        root.style.setProperty('--hero-image', `url('${src}')`);

        const imageEl = root.querySelector('.canvas-hero-search__image');
        if (imageEl) {
          if (pick.alt) {
            imageEl.setAttribute('aria-label', pick.alt);
            imageEl.removeAttribute('aria-hidden');
          } else {
            imageEl.removeAttribute('aria-label');
            imageEl.setAttribute('aria-hidden', 'true');
          }
        }

        const locationEl = root.querySelector('[data-canvas-hero-search-location]');
        const locationText = root.querySelector('.canvas-hero-search__location-text');
        if (locationEl && locationText) {
          if (pick.location) {
            locationText.textContent = pick.location;
            locationEl.hidden = false;
          } else {
            locationEl.hidden = true;
          }
        }
      });
    },
  };
})(Drupal, once);
