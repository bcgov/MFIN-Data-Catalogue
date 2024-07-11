<?php

/**
 * @file
 * page.inc
 */

/**
 * Implements template_preprocess_page.
 */
function themag_preprocess_page(&$variables) {

  // We will put some blocks in a them variable so we can to use
  // them later in a template file.
  // Commerce Cart block
  // .
  if (\Drupal::service('module_handler')->moduleExists('commerce_cart')) {
    $commerce_cart_block = \Drupal::service('plugin.manager.block')
      ->createInstance('commerce_cart', []);
    $variables['themag']['commerce']['commerce_cart_block'] = $commerce_cart_block->build();
  }

  // User Account Menu block
  // .
  $user_account_menu_block = \Drupal::service('plugin.manager.block')
    ->createInstance('system_menu_block:account', []);
  if ($user_account_menu_block->getPluginId() !== 'broken') {
    $variables['themag']['user']['user_account_menu_block'] = $user_account_menu_block->build();
  }
}
