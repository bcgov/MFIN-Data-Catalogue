/**
 * @file
 * Sticky Kit
 *
 * @see http://leafo.net/sticky-kit/
 */

(function ($, Drupal, drupalSettings) {

  /*
   * Attach Sticky Kit
   */
  function attachStickySidebars(element, options) {
    var stickyElement = element.find('>div');

    stickyElement.stick_in_parent(options);
    // console.log('Sticky Kit Attached');
  }

  /*
   * Detach Sticky Kit
   */
  function detachStickySidebars(element) {
    'use strict';

    var stickyElement = element.find('>div');

    stickyElement.trigger('sticky_kit:detach');
    // console.log('Sticky Kit Detached');
  }

  /*
   * Check if sticky elements column is higher than static elements column.
   * If it is, then it will be considered as incompatible.
   */
  function isStickyCompatible(element) {
    var parentHeight = element.parent().height();
    var stickyElement = element.find('>div');

    var stickyElementHeight = stickyElement.css('padding-bottom', '1px').height() + 100;
    if (stickyElementHeight >= parentHeight) {
      return false;
    }
    return true;
  }


  // If sticky header is enabled, set off set_top to header height.
  var stickyOptions = {};
  if (drupalSettings.themag.header.stickyHeader === 1) {
    var header = $('.js-sticky-header-element');
    stickyOptions.offset_top = header.outerHeight() + 30;
  }


  Drupal.behaviors.themagStickyElements = {
    attach: function(context, settings) {

      $(window).once().on('load', function() {
        var largeScreen = window.matchMedia('(min-width: 768px)');
        var stickyColumn = $('.js-sticky-container');

        // Attach StickyKit to each compatible element.
        stickyColumn.each(function(index, element) {
          if (largeScreen.matches && isStickyCompatible($(element))) {
            attachStickySidebars($(element), stickyOptions);
          }
        });

        // Watch for screen changes.
        largeScreen.addListener(function(mql) {
          if (mql.matches) {
            // Attach it to each compatible element.
            stickyColumn.each(function(index, element) {
              if (largeScreen.matches && isStickyCompatible($(element))) {
                attachStickySidebars($(element), stickyOptions);
              }
            });
          }
          else {
            detachStickySidebars(stickyColumn);
          }
        });
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
