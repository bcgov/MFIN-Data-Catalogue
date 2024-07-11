<?php

/**
 * @file
 * page_attachments.inc
 */

/**
 * Implements hook_page_attachments_alter().
 */
function themag_page_attachments_alter(array &$variables) {

  // Attach empty array to drupalSettings which will
  // hold various theme related settings.
  $variables['#attached']['drupalSettings']['theMag'] = [];

  // Bootstrap 4 viewport meta tag.
  $viewport = [
    '#type' => 'html_tag',
    '#tag' => 'meta',
    '#attributes' => [
      'name' => 'viewport',
      'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no',
    ],
  ];
  $variables['#attached']['html_head'][] = [$viewport, 'viewport'];

  // Instructs IE to use highest available engine
  // for established and emerging industry standards.
  $x_ua_compatible = [
    '#type' => 'html_tag',
    '#tag' => 'meta',
    '#attributes' => [
      'http-equiv' => 'X-UA-Compatible',
      'content' => 'IE=Edge',
    ],
  ];
  $variables['#attached']['html_head'][] = [$x_ua_compatible, 'X-UA-Compatible'];

  // ----------------------------
  // Sticky header status
  // ----------------------------
  $sticky_header = theme_get_setting('themag_sticky_header');
  $variables['#attached']['drupalSettings']['themag']['header']['stickyHeader'] = $sticky_header;
  if ($sticky_header) {
    $variables['#attached']['library'][] = 'themag/sticky-header';
  }

  // ----------------------------
  // Header user profile menu
  // ----------------------------
  if (theme_get_setting('themag_header_user_icon')) {
    $variables['#attached']['library'][] = 'themag/header-user-profile-menu';
  }

  // ----------------------------
  // Sticky sidebars
  // ----------------------------
  if (theme_get_setting('themag_sticky_sidebar')) {
    $variables['#attached']['library'][] = 'themag/sticky-sidebars';
  }

  // ----------------------------
  // Search Block Toggler
  // ----------------------------
  if (\Drupal::service('module_handler')->moduleExists('search') || \Drupal::service('module_handler')->moduleExists('search_api')) {
    $variables['#attached']['library'][] = 'themag/toggle-search-block';
  }

  // ----------------------------
  // Scroll to top button
  // ----------------------------
  if (theme_get_setting('themag_toggle_scroll_to_top')) {
    $variables['#attached']['library'][] = 'themag/back-to-top-button';
  }

  // ----------------------------
  // Compatibility Mode
  // ----------------------------
  if (theme_get_setting('themag_compatibility_mode')) {
    $variables['#attached']['library'][] = 'themag/themag-compatibility-mode';
  }

}
