<?php

/**
 * @file
 * block.inc
 */

/**
 * Implements hook_theme_suggestions_block_alter().
 */
function themag_st_theme_suggestions_block_alter(&$suggestions, $variables) {
  foreach ($suggestions as &$suggestion) {
    $suggestion = str_replace('themag_st_', 'themag_', $suggestion);
  }
}
