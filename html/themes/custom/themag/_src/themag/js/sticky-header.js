/**
 * @file
 * TheMAG settings and variables
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  var wrapper = $('.js-page-header');
  var stickyElement = wrapper.find('.js-sticky-header-element');
  var stickyElementTop = stickyElement.offset().top;

  var placeholder = $('<div class="js-sticky-header-placeholder"></div>');
  stickyElement.wrap(placeholder);

  $(window).on('load scroll', function(evt) {

    if($(this).scrollTop() >= stickyElementTop) {
      stickyElement.addClass('header-sticky-wrapper is--stuck');
    } else {
      stickyElement.removeClass('header-sticky-wrapper is--stuck');
    }
  });

  //
  // Adjust sticky header if Drupal Admin Toolbar is enabled.
  //
  if(drupalSettings.toolbar != null) {
    var toolbar = $('#toolbar-bar');
    var toolbarAdminTray = $('.toolbar-tray');

    // Set initial top position of sticky header according Admin toolbar height.
    stickyElement.css('top', toolbar.innerHeight());

    // Recalculate top position of sticky depend on toolbar administration-tray.
    toolbar.find('.trigger').on('click', function () {
      if(toolbarAdminTray.hasClass('is-active')
        && toolbarAdminTray.hasClass('toolbar-tray-horizontal')) {
        stickyElement.css('top', toolbar.innerHeight());
      } else {
        stickyElement.css('top', toolbar.innerHeight() + toolbarAdminTray.innerHeight());
      }
    });

    // Make Admin toolbar sticky on smaller screens.
    if(!window.matchMedia(drupalSettings.toolbar.breakpoints["toolbar.standard"]).matches) {
      toolbar.css({
        'position' : 'fixed',
        'top' : '0'
      });
    }
  }

  Drupal.behaviors.themagStickyHeader = {
    attach: function (context, settings) {
      // Set placeholder height.
      $(window).on('load', function(evt) {
        $(context).find('.js-sticky-header-placeholder').css('height', $('.js-sticky-header-element').outerHeight())
      })
    }
  }

})(jQuery, Drupal, drupalSettings);
