<?php

/**
 * @file
 * Advanced theme settings.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function themag_form_system_theme_settings_alter(&$form, FormStateInterface &$form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  $replacements = [
    '@path_themag_st' => \Drupal::service('extension.list.theme')->getPath('themag_st'),
    '@path_themag' => \Drupal::service('extension.list.theme')->getPath('themag'),
  ];
  // Create vertical tabs for all TheMAG related settings.
  $form['themag'] = [
    '#type' => 'vertical_tabs',
    '#weight' => -10,
  ];

  // ========================
  // General.
  // ========================
  $form['general'] = [
    '#type' => 'details',
    '#title' => t('General'),
    '#group' => 'themag',
  ];

  // ------------------------
  // Options.
  // ------------------------
  $form['general']['options'] = [
    '#type' => 'details',
    '#title' => t('Options'),
    '#weight' => 10,
    '#open' => TRUE,
  ];

  $form['general']['options']['themag_toggle_scroll_to_top'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable scroll to top button'),
    '#default_value' => theme_get_setting('themag_toggle_scroll_to_top'),
    '#description' =>
    t('When a user scrolls past a certain point on the website,
    this helpful button appears, enabling users to easily
    return to the top of a page.'),
  ];

  $form['general']['options']['themag_sticky_sidebar'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable sticky sidebars'),
    '#default_value' => theme_get_setting('themag_sticky_sidebar'),
    '#description' =>
    t('Sticky elements taller than the viewport can scroll independently
    up and down, meaning you don\'t have to worry about your content
    being cut off.'),
  ];

  // ========================
  // Header
  // ========================
  $form['header'] = [
    '#type' => 'details',
    '#title' => t('Header'),
    '#group' => 'themag',
  ];

  // ------------------------
  // Header Settings
  // ------------------------
  $form['header']['heder_options'] = [
    '#type' => 'details',
    '#title' => t('Options'),
    '#weight' => 5,
    '#open' => TRUE,
  ];

  // Select header style.
  $form['header']['heder_options']['themag_header_style'] = [
    '#type' => 'select',
    '#title' => t('Header style'),
    '#options' => [
      'header_a' => t('Header style (A)'),
      'header_b' => t('Header style (B)'),
      'header_c' => t('Header style (C)'),
      'header_d' => t('Header style (D)'),
      'custom_header' => t('Custom Header'),
    ],
    '#default_value' => theme_get_setting('themag_header_style'),
    '#description' => t('Choose a header for your site.
      You can also choose a "Custom Header" and create your own header by editing the
      "<strong>@path_themag_st/templates/header/custom_header.inc</strong>" file.', $replacements),
  ];

  // Header Banner
  // * Show the header banner slot field when either
  // * the header(C) style is used
  // * the header(D) style is used
  // * or the custom header is used.
  $form['header']['heder_options']['themag_header_banner'] = [
    '#type' => 'textarea',
    '#title' => t('Header banner slot'),
    '#default_value' => theme_get_setting('themag_header_banner'),
    '#description' => t('This header style supports header banner ad. The recommended ad size is 728x90px.'),
    '#states' => [
      'visible' => [
        [
          ':input[name="themag_header_style"]' => ['value' => 'header_c'],
        ],
        [
          ':input[name="themag_header_style"]' => ['value' => 'header_d'],
        ],
        [
          ':input[name="themag_header_style"]' => ['value' => 'custom_header'],
        ],
      ],
    ],
  ];

  $form['header']['heder_options']['themag_sticky_header'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable sticky header'),
    '#default_value' => theme_get_setting('themag_sticky_header'),
    '#description' =>
    t('The sticky header can help to make it easier for visitors
        to navigate through a site as they can quickly access the navigation menu
        rather than having to scroll back to the top of the page.'),
  ];

  $form['header']['header_element_display'] = [
    '#type' => 'details',
    '#title' => t('Header Action Menu'),
    '#weight' => 5,
    '#open' => TRUE,
  ];

  $form['header']['header_element_display']['themag_header_user_icon'] = [
    '#type' => 'checkbox',
    '#title' => t('Show user menu'),
    '#default_value' => theme_get_setting('themag_header_user_icon'),
  ];

  $form['header']['header_element_display']['themag_header_search_icon'] = [
    '#type' => 'checkbox',
    '#title' => t('Show search icon'),
    '#default_value' => theme_get_setting('themag_header_search_icon'),
    '#disabled' => (\Drupal::service('module_handler')->moduleExists('search') ? '' : 'disabled'),
  ];

  $form['header']['header_element_display']['search_settings'] = [
    '#type' => 'details',
    '#title' => t('Search settings'),
    '#open' => TRUE,
    '#states' => [
      'visible' => [
        [
          ':input[name="themag_header_search_icon"]' => ['checked' => TRUE],
        ],
      ],
    ],
  ];

  $form['header']['header_element_display']['search_settings']['themag_header_search_icon_as_link'] = [
    '#type' => 'checkbox',
    '#title' => t('Use the search icon as a link to the search page instead of the search form toggle button'),
    '#default_value' => theme_get_setting('themag_header_search_icon_as_link'),
    '#disabled' => (\Drupal::service('module_handler')->moduleExists('search') ? '' : 'disabled'),
    '#states' => [
      'visible' => [
        [
          ':input[name="themag_header_search_icon"]' => ['checked' => TRUE],
        ],
      ],
    ],
  ];

  $form['header']['header_element_display']['search_settings']['themag_search_page_path'] = [
    '#type' => 'url',
    '#title' => t('Search page URL'),
    '#description' => t('Enter URL for the search page. E.g. http://example.com/search'),
    '#default_value' => theme_get_setting('themag_search_page_path'),
    '#disabled' => (\Drupal::service('module_handler')->moduleExists('search') ? '' : 'disabled'),
    '#states' => [
      'visible' => [
        [
          ':input[name="themag_header_search_icon_as_link"]' => ['checked' => TRUE],
        ],
      ],
    ],
  ];

  $form['header']['header_element_display']['themag_header_cart_icon'] = [
    '#type' => 'checkbox',
    '#title' => t('Show shopping cart'),
    '#default_value' => theme_get_setting('themag_header_cart_icon'),
    '#disabled' => (\Drupal::service('module_handler')->moduleExists('commerce_cart') ? '' : 'disabled'),
  ];

  // ========================
  // Social Media
  // ========================
  $form['social'] = [
    '#type' => 'details',
    '#title' => t('Social media pages'),
    '#group' => 'themag',
  ];

  // ------------------------
  // Social Media Pages
  // ------------------------
  $form['social']['social_media_pages'] = [
    '#type' => 'details',
    '#title' => t('Social Media Pages'),
    '#open' => TRUE,
  ];

  $social_media_pages = [
    'facebook' => 'Facebook',
    'twitter' => 'Twitter',
    'google-plus' => 'Google+',
    'youtube' => 'YouTube',
    'instagram' => 'Instagram',
    'pinterest' => 'Pinterest',
    'tumblr' => 'Tumblr',
    'linked-in' => 'LinkedIn',
  ];

  foreach ($social_media_pages as $sm_page => $sm_name) {

    $form['social']['social_media_pages'][$sm_page] = [
      '#type' => 'details',
      '#title' => t('@sm_name', ['@sm_name' => $sm_name]),
      '#open' => FALSE,
    ];

    $form['social']['social_media_pages'][$sm_page]['themag_' . $sm_page] = [
      '#type' => 'url',
      '#title'  => t('@sm_name', ['@sm_name' => $sm_name]),
      '#description' => t('Enter the URL of your @sm_name profile.', ['@sm_name' => $sm_name]),
      '#attributes' => ['placeholder' => 'http://'],
      '#default_value' => theme_get_setting('themag_' . $sm_page),
    ];

    $form['social']['social_media_pages'][$sm_page]['themag_' . $sm_page . '_link_title'] = [
      '#type' => 'textfield',
      '#title'  => t('Link title'),
      '#description' => t("Enter the link title eg. Follow us on ... You can leave this field blank if you don't want use link title."),
      '#default_value' => theme_get_setting('themag_' . $sm_page . '_link_title'),
    ];
  }

  // ========================
  // Search settings.
  // ========================
  $form['search_form'] = [
    '#type' => 'details',
    '#title' => t('Search form'),
    '#group' => 'themag',
  ];

  $form['search_form']['search']['themag_search_button_text'] = [
    '#type' => 'textfield',
    '#title'  => t('Search button text'),
    '#default_value' => theme_get_setting('themag_search_button_text'),
  ];

  $form['search_form']['search']['themag_search_field_placeholder_text'] = [
    '#type' => 'textfield',
    '#title'  => t('Search field placeholder text'),
    '#default_value' => theme_get_setting('themag_search_field_placeholder_text'),
  ];

  // ========================
  // Content
  // ========================
  $form['content'] = [
    '#type' => 'details',
    '#title' => t('Content'),
    '#group' => 'themag',
  ];

  // ------------------------
  // Teasers
  // ------------------------
  $form['content']['teaser'] = [
    '#type' => 'details',
    '#title' => t('Teaser'),
    '#open' => TRUE,
  ];

  $form['content']['teaser']['themag_teaser_show_media_contained_icons'] = [
    '#type' => 'checkbox',
    '#title' => t('Show media icons in the teasers'),
    '#default_value' => theme_get_setting('themag_teaser_show_media_contained_icons'),
    '#description' =>
    t('This feature will add a photo or a video icon over the
        teaser image, depending on the Article\'s content.'),
  ];

  $form['content']['teaser']['themag_teaser_show_author_name'] = [
    '#type' => 'checkbox',
    '#title' => t("Show author's name in the teaser"),
    '#description' => '',
    '#default_value' => theme_get_setting('themag_teaser_show_author_name'),
  ];

  $form['content']['teaser']['themag_teaser_show_post_date'] = [
    '#type' => 'checkbox',
    '#title' => t('Show post date in the teaser'),
    '#description' => '',
    '#default_value' => theme_get_setting('themag_teaser_show_post_date'),
  ];

  // Date formats.
  $date_format = \Drupal::entityTypeManager()->getStorage('date_format')
    ->loadMultiple();
  $date_format_options = [];
  foreach ($date_format as $format) {
    $date_format_options[$format->id()] = date($format->getPattern(), time());
  }

  $form['content']['teaser']['themag_teaser_date_format'] = [
    '#type' => 'select',
    '#title' => t('Date format'),
    '#options' => $date_format_options,
    '#default_value' => theme_get_setting('themag_teaser_date_format'),
    '#description' =>
    t('To create a custom date format go to <a href="@base_path/admin/config/regional/date-time">Date and time formats</a> page.', ['@base_path' => base_path()]),
  ];

  // ========================
  // Mailchimp Signup Block
  // ========================
  $form['mailchimp'] = [
    '#type' => 'details',
    '#title' => t('Mailchimp Signup block'),
    '#group' => 'themag',
  ];

  $form['mailchimp']['themag_mailchimp_signup_headline'] = [
    '#type' => 'textfield',
    '#title' => t('Signup Headline'),
    '#default_value' => theme_get_setting('themag_mailchimp_signup_headline'),
    '#disabled' => (\Drupal::service('module_handler')->moduleExists('mailchimp') ? '' : 'disabled'),
  ];

  $form['mailchimp']['themag_mailchimp_signup_text'] = [
    '#type' => 'textarea',
    '#title' => t('Signup Text/Description'),
    '#default_value' => theme_get_setting('themag_mailchimp_signup_text'),
    '#disabled' => (\Drupal::service('module_handler')->moduleExists('mailchimp') ? '' : 'disabled'),
  ];

  // ========================
  // Advanced.
  // ========================
  $form['advanced'] = [
    '#type' => 'details',
    '#title' => t('Advanced'),
    '#group' => 'themag',
  ];

  // CSS/JS Files.
  $form['advanced']['css_js_files'] = [
    '#type' => 'details',
    '#title' => t('Custom CSS and JavaScript Files'),
    '#weight' => 10,
    '#open' => TRUE,
  ];

  $form['advanced']['css_js_files']['themag_use_custom_css_file'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable custom.css'),
    '#description' => t('You can override existing styles by writing extra CSS in the "@path_themag_st/assets/css/custom.css" file.', $replacements),
    '#default_value' => theme_get_setting('themag_use_custom_css_file'),
  ];

  $form['advanced']['css_js_files']['themag_use_custom_js_file'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable custom.js'),
    '#description' => t('Write your own JavaSscript code in the "@path_themag_st/assets/js/custom.js" file.', $replacements),
    '#default_value' => theme_get_setting('themag_use_custom_js_file'),
  ];

  // Additional CSS.
  $form['advanced']['additional_css'] = [
    '#type' => 'details',
    '#title' => t('Additional CSS Style'),
    '#weight' => 10,
    '#open' => TRUE,
  ];

  $form['advanced']['additional_css']['themag_additional_css'] = [
    '#type' => 'textarea',
    '#title' => t('Additional CSS'),
    '#default_value' => theme_get_setting('themag_additional_css'),
    '#description' => t('Use this field to make small theme tweaks,
      or to add some custom CSS styles. If you plan to make more significant
      style changes please use "<strong>@path_themag/assets/css/custom.css</strong>".
      The style from this field will override custom.css styles. Note: Please use the code without &lt;style&gt; tag included.', $replacements),
  ];

  // Additional JavaScript Code.
  $form['advanced']['js'] = [
    '#type' => 'details',
    '#title' => t('Additional JavaScript'),
    '#weight' => 10,
    '#open' => TRUE,
  ];

  $form['advanced']['js']['themag_additional_javascript'] = [
    '#type' => 'textarea',
    '#title' => t('JavaScript Tracking/SDK code snippets'),
    '#default_value' => theme_get_setting('themag_additional_javascript'),
    '#description' =>
    t('The content of this field is inserted directly after the
      opening &lt;body&gt; tag on each page. Use this field to add JavaScript
      tracking code snippets or JavaScript SDK code snippets
      (example: Facebook SDK for JavaScript). Note: Please use the code
      with &lt;script&gt; tag included.'),
  ];

  // ========================
  // Deprecated Setting
  // ========================
  $form['deprecated'] = [
    '#type' => 'details',
    '#title' => t('Legacy/Compatibility Setting'),
    '#group' => 'themag',
  ];

  $form['deprecated']['compatibility'] = [
    '#type' => 'details',
    '#title' => t('Compatibility Mode'),
    '#description' =>
    t('This option provides a backward theme compatibility.
      You have to enable this option only if you update the theme from any
      version before version 3. You can also use all the new theme features,
      the compatibility mode only loads the styles and libraries which
      are removed from the TheMAG version 3.<br><br>
      <strong>DO NOT ENABLE COMPATIBILITY MODE IF YOU START WITH THE THEME FROM VERSION 3 OR ABOVE!</strong><br><br>'),
    '#open' => TRUE,
  ];

  $form['deprecated']['compatibility']['themag_compatibility_mode'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable Compatibility Mode'),
    '#default_value' => theme_get_setting('themag_compatibility_mode'),
  ];

  // ------------------------
  // Social Buttons
  // ------------------------
  $form['deprecated']['share_buttons'] = [
    '#type' => 'details',
    '#title' => t('Article share buttons'),
    '#open' => TRUE,
  ];

  $form['deprecated']['share_buttons']['themag_rrssb_buttons'] = [
    '#type' => 'checkboxes',
    '#options' => [
      'rrssb_facebook' => t('Facebook'),
      'rrssb_twitter' => t('Twitter'),
      'rrssb_googleplus' => t('Google+'),
      'rrssb_tumblr' => t('Tumblr'),
      'rrssb_linkedin' => t('LinkedIn'),
      'rrssb_reddit' => t('Reddit'),
      'rrssb_vk' => t('vk.com'),
      'rrssb_hackernews' => t('Hackernews'),
      'rrssb_pocket' => t('Pocket'),
      'rrssb_email' => t('E-mail'),
    ],
    '#default_value' => theme_get_setting('themag_rrssb_buttons'),
    '#description' => t('Enable/Disable sharing buttons.'),
  ];
}
