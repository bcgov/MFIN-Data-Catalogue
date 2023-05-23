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
    $page['data_set-table'] = $this->dataSetTableTheme($data_set_nids, 'data-set-table', $this->t('My unpublished data sets'));

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
    $page['bookmark-table'] = $this->dataSetTableTheme($bookmark_nids, 'bookmark-table', $this->t('Bookmarked data sets'));

    return $page;
  }

  /**
   * Return a render array containing a table list of data_set.
   *
   * @param int[] $data_set_nids
   *   The NIDs of the nodes to display.
   * @param string $class
   *   The class to set on the table.
   * @param string $caption
   *   The table caption.
   *
   * @return array
   *   A render array.
   */
  public function dataSetTableTheme(array $data_set_nids, string $class, string $caption): array {
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

      // Bookmark link.
      $flag_link = $this->flagLinkBuilder->build('node', $data_set->id(), 'bookmark');
      unset($flag_link['#attributes']['title']);
      // Theme like a button.
      $flag_link['#attributes']['class'][] = 'button';
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
        'class' => [$class],
      ],
    ];
  }

}
