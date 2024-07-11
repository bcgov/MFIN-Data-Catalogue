<?php

/**
 * @file
 * layouts.inc
 */

/**
 * Implements hook_preprocess_layout().
 */

use Drupal\Core\Template\Attribute;

/**
 * Implements hook_preprocess_layout().
 */
function charm_preprocess_layout(&$variables) {

  if ($variables['layout']->getProvider() != 'themag_layouts') {

    // Adding wrapper class to layouts.
    $variables['attributes'] = new Attribute($variables['attributes']);

    $layout_classes = [
      'layout',
      str_replace('_', '-', $variables['theme_hook_original']),
    ];
    $variables['attributes']->addClass($layout_classes);

    // Adding classes to each layout region.
    if (isset($variables['region_attributes']) && is_array($variables['region_attributes'])) {
      foreach ($variables['region_attributes'] as $region_key => $region_value) {
        $region_value->addClass('layout__region', 'layout__region--' . str_replace('_', '-', $region_key));
        $region_value->setAttribute('id', 'region--' . str_replace('_', '-', $region_key));
      }
    }
  }
}
