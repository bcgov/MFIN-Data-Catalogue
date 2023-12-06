<?php

/**
 * @file
 * Builds custom placeholder replacement tokens for this site.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\tac_lite\Form\SchemeForm;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

/**
 * Implements hook_token_info().
 */
function bc_dc_token_info() {
  $types = [];
  $tokens = [];

  $types['global'] = [
    'name' => t('Global tokens'),
    'description' => t('Tokens with no context.'),
  ]; 

  // Current-user tokens.
  $tokens['global']['bc_dc_infosched_leafterms'] = [
    'name' => t('Information schedule child terms'),
    'description' =>  t('Returns the child terms of the information schedule vocabulary concatentated with +.'),
  ];

  return ['types' => $types, 'tokens' => $tokens];
}

/**
 * Implements hook_tokens().
 */
function bc_dc_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  // Global tokens.
  if ($type == 'global') {
    foreach ($tokens as $name => $original) {
      // Find the desired token by name.
      switch ($name) {
        case 'bc_dc_infosched_leafterms':
          // Getting first the entity type manager service.
          $manager = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
          // Load the taxonomy tree using values.
          $tree = $manager->loadTree(
            'information_schedule',
            0,
            NULL,
            TRUE
          );
 
          $results = [];
          foreach ($tree as $term) {
            // Check if has children or not to validate if is at last level.
            if (empty($manager->loadChildren($term->id()))) { 
              $results[] = $term->id();
            }
          }
          if (!empty($results)) {
            $replacements[$original] = implode('+', $results);
          }
        break;
      }
    }
  }

  return $replacements;
}
