<?php

namespace Drupal\bc_dc\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Field Formatter that displays the review interval as the next review date.
 *
 * @FieldFormatter(
 *   id = "bc_dc_review_interval_date",
 *   label = @Translation("Review interval as date"),
 *   description = @Translation("Displays the review interval as the next review date."),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class BcDcReviewIntervalDateFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    $summary[] = $this->t('Displays the review interval as the next review date.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    $field_last_review_date = $items->getParent()?->getEntity()?->field_last_review_date?->value;

    // Return empty results if no last review date.
    if (!$field_last_review_date) {
      return $element;
    }

    // @todo The title should be configured in the settings for this formatter.
    $element['#title'] = $this->t('Next review date');

    foreach ($items as $item) {
      $field_review_interval = (int) $item->value;

      // Calculate relative date.
      $next_review_date = strtotime($field_last_review_date . '+' . $field_review_interval . 'months');

      $element[] = [
        '#theme' => 'time',
        '#attributes' => [
          'datetime' => date('Y-m-d', $next_review_date),
        ],
      ];
    }

    return $element;
  }

}
