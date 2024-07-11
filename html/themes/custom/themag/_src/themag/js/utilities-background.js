(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.utilitiesBackground = {
    attach: function(context, settings) {

      var elements = $(context).find('[data-background]');
      if(elements.length > 0) {

        var data = '';

        elements.each(function () {
          var data = $(this).data();

          // Set element background color
          if (typeof data.color !== "undefined") {
            $(this).css('background-color', data.color);
          }

          // Set element width to 100% if is not specified
          if (typeof data.width !== "undefined") {
            $(this).css('width', data.width)
          }

          // Set element height if specified
          if (typeof data.height !== "undefined") {
            $(this).css('height', data.height);
          }

          if(data.background === 'image') {
            // Set element height if specified
            if (typeof data.src !== "undefined") {
              $(this).css({
                'background-image': 'url(' + data.src.trim() + ')',
                'background-size': 'cover',
                'background-position': 'center center'
              })
            } else {
              console.error('data-src (background image) is required!')
            }
          }
        });
      }
    }
  }
})(jQuery, Drupal);
