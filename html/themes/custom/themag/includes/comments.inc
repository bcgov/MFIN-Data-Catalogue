<?php

/**
 * @file
 * comments.inc
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function themag_form_comment_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['author']['name']['#attributes']['placeholder'] = t('Name');
}
