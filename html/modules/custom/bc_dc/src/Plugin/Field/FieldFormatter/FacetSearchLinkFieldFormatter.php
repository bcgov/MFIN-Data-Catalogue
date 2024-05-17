<?php

namespace Drupal\bc_dc\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
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
  public static function defaultSettings(): array {
    return [
      'format_as_list' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    $form['format_as_list'] = [
      '#title' => $this->t('Format as list'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->getSetting('format_as_list')),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    $summary[] = $this->t('Links to the facet search for this item. This only works for taxonomy terms.');
    $summary[] = $this->getSetting('format_as_list') ? $this->t('Format as list.') : $this->t('Do not format as list.');
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
    $links = [];

    foreach ($items as $item) {
      if (!$item->entity) {
        continue;
      }
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
      $links[] = Link::createFromRoute($item->entity->getName(), 'page_manager.page_view_site_search_site_search-panels_variant-0', [], $options)->toRenderable();
    }

    if (!$links) {
      return [];
    }

    if ($this->getSetting('format_as_list')) {
      return [
        [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#items' => $links,
        ],
      ];
    }
    else {
      return $links;
    }
  }

}
