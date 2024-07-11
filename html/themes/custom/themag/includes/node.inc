<?php

/**
 * @file
 * node.inc
 */

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Implements template_preprocess_node().
 */
function themag_preprocess_node(&$variables) {

  // Node.
  $node = $variables['node'];

  /*
   * Set the Teaser date format from theme settings.
   */
  $teaser_date_format = theme_get_setting('themag_teaser_date_format');
  $variables['date'] = \Drupal::service('date.formatter')
    ->format($variables['node']->getCreatedTime(), $teaser_date_format);

  /*
   * Create a node variable with paragraph's bundle name that are used
   * in the node. This will allow us to show what type of content contains
   * certain article e.g. if the article contains video paragraph a video
   * icon will appear in the article teaser.
   */
  $has_field_paragraphs = $node->hasField('field_paragraphs');
  $has_field_mg_paragraph = $node->hasField('field_mg_paragraph');

  if ($has_field_paragraphs || $has_field_mg_paragraph) {

    if ($has_field_paragraphs) {
      $paragraph = $node->field_paragraphs->getValue();
    }
    else {
      $paragraph = $node->field_mg_paragraph->getValue();
    }

    if (!empty($paragraph)) {
      $paragraph_bundles = [];
      $media_contained_html = '';

      foreach ($paragraph as $element) {
        $p = Paragraph::load($element['target_id']);
        $paragraph_bundle_name = $p->bundle();
        $paragraph_bundles[] = $paragraph_bundle_name;
      }

      if (in_array('mg_parag_video', $paragraph_bundles)
        || in_array('mg_parag_embedded_video', $paragraph_bundles)
        || in_array('embed_video', $paragraph_bundles)
        || in_array('video', $paragraph_bundles)) {
        $media_contained_html .= '<i class="far fa-play-circle"></i>';
      }
      if (in_array('mg_parag_photo_gallery_slider', $paragraph_bundles)
        || in_array('mg_parag_photo_gallery', $paragraph_bundles)
        || in_array('gallery_grid', $paragraph_bundles)
        || in_array('gallery_slider', $paragraph_bundles)) {
        $media_contained_html .= '<i class="fas fa-camera"></i>';
      }

      $variables['themag']['teaser']['paragraph_bundles'] = array_unique($paragraph_bundles);
      $variables['themag']['teaser']['media_contained']['html'] = $media_contained_html;
    }
  }

  // Adding a comments count variable.
  if ($node->hasField('field_comments') || $node->hasField('field_mg_comments')) {
    $comments_count = $variables['node']->get('field_mg_comments')->comment_count;

    if ($comments_count == 1) {
      $variables['themag']['comments']['count'] = $comments_count;
      $variables['themag']['comments']['count_text'] = $comments_count . ' ' . t('Comment');
    }
    elseif ($comments_count > 1) {
      $variables['themag']['comments']['count'] = $comments_count;
      $variables['themag']['comments']['count_text'] = $comments_count . ' ' . t('Comments');
    }
  }
}

/**
 * Implements hook_theme_suggestions_NODE_alter().
 */
function themag_theme_suggestions_node_alter(array &$suggestions, array $variables) {
  $node = $variables['elements']['#node'];
  $node_type = $node->getType();
  $view_mode = $variables['elements']['#view_mode'];

  // LEGACY - DEPRECATED
  // Changing article layouts with "field_mg_post_layout" is deprecated
  // and will ben remover in some of the future theme versions.
  if ($node_type == 'mg_article') {
    if ($node->hasField('field_mg_post_layout')) {
      $post_layout = $node->get('field_mg_post_layout')->getValue()[0]['value'];
      array_splice($suggestions, 2, 0, 'node__' . $node_type . '__' . $post_layout);
      array_splice($suggestions, 4, 0, 'node__' . $node_type . '__' . $view_mode . '__' . $post_layout);
    }
  }
}
