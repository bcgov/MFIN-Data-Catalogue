(function ($, Drupal) {

  "use strict";

  // This Javascript is to work with and overwrite the CSS set by
  // the Environment Indicator module's 'environment_indicator.js' file.
  Drupal.behaviors.eitweaks = {
    attach: function (context, settings) {
      if (typeof(settings.environmentIndicator) != 'undefined') {

        // Modify gin toolbar so that background is set to the colour we wanted
        // in Environment Indicator.

        // Set a coloured border on the bottom, using the colour that had been earmarked
        // by Environment Indicator as the "foreground" colour. Do it for both the main
        // toolbar, and the narrow-width (mobile phone) version.
        // $(`#toolbar-item-administration-tray .toolbar-lining,
        //     #gin-toolbar-bar`, context)
        //   .css({'border-bottom': settings.environmentIndicator.bgColor + " 4px solid"});

        // Remove the unneeded border at the top, which just wastes vertical space.
        if ($('body').hasClass('gin--horizontal-toolbar')) {
          // $(`#gin-toolbar-bar .toolbar-tab,
          //     #toolbar-item-administration-tray,
          //     #toolbar-item-administration-tray .toolbar-lining,
          //     #toolbar-item-administration-tray .toolbar-lining ul.toolbar-menu,
          //     #toolbar-item-administration-tray .toolbar-lining ul.toolbar-menu li.menu-item`, context)
          //   .css({'border-top': 0});

          // Set our fg/bg colours on the Switcher dropdown.
            $(`.toolbar-tab--toolbar-item-environment-indicator > a`, context)
            .css({
              'color': settings.environmentIndicator.fgColor,
              'background-color': settings.environmentIndicator.bgColor,
          });

          // Remove the 'Configure' item from the Switcher drop-down. It takes you
          // to '/admin/config/development/environment-indicator', which is already
          // available in the admin toolbar. And besides, we want the items in the switcher
          // to be basically hard-coded.
          $(`.toolbar-tab--toolbar-item-environment-indicator a.edit-environments`, context)
            .remove();

          // In the Switcher dropdown, we want to highlight the environment we are currently
          // on, and disable its link as it doesn't make sense to switch to the current environment.
          // Find it by looking for the one with the same background colour as the current environment.

          // Settings provides the colour in hex format (e.g. #FFEE01). JQuery returns them as
          // rgb(12,23,34) format. Below, we want to compare apples to apples.
          var our_bg_color_as_rgb = $(`#toolbar-item-environment-indicator`, context).css('background-color');

          // Loop through the items in the dropdown.
          $(`.toolbar-tab--toolbar-item-environment-indicator ul.toolbar-menu`, context).find('li>a').each(
            function() {
              if ($(this).css('background-color') == our_bg_color_as_rgb) {
                // If we're on dev, then the link should say 'Open on dev'. We want it to just say '--- dev ---'.
                // But this function seems to run more than once, due to 'context'. So test first if
                // we've already switched the text.
                var link_text = $(this, context).text();
                if (link_text.substring(0,8) == 'Open on ') {
                  // Change the text, and also remove the link.
                  $(this)
                    .text('--- ' + link_text.substring(8) + ' ---')
                    .css({'cursor': 'default'})
                    .removeAttr('href')
                  ;
                }
              }
              else {
                // Make a hover-border on the switcher-items that are actually active.
                var transparent_border = 'rgb(255,0,0,0) 2px solid';
                $(this)
                  .css({'border': transparent_border})
                  .hover(
                  function() { $(this).css({"text-decoration": "underline", 'border': 'var(--gin-color-primary) 2px solid'}) },
                  function() { $(this).css({"text-decoration": "none",      'border': transparent_border}) }
                );
              }
            }
          );

        }

      }
    }
  }

})(jQuery, Drupal);
