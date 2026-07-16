<?php

namespace Drupal\bc_dc\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\entity_clone\Event\EntityCloneEvent;
use Drupal\entity_clone\Event\EntityCloneEvents;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sets a revision log message when a data_set node is cloned.
 */
class EntityCloneSubscriber implements EventSubscriberInterface {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs an EntityCloneSubscriber object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * The logger factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[EntityCloneEvents::POST_CLONE][] = ['onPostClone'];
    return $events;
  }

  /**
   * Actions to perform after an entity is cloned.
   *
   * @param \Drupal\entity_clone\Event\EntityCloneEvent $event
   * The entity clone event.
   */
  public function onPostClone(EntityCloneEvent $event): void {
    $cloned_entity = $event->getClonedEntity();

    // Act only if the cloned entity is a 'data_set' node.
    if ($cloned_entity instanceof NodeInterface && $cloned_entity->bundle() === 'data_set') {
      $original_entity = $event->getEntity();

      $message = t('Created by cloning entity "@title (ID: @id)"', [
        '@title' => $original_entity->label(),
        '@id' => $original_entity->id(),
      ]);

      // Apply the revision configuration changes cleanly.
      $cloned_entity->setRevisionLogMessage($message);
      $cloned_entity->setNewRevision(TRUE);

      // Force a dedicated revision save.
      $cloned_entity->save();

      // Log the action to watchdog.
      $this->loggerFactory->get('bc_dc')->info('data_set: created by cloning entity %orig_title (ID: %id).', [
        '%orig_title' => $original_entity->label(),
        '%id' => $original_entity->id(),
      ]);
    }
  }

}
