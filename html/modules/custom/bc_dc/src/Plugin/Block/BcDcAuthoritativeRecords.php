<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Provides an authoritative records block for the dashboard.
 *
 * @Block(
 *   id = "bc_dc_authoritative_records",
 *   admin_label = @Translation("Dashboard number of authoritative records"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class BcDcAuthoritativeRecords extends BcDcRecordsStatsBase {

  /**
   * {@inheritdoc}
   */
  protected function getQuery(): QueryInterface {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    return $nodeStorage
      ->getQuery()
      ->condition('status', 1)
      ->condition('type', 'data_set')
      ->condition('field_authoritative_info', TRUE)
      ->accessCheck(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getText(): array {
    return [
      'singular' => $this->t('Authoritative Asset'),
      'plural' => $this->t('Authoritative Assets'),
    ];
  }

}
