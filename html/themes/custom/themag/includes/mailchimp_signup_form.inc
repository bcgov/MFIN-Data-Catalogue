<?php

/**
 * @file
 * Search.inc.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function themag_form_mailchimp_signup_subscribe_block_newsletter_signup_form_form_alter(&$form, FormStateInterface $form_state, $form_id) {

  $replacements = [
    '@email_title' => $form['mergevars']['EMAIL']['#title']->__toString(),
    '@firstname_title' => $form['mergevars']['FNAME']['#title']->__toString(),
    '@lastname_title' => $form['mergevars']['LNAME']['#title']->__toString(),
    '@birthday_title' => $form['mergevars']['BIRTHDAY']['#title']->__toString(),
  ];

  $form['mergevars']['EMAIL']['#attributes']['placeholder'] =
    t('@email_title', $replacements);

  unset($form['mergevars']['EMAIL']['#title']);

  if (isset($form['mergevars']['FNAME'])) {
    $form['mergevars']['FNAME']['#attributes']['placeholder'] =
      t('@firstname_title', $replacements);

    unset($form['mergevars']['FNAME']['#title']);
  }

  if (isset($form['mergevars']['LNAME'])) {
    $form['mergevars']['LNAME']['#attributes']['placeholder'] =
      t('@lastname_title', $replacements);

    unset($form['mergevars']['LNAME']['#title']);
  }

  if (isset($form['mergevars']['BIRTHDAY'])) {
    $form['mergevars']['BIRTHDAY']['#attributes']['placeholder'] =
      t('@birthday_title', $replacements);

    unset($form['mergevars']['BIRTHDAY']['#title']);
  }

}
