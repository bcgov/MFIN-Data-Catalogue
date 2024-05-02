<?php

namespace Drupal\bc_dc\Service;

use Drupal\bc_dc\Traits\ReviewReminderTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to update field_review_status on data_set nodes.
 */
class UpdateReviewStatus implements ContainerInjectionInterface {

  use ReviewReminderTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Update field_review_status for all data_set nodes.
   */
  public function updateAll(): void {
    $node_storage = $this->entityTypeManager->getStorage('node');

    $query = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'data_set');
    $data_set_nids = $query->execute();

    foreach ($data_set_nids as $data_set_nid) {
      $data_set = $node_storage->load($data_set_nid);
      static::updateEntity($data_set);
    }
  }

  /**
   * Update field_review_status for a single node.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity to act on.
   * @param bool $save
   *   When TRUE, save the entity, otherwise do not.
   */
  public function updateEntity(NodeInterface $entity, bool $save = TRUE): void {
    if (!$entity->hasField('field_review_status')) {
      return;
    }

    $status_mapping = [
      static::REVIEW_NEEDED => 'needs_review',
      static::REVIEW_OVERDUE => 'review_overdue',
    ];

    $update_value = static::dataSetReviewNeeded($entity);
    $update_value = $status_mapping[$update_value] ?? NULL;
    $entity->set('field_review_status', $update_value);

    // Allow use from hook_ENTITY_TYPE_presave() where saving would be disabled.
    if ($save) {
      $entity->save();
    }
  }

}
