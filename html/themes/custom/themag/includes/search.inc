<?php

/**
 * @file
 * search.inc
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function themag_form_search_block_form_alter(&$form, FormStateInterface $form_state, $form_id) {

  $replacements = [
    '@placeholder_text' => theme_get_setting('themag_search_field_placeholder_text'),
    '@button_text' => theme_get_setting('themag_search_button_text'),
  ];
  $form['keys']['#attributes']['placeholder'] = t('@placeholder_text', $replacements);
  $form['actions']['submit']['#value'] = t('@button_text', $replacements);
}
