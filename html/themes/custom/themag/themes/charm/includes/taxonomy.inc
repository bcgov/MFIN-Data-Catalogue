<?php

/**
 * @file
 * taxonomy.inc
 */

/**
 * Implements hook_theme_suggestions_USER_alter().
 */
function charm_theme_suggestions_taxonomy_term_alter(array &$suggestions, array $variables) {
  $view_mode = $variables['elements']['#view_mode'];
  array_splice($suggestions, 1, 0, 'taxonomy_term__' . $view_mode);
}
