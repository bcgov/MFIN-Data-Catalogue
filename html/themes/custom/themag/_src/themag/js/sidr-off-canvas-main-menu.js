/**
 * @file
 * Sidr - Off-canvas main menu
 *
 * @see http://www.berriart.com/sidr/
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.themagSidrOffCanvasMainMenu = {
    attach: function (context, settings) {

      // Hide the canvas before DOM is ready.
      $(context).find('#offcanvas-sidebar').once().css('display', 'block');

      // Initialize Sider
      $(context).find('.js-toggle-offcanvas-sidebar')
        .once().sidr({
        name: 'offcanvas-sidebar',
        renaming: false,
        side: 'left',
        displace: true,
        onOpen: sidrOpen,
        onOpenEnd: sidrOpenEnd,
        onClose: sidrClose
      });

      // Create submenus (drop-down) for all expanded menu items.
      $(context).find('.sidr li.menu-item--expanded')
        .once().each(function (index, menuItem) {
        $(menuItem).find('> a, > .nolink').on('click', function (event) {
          event.preventDefault();
          $(menuItem).toggleClass('active');
          $(menuItem).find('> ul.menu').toggleClass('open');
        })
      });

    }
  };


  // Sider callbacks
  function sidrOpen() {
    $('.offcanvas-sidebar-overlay').addClass('active');
  }

  function sidrOpenEnd() {
    $('.offcanvas-sidebar-overlay').on('click', function () {
      $(this).removeClass('active');
      $.sidr('close', 'offcanvas-sidebar');
    });
  }

  function sidrClose() {
    $('.offcanvas-sidebar-overlay').removeClass('active');
  }

  // Close on resize
  $(window).resize(function () {
    $.sidr('close', 'offcanvas-sidebar');
  });

})(jQuery, Drupal);
