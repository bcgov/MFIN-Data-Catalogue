<?php

/**
 * @file
 * page_attachments.inc
 */

/**
 * Implements hook_page_attachments_alter().
 */
function themag_st_page_attachments_alter(array &$attachments) {

  // Attach custom CSS file.
  if (theme_get_setting('themag_use_custom_css_file')) {
    $attachments['#attached']['library'][] = 'themag_st/custom-css';
  }

  // Attach custom JS file.
  if (theme_get_setting('themag_use_custom_js_file')) {
    $attachments['#attached']['library'][] = 'themag_st/custom-js';
  }

}
