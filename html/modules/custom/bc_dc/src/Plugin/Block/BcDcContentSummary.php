<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a content summary block for the dashboard.
 *
 * @Block(
 *   id = "bc_dc_content_summary",
 *   admin_label = @Translation("Dashboard content summary"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class BcDcContentSummary extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'access user manage');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = $this->getContextValue('user');
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $build = [];

    $query = $nodeStorage
      ->getQuery()
      ->condition('status', 1)
      ->condition('type', 'data_set')
      ->condition('uid', $user->id())
      ->accessCheck(FALSE);
    $nids = $query->execute();

    if ($nids) {
      $total_nodes = count($nids);
      $total_bookmarks = 0;
      foreach ($nids as $nid) {
        $node = $nodeStorage->load($nid);
        $total_bookmarks += bc_dc_count_node_bookmarks($node);
      }

      $message = $this->formatPlural($total_nodes,
        'You have @count published metadata record that has been @bookmarked_times.',
        'You have @count published metadata records that have been @bookmarked_times.', [
          '@count' => $total_nodes,
          '@bookmarked_times' => $this->formatPlural($total_bookmarks,
            'bookmarked once',
            'bookmarked @num_bookmarks times', [
              '@num_bookmarks' => $total_bookmarks,
            ]),
        ]);
    }
    else {
      $message = $this->t('You currently have no published metadata records.');
    }
    $build['message'] = [
      '#markup' => $message,
      '#prefix' => '<p class="p-2">',
      '#suffix' => '</p>',
    ];

    if ($nids) {
      $options = [
        'attributes' => [
          'class' => [
            'btn',
            'btn-primary',
          ],
        ],
      ];
      $url = Url::fromRoute('bc_dc.user_manage_tab', ['user' => $user->id()], $options);
      $build['link'] = [
        '#type' => 'link',
        '#url' => $url,
        '#title' => $this->t('Manage metadata records'),
      ];
    }

    return $build;
  }

}
