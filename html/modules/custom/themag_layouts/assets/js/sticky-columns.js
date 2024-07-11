/**
 * @file
 * Sticky Kit
 *
 * @see http://leafo.net/sticky-kit/
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  // If sticky header is enabled, set off set_top to header height.
  var stickyOptions = {};
  if (drupalSettings.themag.header.stickyHeader === 1) {
    var header = $('.js-sticky-header-element');
    stickyOptions.offset_top = header.outerHeight() + 30;
  }

  Drupal.behaviors.themagLayouts = {
    attach: function(context, settings) {

      $(window).on('load', function() {
        var largeScreen = window.matchMedia('(min-width: 768px)'),
            stickyColumn = $('.js-sticky-column');

        // Attach StickyKit to each compatible element.
        stickyColumn.each(function(index, element) {
          if (largeScreen.matches && isStickyCompatible($(element))) {
            attachStickyColumns($(element), stickyOptions);
          }
        });

        // Watch for screen changes.
        largeScreen.addListener(function(mql) {
          if (mql.matches) {
            // Attach it to each compatible element.
            stickyColumn.each(function(index, element) {
              if (largeScreen.matches && isStickyCompatible($(element))) {
                attachStickyColumns($(element), stickyOptions);
              }
            });
          }
          else {
            detachStickyColumns(stickyColumn);
          }
        });
      });
    }
  };

  /*
   * Attach Sticky Kit
   */
  function attachStickyColumns(element, options) {
    var stickyElement = element.find('>div');
    stickyElement.stick_in_parent(options);
  }

  /*
   * Detach Sticky Kit
   */
  function detachStickyColumns(element) {
    var stickyElement = element.find('>div');
    stickyElement.trigger('sticky_kit:detach');
  }

  /*
   * Check if sticky elements column is higher than static elements column.
   * If it is, then it will be considered as incompatible.
   */
  function isStickyCompatible(element) {
    var parentHeight = element.parent().height(),
      stickyElement = element.find('>div'),
      stickyElementHeight = stickyElement.css('padding-bottom', '1px').height() + 100;

    if (stickyElementHeight >= parentHeight) {
      return false;
    }
    return true;
  }

}(jQuery, Drupal, drupalSettings));
