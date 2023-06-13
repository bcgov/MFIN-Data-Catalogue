<?php

namespace Drupal\bc_dc\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Link;

/**
 * Field Formatter that links to the search facet for its value.
 *
 * This only works for taxonomy terms.
 *
 * @FieldFormatter(
 *   id = "data_set_facet_search_link",
 *   label = @Translation("Data set facet search link"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class FacetSearchLinkFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    $summary[] = $this->t('Links to the facet search for this item. This only works for taxonomy terms.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This only works for taxonomy terms.
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'taxonomy_term';
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    foreach ($items as $item) {
      // Remove 'field_' from start.
      $facet_key = substr($item->getFieldDefinition()->getName(), 6);
      // Query string for searching for this value as a facet.
      $options = [
        'query' => [
          'f' => [
            $facet_key . ':' . $item->entity->id(),
          ],
        ],
      ];
      // Link to search for this item value.
      $element[] = Link::createFromRoute($item->entity->getName(), 'page_manager.page_view_site_search_site_search-panels_variant-0', [], $options)->toRenderable();
    }

    return $element;
  }

}
