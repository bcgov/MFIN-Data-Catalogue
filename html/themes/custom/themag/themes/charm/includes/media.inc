<?php

/**
 * @file
 * media.inc
 */

/**
 * Implements hook_preprocess_media()
 */

use Drupal\Core\Template\Attribute;

/**
 * Implemenets hook_preprocess_media().
 */
function charm_preprocess_media(&$variables) {

  $drupal_module_handler = \Drupal::moduleHandler();

  // This applies only for core media module.
  if ($drupal_module_handler->moduleExists('media')) {
    $media = $variables['elements']['#media'];
    $media_bundle = $media->bundle();
    $media_view_mode = $variables['view_mode'];

    // Add classes to wrapper element.
    $variables['attributes'] = new Attribute($variables['attributes']);
    $variables['attributes']->addClass($variables['theme_hook_original'] . '-' . $media_bundle);
    $variables['attributes']->addClass('view-mode-' . str_replace('_', '-', $media_view_mode));
  }
}
