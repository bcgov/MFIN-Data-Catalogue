/**
 * @file
 * Toggle search block
 *
 * Toggle header search block by clicking on the search icon.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.themagToggleSearchBlock = {
    attach: function (context, settings) {
      $(context).find('.js-toggle-search')
        .once().on('click', function (event) {
        event.preventDefault();
        $(this).toggleClass('active');
        $(context).find('.region-search').toggleClass('active');
      });
    }
  }
})(jQuery, Drupal);