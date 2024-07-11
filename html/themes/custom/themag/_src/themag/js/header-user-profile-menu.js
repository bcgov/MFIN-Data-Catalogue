/**
 * @file
 * Toggle user profile menu
 */
(function ($) {
  'use strict';

  var headerAccountMenu = $('.header__toggleable-account-menu')

  headerAccountMenu.find('.js-toggle-account-menu')
    .on('click', function () {
      $(this).toggleClass('is--active');
      headerAccountMenu.find('.menu').toggleClass('is--active');
    })
})(jQuery);
