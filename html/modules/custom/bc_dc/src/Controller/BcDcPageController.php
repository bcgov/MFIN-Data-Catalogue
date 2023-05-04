<?php

namespace Drupal\bc_dc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Page callbacks.
 */
class BcDcPageController extends ControllerBase {

  /**
   * Page callback to display a build page for a dataset.
   *
   * @param Drupal\node\NodeInterface $node
   *   The node for which to display the build page.
   *
   * @return array
   *   A render array for the build page.
   */
  public function buildPage(NodeInterface $node): array {
    return $this->entityTypeManager()
      ->getViewBuilder('node')
      ->view($node, 'data_set_build_page');
  }

}
