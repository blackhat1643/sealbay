/* Admin panel: confirmation prompts for destructive forms. */
(function () {
  'use strict';
  document.addEventListener('submit', function (ev) {
    var message = ev.target.getAttribute('data-confirm');
    if (message && !window.confirm(message)) {
      ev.preventDefault();
    }
  });
})();
