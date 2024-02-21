<?php

namespace Drupal\bc_dc\EventSubscriber;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to config events.
 */
class BcDcConfigImportEventSubscriber implements EventSubscriberInterface {

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
   * Act on config save or import. Create default content.
   */
  public function onConfigImport(): void {
    // UUID of the home page block placed in page manager.
    $uuid = 'c5810526-ea89-4f3e-a9b5-d2a6a7ca32fe';
    // This defines the default home page text block. Create the block if it
    // does not exist. It can be edited and changes will stay unless deleted.
    $blocks = $this->entityTypeManager->getStorage('block_content')->loadByProperties(['uuid' => $uuid]);
    if (!$blocks) {
      $block = BlockContent::create([
        'info' => 'Home page text',
        'body' => 'The Finance Data Catalogue enables users to discover data and data-related assets at the BC Ministry of Finance offering information and functionality that benefits consumers of data for business purposes.',
        'type' => 'basic',
        'langcode' => 'en',
        'uuid' => $uuid,
      ]);
      $block->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run for every config import. ConfigEvents::IMPORT runs only when there
    // are changes to import.
    $events[ConfigEvents::STORAGE_TRANSFORM_IMPORT][] = ['onConfigImport'];
    return $events;
  }

}
