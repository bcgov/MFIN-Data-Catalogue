/**
 * @file
 * In-Article gallery grid
 *
 * jquery.photoswipe
 * @see https://github.com/yaquawa/jquery.photoswipe
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.themagPhotoswipeInarticleGalleryGrid = {
    attach: function (context, settings) {
      $(context).find('.paragraph--type--mg-parag-photo-gallery, .view-mode-gallery-grid').once().photoSwipe();
    }
  }

})(jQuery, Drupal);