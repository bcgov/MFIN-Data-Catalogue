/**
 * @file
 * Cart block
 */
(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.themagCartBlock = {
    attach: function(context, settings) {
      var $context = $(context);
      var $cart = $context.find('.cart--cart-block');
      var $cartButton = $context.find('.cart-block--link__expand');
      var $cartContents = $cart.find('.cart-block--contents');

      if ($cartContents.length > 0) {
        // Expand the block when the link is clicked.
        $cartButton.on('click', function() {
          $cartButton.toggleClass('cart-block--link-active');
        });
      }
    }
  };
}(jQuery, Drupal, drupalSettings));
