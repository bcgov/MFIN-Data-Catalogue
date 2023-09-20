<?php

namespace Drupal\bc_dc\Trait;

use Drupal\node\NodeInterface;

/**
 * Code used for review reminders.
 */
trait ReviewReminderTrait {

  /**
   * Review type constants.
   *
   * @todo Define these only here after the upgrade to PHP 8.2
   *
   * @code
   * const REVIEW_NEEDED = 1;
   * const REVIEW_OVERDUE = 2;
   * @endcode
   */

  /**
   * Determines whether a data_set nodes needs review.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The data_set node to check.
   *
   * @return int|null
   *   REVIEW_OVERDUE if the data_set is passed its review date.
   *   REVIEW_NEEDED if the data_set needs a review within the number of
   *   days in data_set_review_period_alert. Otherwise, NULL; this could be that
   *   the entity is not a data_set, the data_set has no review period
   *   configured, or a review is not yet due.
   */
  public static function dataSetReviewNeeded(NodeInterface $entity): ?int {
    // Act only on data_set nodes.
    if ($entity->bundle() !== 'data_set') {
      return NULL;
    }

    // If no interval is configured, no review needed.
    $field_review_interval = $entity->field_review_interval->value;
    if (!$field_review_interval) {
      return NULL;
    }

    // If it has never been reviewed, no review needed.
    $field_last_review_date = $entity->field_last_review_date->value;
    if (!$field_last_review_date) {
      return NULL;
    }

    // Check if the review was long enough ago that it is due.
    // Start with the date of last review.
    $review_due = new \DateTime($field_last_review_date);
    // Add the interval.
    $review_due->add(new \DateInterval('P' . $field_review_interval . 'M'));
    // If the date is in the past, review is overdue.
    if ($review_due->getTimestamp() < time()) {
      return static::REVIEW_OVERDUE;
    }

    // Substract the configured warning duration.
    $data_set_review_period_alert = (int) \Drupal::config('bc_dc.settings')->get('data_set_review_period_alert');
    $review_due->sub(new \DateInterval('P' . $data_set_review_period_alert . 'D'));
    // If the date is in the past, review is needed.
    if ($review_due->getTimestamp() < time()) {
      return static::REVIEW_NEEDED;
    }

    // Review is not yet needed.
    return NULL;
  }

}
