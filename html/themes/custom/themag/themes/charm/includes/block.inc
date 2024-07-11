<?php

/**
 * @file
 * block.inc
 */

use Drupal\block\Entity\Block;
use Drupal\Core\Template\Attribute;

/**
 * Implements hook_theme_suggestions_BLOCK_alter().
 */
function charm_preprocess_block(&$variables) {

  $variables['attributes'] =
    new Attribute($variables['attributes']);
  $variables['title_attributes'] =
    new Attribute($variables['title_attributes']);
  $variables['content_attributes'] =
    new Attribute($variables['content_attributes']);

  // Add block class.
  $variables['attributes']->addClass('block');

  // Add block--[PROVIDE] modifier class.
  if (isset($variables['configuration']['provider'])) {
    $variables['attributes']
      ->addClass('block--' . str_replace('_', '-', $variables['configuration']['provider']));
  }

  // Add block--[REGION] modifier class.
  if (isset($variables['elements']['#id'])) {
    // Loading block has no performance impact because is already loaded)
    $block = Block::load($variables['elements']['#id']);
    if ($block != NULL) {
      $region = $block->getRegion();
      $variables['attributes']
        ->addClass('block--region-' . str_replace('_', '-', $region));
    }
  }

  // Add block--[PLUGIN_ID] modifier class.
  if (isset($variables['configuration']['provider'])) {
    $variables['attributes']
      ->addClass('block--' . str_replace('_', '-', $variables['plugin_id']));
  }

  $variables['title_attributes']->addClass('block__title');
  $variables['content_attributes']->addClass('block__content');

  // kint($variables);
}

/**
 * Implements hook_theme_suggestions_BLOCK_alter().
 */
function charm_theme_suggestions_block_alter(array &$suggestions, array $variables) {

  // Register $classes variable in case of class attributes manipulation.
  $classes = [];

  // Suggestion for block bundle.
  if (isset($variables['elements']['content']['#block_content'])) {
    $bundle = $variables['elements']['content']['#block_content']->bundle();
    array_splice($suggestions, 1, 0, 'block__block__' . $bundle);
  }

  // Suggestions for view modes.
  if (isset($variables['elements']['#configuration']['view_mode'])) {
    $view_mode = $variables['elements']['#configuration']['view_mode'];
    array_splice($suggestions, 1, 0, 'block__block__' . $view_mode);
  }

  if (isset($bundle) && isset($view_mode)) {
    array_splice($suggestions, 3, 0, 'block__block__' . $bundle . '__' . $view_mode);
  }

  // Field block suggestions.
  if (isset($variables['elements']['content']['#entity_type'])) {
    if ($variables['elements']['#base_plugin_id'] == 'field_block') {
      array_splice($suggestions, 3, 0, 'block__' . $variables['elements']['#base_plugin_id'] . '__' . $variables['elements']['content']['#entity_type'] . '__' . $variables['elements']['content']['#field_name']);
    }
  }
}
