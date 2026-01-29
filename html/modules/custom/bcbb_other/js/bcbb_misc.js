/**
 * @file
 * Miscellaneous JS that can safely be on every page.
 */

/*jslint browser indent2 long*/

(function (document) {

  /**
   * Event listener for DOMContentLoaded.
   */
  document.addEventListener("DOMContentLoaded", function () {
    var is_wide_cur;

    // Open/close `details` elements with class `bcbb-desktop-open` when the
    // window width crosses the wide breakpoint.
    const open_close_details = function () {
      var is_wide_new = window.matchMedia("(min-width: 992px)").matches;
      if (is_wide_cur !== is_wide_new) {
        is_wide_cur = is_wide_new;
        document.querySelectorAll("details.bcbb-desktop-open").forEach(function (details) {
          details.open = is_wide_cur;
        });
      }
    };
    // Run above on page load.
    open_close_details();
    // Run the above when the window width changes.
    window.addEventListener("resize", open_close_details);
  });

}(document));
