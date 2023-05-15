<?php

namespace Drupal\bc_dc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Page callbacks.
 */
class BcDcPageController extends ControllerBase {

  /**
   * Page callback to display a build page for a dataset.
   *
   * This displays the data_set_build_page layout builder page, allowing it to
   * be placed at the URL path configured in routing.
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

  /**
   * Page callback for the data set dashboard.
   *
   * @return array
   *   A render array for the build page.
   */
  public function dataSetDashboardPage(): array {
    $page = [];

    // Button to create a data_set.
    $page['add-new'] = [
      '#title' => $this->t('Add new data set'),
      '#type' => 'link',
      '#url' => Url::fromRoute('node.add', ['node_type' => 'data_set'], ['query' => ['display' => 'data_set_description']]),
      '#attributes' => [
        'class' => ['button', 'button--action', 'button--primary'],
      ],
    ];

    // Table of data_set nodes.
    //
    // Query for data_set nodes by this user that are unpublished.
    $query = $this->entityTypeManager()->getStorage('node')->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('type', 'data_set');
    $query->condition('uid', $this->currentUser()->id());
    $query->condition('status', NodeInterface::NOT_PUBLISHED);
    $data_set_nids = $query->execute();
    // Table headers.
    $header = [
      $this->t('Title'),
      $this->t('Actions'),
    ];
    // Table rows. One row per data_set.
    $rows = [];
    foreach ($data_set_nids as $nid) {
      $data_set = $this->entityTypeManager()->getStorage('node')->load($nid);

      $rows[] = [
        $data_set->getTitle(),
        [
          'data' => [
            'view' => [
              '#title' => $this->t('View'),
              '#type' => 'link',
              '#url' => $data_set->toUrl(),
              '#attributes' => [
                'class' => ['button'],
                'aria-label' => $this->t('View @title', ['@title' => $data_set->getTitle()]),
              ],
            ],
            'build' => [
              '#title' => $this->t('Build'),
              '#type' => 'link',
              '#url' => Url::fromRoute('bc_dc.data_set_build_page', ['node' => $data_set->id()]),
              '#attributes' => [
                'class' => ['button'],
                'aria-label' => $this->t('Build @title', ['@title' => $data_set->getTitle()]),
              ],
            ],
          ],
        ],
      ];
    }
    // Theme the table.
    $page['data_set-table'] = [
      '#type' => 'table',
      '#caption' => $this->t('My unpublished data sets'),
      '#empty' => $this->t('No data sets to show.'),
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['data-set-table'],
      ],
    ];

    return $page;
  }

}
