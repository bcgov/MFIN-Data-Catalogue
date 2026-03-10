(function ($, Drupal) {

  "use strict";

  // This Javascript is to work with and overwrite the CSS set by
  // the Environment Indicator module's 'environment_indicator.js' file.
  Drupal.behaviors.eitweaks = {
    attach: function (context, settings) {
      
      // Get params passed in from eitweaks.module
      const params = drupalSettings.eitweaks || {};

      if (typeof(settings.environmentIndicator) != 'undefined') {

        // Remove the unneeded border at the top, which just wastes vertical space.
        if ($('body').hasClass('gin--horizontal-toolbar')) {

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

          // Environment name looks like "Cabops Dev". We want just the "dev":
          var site_environment = params.environment_name.trim().split(" ").pop().toLowerCase();

          // In the Switcher dropdown, we want to highlight the environment we are currently
          // on, and disable its link as it doesn't make sense to switch to the current environment.

          // Loop through the items in the dropdown.
          $(`.toolbar-tab--toolbar-item-environment-indicator ul.toolbar-menu`, context).find('li a').each(
            function() {
              var link_text = $(this, context).text();

              // Link text may be "Open on Dev". We want just "dev":
              var link_environment = link_text.trim().split(" ").pop().toLowerCase();

              if (site_environment != 'localhost' && link_environment == 'localhost' && !params.user_is_developer) {
                // Remove the DDEV item for non-admins when on one of the Openshift servers.
                (this).closest('li').remove();
              }

              if (site_environment == link_environment) {
                // If we're on dev, then the link should say 'Open on dev'. We want it to just say '--- dev ---'.
                // But this function seems to run more than once, due to 'context'. So test first if
                // we've already switched the text.
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
