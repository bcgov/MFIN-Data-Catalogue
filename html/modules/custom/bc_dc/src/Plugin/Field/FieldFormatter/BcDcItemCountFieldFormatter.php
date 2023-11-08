<?php

namespace Drupal\bc_dc\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Field Formatter that displays the count of the number of items.
 *
 * The field_types key can contain any type.
 *
 * @FieldFormatter(
 *   id = "bc_dc_item_count",
 *   label = @Translation("Item count"),
 *   description = @Translation("Displays the count of the number of items."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class BcDcItemCountFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    $summary[] = $this->t('Displays the count of the number of items.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    $element[] = [
      '#markup' => count($items),
    ];

    return $element;
  }

}
