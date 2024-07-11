<?php

/**
 * @file
 * page.inc
 */

/**
 * Implements hook_theme_suggestions_PAGE_alter().
 */
function charm_theme_suggestions_page_alter(array &$suggestions, array $variables) {

  $current_rout_name = \Drupal::service('current_route_match')->getRouteName();

  // --------------------------------
  // Pages node suggestions
  // --------------------------------
  if ($node = \Drupal::routeMatch()->getParameter('node')) {
    // Get node revision bundle.
    if ($revision = \Drupal::routeMatch()->getRawParameter('node_revision')) {
      $revised_node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadRevision($revision);
      array_splice($suggestions, 1, 0, 'page__node__' . $revised_node->bundle());
    }
    else {
      $suggestions = __charm_page_entity_suggestions($suggestions, 'node');
    }
  }
  elseif (\Drupal::routeMatch()->getRouteName() == 'entity.user.canonical') {
    $suggestions = __charm_page_entity_suggestions($suggestions, 'user');
    array_splice($suggestions, 1, 0, 'page__user_profile');
  }
  elseif (\Drupal::routeMatch()->getRouteName() == 'entity.taxonomy_term.canonical') {
    $suggestions = __charm_page_entity_suggestions($suggestions, 'taxonomy_term');
  }

  // -----------------------
  // Layout edit page
  // -----------------------
  if (in_array('layout_builder', explode('.', $current_rout_name))) {
    array_splice($suggestions, -1, 0, 'page__admin__structure__overview__display__layout');
  }

  // --------------------------------------
  // Page Manager Suggestions
  // --------------------------------------
  if (\Drupal::moduleHandler()->moduleExists('page_manager')) {
    $request = \Drupal::routeMatch()->getRouteObject();
    if ($request->getDefault('_entity_view') === 'page_manager_page_variant') {
      $page_variant = $request->getDefault('page_manager_page_variant');
      $name = 'page_manager.page_variant.' . $page_variant;
      $config = \Drupal::configFactory()->getEditable($name);
      if ($config->get('variant') === 'panels_variant') {
        $layout_id = $config->get('variant_settings.layout');
        $suggestions[] = 'page__page_manager';
        $suggestions[] = 'page__page_manager__' . $layout_id;
      }
    }
  }

  // --------------------------------
  // Preview suggestions
  // --------------------------------
  if ($preview = \Drupal::routeMatch()->getParameter('node_preview')) {
    array_splice($suggestions, 2, 0, 'page__node__preview__' . $preview->getType());
  }

  // --------------------------------------
  // Error page suggestions.
  // Error 404 - Page not found.
  // --------------------------------------
  if ($current_rout_name == 'system.404') {
    array_splice($suggestions, 0, 0, 'page__404');
  }

  // --------------------------------------
  // Error page suggestions.
  // Error 403 - Access denied.
  // --------------------------------------
  if ($current_rout_name == 'system.403') {
    array_splice($suggestions, 0, 0, 'page__403');
  }
}

/**
 * Returns suggestions for particular entity.
 */
function __charm_page_entity_suggestions($suggestions, $entity_name) {

  $entity = \Drupal::routeMatch()->getParameter($entity_name);
  $bundle = $entity->bundle();
  $view_modes = \Drupal::service('entity_display.repository')
    ->getViewModeOptionsByBundle($entity_name, $bundle);

  // Add suggestion for entity bundle.
  array_splice($suggestions, 1, 0, 'page__' . $entity_name . '__' . $bundle);

  // Add suggestions depends on third party settings.
  foreach (array_keys($view_modes) as $view_mode) {
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load($entity_name . '.' . $bundle . '.' . $view_mode);

    // If layout builder is enabled.
    if ($display->getThirdPartySetting('layout_builder', 'enabled')) {
      array_splice($suggestions, 2, 0, 'page__layout_builder_enabled');
      array_splice($suggestions, 3, 0, 'page__' . $entity_name . '__layout_builder_enabled');
    }

    // Add suggestion for Panelizer pages.
    if (\Drupal::moduleHandler()->moduleExists('panelizer')) {
      $is_panelized = \Drupal::service('panelizer')->isPanelized($entity_name, $bundle, $view_mode);
      if ($is_panelized) {
        array_splice($suggestions, 2, 0, 'page__' . $entity_name . '__panelized');
        array_splice($suggestions, 3, 0, 'page__' . $entity_name . '__' . $bundle . '__panelized');
      }
    }
  }

  return $suggestions;
}
