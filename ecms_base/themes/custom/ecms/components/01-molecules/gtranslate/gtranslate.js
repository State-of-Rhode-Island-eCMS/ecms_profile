document.addEventListener("DOMContentLoaded", function() {

  var qh_gtranslate_btn = document.getElementById('js__gtranslate__toggle');

  if (qh_gtranslate_btn !== null && qh_gtranslate_btn !== undefined) {
    qh_gtranslate_btn.addEventListener('click', function(event) {
      // a11yClick function restricts keypress to spacebar or enter
      if (a11yClick(event) === true) {
        var expanded = qh_gtranslate_btn.getAttribute('aria-expanded');
        if (expanded == 'true') {
          qh_gtranslate_btn.setAttribute('aria-expanded', 'false');
          deactivatePageOverlay();
        } else {
          allMenuCloser();
          qh_gtranslate_btn.setAttribute('aria-expanded', 'true');
          activatePageOverlay();
        }
      }
    });
  }

});
