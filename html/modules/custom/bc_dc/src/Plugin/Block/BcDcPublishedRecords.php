<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Provides a published records block for the dashboard.
 *
 * @Block(
 *   id = "bc_dc_published_records",
 *   admin_label = @Translation("Dashboard number of published records"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class BcDcPublishedRecords extends BcDcRecordsStatsBase {

  /**
   * {@inheritdoc}
   */
  protected function getQuery(): QueryInterface {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    return $nodeStorage
      ->getQuery()
      ->condition('status', 1)
      ->condition('type', 'data_set')
      ->accessCheck(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getText(): array {
    return [
      'singular' => $this->t('Published Metadata Record'),
      'plural' => $this->t('Published Metadata Records'),
    ];
  }

}
