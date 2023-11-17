<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\bc_dc\Trait\ReviewReminderTrait;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\flag\FlagLinkBuilder;
use Drupal\flag\FlagService;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block that displays one of several tables of data_set nodes.
 *
 * @Block(
 *   id = "bc_dc_data_set_table",
 *   admin_label = @Translation("Metadata record table"),
 * )
 */
class BcDcDataSetTables extends BlockBase implements ContainerFactoryPluginInterface {

  use ReviewReminderTrait;

  const REVIEW_NEEDED = 1;
  const REVIEW_OVERDUE = 2;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current_user service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\flag\FlagService $flagService
   *   The flag service.
   * @param \Drupal\flag\FlagLinkBuilder $flagLinkBuilder
   *   The flag.link_builder service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ConfigFactoryInterface $configFactory,
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FlagService $flagService,
    protected FlagLinkBuilder $flagLinkBuilder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('flag'),
      $container->get('flag.link_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['table_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type of table to display'),
      '#options' => [
        'user_unpublished' => $this->t('My unpublished data sets'),
        'user_bookmarked' => $this->t('Bookmarked data sets'),
        'user_bookmark_counts' => $this->t('My data sets bookmarked by at least one user'),
        'most_bookmarked' => $this->t('Most bookmarked data sets'),
        'user_need_review' => $this->t('My data sets that need review'),
      ],
      '#default_value' => $this->configuration['table_type'] ?? NULL,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['table_type'] = $form_state->getValue('table_type');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    switch ($this->configuration['table_type'] ?? NULL) {
      case 'user_unpublished':
        // Table of data_set nodes.
        // Query for data_set nodes by this user that are unpublished.
        $data_set_nids = $this->getDataSetUserQuery()
          ->condition('status', NodeInterface::NOT_PUBLISHED)
          ->execute();
        $classes = ['dc-dashboard-table', 'dc-dashboard-table-mydatasets'];
        $table = $this->dataSetTableTheme($data_set_nids, $classes, $this->t('My unpublished data sets'));
        break;

      case 'user_bookmarked':
        // Table of data_set nodes bookmarked by this user.
        // Get all bookmark flags for this user.
        $values = [
          'flag_id' => 'bookmark',
          'uid' => $this->currentUser->id(),
        ];
        $bookmarks = $this->entityTypeManager->getStorage('flagging')->loadByProperties($values);
        // Make array of bookmarked nodes.
        $bookmark_nids = [];
        foreach ($bookmarks as $bookmark) {
          $bookmark_nids[] = $bookmark->getFlaggableId();
        }
        // Generate the table.
        $classes = ['dc-dashboard-table', 'dc-dashboard-table-bookmarks'];
        $table = $this->dataSetTableTheme($bookmark_nids, $classes, $this->t('Bookmarked data sets'));
        break;

      case 'user_bookmark_counts':
        // Table of data_set nodes by this user with total bookmark counts.
        $classes = ['dc-dashboard-table', 'dc-dashboard-table-datasets-bookmarks'];
        $table = $this->dataSetTableTheme($this->getDataSetUserQuery()->execute(), $classes, $this->t('My data sets bookmarked by at least one user'), TRUE);
        break;

      case 'most_bookmarked':
        // Table of data_set nodes with the most bookmarks.
        // Get all bookmark flags.
        $values = [
          'flag_id' => 'bookmark',
        ];
        $bookmarks = $this->entityTypeManager->getStorage('flagging')->loadByProperties($values);
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
        $table = $this->dataSetTableTheme(array_keys($bookmark_nids), $classes, $this->t('Most bookmarked data sets'), TRUE);
        break;

      case 'user_need_review':
        // Table of data_set nodes by this user that need review.
        $data_set_nids_need_review = [];
        $data_sets = $this->entityTypeManager->getStorage('node')->loadMultiple($this->getDataSetUserQuery()->execute());
        foreach ($data_sets as $data_set) {
          if (static::dataSetReviewNeeded($data_set)) {
            $data_set_nids_need_review[] = $data_set->id();
          }
        }
        $classes = ['dc-dashboard-table', 'dc-dashboard-table-my-review-data-sets'];
        $table = $this->dataSetTableTheme($data_set_nids_need_review, $classes, $this->t('My data sets that need review'));
        break;
    }

    $build = [
      'table' => $table,
    ];

    return $build;
  }

  /**
   * Return a query of data_set nodes owned by this user.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query.
   */
  public function getDataSetUserQuery(): QueryInterface {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('type', 'data_set');
    $query->condition('uid', $this->currentUser->id());
    return $query;
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

    $review_needed_message = trim($this->configFactory->get('bc_dc.settings')->get('review_needed_message'), '.');
    $review_overdue_message = trim($this->configFactory->get('bc_dc.settings')->get('review_overdue_message'), '.');

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
      $data_set = $this->entityTypeManager->getStorage('node')->load($nid);

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
            $url = Url::fromRoute('bc_dc.data_set_build_page_tab', ['node' => $data_set->id()]);
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

}
