<?php

namespace Drupal\bc_dc\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Field Formatter that displays just the name of a data_set column.
 *
 * @FieldFormatter(
 *   id = "data_set_column_name",
 *   label = @Translation("Data set column name"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class DataSetColumnNameFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays just the name of a data set column.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Make an array of column names.
    $column_names = [];
    foreach ($items as $item) {
      $column_names[] = $item->entity->field_column_name->value;
    }

    $element = [];

    // Display each column name as a markup element.
    foreach ($column_names as $column_name) {
      $element[] = ['#markup' => $column_name];
    }

    return $element;
  }

}
