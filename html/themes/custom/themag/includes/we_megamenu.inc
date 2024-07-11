<?php

/**
 * @file
 * we_megamenu.inc
 */

/**
 * Implements template_preprocess_we_megamenu_row.
 */
function themag_preprocess_we_megamenu_row(&$variables) {
  $variables['attributes']
    ->addClass('row')
    ->addClass('no-gutters');
}

/**
 * Implements template_preprocess_we_megamenu_col.
 */
function themag_preprocess_we_megamenu_col(&$variables) {
  $col_items = $variables['item_config'];
  $col_config = $col_items->col_config;

  $variables['attributes']
    ->removeClass('span' . $col_config->width)
    ->addClass('col-' . $col_config->width);
}
