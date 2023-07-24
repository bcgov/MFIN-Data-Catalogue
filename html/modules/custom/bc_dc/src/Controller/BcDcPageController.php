<?php

namespace Drupal\bc_dc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\flag\FlagLinkBuilder;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Page callbacks.
 */
class BcDcPageController extends ControllerBase {

  /**
   * Create a BcDcPageController instance.
   *
   * @param \Drupal\flag\FlagLinkBuilder $flagLinkBuilder
   *   The flag.link_builder service.
   */
  public function __construct(
    protected FlagLinkBuilder $flagLinkBuilder,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): object {
    return new static(
      $container->get('flag.link_builder'),
    );
  }

  /**
   * Page callback for the data set dashboard.
   *
   * @return array
   *   A render array for the build page.
   */
  public function dataSetDashboardPage(): array {
    $page = [];

    // Wrapper element for actions.
    $page['actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'dc-dashboard-actions',
        ],
      ],
    ];

    // Button to create a data_set.
    $page['actions']['add-new'] = [
      '#title' => $this->t('Add new data set'),
      '#type' => 'link',
      '#url' => Url::fromRoute('node.add', ['node_type' => 'data_set'], ['query' => ['display' => 'data_set_description']]),
      '#attributes' => [
        'class' => [
          'button',
          'button--action',
          'button--primary',
        ],
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
    $classes = ['dc-dashboard-table', 'dc-dashboard-table-mydatasets'];
    $page['data_set-table'] = $this->dataSetTableTheme($data_set_nids, $classes, $this->t('My unpublished data sets'));

    // Table of data_set nodes bookmarked by this user.
    $query = $this->entityTypeManager()->getStorage('flagging')->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('flag_id', 'bookmark');
    $query->condition('uid', $this->currentUser()->id());
    $bookmark_flagging_ids = $query->execute();
    // Convert the array of IDs of the bookmark flags into an array of IDs of
    // the nodes that are bookmarked.
    $bookmarks = $this->entityTypeManager()->getStorage('flagging')->loadMultiple($bookmark_flagging_ids);
    $bookmark_nids = [];
    foreach ($bookmarks as $bookmark) {
      $bookmark_nids[] = $bookmark->getFlaggableId();
    }
    // Generate the table.
    $classes = ['dc-dashboard-table', 'dc-dashboard-table-bookmarks'];
    $page['bookmark-table'] = $this->dataSetTableTheme($bookmark_nids, $classes, $this->t('Bookmarked data sets'));

    return $page;
  }

  /**
   * Return a render array containing a table list of data_set.
   *
   * @param int[] $data_set_nids
   *   The NIDs of the nodes to display.
   * @param array $classes
   *   The classes to set on the table.
   * @param string $caption
   *   The table caption.
   *
   * @return array
   *   A render array.
   */
  public function dataSetTableTheme(array $data_set_nids, array $classes, string $caption): array {
    // Table headers.
    $header = [
      $this->t('Title'),
      $this->t('Actions'),
    ];
    // Table rows. One row per data_set.
    $rows = [];
    foreach ($data_set_nids as $nid) {
      $data_set = $this->entityTypeManager()->getStorage('node')->load($nid);

      $row = [
        $data_set->getTitle(),
        [
          'data' => [
            'view' => [
              '#title' => $this->t('View'),
              '#type' => 'link',
              '#url' => $data_set->toUrl(),
              '#attributes' => [
                'class' => ['button'],
                'aria-label' => $this->t('View "@title".', ['@title' => $data_set->getTitle()]),
              ],
            ],
            'build' => [
              '#title' => $this->t('Build'),
              '#type' => 'link',
              '#url' => Url::fromRoute('page_manager.page_view_data_set_build_data_set_build-block_display-0', ['node' => $data_set->id()]),
              '#attributes' => [
                'class' => ['button'],
                'aria-label' => $this->t('Build "@title".', ['@title' => $data_set->getTitle()]),
              ],
            ],
          ],
        ],
      ];

      // Bookmark link.
      $flag_link = $this->flagLinkBuilder->build('node', $data_set->id(), 'bookmark');
      // Add to actions.
      $row[1]['data']['bookmark'] = $flag_link;

      $rows[] = $row;
    }
    // Theme the table.
    return [
      '#type' => 'table',
      '#caption' => $caption,
      '#empty' => $this->t('No data sets to show.'),
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => $classes,
      ],
    ];
  }

  /**
   * Page callback for the data set landing page.
   *
   * @return array
   *   A Drupal render array.
   */
  public function dataSetLandingPage(): array {
    $page = [];

    return $page;
  }

}
