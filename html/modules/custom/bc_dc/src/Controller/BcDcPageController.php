<?php

namespace Drupal\bc_dc\Controller;

use Drupal\bc_dc\Trait\ReviewReminderTrait;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\flag\FlagLinkBuilder;
use Drupal\flag\FlagService;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Page callbacks.
 */
class BcDcPageController extends ControllerBase {

  use ReviewReminderTrait;

  const REVIEW_NEEDED = 1;
  const REVIEW_OVERDUE = 2;

  /**
   * Create a BcDcPageController instance.
   *
   * @param \Drupal\flag\FlagService $flagService
   *   The flag service.
   * @param \Drupal\flag\FlagLinkBuilder $flagLinkBuilder
   *   The flag.link_builder service.
   */
  public function __construct(
    protected FlagService $flagService,
    protected FlagLinkBuilder $flagLinkBuilder,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): object {
    return new static(
      $container->get('flag'),
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
    // Anonymous cannot have a dashboard.
    if (!$this->currentUser()->id()) {
      throw new NotFoundHttpException();
    }

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
    $url = Url::fromRoute('node.add', ['node_type' => 'data_set'], ['query' => ['display' => 'data_set_description']]);
    if ($url->access()) {
      $page['actions']['add-new'] = [
        '#title' => $this->t('Add new data set'),
        '#type' => 'link',
        '#url' => $url,
        '#attributes' => [
          'class' => [
            'button',
            'button--action',
            'button--primary',
          ],
        ],
      ];
    }

    // Link to saved searches.
    $url = Url::fromRoute('view.saved_searches.page', ['user' => $this->currentUser()->id()]);
    if ($url->access()) {
      $page['actions']['saved-searches'] = [
        '#title' => $this->t('My saved searches'),
        '#type' => 'link',
        '#url' => $url,
        '#attributes' => [
          'class' => [
            'button',
          ],
        ],
      ];
    }

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
    // Get all bookmark flags for this user.
    $values = [
      'flag_id' => 'bookmark',
      'uid' => $this->currentUser()->id(),
    ];
    $bookmarks = $this->entityTypeManager()->getStorage('flagging')->loadByProperties($values);
    // Make array of bookmarked nodes.
    $bookmark_nids = [];
    foreach ($bookmarks as $bookmark) {
      $bookmark_nids[] = $bookmark->getFlaggableId();
    }
    // Generate the table.
    $classes = ['dc-dashboard-table', 'dc-dashboard-table-bookmarks'];
    $page['bookmark-table'] = $this->dataSetTableTheme($bookmark_nids, $classes, $this->t('Bookmarked data sets'));

    // Table of data_set nodes by this user with total bookmark counts.
    $query = $this->entityTypeManager()->getStorage('node')->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('type', 'data_set');
    $query->condition('uid', $this->currentUser()->id());
    $data_set_nids = $query->execute();
    $classes = ['dc-dashboard-table', 'dc-dashboard-table-datasets-bookmarks'];
    $page['data_set-bookmarks'] = $this->dataSetTableTheme($data_set_nids, $classes, $this->t('My data sets bookmarked by at least one user'), TRUE);

    // Table of data_set nodes with the most bookmarks.
    // Get all bookmark flags.
    $values = [
      'flag_id' => 'bookmark',
    ];
    $bookmarks = $this->entityTypeManager()->getStorage('flagging')->loadByProperties($values);
    // Make an array of bookmark counts keyed by NID.
    $bookmark_nids = [];
    foreach ($bookmarks as $bookmark) {
      $node = $bookmark->getFlaggable();
      // Remove duplicates.
      if (isset($bookmark_nids[$node->id()])) {
        continue;
      }
      // Store count of bookmarks on this node.
      $bookmark_nids[$node->id()] = \bc_dc_count_node_bookmarks($node);
    }
    // Sort by bookmark count.
    asort($bookmark_nids, SORT_NUMERIC);
    // Take the top 10.
    $bookmark_nids = array_slice($bookmark_nids, 0, 10, TRUE);
    // Generate the table.
    $classes = ['dc-dashboard-table', 'dc-dashboard-table-most-bookmarked'];
    $page['most-bookmark-table'] = $this->dataSetTableTheme(array_keys($bookmark_nids), $classes, $this->t('Most bookmarked data sets'), TRUE);

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
   * @param bool $only_bookmarked
   *   Only show items that have at least one bookmark by anyone.
   *
   * @return array
   *   A render array.
   */
  public function dataSetTableTheme(array $data_set_nids, array $classes, string $caption, bool $only_bookmarked = FALSE): array {
    $bookmark_flag = $this->flagService->getFlagById('bookmark');

    $review_needed_message = trim($this->config('bc_dc.settings')->get('review_needed_message'), '.');
    $review_overdue_message = trim($this->config('bc_dc.settings')->get('review_overdue_message'), '.');

    // Table headers.
    $header = [
      'title' => $this->t('Title'),
    ];
    if ($only_bookmarked) {
      $header['count'] = $this->t('Bookmark count');
    }
    else {
      $header['actions'] = $this->t('Actions');
    }
    // Table rows. One row per data_set.
    $rows = [];
    foreach ($data_set_nids as $nid) {
      $data_set = $this->entityTypeManager()->getStorage('node')->load($nid);

      // Only show items that have at least one bookmark by anyone.
      if ($only_bookmarked) {
        $count = \bc_dc_count_node_bookmarks($data_set);
        if ($count < 1) {
          continue;
        }
      }

      $row = [];

      // Add column for sorting.
      $row['sort'] = $data_set->getTitle();

      // Display only columns that appear in $headers.
      foreach (array_keys($header) as $column_key) {
        switch ($column_key) {
          case 'title':
            $cell = ['data' => $data_set->toLink()->toRenderable()];

            $badges = [];

            // Display indication that data_set has been modified since view.
            $bookmark_flagging = $this->flagService->getFlagging($bookmark_flag, $data_set);
            $field_last_viewed_date = $bookmark_flagging?->get('field_last_viewed_date')->value;
            if ($field_last_viewed_date && $data_set->field_modified_date->value > $field_last_viewed_date) {
              $badges[] = '<span class="badge text-bg-success">' . $this->t('Updated') . '</span>';
            }

            match (static::dataSetReviewNeeded($data_set)) {
              static::REVIEW_NEEDED => $badges[] = '<span class="badge text-bg-warning">' . $review_needed_message . '</span>',
              static::REVIEW_OVERDUE => $badges[] = '<span class="badge text-bg-danger">' . $review_overdue_message . '</span>',
              default => NULL,
            };

            // Combine badges, adding a trailing space.
            $badges[] = '';
            $cell['data']['#prefix'] = implode(' ', $badges);
            break;

          case 'count':
            $cell = $count;
            break;

          case 'actions':
            $actions = [];
            // Build link.
            $url = Url::fromRoute('page_manager.page_view_data_set_build_data_set_build-block_display-0', ['node' => $data_set->id()]);
            if ($url->access()) {
              $actions['build'] = [
                '#title' => $this->t('Build'),
                '#type' => 'link',
                '#url' => $url,
                '#attributes' => [
                  'class' => ['button'],
                  'aria-label' => $this->t('Build "@title".', ['@title' => $data_set->getTitle()]),
                ],
              ];
            }
            // Bookmark link.
            $actions['bookmark'] = $this->flagLinkBuilder->build('node', $data_set->id(), 'bookmark');
            $cell = ['data' => $actions];
            break;
        }
        $row[$column_key] = $cell;
      }

      $rows[] = $row;
    }

    // Sort the rows.
    if ($only_bookmarked) {
      // Sort by bookmark count, title.
      array_multisort(array_column($rows, 'count'), SORT_DESC, array_column($rows, 'sort'), SORT_ASC, $rows);
    }
    else {
      // Sort by title.
      array_multisort(array_column($rows, 'sort'), SORT_ASC, $rows);
    }

    // Remove column for sorting.
    foreach ($rows as &$row) {
      unset($row['sort']);
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
