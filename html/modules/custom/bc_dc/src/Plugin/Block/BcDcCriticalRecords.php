<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Provides a overdue records block for the dashboard.
 *
 * @Block(
 *   id = "bc_dc_critical_records",
 *   admin_label = @Translation("Dashboard number of critical records"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class BcDcCriticalRecords extends BcDcRecordsStatsBase {

  /**
   * {@inheritdoc}
   */
  protected function getQuery(): QueryInterface {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    return $nodeStorage
      ->getQuery()
      ->condition('status', 1)
      ->condition('type', 'data_set')
      ->condition('field_critical_information', TRUE)
      ->accessCheck(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getText(): array {
    return [
      'singular' => $this->t('Critical Asset'),
      'plural' => $this->t('Critical Assets'),
    ];
  }

}
