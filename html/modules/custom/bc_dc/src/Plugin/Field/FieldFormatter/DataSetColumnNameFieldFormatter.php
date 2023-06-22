<?php

namespace Drupal\bc_dc\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

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
  public static function defaultSettings(): array {
    return [
      // When TRUE, display comma-separated, otherwise are HTML 'ul'.
      'display_inline' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    $form['display_inline'] = [
      '#title' => $this->t('Display inline'),
      '#description' => $this->t('When checked, the column names will display as a comma-separated list. Otherwise, they will display as an HTML unordered list.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_inline') ?? FALSE,
    ];

    return $form;
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

    if ($this->getSetting('display_inline')) {
      // Theme as comma-separated list of the column names.
      $list = [
        '#markup' => implode(', ', $column_names),
      ];
    }
    else {
      // Theme as unordered list of the column names.
      $list = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $column_names,
        '#empty' => $this->t('None'),
      ];
    }

    $element = [];

    $element[] = $list;

    return $element;
  }

}
