<?php

/**
 * @file
 * user.inc
 */

/**
 * Implements hook_preprocess_user().
 */
function charm_preprocess_user(&$variables) {
  $route_name = \Drupal::routeMatch()->getRouteName();
  $account = $variables['elements']['#user'];

  // Use getDisplayName() instead getAccountName() while setting the head title.
  if ($route_name == 'entity.user.canonical') {
    $variables['#attached']['html_head'][] = [
      [
        '#tag' => 'title',
        '#value' => $account->getDisplayName() . ' | ' . \Drupal::config('system.site')->get('name'),
      ],
      'title',
    ];
  }
}

/**
 * Implements hook_theme_suggestions_USER_alter().
 */
function charm_theme_suggestions_user_alter(array &$suggestions, array $variables) {

  $view_mode = $variables['elements']['#view_mode'];
  array_splice($suggestions, 1, 0, 'user__' . $view_mode);
}
