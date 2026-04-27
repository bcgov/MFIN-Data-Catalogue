<?php

/**
 * @file
 * Contains \Drupal\field_count_formatter\Plugin\Field\FieldFormatter\Count.
 */

namespace Drupal\field_count_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'CountFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "count",
 *   label = @Translation("Field count"),
 *   field_types = {
 *   }
 * )
 */
class Count extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Displays the number of items/count.')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Needs to be nested (element 0) so we preserve the default field title rendering.
    return [
      [
        '#markup' => $items->count(),
      ],
    ];
  }
}
