/**
 * @file
 * Navigation tabs
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.themagTabs = {
    attach: function (context, settings) {
      var tabs = $(context).find('.tabs');
      var tabsToggleButtomn = $(context).find('.js-toggle-primary-tabs');

      // Set .cart-block--contents top position according header height.
      if(tabs.length) {
        tabsToggleButtomn.once().on('click', function () {
          tabs.toggleClass('active');
        });
      }
    }
  }
})(jQuery, Drupal);
