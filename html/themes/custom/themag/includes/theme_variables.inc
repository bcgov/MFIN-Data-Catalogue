<?php

/**
 * @file
 * Implements hook_preprocess.
 */

/**
 * Implements hook_preprocess().
 */
function themag_preprocess(&$variables, $hook) {

  // Check if the modules required by the theme are enabled.
  $moduleHandler = \Drupal::service('module_handler');

  $variables['themag']['modules'] = [
    'search' => FALSE,
    'search_api' => FALSE,
    'masonry' => FALSE,
    'commerce_cart' => FALSE,
  ];

  foreach ($variables['themag']['modules'] as $module => $val) {
    if ($moduleHandler->moduleExists($module)) {
      $variables['themag']['modules'][$module] = TRUE;
    }
  }

  // Compatibility mode.
  $variables['themag']['compatibility_mode'] = theme_get_setting('themag_compatibility_mode');

  // Additional JavaScripts and CSS.
  // Variables accessible only in html.html.twig.
  if ($hook == 'html') {
    $variables['themag']['toggle_scroll_to_top'] = theme_get_setting('themag_toggle_scroll_to_top');
    $variables['themag']['additional_css'] = theme_get_setting('themag_additional_css');
    $variables['themag']['additional_javascript'] = theme_get_setting('themag_additional_javascript');
  }

  // Header variables.
  $variables['themag']['header_style'] = theme_get_setting('themag_header_style');
  $variables['themag']['header_banner'] = theme_get_setting('themag_header_banner');
  $variables['themag']['header_user_icon'] = theme_get_setting('themag_header_user_icon');
  $variables['themag']['header_search_icon'] = theme_get_setting('themag_header_search_icon');
  $variables['themag']['header_search_icon_as_link'] = theme_get_setting('themag_header_search_icon_as_link');
  $variables['themag']['search_page_path'] = theme_get_setting('themag_search_page_path');
  $variables['themag']['header_cart_icon'] = theme_get_setting('themag_header_cart_icon');

  // Sticky features.
  $variables['themag']['sticky_header'] = theme_get_setting('themag_sticky_header');
  $variables['themag']['sticky_sidebar'] = theme_get_setting('themag_sticky_sidebar');

  // =========
  // Teaser
  // =========
  $variables['themag']['teaser']['show_media_contained_icons']
    = theme_get_setting('themag_teaser_show_media_contained_icons');

  $variables['themag']['teaser']['show_author_name']
    = theme_get_setting('themag_teaser_show_author_name');

  $variables['themag']['teaser']['show_post_date']
    = theme_get_setting('themag_teaser_show_post_date');

  // =====================
  // Social Media Pages
  // =====================
  // Social media pages.
  $social_media_pages = [
    'facebook' => [
      'name' => 'Facebook',
      'fa_icon' => 'fab fa-facebook-f',
    ],
    'twitter' => [
      'name' => 'Twitter',
      'fa_icon' => 'fab fa-twitter',
    ],
    'google-plus' => [
      'namae' => 'Google+',
      'fa_icon' => 'fab fa-google-plus-g',
    ],
    'youtube' => [
      'name' => 'YouTube',
      'fa_icon' => 'fab fa-youtube',
    ],
    'instagram' => [
      'name' => 'Instagram',
      'fa_icon' => 'fab fa-instagram',
    ],
    'pinterest' => [
      'name' => 'Pinterest',
      'fa_icon' => 'fab fa-pinterest-p',
    ],
    'tumblr' => [
      'name' => 'Tumblr',
      'fa_icon' => 'fab fa-tumblr',
    ],
    'linked-in' => [
      'name' => 'LinkedIn',
      'fa_icon' => 'fab fa-linkedin-in',
    ],
  ];

  foreach ($social_media_pages as $page_key => $page) {
    $social_media_page_url = theme_get_setting('themag_' . $page_key);

    if (isset($social_media_page_url) && $social_media_page_url != "") {
      $variables['themag']['social_media_pages'][$page_key]['url'] = theme_get_setting('themag_' . $page_key);
      $variables['themag']['social_media_pages'][$page_key]['title'] = theme_get_setting('themag_' . $page_key . '_link_title');
      $variables['themag']['social_media_pages'][$page_key]['icon'] = '<i class="' . $page['fa_icon'] . '"></i>';
    }
  }

  // ========================
  // Mailchimp signup block
  // ========================
  if ($hook == 'block') {
    if ($moduleHandler->moduleExists('mailchimp')) {
      $variables['themag']['mailchimp']['signup_headline'] = theme_get_setting('themag_mailchimp_signup_headline');
      $variables['themag']['mailchimp']['signup_text'] = theme_get_setting('themag_mailchimp_signup_text');
    }
  }

  // =========
  // Legacy
  // =========
  if ($variables['themag']['compatibility_mode']) {
    $variables['themag']['rrssb_buttons'] = theme_get_setting('themag_rrssb_buttons');
  }
}
