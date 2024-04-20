<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Provides a overdue records block for the dashboard.
 *
 * @Block(
 *   id = "bc_dc_overdue_records",
 *   admin_label = @Translation("Dashboard number of overdue records"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class BcDcOverdueRecords extends BcDcRecordsStatsBase {

  /**
   * {@inheritdoc}
   */
  protected function getQuery(): QueryInterface {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    return $nodeStorage
      ->getQuery()
      ->condition('status', 1)
      ->condition('type', 'data_set')
      ->condition('field_review_status', 'review_overdue')
      ->accessCheck(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getText(): array {
    return [
      'singular' => $this->t('Overdue Metadata Record'),
      'plural' => $this->t('Overdue Metadata Records'),
    ];
  }

}
