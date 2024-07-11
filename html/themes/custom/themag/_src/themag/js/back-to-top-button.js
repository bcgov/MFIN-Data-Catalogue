/**
 * @file
 * Scroll to top button.
 */

(function($) {
  'use strict';

  var scrollToTopButton = $('<a href="#top" class="scroll-to-top-button" ' +
    'onclick="return 0" data-smooth-scroll><i class="fas fa-angle-up"></i></a>');

  scrollToTopButton.appendTo($('body'));

  $(window).scroll(function() {
    if ($(window).scrollTop() >= 700) {
      $(scrollToTopButton).addClass('active');
    }
    else {
      $(scrollToTopButton).removeClass('active');
    }
  });
})(jQuery);
